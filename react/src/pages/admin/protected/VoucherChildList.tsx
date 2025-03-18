import React, { useEffect } from "react";
import { setTitle } from "@/common/appSlice";
import { useAppDispatch } from "@/app/hooks";
import { RouteType } from "@/interface/InterfaceType";
import VoucherChildList from "@/features/admin/voucher/components/VoucherChildList";

const Internal: React.FC<RouteType> = () => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(setTitle("Voucher section"));
  }, []);

  return (
    <>
      <VoucherChildList />
    </>
  );
};

export default Internal;
