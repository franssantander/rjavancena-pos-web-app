import React, { useEffect, useState } from "react";
import { MinusIcon, PlusIcon } from "@radix-ui/react-icons";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
  decrementQty,
  editCustomerName,
  incrementQty,
  loadingStatus,
  removeCustomerName,
  menuError,
  deleteProduct,
  getMenuData,
  getCustomerData,
} from "@/app/slice/menuSlice";
import Cookies from "js-cookie";
import _ from "lodash";
import { Input } from "@/components/ui/input";
import { Icon } from "@iconify/react/dist/iconify.js";
import { Label } from "@/components/ui/label";
import { OrderListProps } from "@/interface/InterfaceType";
import { useAppDispatch, useAppSelector } from "@/app/hooks";

const OrdersList: React.FC<OrderListProps> = ({ customerId, dataCustomer }) => {
  const dispatch = useAppDispatch();
  const orderListError = useAppSelector(menuError);
  const menuStatus = useAppSelector(loadingStatus);

  const customer = dataCustomer?.find(
    (customer: any) => customer?.customer_id === customerId
  );

  const [itemQuantities, setItemQuantities] = useState<any>({});
  const [isEdit, setIsEdit] = useState<boolean>(false);
  const [errorMessage, setErrorMessage] = useState<any | null>(null);

  const [isDelete, setIsDelete] = useState<boolean>(false);
  const [payloadData, setPayloadData] = useState<any>({});
  const [hideMessage, setHideMessage] = useState<boolean>(false);
  const [btnOperator, setBtnOperator] = useState<string>("");
  const [customerName, setCustomerName] = useState(
    customer?.customer_name || customer?.customer_id
  );

  useEffect(() => {
    let timer: any;

    if (payloadData && Object.keys(payloadData).length > 0) {
      timer = setTimeout(() => {
        Object.keys(payloadData).forEach((key) => {
          const payload = payloadData[key];
          if (btnOperator === "plus") {
            dispatch(
              incrementQty({
                url: "purchase/update-qty",
                method: "POST",
                data: payload,
              })
            );
          } else if (btnOperator === "minus") {
            dispatch(
              decrementQty({
                url: "purchase/update-qty",
                method: "POST",
                data: payload,
              })
            );
          }
        });
        setPayloadData({});
      }, 1000);

      return () => clearTimeout(timer);
    }
  }, [payloadData, btnOperator, dispatch]);

  useEffect(() => {
    if (menuStatus === "customerData/success") {
      setItemQuantities({});
      setPayloadData({});
    }
    if (menuStatus === "incrementQty/failed") {
      setErrorMessage(orderListError);
    }
    if (menuStatus === "deleteProduct/success") {
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-customer",
          method: "GET",
        })
      );
    }
  }, [menuStatus, dispatch, orderListError]);

  useEffect(() => {
    localStorage.setItem("customerData", JSON.stringify(customer));
  }, [customer]);

  const handleIncrementQty = (item: any) => {
    setBtnOperator("plus");
    setItemQuantities((prevQuantities: any) => {
      const currentQuantity =
        (prevQuantities[item?.inventory_product_id]?.quantity ?? item?.count) +
        1;

      if (item.stocks === 0) {
        return prevQuantities;
      }

      const newQuantities = {
        ...prevQuantities,
        [item?.inventory_product_id]: {
          ...prevQuantities[item?.inventory_product_id],
          quantity: currentQuantity,
        },
      };

      const newPayload = {
        ...payloadData,
        [item.inventory_product_id]: {
          purchase_id: item.purchase_id,
          quantity: currentQuantity,
          purchase_group_id: item.purchase_group_id,
          user_id_customer: item.user_id_customer,
          inventory_id: item.inventory_id,
          inventory_product_id: item.inventory_product_id,
          eu_device: Cookies.get("eu"),
        },
      };

      setPayloadData(newPayload);

      return newQuantities;
    });
  };

  const handleDecrementQty = (item: any) => {
    setBtnOperator("minus");
    setHideMessage(true);
    setItemQuantities((prevQuantities: any) => {
      const currentQuantity =
        (prevQuantities[item.inventory_product_id]?.quantity ?? item.count) - 1;

      if (currentQuantity === 0) {
        return prevQuantities;
      }

      const newQuantities = {
        ...prevQuantities,
        [item.inventory_product_id]: {
          ...prevQuantities[item.inventory_product_id],
          quantity: currentQuantity,
        },
      };

      const newPayload = {
        ...payloadData,
        [item.inventory_product_id]: {
          purchase_id: item.purchase_id,
          quantity: currentQuantity,
          purchase_group_id: item.purchase_group_id,
          user_id_customer: item.user_id_customer,
          inventory_id: item.inventory_id,
          inventory_product_id: item.inventory_product_id,
          eu_device: Cookies.get("eu"),
        },
      };

      setPayloadData(newPayload);

      return newQuantities;
    });
  };

  const getQuantity = (item: any) => {
    return itemQuantities[item.inventory_product_id]?.quantity || item?.count;
  };

  const handleSaveName = async (purchaseGroupId: string, userId: string) => {
    setIsEdit(false);

    const payload = {
      purchase_group_id: purchaseGroupId,
      user_id_customer: userId,
      customer_name: customerName,
      eu_device: Cookies.get("eu"),
    };
    await dispatch(
      editCustomerName({
        url: "purchase/update-name",
        method: "POST",
        data: payload,
      })
    );
  };

  const handleEditName = (e: any) => {
    setCustomerName(e.target.value);
  };

  const handleDeleteCustomer = () => {
    const payload = {
      payment_id: customer.payment_id,
      user_id: customer.user_id_customer,
      purchase_group_id: customer.purchase_group_id,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      removeCustomerName({
        url: "purchase/delete-customer",
        method: "DELETE",
        data: payload,
      })
    );
  };

  const handleDeleteProduct = (item: any) => {
    const payload = {
      purchase_id: item.arr_purchase_id,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      deleteProduct({
        url: "purchase/delete-all-qty",
        method: "DELETE",
        data: payload,
      })
    );
  };

  if (!customer) {
    return <div>No customer found</div>;
  }
  return (
    <>
      {isDelete && (
        <AlertDialog open={isDelete} onOpenChange={setIsDelete}>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>
                Are you sure you want to delete this customer?
              </AlertDialogTitle>
              <AlertDialogTitle className="text-sm">
                {_.startCase(_.replace(customerName, "-", " "))}
              </AlertDialogTitle>
              <AlertDialogDescription>
                All customer data, including items currently in the cart, will
                be removed. If you proceed, the customer will lose any items
                saved in their cart.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>Cancel</AlertDialogCancel>
              <AlertDialogAction onClick={() => handleDeleteCustomer()}>
                Confirm
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      )}

      <h1 className="font-bold flex items-center gap-2">
        Orders
        <div className="bg-primary rounded-full w-6 h-6 text-white flex items-center">
          <span className="m-auto text-xs">{customer?.total_orders}</span>
        </div>
      </h1>
      <div className="text-muted-foreground py-8 items-center flex gap-3 relative">
        <Input
          className="absolute text-md"
          value={_.startCase(_.replace(customerName, "-", " "))}
          onChange={handleEditName}
          disabled={!isEdit}
        />
        {isEdit ? (
          <>
            <Button
              onClick={() =>
                handleSaveName(
                  customer?.purchase_group_id,
                  customer?.user_id_customer
                )
              }
              size="xs"
              className="absolute flex z-20 right-16 text-xs"
            >
              Save
            </Button>
            <Label
              onClick={() => setIsEdit(!isEdit)}
              className="z-20 flex right-4 absolute cursor-pointer text-xs"
            >
              Cancel
            </Label>
          </>
        ) : (
          <div className="flex right-4 absolute items-center gap-2">
            <Button className="z-20 bg-neutral-200 px-2 h-7 hover:bg-neutral-300 cursor-pointer">
              <Icon
                onClick={() => setIsEdit(true)}
                className="cursor-pointer"
                color="black"
                icon="radix-icons:pencil-2"
              />
            </Button>
            <Icon
              className="cursor-pointer"
              onClick={() => setIsDelete(true)}
              fontSize={16}
              color="red"
              icon="radix-icons:trash"
            />
          </div>
        )}
      </div>
      <div>
        <ScrollArea className="h-[400px] rounded-md border p-2">
          <div className=" flex flex-col gap-4">
            {customer?.items?.map((item: any, index: any) => (
              <Card key={index} className="p-2">
                <div className="flex gap-2">
                  {item?.image ? (
                    <img
                      className="w-20 h-auto rounded-lg bg-contain bg-no-repeat"
                      src={item.image}
                      alt={item.image}
                    />
                  ) : (
                    <Skeleton className="w-20 h-auto p-8" />
                  )}
                  <CardContent className="w-full relative px-1">
                    <div>
                      <div className="flex w-full items-center justify-between">
                        <h1
                          title={item?.name}
                          className="text-xs font-bold text-primary"
                        >
                          {_.truncate(item?.name, {
                            length: 20,
                            separator: " ...",
                          })}
                        </h1>
                        <h1 className="text-xs font-bold text-primary">
                          {new Intl.NumberFormat("en-PH", {
                            style: "currency",
                            currency: "PHP",
                          }).format(item?.retail_price)}
                        </h1>
                      </div>
                      <p
                        title={item?.category}
                        className="text-xs text-muted-foreground"
                      >
                        {_.truncate(item?.category, {
                          length: 15,
                          separator: " ...",
                        })}
                      </p>
                    </div>
                    <small
                      className={`text-red-500 text-xs ${
                        hideMessage && "hidden"
                      }`}
                    >
                      {errorMessage?.parameter === item?.item_code && (
                        <>{errorMessage?.item}</>
                      )}
                    </small>
                    <div className="flex w-full h-full items-center justify-between">
                      <div className="flex items-center  ">
                        <Button
                          onClick={() => handleDecrementQty(item)}
                          className="rounded-r-none bg-neutral-200 hover:bg-neutral-300"
                          size="btnPayment"
                        >
                          <MinusIcon color="black" />
                        </Button>
                        <div className="bg-white p-0.5 px-3 text-xs">
                          {getQuantity(item)}
                        </div>
                        <Button
                          onClick={() => handleIncrementQty(item)}
                          className="rounded-l-none bg-bgrjavancena"
                          size="btnPayment"
                        >
                          <PlusIcon />
                        </Button>
                      </div>
                      <div>
                        <Icon
                          className="cursor-pointer"
                          onClick={() => handleDeleteProduct(item)}
                          fontSize={20}
                          color="red"
                          icon="radix-icons:trash"
                        />
                      </div>
                    </div>
                  </CardContent>
                </div>
              </Card>
            ))}
          </div>
        </ScrollArea>
      </div>
    </>
  );
};

export default OrdersList;
