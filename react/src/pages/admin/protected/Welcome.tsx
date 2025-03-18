import React, { useEffect } from "react";
import { setTitle } from "../../../common/appSlice";
import Welcome from "@/features/admin/welcome/Welcome";
// import { getUsersData } from "@/app/slice/usersManagementSlice";
import { useAppDispatch } from "@/app/hooks";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = () => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(setTitle("Welcome!"));
    // dispatch(getUsersData({ url: props.path_key, method: "GET" }));
  }, []);

  return (
    <>
      <Welcome />
    </>
  );
};

export default Internal;
