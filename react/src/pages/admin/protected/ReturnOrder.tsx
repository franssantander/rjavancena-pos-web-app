import React, { useEffect } from "react";
import { useDispatch } from "react-redux";
import { setTitle } from "../../../common/appSlice";
import ReturnOrder from "@/features/admin/orders/return/ReturnOrder";

const Internal: React.FC = () => {
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(setTitle("Return Order"));
  }, []);

  return (
    <>
      <ReturnOrder />
    </>
  );
};

export default Internal;
