import React from "react";
import { DataTable } from "@/components/ui/data-table";
import { ColumnsShipping } from "@/components/ui/columns";
import ShippingData from "@/data/Shipping.json";

const Shipping: React.FC = () => {
  return (
    <>
      <DataTable
        title="Shipping"
        columns={ColumnsShipping}
        data={ShippingData}
      />
    </>
  );
};

export default Shipping;
