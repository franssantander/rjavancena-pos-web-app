import React, { useEffect, useState } from "react";
import { PlusIcon } from "@radix-ui/react-icons";
import { RadioGroupItem, RadioGroup } from "@/components/ui/radio-group";
import { IconReload } from "@tabler/icons-react";
import {
  IconCashBanknoteFilled,
  IconCreditCardFilled,
  IconQrcode,
} from "@tabler/icons-react";
import _ from "lodash";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { useForm } from "react-hook-form";
import {
  placeOrder,
  loading,
  menuError,
  addVoucher,
  deleteVoucher,
  editVoucherData,
  getCustomerData,
  loadingStatus,
} from "@/app/slice/menuSlice";
import Cookies from "js-cookie";
import { PaymentProps, PaymentMethod } from "@/interface/InterfaceType";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { Icon } from "@iconify/react/dist/iconify.js";

const Payment: React.FC<PaymentProps> = ({ customerId, dataCustomer }) => {
  const { getValues, setValue, register, reset } = useForm({
    defaultValues: {
      voucher_code: "", // Ensuring there's an initial value for voucher_code
    },
  });

  const paymentMethod: PaymentMethod[] = [
    {
      icon: <IconCashBanknoteFilled />,
      label: "Cash",
    },
    {
      icon: <IconCreditCardFilled />,
      label: "Debit",
    },
    {
      icon: <IconQrcode />,
      label: "E-Wallet",
    },
  ];
  const [cashInput, setCashInput] = useState<number | string>("");
  const errorPayment = useAppSelector(menuError);
  const loadingPayment = useAppSelector(loading);
  const [onChangeVoucherVal, setOnChangeVoucherVal] = useState([]);
  const deleteVoucherLoading = useAppSelector(
    (state) => state.menu.deleteVoucherLoading
  );
  const [voucherId, setVoucherId] = useState();
  const addVoucherLoading = useAppSelector(
    (state) => state.menu.addVoucherLoading
  );
  const menuStatus = useAppSelector(loadingStatus);

  const dispatch = useAppDispatch();

  const loadingPurchase = useAppSelector((state) => state.menu.loadingPurchase);

  const customer = dataCustomer?.find(
    (customer: any) => customer?.customer_id === customerId
  );

  const handlePlaceOrder = (values: any) => {
    const payload = {
      payment_id: values.payment_id,
      purchase_group_id: values.purchase_group_id,
      money: cashInput,
      user_id: values.user_id_customer,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      placeOrder({
        url: "payment/payment",
        method: "POST",
        data: payload,
      })
    );
  };

  const handleApplyVoucher = (values: any) => {
    const formValues = getValues();
    const payload = {
      purchase_group_id: values.purchase_group_id,
      user_id_customer: values.user_id_customer,
      voucher_code: formValues.voucher_code,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      addVoucher({
        url: "purchase/add-voucher",
        method: "POST",
        data: payload,
      })
    );
  };

  useEffect(() => {
    customer?.voucher?.forEach((val: any) => {
      setValue(`voucher_code_${val.voucher_used_id}`, val.voucher_code);
    });
  }, [customer, setValue]);

  const actionsBtn = (values: any) => {
    const btnName = values.button_name;

    switch (btnName) {
      case "Delete":
        console.log("values: ", values.voucher_used_id);
        setVoucherId(values.voucher_used_id);

        const payload = {
          purchase_group_id: values.purchase_group_id,
          user_id_customer: values.user_id_customer,
          voucher_used_id: values.voucher_used_id,
          eu_device: Cookies.get("eu"),
        };

        // return;

        dispatch(
          deleteVoucher({
            url: "purchase/delete-voucher",
            method: "DELETE",
            data: payload,
          })
        );
        break;

      default:
        break;
    }
  };

  const handleOnChange = (values: any) => {
    const formValues = getValues();
    const updatedVouchers = [...onChangeVoucherVal];

    const existingIndex = updatedVouchers.findIndex(
      (voucher) => voucher.id === values.voucher_used_id
    );

    if (existingIndex !== -1) {
      updatedVouchers[existingIndex].voucher_value =
        formValues.update_voucher_code;
      updatedVouchers[existingIndex].isTouched = true;
    } else {
      updatedVouchers.push({
        id: values.voucher_used_id,
        voucher_value: formValues.update_voucher_code,
        isTouched: true,
      });
    }

    setOnChangeVoucherVal(updatedVouchers);
  };

  const handleSaveClick = (values: any) => {
    const formValues = getValues();
    const voucherCodeKey = `voucher_code_${values.voucher_used_id}`;

    console.log("formValues Save: ", formValues);

    const payload = {
      purchase_group_id: values.purchase_group_id,
      user_id_customer: values.user_id_customer,
      voucher_used_id: values.voucher_used_id,
      voucher_code: formValues[voucherCodeKey],
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      editVoucherData({
        url: "purchase/update-voucher",
        method: "POST",
        data: payload,
      })
    );
  };

  const handleDeleteVoucher = (values: any) => {
    console.log(values);

    setOnChangeVoucherVal((prevVouchers) =>
      prevVouchers.filter((voucher) => {
        console.log("Voucher being filtered:", voucher); // Log each voucher
        return voucher.id !== itemVoucherUsedId; // Filter condition
      })
    );

    return;
    const payload = {
      purchase_group_id: values.purchase_group_id,
      user_id_customer: values.user_id_customer,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      deleteVoucher({
        url: "purchase/delete-voucher",
        method: "DELETE",
        data: payload,
      })
    );
  };

  const handleInputChange = (e) => {
    const value = e.target.value;
    if (value === "") {
      setCashInput("");
    } else {
      setCashInput(Number(value));
    }
  };

  useEffect(() => {
    if (menuStatus === "addVoucher/success") {
      console.log("menuStatus: ", menuStatus);
      reset();
    }
  }, [menuStatus]);

  return (
    <>
      <div className="py-5">
        {customer?.payment?.map((payment: any) => (
          <>
            <div className="w-full pb-6">
              <h1 className="font-bold">Voucher Code</h1>
              <div className="gap-3 pt-3 relative flex items-center">
                <Input
                  className="h-12"
                  placeholder="Enter voucher code"
                  {...register("voucher_code")}
                />
                <Button
                  className="absolute right-2 w-14"
                  onClick={() => handleApplyVoucher(payment)}
                  disabled={addVoucherLoading}
                  variant="outline"
                  size="icon"
                >
                  {!addVoucherLoading && "Apply"}
                  {addVoucherLoading && (
                    <Icon
                      fontSize={20}
                      className="animate-spin"
                      icon="radix-icons:reload"
                    />
                  )}
                </Button>
              </div>
              <ScrollArea className="h-56 w-full rounded-md border my-3">
                <div className="px-3 w-full py-4 grid gap-4">
                  <div className="w-full flex flex-col gap-4 items-center justify-between">
                    {customer?.voucher?.map((item: any, index: number) => (
                      <div
                        className="w-full relative flex items-center"
                        key={index}
                      >
                        <Input
                          key={index}
                          className={`w-full h-14 font-bold text-sm input-${index}`}
                          {...register(`voucher_code_${item.voucher_used_id}`)}
                          onChange={() => handleOnChange(item)}
                        />
                        <div className="flex absolute gap-3 items-center right-4 bg-white">
                          {item.actions?.map((action: any, index: number) => (
                            <>
                              {onChangeVoucherVal.some(
                                (voucher) =>
                                  voucher.isTouched &&
                                  voucher.id === action.voucher_used_id
                              ) && (
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  onClick={() => handleSaveClick(action)}
                                >
                                  Save
                                </Button>
                              )}

                              <Button
                                className="text-neutral-500 cursor-pointer"
                                key={index}
                                disabled={deleteVoucherLoading}
                                onClick={() => actionsBtn(action)}
                                variant="outline"
                                size="icon"
                              >
                                {deleteVoucherLoading &&
                                voucherId === action.voucher_used_id ? (
                                  <Icon
                                    fontSize={20}
                                    className="animate-spin"
                                    icon="radix-icons:reload"
                                  />
                                ) : (
                                  <Icon fontSize={20} icon={action.icon} />
                                )}
                              </Button>
                            </>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </ScrollArea>
            </div>
            <div>
              <h1 className="font-bold">Payment Summary</h1>
              <div className="flex flex-col gap-3 pt-4 pb-6">
                <div className="flex items-center justify-between">
                  <h1>Total Discount</h1>
                  <p>
                    {loadingPayment
                      ? "Calculating..."
                      : `₱${payment?.total_discounted_amount}`}
                  </p>
                </div>
                <div className="flex items-center justify-between">
                  <h1>Sub Total</h1>
                  <p>
                    {loadingPayment
                      ? "Calculating..."
                      : new Intl.NumberFormat("en-PH", {
                          style: "currency",
                          currency: "PHP",
                        }).format(payment?.total_amount)}
                  </p>
                </div>
                <div className="flex items-center justify-between">
                  <h1>Tax</h1>
                  <p>{loadingPayment ? "Calculating..." : "₱0"}</p>
                </div>
              </div>
            </div>
            <div>
              <div className="flex items-center justify-between border-dashed border-t-2 border-stone-400 py-4">
                <h1 className="font-bold text-lg">Total Amount</h1>
                <p className="font-bold text-lg">
                  {loadingPayment
                    ? "Calculating..."
                    : new Intl.NumberFormat("en-PH", {
                        style: "currency",
                        currency: "PHP",
                      }).format(
                        payment?.final_total_amount ?? payment?.total_amount
                      )}
                </p>
              </div>
            </div>
            <div className="w-full">
              <h1 className="font-semibold">Payment Method</h1>
              <div className="pt-4">
                <RadioGroup
                  defaultValue="Cash"
                  className="grid grid-cols-3 gap-2 w-full"
                >
                  {paymentMethod?.map((item, index) => (
                    <div key={index}>
                      <RadioGroupItem
                        value={item?.label}
                        id={item?.label}
                        className="peer sr-only"
                      />
                      <Label
                        htmlFor={item?.label === "Cash"}
                        className={`flex cursor-pointer flex-col text-xs items-center font-semibold justify-between rounded-md border-2 border-neutral-300 bg-popover p-3 hover:bg-primary hover:text-white peer-data-[state=checked]:bg-primary peer-data-[state=checked]:text-white [&:has([data-state=checked])]:text-white ${
                          item?.label === "Debit" &&
                          "bg-neutral-200 text-neutral-500"
                        } ${
                          item?.label === "E-Wallet" &&
                          "bg-neutral-200 text-neutral-500"
                        }`}
                      >
                        {item?.icon}
                        {item?.label}
                      </Label>
                    </div>
                  ))}
                </RadioGroup>
              </div>
              <div className="flex flex-col gap-6 py-6 w-full">
                <div className="grid gap-2">
                  <Label htmlFor="name">Cash</Label>
                  <Input
                    type="number"
                    value={cashInput}
                    onChange={handleInputChange}
                    className="w-full"
                    id="name"
                    placeholder="Enter cash"
                  />
                  <span className="text-red-500">{errorPayment?.message}</span>
                  {cashInput >= payment?.total_amount && (
                    <Label className="font-medium">
                      Total change:{" "}
                      <span className="font-semibold">
                        {cashInput - payment?.total_amount}
                      </span>
                    </Label>
                  )}
                </div>
                <Button
                  disabled={
                    cashInput <
                      (payment?.final_total_amount ?? payment?.total_amount) ||
                    loadingPurchase
                  }
                  onClick={() => handlePlaceOrder(customer)}
                  size="lg"
                  className={`w-full font-semibold bg-bgrjavancena ${
                    loadingPurchase && "disabled:opacity-100"
                  }`}
                >
                  {loadingPurchase && (
                    <span className="flex items-center gap-2">
                      Purcashing...{" "}
                      <IconReload className="animate-spin" size={16} />
                    </span>
                  )}
                  {!loadingPurchase && "Place Order"}
                </Button>
              </div>
            </div>
          </>
        ))}
      </div>
    </>
  );
};

export default Payment;
