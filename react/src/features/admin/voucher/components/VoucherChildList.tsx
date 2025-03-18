import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { getChildVoucherData } from "@/app/slice/voucherSlice";
import useColumnsProduct from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import { TableProvider } from "@/hooks/TableContext";
import { ArrowLeftIcon } from "@radix-ui/react-icons";
import React, { useEffect, useState } from "react";
import { Link, useLocation, useParams } from "react-router-dom";
import { useToast } from "@/components/ui/use-toast";

const VoucherChildList: React.FC = () => {
  const dispatch = useAppDispatch();
  const childVoucherData = useAppSelector(
    (state) => state.voucher.voucherChildData?.data?.voucher_items
  );
  const { toast } = useToast();

  const [data, setData] = useState([]);
  const status = useAppSelector((state) => state.voucher.status);
  const addChildVoucherMessage = useAppSelector(
    (state) => state.voucher.addChildVoucherMessage
  );
  const updateChildVoucherMessage = useAppSelector(
    (state) => state.voucher.updateChildVoucherMessage
  );
  const addChildVoucherError = useAppSelector(
    (state) => state.voucher.addChildVoucherError
  );
  const deleteChildVoucherMessage = useAppSelector(
    (state) => state.voucher.deleteChildVoucherMessage
  );
  let { state } = useLocation();

  const { id } = useParams();
  const columnsProduct = useColumnsProduct("voucher");

  // useEffect(() => {
  //   dispatch(getChildVoucherData({ url: id, method: "GET" }));
  // }, [id]);

  useEffect(() => {
    if (status === "getChildVoucherData/success") {
      setData(childVoucherData);
    }

    if (status === "addChildVoucherData/success") {
      dispatch(getChildVoucherData({ url: id, method: "GET" }));
      toast({
        variant: "success",
        title: addChildVoucherMessage?.message,
      });
    }

    if (status === "addChildVoucherData/failed") {
      toast({
        variant: "destructive",
        title: addChildVoucherError?.message,
      });
    }

    if (status === "updateChildVoucherData/success") {
      dispatch(getChildVoucherData({ url: id, method: "GET" }));
      toast({
        variant: "success",
        title: updateChildVoucherMessage?.message,
      });
    }

    if (status === "deleteChildVoucherData/success") {
      dispatch(getChildVoucherData({ url: id, method: "GET" }));
      toast({
        variant: "success",
        title: deleteChildVoucherMessage?.message,
      });
    }
  }, [status, id]);

  console.log(childVoucherData);

  return (
    <>
      <TableProvider page="Voucher" voucherId={id}>
        <Link
          to="/app/voucher"
          className="flex gap-2 items-center mb-6 text-xs w-32"
        >
          <ArrowLeftIcon className="w-3 h-3" />
          Back to voucher
        </Link>

        <div>
          <DataTable
            url={id}
            fetchData={getChildVoucherData}
            title={`Voucher in ${state?.name}`}
            columns={columnsProduct}
            data={data}
          />
        </div>
      </TableProvider>
    </>
  );
};

export default VoucherChildList;
