import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { addFile, getChildExpensesData } from "@/app/slice/expensesSlice";
import { setTitle } from "@/common/appSlice";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";
import { Label } from "@/components/ui/label";
import useColumnsProduct from "@/components/ui/columns";
import React, { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { useForm } from "react-hook-form";
import Cookies from "js-cookie";
import ImageList from "./ImageList";
import { DataTable } from "@/components/ui/data-table";
import { buttonVariants } from "@/components/ui/button";
import { useToast } from "@/components/ui/use-toast";
import { TableProvider } from "@/hooks/TableContext";
import { ArrowLeftIcon } from "@radix-ui/react-icons";

const ExpensesView: React.FC = () => {
  const { control, handleSubmit, getValues, setValue, register, watch } =
    useForm({});
  const dispatch = useAppDispatch();
  const { toast } = useToast();
  const { id } = useParams();
  const childExpensesData = useAppSelector(
    (state) => state.expenses.childExpensesData.data
  );
  const [data, setData] = useState([]);

  useEffect(() => {
    dispatch(setTitle("Voucher section"));
  }, []);

  const columnsTable = useColumnsProduct("expensesChild");
  const status = useAppSelector((state) => state.expenses.status);
  const addFileExpensesMessage = useAppSelector(
    (state) => state.expenses.addFileExpensesMessage
  );
  const deleteImgExpensesMessage = useAppSelector(
    (state) => state.expenses.deleteImgExpensesMessage
  );

  const deleteFileExpensesMessage = useAppSelector(
    (state) => state.expenses.deleteFileExpensesMessage
  );

  const handleAddFile = (event: any) => {
    const file = event.target.files[0];
    const formValues = getValues();
    const payload = {
      expenses_id: id,
      file: file,
      eu_device: Cookies.get("eu"),
    };

    console.log(formValues);
    dispatch(
      addFile({
        url: "expenses/expense-image/store",
        method: "POST",
        data: payload,
      })
    );
  };

  useEffect(() => {
    if (status === "getChildExpensesData/success") {
      setData(childExpensesData?.expenses_file);
    }

    if (status === "addFile/success") {
      dispatch(getChildExpensesData({ url: id, method: "GET" }));
      toast({
        variant: "success",
        title: addFileExpensesMessage?.message,
      });
    }

    if (status === "deleteImg/success") {
      dispatch(getChildExpensesData({ url: id, method: "GET" }));
      toast({
        variant: "success",
        title: deleteImgExpensesMessage?.message,
      });
    }

    if (status === "deleteFile/success") {
      dispatch(getChildExpensesData({ url: id, method: "GET" }));
      toast({
        variant: "success",
        title: deleteFileExpensesMessage?.message,
      });
    }
  }, [status]);

  return (
    <div className="w-full lg:max-w-[96rem] lg:mx-auto">
      <Link to="/app/expenses" className="flex gap-2 items-center mb-6 text-xs">
        <ArrowLeftIcon className="w-3 h-3" />
        Back to expenses table
      </Link>
      <div>
        <div>
          <Input
            id="picture"
            {...register("file")}
            type="file"
            accept="image/*,application/pdf"
            onChange={handleAddFile}
            className="hidden"
          />
          <Button
            variant="outlineRjavancena"
            onClick={() => document.getElementById("picture").click()}
          >
            Add images or files
          </Button>
        </div>
      </div>
      <div>
        <ImageList childExpensesData={childExpensesData} id={id} />
      </div>
      <div>
        <TableProvider page="Expenses View">
          <DataTable
            url={id}
            fetchData={getChildExpensesData}
            title="You file"
            columns={columnsTable}
            data={data}
          />
        </TableProvider>
      </div>
    </div>
  );
};

export default ExpensesView;
