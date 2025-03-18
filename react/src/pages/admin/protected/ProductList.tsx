import React, { useEffect } from "react";
import ProductList from "@/features/admin/inventory/components/ProductList";
import { useDispatch } from "react-redux";
import { setTitle } from "@/common/appSlice";
import { useTableContext } from "@/hooks/TableContext";

const Internal: React.FC = () => {
  const { setPage } = useTableContext();
  const dispatch = useDispatch();

  useEffect(() => {
    setPage("Inventory");
    dispatch(setTitle("Product List"));
  }, []);

  return (
    <>
      <ProductList />
    </>
  );
};

export default Internal;
