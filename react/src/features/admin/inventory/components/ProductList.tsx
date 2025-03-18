import React, { useEffect, useState } from "react";
import useColumnsProduct from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import { ArrowLeftIcon } from "@radix-ui/react-icons";
import { Link, useLocation, useParams } from "react-router-dom";
import { ProductType } from "@/interface/InterfaceType";
import {
  getInventoryDataChild,
  inventoryData,
  loadingStatus,
  inventoryError,
} from "@/app/slice/inventorySlice";
import { TableProvider } from "@/hooks/TableContext";
import { toast } from "@/components/ui/use-toast";
import { useAppDispatch, useAppSelector } from "@/app/hooks";

const ProductList: React.FC = () => {
  const dispatch = useAppDispatch();
  const inventoryChild = useAppSelector(inventoryData);
  const inventoryChildError = useAppSelector(inventoryError);
  const inventoryLoading = useAppSelector(loadingStatus);
  const [data, setData] = useState<ProductType[]>([]);
  const { id } = useParams();
  const columnsProduct = useColumnsProduct("inventory");
  const updateChildMessage = useAppSelector(
    (state) => state.inventory.updateChildMessage
  );

  const loadingTable = useAppSelector((state) => state.inventory.loadingTable);
  let { state } = useLocation();

  // useEffect(() => {
  //   dispatch(getInventoryDataChild({ url: id }));
  // }, [id]);

  useEffect(() => {
    if (inventoryLoading === "getInventoryDataChild/success") {
      setData(inventoryChild?.data?.inventory_product);
    }

    if (inventoryLoading === "createInventoryChild/success") {
      toast({ title: inventoryChild?.message || inventoryChild });
      dispatch(getInventoryDataChild({ url: id }));
    }

    if (inventoryLoading === "updateInventoryChild/success") {
      toast({
        variant: "success",
        title: updateChildMessage?.message,
      });
      dispatch(getInventoryDataChild({ url: id }));
    }

    console.log(inventoryChildError);

    if (inventoryLoading === "updateInventoryChild/failed") {
      if (typeof inventoryChildError === "string") {
        toast({
          variant: "destructive",
          title: inventoryChildError,
        });
      }
    }

    // if (usersLoading === "editUserInfo/failed") {
    //   if (typeof editUserInfoError?.message === "string") {
    //     toast({
    //       variant: "destructive",
    //       title: editUserInfoError?.message,
    //     });
    //   }
    // }

    if (inventoryLoading === "deleteInventoryChildData/success") {
      toast({ title: inventoryChild.message || inventoryChild });
      dispatch(getInventoryDataChild({ url: id }));
    }
    if (inventoryLoading === "deleteInventoryChildData/failed") {
      toast({
        variant: "destructive",
        title:
          inventoryChildError ||
          inventoryChildError.message ||
          "Uh oh! Something went wrong.",
        description: "There was a problem with your request.",
      });
    }
  }, [inventoryChild, inventoryLoading]);

  return (
    <>
      {loadingTable && <></>}
      <TableProvider page="Inventory" inventoryId={id}>
        <Link
          to="/app/inventory"
          className="flex gap-2 items-center mb-6 text-xs w-32"
        >
          <ArrowLeftIcon className="w-3 h-3" />
          Back to inventory
        </Link>

        <div>
          <DataTable
            title={`Product in ${state?.name}`}
            columns={columnsProduct}
            url={id}
            data={data}
            fetchData={getInventoryDataChild}
          />
        </div>
      </TableProvider>
    </>
  );
};

export default ProductList;
