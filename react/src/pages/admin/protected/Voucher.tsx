import React, { useEffect } from "react";
import { setTitle } from "@/common/appSlice";
import { useAppDispatch } from "@/app/hooks";
import Voucher from "@/features/admin/voucher/Voucher";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = (props: any) => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(setTitle("Voucher section"));
  }, []);

  return (
    <>
      <Voucher {...props} />
    </>
  );
};

export default Internal;
