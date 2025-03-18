import React, { useEffect } from "react";
import { setTitle } from "@/common/appSlice";
import { useAppDispatch } from "@/app/hooks";
import Expenses from "@/features/admin/expenses/Expenses";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = (props: any) => {
  const dispatch = useAppDispatch();
  console.log(props);

  useEffect(() => {
    dispatch(setTitle("Expenses"));
  }, []);

  return (
    <>
      <Expenses {...props} />
    </>
  );
};

export default Internal;
