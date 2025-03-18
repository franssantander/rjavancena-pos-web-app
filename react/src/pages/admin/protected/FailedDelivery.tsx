import React, { useEffect } from "react";
import { useDispatch } from "react-redux";
import { setTitle } from "../../../common/appSlice";
import FailedOrder from "@/features/admin/orders/failed/FailedOrder";

const Internal: React.FC = () => {
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(setTitle("Failed Order"));
  }, []);

  return (
    <>
      <FailedOrder />
    </>
  );
};

export default Internal;
