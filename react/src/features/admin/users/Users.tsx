import { DataTable } from "@/components/ui/data-table";
import React, { useEffect, useState } from "react";
import useColumnsProduct from "@/components/ui/columns";
import {
  loadingStatus,
  usersData,
  getUsersData,
} from "@/app/slice/usersManagementSlice";
import { RouteType } from "@/interface/InterfaceType";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { useToast } from "@/components/ui/use-toast";
import _ from "lodash";
import { TableProvider } from "@/hooks/TableContext";

const Users: React.FC<RouteType> = (props) => {
  const columnsProduct = useColumnsProduct("users");
  const dispatch = useAppDispatch();

  const [data, setData] = useState([]);
  const usersLoading = useAppSelector(loadingStatus);
  const usersParentData = useAppSelector(usersData);
  const editUserError = useAppSelector(
    (state) => state.usersManagement.editUserError
  );
  const { toast } = useToast();
  const editUserInfoError = useAppSelector(
    (state) => state.usersManagement.editUserInfoError
  );

  const createUsersData = useAppSelector(
    (state) => state.usersManagement.createUsersData
  );
  const editUserData = useAppSelector(
    (state) => state.usersManagement.editUserData
  );

  console.log(createUsersData);

  useEffect(() => {
    if (usersLoading === "getUsersData/success") {
      setData(usersParentData?.data?.account);
    }

    if (usersLoading === "addUser/success") {
      dispatch(getUsersData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: createUsersData?.message,
      });
    }

    if (usersLoading === "addUser/failed") {
      if (typeof createUsersData?.message === "string") {
        toast({
          variant: "destructive",
          title: createUsersData?.message,
        });
      }
    }

    if (usersLoading === "editUser/success") {
      dispatch(getUsersData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: editUserData?.message,
      });
    }

    if (usersLoading === "editUser/failed") {
      if (typeof editUserError?.message === "string") {
        toast({
          variant: "destructive",
          title: editUserError?.message,
        });
      }
    }

    if (usersLoading === "editUserInfo/success") {
      dispatch(getUsersData({ url: props.path_key, method: "GET" }));
    }

    if (usersLoading === "editUserInfo/failed") {
      if (typeof editUserInfoError?.message === "string") {
        toast({
          variant: "destructive",
          title: editUserInfoError?.message,
        });
      }
    }

    if (usersLoading === "deleteUser/success") {
      dispatch(getUsersData({ url: props.path_key, method: "GET" }));
    }
  }, [usersParentData, usersLoading]);

  return (
    <>
      <TableProvider page={props.title}>
        <DataTable
          url={props.path_key}
          title="Users"
          columns={columnsProduct}
          data={data}
          fetchData={getUsersData}
        />
      </TableProvider>
    </>
  );
};

export default Users;
