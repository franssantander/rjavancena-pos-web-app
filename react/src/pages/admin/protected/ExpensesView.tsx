import React, { useEffect } from "react";
import { useDispatch } from "react-redux";
import { setTitle } from "../../../common/appSlice";
import ExpensesView from "@/features/admin/expenses/components/ExpensesView";

const Internal: React.FC = () => {
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(setTitle("Expenses View"));
  }, []);

  return (
    <>
      <ExpensesView />
    </>
  );
};

export default Internal;
