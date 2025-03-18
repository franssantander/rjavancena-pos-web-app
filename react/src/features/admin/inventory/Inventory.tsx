import React, { useEffect, useRef, useState } from "react";
import { IconReload } from "@tabler/icons-react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import _ from "lodash";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import InventoryList from "./components/InventoryList";
import {
  getInventoryData,
  inventoryData,
  createInventoryData,
  loadingStatus,
  inventoryError,
} from "@/app/slice/inventorySlice";
import { SubmitHandler, useForm } from "react-hook-form";
import Cookies from "js-cookie";
import { useToast } from "@/components/ui/use-toast";
import { ToastAction } from "@radix-ui/react-toast";
import { RouteType } from "@/interface/InterfaceType";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import {
  ParentInventory,
  InventoryItem,
  InventoryResponse,
} from "@/interface/InterfaceType";
import { DialogClose } from "@radix-ui/react-dialog";

const Inventory: React.FC<RouteType> = (props) => {
  const inventoryResData: InventoryResponse = useAppSelector(inventoryData);
  const inventoryErrorMess: any = useAppSelector(inventoryError);
  const [dataInventory, setDataInventory] = useState<InventoryItem[]>([]);
  const inventoryLoading = useAppSelector(loadingStatus);
  const [searchQuery, setSearchQuery] = useState("");
  const [filteredCategory, setFilteredCategory] = useState<any>("");
  const [filteredData, setFilteredData] = useState<any[]>([]);
  const imageInputRef = useRef<null>(null);
  const loadingCreate = useAppSelector(
    (state) => state.inventory.loadingCreate
  );

  const { toast } = useToast();
  const dispatch = useAppDispatch();

  const { register, handleSubmit, reset } = useForm<ParentInventory>();

  const handleFormSubmit =
    (invBtn: any): SubmitHandler<ParentInventory> =>
    async (data: any) => {
      const euDevice = Cookies.get("eu");
      console.log(data);

      const payload = {
        name: data.product_name,
        category: data.product_category,
        image: data.image[0],
        eu_device: euDevice,
      };

      dispatch(
        createInventoryData({
          url: invBtn.url,
          method: invBtn.method,
          data: payload,
        })
      );
    };

  const handleSearch = (event: any) => {
    setSearchQuery(event.target.value);
  };

  const handleCategoryChange = (category: any) => {
    setFilteredCategory(category === "all" ? "" : category);
  };

  useEffect(() => {
    // Apply both category and search filters
    const filtered = dataInventory.filter((item) => {
      const matchesCategory = filteredCategory
        ? item.category.toLowerCase() === filteredCategory.toLowerCase()
        : true;
      const matchesSearchQuery = searchQuery
        ? item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
          item.category.toLowerCase().includes(searchQuery.toLowerCase())
        : true;
      return matchesCategory && matchesSearchQuery;
    });
    setFilteredData(filtered);
  }, [filteredCategory, searchQuery, dataInventory]);

  useEffect(() => {
    //* GET INVENTORY DATA
    if (inventoryLoading === "getInventoryData/success") {
      setDataInventory(inventoryResData?.data?.inventory);
    }

    if (inventoryLoading === "getInventoryData/failed") {
      toast({
        variant: "destructive",
        title: "Uh oh! Something went wrong.",
        description: "There was a problem with your request.",
        action: <ToastAction altText="Try again">Try again</ToastAction>,
      });
    }

    //* CREATE INVENTORY DATA
    if (inventoryLoading === "createInventoryParent/success") {
      dispatch(getInventoryData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: inventoryResData.message,
      });
      reset();
    }

    //* UPDATE INVENTORY DATA
    if (inventoryLoading === "updateInventoryParent/success") {
      toast({ variant: "success", title: inventoryResData.message });
      dispatch(getInventoryData({ url: props.path_key, method: "GET" }));
    }

    //* DELETE INVENTORY DATA
    if (inventoryLoading === "deleteInventoryData/success") {
      toast({ variant: "success", title: inventoryResData.message });
      dispatch(getInventoryData({ url: props.path_key, method: "GET" }));
    }

    if (inventoryLoading === "deleteInventoryData/failed") {
      toast({
        variant: "destructive",
        title: "Uh oh! Something went wrong.",
        description: "There was a problem with your request.",
        action: <ToastAction altText="Try again">Try again</ToastAction>,
      });
    }
  }, [props, inventoryLoading, toast]);

  return (
    <>
      <div className="flex flex-col gap-6 md:flex-row lg:items-center justify-between">
        <div className="flex flex-col md:flex-row lg:items-center gap-4">
          <Input
            className="w-96"
            type="text"
            value={searchQuery}
            onChange={handleSearch}
            placeholder="Search Product"
          />
          <div>
            <Select onValueChange={handleCategoryChange}>
              <SelectTrigger className="w-32">
                <SelectValue placeholder="Select category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                {inventoryResData &&
                  inventoryResData?.data?.filter_category?.map(
                    (filt: any, index: any) => (
                      <SelectItem key={index} value={filt}>
                        {filt}
                      </SelectItem>
                    )
                  )}
              </SelectContent>
            </Select>
          </div>
        </div>

        {Array.isArray(inventoryResData.data?.buttons) &&
          inventoryResData?.data?.buttons?.map((invBtn: any, index: any) => (
            <Dialog key={index}>
              <DialogTrigger asChild>
                <Button size="sm" className="w-32 bg-bgrjavancena">
                  {invBtn.button_name}
                </Button>
              </DialogTrigger>
              <DialogContent className="sm:max-w-[425px]">
                <form onSubmit={handleSubmit(handleFormSubmit(invBtn))}>
                  <DialogHeader>
                    <DialogTitle>Create New Product</DialogTitle>
                    <DialogDescription>
                      Fill out the following details to bring your new product.
                    </DialogDescription>
                  </DialogHeader>
                  <div className="flex flex-col gap-5 py-4">
                    {invBtn?.details?.map((item: any) => {
                      return (
                        <>
                          {item.type === "input" && (
                            <div className="flex flex-col gap-2">
                              <Label className="text-left font-medium">
                                {item.label}
                              </Label>
                              <Input
                                id="name"
                                placeholder={`Enter ${_.lowerCase(item.label)}`}
                                {...register(
                                  item.label.replace(/\s+/g, "_").toLowerCase()
                                )}
                              />
                              <Label className="text-red-500">
                                {inventoryErrorMess?.name ||
                                  inventoryErrorMess?.message}
                              </Label>
                            </div>
                          )}
                          {item.type === "file" && (
                            <div className="flex flex-col gap-2">
                              <Label className="text-sm font-medium">
                                {item.label}
                              </Label>
                              <Input
                                id="productImg"
                                accept="image/png,image/jpeg"
                                type="file"
                                className="col-span-3"
                                ref={(e) => {
                                  register(e);
                                  imageInputRef.current = e;
                                }}
                                {...register("image")}
                              />
                            </div>
                          )}
                        </>
                      );
                    })}
                  </div>
                  <DialogFooter>
                    <Button
                      disabled={loadingCreate}
                      className="bg-bgrjavancena disabled:opacity-100"
                      type="submit"
                    >
                      {loadingCreate && (
                        <span className="flex items-center gap-1">
                          Adding...
                          <IconReload className="animate-spin" size={16} />
                        </span>
                      )}
                      {!loadingCreate && "Add product"}
                    </Button>
                    <DialogClose asChild>
                      <Button type="button" variant="secondary">
                        Cancel
                      </Button>
                    </DialogClose>
                  </DialogFooter>
                </form>
              </DialogContent>
            </Dialog>
          ))}
      </div>

      <div className="flex flex-col gap-4 py-4">
        {inventoryResData && <InventoryList filteredData={filteredData} />}
      </div>
    </>
  );
};

export default Inventory;
