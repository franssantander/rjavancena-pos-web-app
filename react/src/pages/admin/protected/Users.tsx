import React, { useEffect } from "react";
import { setTitle } from "../../../common/appSlice";
import Users from "@/features/admin/users/Users";
import { useTableContext } from "@/hooks/TableContext";
import { getUsersData } from "@/app/slice/usersManagementSlice";
import { useAppDispatch } from "@/app/hooks";
import { RouteType } from "@/interface/InterfaceType";

const Internal: React.FC<RouteType> = (props) => {
  const { setPage } = useTableContext();
  const dispatch = useAppDispatch();

  useEffect(() => {
    setPage("Users");
    dispatch(setTitle("Users"));
    // dispatch(getUsersData({ url: props.path_key, method: "GET" }));
  }, []);

  return (
    <>
      <Users {...props} />
    </>
  );
};

export default Internal;
