import Dashboard from "@/features/admin/dashboard/Dashboard";
import React, { useEffect } from "react";
import { setTitle } from "../../../common/appSlice";
import { useTableContext } from "@/hooks/TableContext";
import { getDashboardData } from "@/app/slice/dashboardSlice";
import { useAppDispatch } from "@/app/hooks";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = (props) => {
  const dispatch = useAppDispatch();
  const { setPage } = useTableContext();

  useEffect(() => {
    dispatch(setTitle("Dashboard"));
    setPage("Dashboard");
    dispatch(getDashboardData({ url: props.path_key, method: "GET" }));
  }, []);

  return (
    <>
      <Dashboard {...props} />
    </>
  );
};

export default Internal;
