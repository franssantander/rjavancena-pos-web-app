import Menu from "@/features/admin/menu/Menu";
import React, { useEffect } from "react";
import { setTitle } from "../../../common/appSlice";
import { getMenuData } from "@/app/slice/menuSlice";
import { RouteType } from "@/interface/InterfaceType";
import { useAppDispatch } from "@/app/hooks";

const Internal: React.FC<RouteType> = (props) => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(setTitle("Menu"));
    dispatch(getMenuData({ url: props.path_key, method: "GET" }));
  }, [props.path_key]);

  return (
    <>
      <Menu />
    </>
  );
};

export default Internal;
