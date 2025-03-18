import React, { useEffect } from "react";
import { setTitle } from "@/common/appSlice";
import { useAppDispatch } from "@/app/hooks";
import Customer from "@/features/admin/customer/Customer";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = (props) => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(setTitle("Customer Transanction"));
  }, []);

  return (
    <>
      <Customer {...props} />
    </>
  );
};

export default Internal;
