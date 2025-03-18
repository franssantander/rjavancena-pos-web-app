import { ColumnsDelivered } from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import deliveredData from "@/data/delivered.json";
import React from "react";

const Delivered: React.FC = () => {
  return (
    <>
      <DataTable
        title="Delivered"
        columns={ColumnsDelivered}
        data={deliveredData}
      />
    </>
  );
};

export default Delivered;
