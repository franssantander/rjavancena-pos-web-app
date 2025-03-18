import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { getExpensesData } from "@/app/slice/expensesSlice";
import useColumnsProduct from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import { useToast } from "@/components/ui/use-toast";
import { TableProvider } from "@/hooks/TableContext";
import React, { useEffect, useState } from "react";

const Expenses: React.FC = (props) => {
  const columnsTable = useColumnsProduct("expenses");
  const [data, setData] = useState([]);

  console.log(data);
  const dispatch = useAppDispatch();
  const { toast } = useToast();

  const status = useAppSelector((state) => state.expenses.status);
  const message = useAppSelector((state) => state.expenses.message);
  const addExpensesMessage = useAppSelector(
    (state) => state.expenses.addExpensesMessage
  );
  const updateExpensesMessage = useAppSelector(
    (state) => state.expenses.updateExpensesMessage
  );
  const deleteExpensesMessage = useAppSelector(
    (state) => state.expenses.deleteExpensesMessage
  );
  console.log(status);

  const expensesData = useAppSelector((state) => state.expenses.expensesData);

  useEffect(() => {
    if (status === "expenses/success") {
      setData(expensesData?.data?.expenses);
    }

    if (status === "expenses/failed") {
      toast({
        variant: "destructive",
        title: message?.message,
      });
    }

    if (status === "addExpensesData/success") {
      dispatch(getExpensesData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: addExpensesMessage?.message,
      });
    }

    if (status === "updateExpensesData/success") {
      dispatch(getExpensesData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: updateExpensesMessage?.message,
      });
    }

    if (status === "updateExpensesData/success") {
      dispatch(getExpensesData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: updateExpensesMessage?.message,
      });
    }

    if (status === "deleteExpensesData/success") {
      dispatch(getExpensesData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: deleteExpensesMessage?.message,
      });
    }
  }, [status]);

  return (
    <div>
      <TableProvider page={props.title}>
        <DataTable
          url={props.path_key}
          fetchData={getExpensesData}
          title="Expenses table"
          columns={columnsTable}
          data={data}
        />
      </TableProvider>
    </div>
  );
};

export default Expenses;
