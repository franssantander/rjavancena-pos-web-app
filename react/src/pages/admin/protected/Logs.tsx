import React, { useEffect } from "react";
import { setTitle } from "@/common/appSlice";
import { useAppDispatch } from "@/app/hooks";
import Logs from "@/features/admin/logs/Logs";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = (props: any) => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(setTitle("Logs"));
  }, []);

  return (
    <>
      <Logs {...props} />
    </>
  );
};

export default Internal;
