import React, { useEffect } from "react";
import { useDispatch } from "react-redux";
import { setTitle } from "../../../common/appSlice";
import CustomerOrder from "@/features/admin/orders/order/CustomerOrder";
import { useTableContext } from "@/hooks/TableContext";

const Internal: React.FC = () => {
  const dispatch = useDispatch();
  const { setPage } = useTableContext();

  useEffect(() => {
    setPage("CustomerOrder");
    dispatch(setTitle("Customer Order"));
  }, []);

  return (
    <>
      <CustomerOrder />
    </>
  );
};

export default Internal;
