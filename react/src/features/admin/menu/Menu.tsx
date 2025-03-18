import React, { useEffect, useState } from "react";
import { Input } from "@/components/ui/input";
import { MagnifyingGlassIcon } from "@radix-ui/react-icons";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ScrollArea, ScrollBar } from "@/components/ui/scroll-area";
import { Button } from "@/components/ui/button";
import MenuList from "./components/MenuList";
import OrdersList from "./components/OrdersList";
import Payment from "./components/Payment";
import _ from "lodash";
import { useToast } from "@/components/ui/use-toast";
import {
  loadingStatus,
  menuData,
  getMenuData,
  getCustomerData,
  setCustomerDisplay,
} from "@/app/slice/menuSlice";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { fetchNotifications, useNotification } from "../notification/api";
import { useFetchApi } from "@/app/api/api";

const Menu: React.FC = () => {
  const dispatch = useAppDispatch();
  const [dataMenu, setDataMenu] = useState<any[]>([]);
  const [dataCustomer, setDataCustomer] = useState<any[]>([]);
  const [tabsMenu, setTabsMenu] = useState<any[]>([]);
  const [quantities, setQuantities] = useState({});
  const [activeTab, setActiveTab] = useState<any>(null);

  const menuRes = useAppSelector(menuData);
  const customerRes = useAppSelector(menuData);
  const menuStatus = useAppSelector(loadingStatus);
  const { toast } = useToast();

  const [tabCategory, setTabCategory] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [filteredData, setFilteredData] = useState<any>([]);
  const updateCustomer = useAppSelector((state) => state.menu.updateCustomer);
  const removeCustomer = useAppSelector((state) => state.menu.removeCustomer);
  const removeProduct = useAppSelector((state) => state.menu.removeProduct);

  const addVoucherMessage = useAppSelector(
    (state) => state.menu?.addVoucherMessage
  );
  const addVoucherError = useAppSelector(
    (state) => state.menu?.addVoucherError
  );
  const editVoucherMessage = useAppSelector(
    (state) => state.menu?.editVoucherMessage
  );
  const editVoucherError = useAppSelector(
    (state) => state.menu?.editVoucherError
  );
  const deleteVoucherMessage = useAppSelector(
    (state) => state.menu?.deleteVoucherMessage
  );
  const deleteVoucherError = useAppSelector(
    (state) => state.menu?.deleteVoucherError
  );

  console.log(addVoucherError?.message);

  const customerData = useAppSelector((state) => state.menu.customerData);

  const addMenuCartMessage = useAppSelector(
    (state) => state.menu.addMenuCartMessage
  );

  const { refetch } = useNotification();

  useEffect(() => {
    if (menuStatus === "menuData/success") {
      setDataMenu(menuRes?.data?.inventory_product);
      setTabsMenu(menuRes?.data?.filter_category);
      const initialQuantities = menuRes.data.inventory_product.reduce(
        (acc: any, item: any) => {
          acc[item.inventory_id] = 0;
          return acc;
        },
        {}
      );
      setQuantities(initialQuantities);
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
    }

    if (menuStatus === "menuAddCart/success") {
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );

      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );

      toast({
        variant: "success",
        title: addMenuCartMessage?.message,
      });
    }

    if (menuStatus === "customerData/success") {
      setDataCustomer(customerRes.data);
    }

    if (menuStatus === "incrementQty/success") {
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
      // dispatch(setCustomerDisplay(customer));
    }

    if (menuStatus === "incrementQty/failed") {
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
    }

    if (menuStatus === "decrementQty/success") {
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
    }

    if (menuStatus === "editCustomerName/success") {
      toast({
        variant: "success",
        title: updateCustomer?.message,
      });
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
    }

    if (menuStatus === "removeCustomerName/success") {
      setDataCustomer(customerRes.data);
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: removeCustomer?.message,
      });
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
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: removeProduct?.message,
      });
    }

    if (menuStatus === "placeOrder/success") {
      dispatch(
        getMenuData({
          url: "inventory/product/index",
          method: "GET",
        })
      );
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
      refetch();
      toast({
        variant: "success",
        title: menuRes?.message,
      });
    }

    if (menuStatus === "addVoucher/success") {
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: addVoucherMessage?.message,
      });
    }

    if (menuStatus === "addVoucher/failed") {
      toast({
        variant: "destructive",
        title: addVoucherError?.message,
      });
    }

    if (menuStatus === "editVoucherData/success") {
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: editVoucherMessage?.message,
      });
    }

    if (menuStatus === "editVoucherData/failed") {
      toast({
        variant: "destructive",
        title: editVoucherError?.message,
      });
    }

    if (menuStatus === "deleteVoucher/success") {
      dispatch(
        getCustomerData({
          url: "purchase/get-user-id-menu-costumer",
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: deleteVoucherMessage?.message,
      });
    }
  }, [menuStatus, dispatch]);

  const handleTabCategory = (value: string) => {
    setTabCategory(value);
  };

  useEffect(() => {
    const filtered = dataMenu?.filter((item) => {
      const matchesCategory =
        tabCategory === "all" || item.category === tabCategory;
      const matchesSearchQuery =
        item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.category.toLowerCase().includes(searchQuery.toLowerCase());
      return matchesCategory && matchesSearchQuery;
    });
    setFilteredData(filtered);
  }, [tabCategory, searchQuery, dataMenu]);

  const handleSearch = (event: any) => {
    setSearchQuery(event.target.value);
  };

  const handleTabChange = (value: string) => {
    setActiveTab((prevValue: string) => (prevValue === value ? null : value));
  };

  const handleShowCustomerBtn = () => {
    window.open("/app/customer-payment/", "_blank");
  };

  return (
    <>
      <div className="py-7 lg:grid lg:grid-cols-4 lg:grid-rows-4 lg:py-4 lg:gap-4 lg:mx-auto lg:max-w-[96rem]">
        <div className="lg:col-span-3 lg:row-span-4">
          <div className="w-full  flex items-center justify-between gap-3">
            <div className="flex items-center relative lg:w-96">
              <MagnifyingGlassIcon className="absolute ml-4 text-neutral-500 h-5 w-5" />
              <Input
                value={searchQuery}
                onChange={handleSearch}
                className="pl-12"
                placeholder="Search Product"
              />
            </div>
            <Button
              variant="outlineRjavancena"
              size="sm"
              onClick={handleShowCustomerBtn}
            >
              Display payment
            </Button>
          </div>
          <div className="py-4">
            {menuRes && (
              <MenuList
                filteredData={filteredData}
                tabCategory={tabCategory}
                handleTabCategory={handleTabCategory}
                customerId={activeTab}
                dataCustomer={dataCustomer}
                tabsMenu={tabsMenu}
                quantities={quantities}
                setQuantities={setQuantities}
              />
            )}
          </div>
        </div>
        <div className="py-4 lg:py-0 lg:row-span-4 lg:w-full lg:h-full lg:col-start-4">
          <Tabs defaultValue={activeTab} className="bg-white">
            <ScrollArea className="whitespace-nowrap rounded-md border">
              <TabsList className="bg-white h-12">
                {dataCustomer &&
                  dataCustomer?.map((customer, index) => (
                    <TabsTrigger
                      key={index}
                      className={`  text-xs ${
                        activeTab === customer.customer_id
                          ? "data-[state=active]:bg-bgrjavancena data-[state=active]:text-white"
                          : "data-[state=active]:text-black"
                      }`}
                      value={customer.customer_id}
                      onClick={() => handleTabChange(customer.customer_id)}
                    >
                      {_.startCase(
                        _.replace(
                          customer.customer_name
                            ? customer.customer_name
                            : customer.customer_id,
                          "-",
                          " "
                        )
                      )}
                    </TabsTrigger>
                  ))}
              </TabsList>
              <ScrollBar orientation="horizontal" />
            </ScrollArea>
            {activeTab ? (
              <TabsContent value={activeTab}>
                <OrdersList
                  customerId={activeTab}
                  dataCustomer={dataCustomer}
                />
                <Payment customerId={activeTab} dataCustomer={dataCustomer} />
              </TabsContent>
            ) : (
              <div className="p-4">Select a customer.</div>
            )}
          </Tabs>
        </div>
      </div>
    </>
  );
};

export default Menu;
