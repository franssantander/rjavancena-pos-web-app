import { DataTable } from "@/components/ui/data-table";
import orderCustomer from "@/data/orderCustomer.json";
import { ColumnsCustomerOrder } from "@/components/ui/columns";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import React from "react";
import ToshipTab from "./components/ToshipTab";
import Shipping from "./components/Shipping";
import Delivered from "./components/Delivered";
import FailedDeliver from "./components/FailedDeliver";
import CancelledDeliver from "./components/CancelledDeliver";

const CustomerOrder: React.FC = () => {
  const tabsContent = [
    {
      title: "Overview",
      value: "overview",
      icon: "",
    },
    {
      title: "To Ship",
      value: "toShip",
      icon: "",
    },
    {
      title: "Shipping",
      value: "shipping",
      icon: "",
    },
    {
      title: "Delivered",
      value: "delivered",
      icon: "",
    },
    {
      title: "Failed Deliver",
      value: "failedDeliver",
      icon: "",
    },
    {
      title: "Cancelled Deliver",
      value: "cancelledDeliver",
      icon: "",
    },
  ];

  return (
    <>
      <Tabs defaultValue="overview">
        <TabsList className="bg-transparent">
          {tabsContent.map((tab, index) => (
            <TabsTrigger
              key={index}
              value={tab.value}
              className="data-[state=active]:font-semibold bg-transparent"
            >
              {tab.title}
            </TabsTrigger>
          ))}
        </TabsList>
        <TabsContent value="overview">
          <DataTable
            title="Customer Order"
            columns={ColumnsCustomerOrder}
            data={orderCustomer}
          />
        </TabsContent>

        <TabsContent value="toShip" className="relative">
          <ToshipTab />
        </TabsContent>

        <TabsContent value="shipping">
          <Shipping />
        </TabsContent>

        <TabsContent value="delivered">
          <Delivered />
        </TabsContent>

        <TabsContent value="failedDeliver">
          <FailedDeliver />
        </TabsContent>

        <TabsContent value="cancelledDeliver">
          <CancelledDeliver />
        </TabsContent>
      </Tabs>
    </>
  );
};

export default CustomerOrder;
