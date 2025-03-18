import React, { useEffect, useState } from "react";
import { TableProvider } from "@/hooks/TableContext";
import useColumnsProduct from "@/components/ui/columns";
import { useParams } from "react-router-dom";
import { DataTable } from "@/components/ui/data-table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { useToast } from "@/components/ui/use-toast";
import {
  getInventoryFoundDataChild,
  getInventoryLostDataChild,
  getInventoryRestockDataChild,
} from "@/app/slice/inventorySlice";

const LostList: React.FC = () => {
  const { id } = useParams();
  const dispatch = useAppDispatch();
  const { toast } = useToast();

  const inventoryLost = useAppSelector(
    (state) => state.inventory?.inventoryLostData
  );
  const inventoryFound = useAppSelector(
    (state) => state.inventory.inventoryFoundData
  );
  const inventoryRestock = useAppSelector(
    (state) => state.inventory.inventoryRestockData
  );

  const inventoryToastMessage = useAppSelector(
    (state) => state.inventory.inventoryToastMessage
  );
  const status = useAppSelector((state) => state.inventory.status);
  const [activeTab, setActiveTab] = useState("inventory-lost");

  const [data, setData] = useState([]);
  const columnsProduct = useColumnsProduct(activeTab);

  console.log(columnsProduct);

  console.log("inventoryRestock", inventoryRestock);
  console.log(data);

  useEffect(() => {
    switch (activeTab) {
      case "inventory-lost":
        dispatch(
          getInventoryLostDataChild({
            url: `inventory/product/lost/show/${id}`,
            method: "GET",
          })
        );
        break;
      case "inventory-found":
        dispatch(
          getInventoryFoundDataChild({
            url: `inventory/product/found/show/${id}`,
            method: "GET",
          })
        );
        break;
      case "inventory-restock":
        dispatch(
          getInventoryRestockDataChild({
            url: `inventory/product/restock/show/${id}`,
            method: "GET",
          })
        );
        break;
      default:
        break;
    }
  }, [activeTab, dispatch, id]);

  useEffect(() => {
    if (activeTab === "inventory-lost" && inventoryLost) {
      setData(inventoryLost?.data?.inventory_product_lost);
    }
    if (activeTab === "inventory-found" && inventoryFound) {
      setData(inventoryFound?.data?.inventory_product_found);
    }
    if (activeTab === "inventory-restock" && inventoryRestock) {
      setData(inventoryRestock?.data?.inventory_product_restock);
    }
  }, [activeTab, inventoryLost, inventoryFound, inventoryRestock]);

  console.log(status);

  useEffect(() => {
    if (status === "addInventoryLost/success") {
      dispatch(
        getInventoryLostDataChild({
          url: `inventory/product/lost/show/${id}`,
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: inventoryToastMessage?.message,
      });
    }
    if (status === "addInventoryFound/success") {
      dispatch(
        getInventoryFoundDataChild({
          url: `inventory/product/found/show/${id}`,
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: inventoryToastMessage?.message,
      });
    }

    if (status === "addInventoryRestock/success") {
      dispatch(
        getInventoryRestockDataChild({
          url: `inventory/product/restock/show/${id}`,
          method: "GET",
        })
      );
      toast({
        variant: "success",
        title: inventoryToastMessage?.message,
      });
    }

    if (status === "updateInventoryLost/success") {
      dispatch(
        getInventoryLostDataChild({
          url: `inventory/product/lost/show/${id}`,
          method: "GET",
        })
      );
    }

    if (status === "deleteInventoryLost/success") {
      dispatch(
        getInventoryLostDataChild({
          url: `inventory/product/lost/show/${id}`,
          method: "GET",
        })
      );
    }

    if (status === "updateInventoryFound/success") {
      dispatch(
        getInventoryFoundDataChild({
          url: `inventory/product/found/show/${id}`,
          method: "GET",
        })
      );
    }

    if (status === "deleteInventoryFound/success") {
      dispatch(
        getInventoryFoundDataChild({
          url: `inventory/product/found/show/${id}`,
          method: "GET",
        })
      );
    }

    if (status === "updateInventoryRestock/success") {
      dispatch(
        getInventoryRestockDataChild({
          url: `inventory/product/restock/show/${id}`,
          method: "GET",
        })
      );
    }

    if (status === "deleteInventoryRestock/success") {
      dispatch(
        getInventoryRestockDataChild({
          url: `inventory/product/restock/show/${id}`,
          method: "GET",
        })
      );
    }
  }, [status, inventoryLost, inventoryFound, inventoryRestock]);

  return (
    <div className="w-full">
      <TableProvider page={activeTab} inventoryId={id}>
        <Tabs
          value={activeTab}
          onValueChange={setActiveTab}
          defaultValue="lost"
        >
          <div className="w-full border-b-2">
            <TabsList>
              <TabsTrigger
                value="inventory-lost"
                className="data-[state=active]:bg-bgrjavancena data-[state=active]:text-white"
              >
                Lost
              </TabsTrigger>
              <TabsTrigger
                value="inventory-found"
                className="data-[state=active]:bg-bgrjavancena data-[state=active]:text-white"
              >
                Found
              </TabsTrigger>
              <TabsTrigger
                value="inventory-restock"
                className="data-[state=active]:bg-bgrjavancena data-[state=active]:text-white"
              >
                restock
              </TabsTrigger>
            </TabsList>
          </div>
          <TabsContent value={activeTab}>
            <DataTable
              title={`Product in `}
              columns={columnsProduct}
              data={data}
            />
          </TabsContent>
        </Tabs>
      </TableProvider>
    </div>
  );
};

export default LostList;
