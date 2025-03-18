import { DataTable } from "@/components/ui/data-table";
import React, { useEffect, useState } from "react";
import returnJSON from "@/data/returnOrder.json";
import { ColumnsReturnOrder } from "@/components/ui/columns";

const ReturnOrder: React.FC = () => {
  const [returnData, setReturnData] = useState<any>([]);

  useEffect(() => {
    setReturnData(returnJSON);
  }, []);

  return (
    <>
      <DataTable
        title="Return Orders"
        columns={ColumnsReturnOrder}
        data={returnData}
      />
    </>
  );
};

export default ReturnOrder;
