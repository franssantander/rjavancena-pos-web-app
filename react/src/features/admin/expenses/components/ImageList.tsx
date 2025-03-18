import React, { useState } from "react";
import { Skeleton } from "@/components/ui/skeleton";
import sampleImg from "@/assets/images/threads.jpg";
import sampleImg2 from "@/assets/images/Index.png";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  TransformWrapper,
  TransformComponent,
  useControls,
} from "react-zoom-pan-pinch";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "react-day-picker";
import { DialogPortal } from "@radix-ui/react-dialog";
import {
  IconMinus,
  IconPlus,
  IconTrash,
  IconTrashFilled,
  IconZoomIn,
  IconZoomOut,
  IconZoomReset,
} from "@tabler/icons-react";
import { Icon } from "@iconify/react/dist/iconify.js";
import Cookies from "js-cookie";
import { useAppDispatch } from "@/app/hooks";
import { deleteImg } from "@/app/slice/expensesSlice";

const ImageList: React.FC = ({ childExpensesData, id }) => {
  const dispatch = useAppDispatch();

  const handleDeleteImg = (values: any) => {
    const payload = {
      expenses_id: id,
      expenses_image_id: values.expenses_image_id,
      eu_device: Cookies.get("eu"),
    };

    dispatch(deleteImg({ url: values.url, method: "DELETE", data: payload }));
  };

  return (
    <>
      <div className="w-full py-10">
        <h1 className="font-medium">Images:</h1>
        <div className="pt-5 grid grid-cols-2 gap-x-4 gap-y-4 md:grid-cols-3 xl:grid-cols-5">
          {childExpensesData?.expenses_image?.map(
            (item: any, index: number) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Card className="max-w-64 max-h-48">
                    <CardContent className="p-0">
                      <img
                        className="w-full h-48 p-3 rounded-md object-cover"
                        src={item?.file}
                        alt={item?.file}
                      />
                    </CardContent>
                  </Card>
                </DialogTrigger>
                <DialogPortal>
                  <DialogContent className="p-0 border-none bg-transparent w-auto h-auto mx-auto">
                    <TransformWrapper>
                      {({ zoomIn, zoomOut, resetTransform }) => (
                        <>
                          <div className="flex items-center h-screen relative">
                            <TransformComponent>
                              <img
                                className="mx-auto h-auto w-auto bg-no-repeat bg-contain"
                                src={item?.file}
                                alt={item?.file}
                              />
                            </TransformComponent>
                            <div className="tools w-full absolute bottom-4">
                              <div className="flex items-center relative">
                                <div className="bg-primary px-3 py-1 w-40 rounded-full m-auto flex items-center gap-4">
                                  <button
                                    className="hover:bg-black/60 p-2 rounded-full"
                                    onClick={() => zoomOut()}
                                  >
                                    <IconMinus size={20} color="white" />
                                  </button>
                                  <button
                                    className="hover:bg-black/60 p-2 rounded-full"
                                    onClick={() => zoomIn()}
                                  >
                                    <IconPlus size={20} color="white" />
                                  </button>
                                  <button
                                    className="hover:bg-black/60 p-2 rounded-full"
                                    onClick={() => resetTransform()}
                                  >
                                    <IconZoomReset size={20} color="white" />
                                  </button>

                                  <div>
                                    {item.actions.map((btn: any) => (
                                      <>
                                        {btn.button_name === "Delete" && (
                                          <Button
                                            className="p-2 hover:bg-red-500/10 rounded-full"
                                            onClick={() => handleDeleteImg(btn)}
                                          >
                                            <IconTrash
                                              className="text-red-500"
                                              fontSize={20}
                                            />
                                          </Button>
                                        )}
                                      </>
                                    ))}
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </>
                      )}
                    </TransformWrapper>
                  </DialogContent>
                </DialogPortal>
              </Dialog>
            )
          )}
        </div>
      </div>
    </>
  );
};

export default ImageList;
