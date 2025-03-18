import React from "react";
import { ColumnsCancelledDeliver } from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import cancelDeliver from "@/data/cancelDeliver.json";

const CancelledDeliver: React.FC = () => {
  return (
    <>
      <DataTable
        title="Cancelled Deliver"
        columns={ColumnsCancelledDeliver}
        data={cancelDeliver}
      />
    </>
  );
};

export default CancelledDeliver;
