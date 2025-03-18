import React, { useEffect } from "react";
import { useDispatch } from "react-redux";
import { setTitle } from "../../../common/appSlice";
import Cancellation from "@/features/admin/orders/cancellation/Cancellation";

const Internal: React.FC = () => {
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(setTitle("Cancellation"));
  }, []);

  return (
    <>
      <Cancellation />
    </>
  );
};

export default Internal;
