import { TableProvider } from "@/hooks/TableContext";
import useColumnsProduct from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import React, { useEffect, useState } from "react";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import {
  getCustomerCashierData,
  customerData,
} from "@/app/slice/customerSlice";
import { useToast } from "@/components/ui/use-toast";

const Customer: React.FC<any> = (props: any) => {
  const columnsProduct = useColumnsProduct("customer");

  const dispatch = useAppDispatch();
  const voidMessage = useAppSelector((state) => state.customer.voidMessage);
  const { toast } = useToast();
  const [data, setData] = useState([]);

  const cashierData = useAppSelector(customerData);

  const status = useAppSelector((state) => state?.customer?.status);

  useEffect(() => {
    if (status === "customerCashier/success") {
      setData(cashierData?.data.recent_transactions);
    }

    if (status === "voidPaidCustomer/success") {
      dispatch(getCustomerCashierData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: voidMessage?.message,
      });
    }
    if (status === "voidPaid/failed") {
      toast({
        variant: "destructive",
        title: voidMessage?.message,
      });
    }
  }, [status]);

  return (
    <div className="w-full">
      <TableProvider page={props.title}>
        <DataTable
          url={props.path_key}
          fetchData={getCustomerCashierData}
          title="Customer Transactions"
          columns={columnsProduct}
          data={data}
        />
      </TableProvider>
    </div>
  );
};

export default Customer;
