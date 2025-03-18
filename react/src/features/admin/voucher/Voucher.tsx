import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { addVoucherData, getVoucherData } from "@/app/slice/voucherSlice";
import React, { useEffect, useState } from "react";
import VoucherList from "./components/VoucherList";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/react/dist/iconify.js";
import { useForm } from "react-hook-form";
import _ from "lodash";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  SelectGroup,
} from "@/components/ui/select";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import Cookies from "js-cookie";
import "flatpickr/dist/flatpickr.min.css";
import { IconReload } from "@tabler/icons-react";
import { useToast } from "@/components/ui/use-toast";

const Voucher: React.FC = (props: any) => {
  const dispatch = useAppDispatch();
  const voucherData = useAppSelector((state) => state.voucher.voucherData.data);
  const addVoucherError = useAppSelector(
    (state) => state.voucher.addVoucherError
  );

  console.log(addVoucherError);

  const voucherStatus = useAppSelector((state) => state.voucher.status);
  const addVoucherMessage = useAppSelector(
    (state) => state.voucher.addVoucherMessage
  );
  const { toast } = useToast();
  console.log(addVoucherMessage);

  const { control, handleSubmit, getValues, setValue, register, watch, reset } =
    useForm({});

  const [counterVoucherVal, setCounterVoucherVal] = useState("");
  const addVoucherLoading = useAppSelector(
    (state) => state.voucher.addVoucherLoading
  );

  const handleAddVoucher = () => {
    const formValues = getValues();
    const payload = {
      voucher_code: formValues.voucher_code,
      name: formValues.name,
      description: formValues.description,
      discount_amount: formValues.discount_amount,
      max_usage: formValues.max_usage,
      generate_vouchers: formValues.generate_vouchers,
      counter_vouchers: formValues.counter_vouchers,
      expiration_start_at: formValues.expiration_start_at,
      expiration_end_at: formValues.expiration_end_at,
      status: formValues.status,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      addVoucherData({
        url: "voucher/parent/store",
        method: "POST",
        data: payload,
      })
    );
  };
  const formValues = getValues();
  const generateVouchers = watch("generate_vouchers");

  useEffect(() => {
    if (generateVouchers === "YES") {
      setCounterVoucherVal(formValues.max_usage);
    }
  }, [generateVouchers, formValues.max_usage]);

  useEffect(() => {
    dispatch(getVoucherData({ url: props.path_key, method: "GET" }));
  }, []);

  useEffect(() => {
    if (voucherStatus === "addVoucherData/success") {
      dispatch(getVoucherData({ url: props.path_key, method: "GET" }));
      reset();
      toast({
        variant: "success",
        title: addVoucherMessage?.message,
      });
    }

    if (voucherStatus === "editVoucherData/success") {
      dispatch(getVoucherData({ url: props.path_key, method: "GET" }));
    }

    if (voucherStatus === "deleteVoucherData/success") {
      dispatch(getVoucherData({ url: props.path_key, method: "GET" }));
    }
  }, [voucherStatus]);

  return (
    <div className="w-full lg:max-w-[96rem] lg:mx-auto">
      <VoucherList voucherData={voucherData} />

      {voucherData?.buttons.map((btn: any) => (
        <div className="bottom-5 fixed pb-4 right-10">
          <Dialog>
            <DialogTrigger>
              <Button className="rounded-full bg-bgrjavancena flex items-center gap-1">
                <Icon icon={btn.icon} />
                {btn.button_name}
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Add new voucher</DialogTitle>
              </DialogHeader>
              <div className="w-full">
                <ScrollArea className="h-72 w-full">
                  {btn?.details?.map((detail: any) => (
                    <>
                      <div className="grid py-2 mx-6">
                        <div className="grid gap-1">
                          {detail.type !== "select" && (
                            <>
                              <Label className="font-semibold text-xs">
                                {detail?.label}
                              </Label>
                              <Input
                                placeholder={`Enter ${detail.label}`}
                                type={detail.type}
                                {...register(
                                  detail.label
                                    .replace(/\s+/g, "_")
                                    .toLowerCase()
                                )}
                                value={
                                  detail.label === "Counter vouchers"
                                    ? counterVoucherVal
                                    : undefined // Don't pass value for other inputs to allow typing
                                }
                                onChange={(e) => {
                                  if (detail.label === "Counter vouchers") {
                                    setCounterVoucherVal(e.target.value);
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
                                  <SelectValue placeholder={detail.value} />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectGroup>
                                    {detail?.option?.map((opt: any) => (
                                      <SelectItem
                                        key={opt.value}
                                        value={opt.value}
                                      >
                                        {opt.label}
                                      </SelectItem>
                                    ))}
                                  </SelectGroup>
                                </SelectContent>
                              </Select>
                            </>
                          )}

                          <small className="text-red-500 w-full">
                            {addVoucherError?.message &&
                              addVoucherError?.message[
                                _.replace(_.lowerCase(detail.label), " ", "_")
                              ]}
                          </small>
                        </div>
                      </div>
                    </>
                  ))}
                </ScrollArea>
              </div>
              <DialogFooter>
                <Button
                  className="bg-bgrjavancena disabled:opacity-100"
                  type="submit"
                  disabled={addVoucherLoading}
                  onClick={handleAddVoucher}
                >
                  {addVoucherLoading && (
                    <span className="flex items-center gap-1">
                      Adding...
                      <IconReload className="animate-spin" size={16} />
                    </span>
                  )}
                  {!addVoucherLoading && "Add new"}
                </Button>
                <DialogClose asChild>
                  <Button type="button" variant="secondary">
                    Close
                  </Button>
                </DialogClose>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      ))}
    </div>
  );
};

export default Voucher;
