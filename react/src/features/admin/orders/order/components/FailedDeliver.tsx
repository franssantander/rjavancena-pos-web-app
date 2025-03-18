import React from "react";
import { ColumnsFailedDeliver } from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import failedDeliver from "@/data/failedDeliver.json";

const FailedDeliver: React.FC = () => {
  return (
    <>
      <DataTable
        title="Failed Deliver"
        columns={ColumnsFailedDeliver}
        data={failedDeliver}
      />
    </>
  );
};

export default FailedDeliver;
