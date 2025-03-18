import React, { useEffect, useState } from "react";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  SelectGroup,
} from "@/components/ui/select";
import { useForm } from "react-hook-form";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import _ from "lodash";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/react/dist/iconify.js";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogPortal,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { deleteVoucherData, editVoucherData } from "@/app/slice/voucherSlice";
import Cookies from "js-cookie";
import { IconReload } from "@tabler/icons-react";
import { Link } from "react-router-dom";

const VoucherList: React.FC = ({ voucherData }) => {
  const { control, handleSubmit, getValues, setValue, register, watch } =
    useForm({});
  const [showEditDialog, setShowEditDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [modalData, setModalData] = useState(false);
  const dispatch = useAppDispatch();
  const editVoucherLoading = useAppSelector(
    (state) => state.voucher.editVoucherLoading
  );
  const editVoucherError = useAppSelector(
    (state) => state.voucher.editVoucherError
  );
  const deleteVoucherLoading = useAppSelector(
    (state) => state.voucher.deleteVoucherLoading
  );

  console.log(modalData);

  const [counterVoucherVal, setCounterVoucherVal] = useState("");
  const formValues = getValues();

  const generateVouchers = watch("generate_vouchers");

  useEffect(() => {
    if (generateVouchers === "YES") {
      setCounterVoucherVal(formValues.max_usage);
    }
  }, [generateVouchers, formValues.max_usage]);

  const handleDelete = () => {
    setShowDeleteDialog(true);
    setShowEditDialog(false);

    const payload = {
      voucher_id: modalData?.voucher_id,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      deleteVoucherData({
        url: modalData?.url,
        method: "DELETE",
        data: payload,
      })
    );
  };

  const handleClickBtn = (values: any) => {
    const btnName = values.button_name;

    switch (btnName) {
      case "Edit":
        values.details.forEach((val) => {
          const fieldName = val.label.replace(/\s+/g, "_").toLowerCase();
          setValue(fieldName, val.value);
        });

        setModalData(values);
        setShowEditDialog(true);
        setShowDeleteDialog(false);
        break;

      case "Delete":
        console.log(btnName);
        setModalData(values);
        setShowDeleteDialog(true);
        setShowEditDialog(false);
        break;

      default:
        break;
    }
  };

  const handleSaveClick = () => {
    const formValues = getValues();

    console.log(formValues);

    const payload = {
      voucher_id: modalData.voucher_id,
      voucher_code: formValues.voucher_code,
      name: formValues.name,
      description: formValues.description,
      discount_amount: formValues.discount_amount,
      max_usage: formValues.max_usage,
      expiration_start_at: formValues.expiration_start_at,
      expiration_end_at: formValues.expiration_end_at,
      status: formValues.status,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      editVoucherData({ url: modalData.url, method: "POST", data: payload })
    );
  };

  return (
    <div className="w-full grid sm:grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 lg:gap-7">
      {voucherData &&
        voucherData?.voucher?.map((item: any, index: number) => (
          <Card key={index}>
            <CardHeader>
              <CardTitle className="text-lg">{item?.voucher_code}</CardTitle>
              <CardDescription className="text-sm">
                {item?.name}
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-2">
              <div className="grid sm:grid-cols-2">
                <small className="font-bold">Price:</small>
                <small className="text-neutral-400">
                  {item?.discount_amount}
                </small>
              </div>
             <div className="grid sm:grid-cols-2">
                <small className="font-bold">Vouchers:</small>
                <small className="text-neutral-400">{item?.vouchers}</small>
              </div>
             <div className="grid sm:grid-cols-2">
                <small className="font-bold">Used:</small>
                <small className="text-neutral-400">{item?.used}</small>
              </div>
             <div className="grid sm:grid-cols-2">
                <small className="font-bold">Start at:</small>
                <small className="text-neutral-400">
                  {item?.expiration_start_at}
                </small>
              </div>
             <div className="grid sm:grid-cols-2">
                <small className="font-bold">Expiration:</small>
                <small className="text-neutral-400">
                  {item?.expiration_end_at}
                </small>
              </div>
            </CardContent>
            <CardFooter className="w-full gap-3">
              {item?.actions?.map((btn: any) => (
                <>
                  {console.log(btn)}
                  {btn?.button_name === "View details" && (
                    <Link
                      state={{ name: btn?.name }}
                      className="w-full"
                      to={`/app/voucher/voucher-child/${btn.url}`}
                    >
                      <Button
                        size="sm"
                        className="w-full"
                        variant="outlineRjavancena"
                      >
                        {btn?.button_name}
                      </Button>
                    </Link>
                  )}
                </>
              ))}
              <>
                <Dialog>
                  <DropdownMenu>
                    <DropdownMenuTrigger>
                      <Button className="w-full" size="sm" variant="outline">
                        <Icon icon="radix-icons:dots-horizontal" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                      <DropdownMenuLabel>Actions</DropdownMenuLabel>
                      <DropdownMenuSeparator />
                      <DialogTrigger className="w-full">
                        {item?.actions?.map((btn: any) => (
                          <>
                            {btn?.button_name === "Edit" && (
                              <DropdownMenuItem
                                className="cursor-pointer"
                                onClick={() => handleClickBtn(btn)}
                              >
                                <Icon className="mr-2" icon={btn.icon} />
                                <span>{btn?.button_name}</span>
                              </DropdownMenuItem>
                            )}
                            {btn?.button_name === "Delete" && (
                              <DropdownMenuItem
                                className="cursor-pointer w-full"
                                onClick={() => handleClickBtn(btn)}
                              >
                                <Icon className="mr-2" icon={btn.icon} />
                                <span>{btn?.button_name}</span>
                              </DropdownMenuItem>
                            )}
                          </>
                        ))}
                      </DialogTrigger>
                    </DropdownMenuContent>
                  </DropdownMenu>
                  <DialogPortal>
                    {showEditDialog && (
                      <DialogContent className="sm:max-w-[34rem]">
                        <DialogHeader>
                          <DialogTitle>Edit voucher code details</DialogTitle>
                          <DialogDescription>
                            Edit voucher code details below. Update information
                            and click save to apply changes.
                          </DialogDescription>
                        </DialogHeader>
                        <ScrollArea className="h-72 w-full">
                          <div className="w-full">
                            {modalData?.details.map((detail: any) => (
                              <>
                                <div className="grid py-2 mx-6">
                                  <div className="grid gap-1">
                                    {detail.type !== "select" &&
                                      detail.type !== "date" && (
                                        <>
                                          <Label className="font-semibold text-xs">
                                            {detail.label}
                                          </Label>
                                          <Input
                                            placeholder={`Enter ${detail.label}`}
                                            type={detail?.type}
                                            {...register(
                                              detail?.label
                                                .replace(/\s+/g, "_")
                                                .toLowerCase()
                                            )}
                                            onChange={(e) => {
                                              if (
                                                detail.label ===
                                                "Counter vouchers"
                                              ) {
                                                setCounterVoucherVal(
                                                  e.target.value
                                                );
                                              }
                                              setValue(
                                                detail.label
                                                  .replace(/\s+/g, "_")
                                                  .toLowerCase(),
                                                e.target.value
                                              );
                                            }}
                                            className="w-full"
                                          />
                                        </>
                                      )}
                                    {detail.type === "date" && (
                                      <>
                                        <Label className="font-semibold text-xs">
                                          {detail.label}
                                        </Label>
                                        <Input
                                          placeholder={`Enter ${detail.label}`}
                                          type={detail?.type}
                                          {...register(
                                            detail?.label
                                              .replace(/\s+/g, "_")
                                              .toLowerCase()
                                          )}
                                          value={detail.value}
                                          className="w-full"
                                        />
                                      </>
                                    )}
                                    {detail.type === "select" && (
                                      <>
                                        <Label className="font-semibold text-xs">
                                          {detail?.label}
                                        </Label>
                                        <Select
                                          onValueChange={(value) =>
                                            setValue(
                                              detail.label
                                                .replace(/\s+/g, "_")
                                                .toLowerCase(),
                                              value
                                            )
                                          }
                                          value={watch(
                                            detail.label
                                              .replace(/\s+/g, "_")
                                              .toLowerCase()
                                          )}
                                        >
                                          <SelectTrigger>
                                            <SelectValue
                                              placeholder={detail.value}
                                            />
                                          </SelectTrigger>
                                          <SelectContent>
                                            <SelectGroup>
                                              {detail?.option?.map(
                                                (opt: any) => (
                                                  <SelectItem
                                                    key={opt.value}
                                                    value={opt.value}
                                                  >
                                                    {opt.label}
                                                  </SelectItem>
                                                )
                                              )}
                                            </SelectGroup>
                                          </SelectContent>
                                        </Select>
                                      </>
                                    )}
                                    <small className="text-red-500 w-full">
                                      {editVoucherError?.message &&
                                        editVoucherError?.message[
                                          _.replace(
                                            _.lowerCase(detail.label),
                                            " ",
                                            "_"
                                          )
                                        ]}
                                    </small>
                                  </div>
                                </div>
                              </>
                            ))}
                          </div>
                        </ScrollArea>
                        <DialogFooter>
                          <Button
                            className="bg-bgrjavancena disabled:opacity-100"
                            type="submit"
                            disabled={editVoucherLoading}
                            onClick={() => handleSaveClick()}
                          >
                            {editVoucherLoading && (
                              <span className="flex items-center gap-1">
                                Saving...
                                <IconReload
                                  className="animate-spin"
                                  size={16}
                                />
                              </span>
                            )}
                            {!editVoucherLoading && "Save changes"}
                          </Button>
                          <DialogClose asChild>
                            <Button type="button" variant="secondary">
                              Close
                            </Button>
                          </DialogClose>
                        </DialogFooter>
                      </DialogContent>
                    )}
                    {showDeleteDialog && (
                      <DialogContent className="sm:max-w-[34rem]">
                        <DialogHeader>
                          <DialogTitle>
                            Are you sure you want to delete this voucher?{" "}
                            {modalData?.voucher_code}
                          </DialogTitle>
                          <DialogDescription>
                            Deleting this voucher code is a permanent action and
                            cannot be undone.
                          </DialogDescription>
                        </DialogHeader>

                        <DialogFooter>
                          <Button
                            className="bg-bgrjavancena disabled:opacity-100"
                            type="submit"
                            disabled={deleteVoucherLoading}
                            onClick={() => handleDelete()}
                          >
                            {deleteVoucherLoading && (
                              <span className="flex items-center gap-1">
                                Deleting...
                                <IconReload
                                  className="animate-spin"
                                  size={16}
                                />
                              </span>
                            )}
                            {!deleteVoucherLoading && "Delete"}
                          </Button>
                          <DialogClose asChild>
                            <Button type="button" variant="secondary">
                              Close
                            </Button>
                          </DialogClose>
                        </DialogFooter>
                      </DialogContent>
                    )}
                  </DialogPortal>
                </Dialog>
              </>
            </CardFooter>
          </Card>
        ))}
    </div>
  );
};

export default VoucherList;
