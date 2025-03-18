import Inventory from "@/features/admin/inventory/Inventory";
import React from "react";
import { setTitle } from "../../../common/appSlice";
import { useEffect } from "react";
import { useTableContext } from "@/hooks/TableContext";
import { getInventoryData } from "@/app/slice/inventorySlice";
import { useAppDispatch } from "@/app/hooks";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = (props) => {
  const dispatch = useAppDispatch();
  const { setPage } = useTableContext();

  useEffect(() => {
    setPage("Inventory");
    dispatch(setTitle("Inventory"));
    dispatch(getInventoryData({ url: props.path_key, method: "GET" }));
  }, []);

  return (
    <>
      <Inventory {...props} />
    </>
  );
};

export default Internal;
