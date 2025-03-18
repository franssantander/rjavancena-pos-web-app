import React, {
  createContext,
  ReactNode,
  useContext,
  useEffect,
  useRef,
  useState,
} from "react";
import _ from "lodash";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
  DialogClose,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { IconReload } from "@tabler/icons-react";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Input } from "@/components/ui/input";
import phFlag from "@/assets/images/phflag.svg";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  SelectLabel,
  SelectGroup,
} from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { SubmitHandler, useForm } from "react-hook-form";
import Cookies from "js-cookie";
import {
  createInventoryChildData,
  inventoryError,
  inventoryData,
  addInventoryLost,
  addInventoryFound,
  addInventoryRestock,
} from "@/app/slice/inventorySlice";

import {
  addUser,
  usersData,
  usersError,
} from "@/app/slice/usersManagementSlice";

import { useParams } from "react-router-dom";
import { useAppDispatch, useAppSelector } from "@/app/hooks";

import { TableContextType } from "@/interface/InterfaceType";
import axios from "axios";
import { addChildVoucherData } from "@/app/slice/voucherSlice";
import { addExpensesData } from "@/app/slice/expensesSlice";
import { Textarea } from "@/components/ui/textarea";

const defaultTableContextValue: TableContextType = {
  children: null,
  page: "",
  setPage: () => {},
  placeHolder: "",
  columnName: "",
  rowsSelection: "",
  jsx: <div></div>,
  tablesOptionsJsx: <div></div>,
  selectedOption: null,
  tablesOptions: null,
  setSelectedOption: () => {},
  setTablesOptions: () => {},
};

const TableContext = createContext<TableContextType>(defaultTableContextValue);

export const TableProvider: React.FC<{ children: ReactNode; page: string }> = ({
  children,
  page: initialPage,
}) => {
  interface FormSubmit {
    item_code: number;
    image: string;
    refundable: string;
    name: string;
    retail_price: number;
    discounted_price: number;
    stocks: number;
    supplier_name: string;
    email: string;
    password: any;
    password_confirmation: any;
    role: string;
    status: string;
  }

  const [page, setPage] = useState<string | any>(initialPage);
  const [selectedOption, setSelectedOption] = useState<any>("packOrders");

  console.log(initialPage);

  const dispatch = useAppDispatch();

  let placeHolder;
  let columnName;
  let jsx;
  let rowsSelection;

  const {
    control,
    handleSubmit,
    getValues,
    setValue,
    register,
    watch,
    reset,
    formState: { isDirty },
  } = useForm({});

  const imageInputRef = useRef<null>(null);

  const { id } = useParams();

  //* INVENTORY PRODUCT LIST
  const inventoryChildData = useAppSelector(inventoryData);
  const inventoryChildError = useAppSelector(inventoryError);
  const loadingCreateChild = useAppSelector(
    (state) => state?.inventory?.loadingCreateChild
  );
  const loadingCreateVoucherChild = useAppSelector(
    (state) => state?.voucher?.addChildVoucherLoading
  );
  const statusInventoryChild = useAppSelector(
    (state) => state.inventory.status
  );

  //* ADD USER MANAGEMENT
  const usersParentData = useAppSelector(usersData);
  const usersParentError = useAppSelector(usersError);
  const loadingCreateUser = useAppSelector(
    (state) => state?.usersManagement?.loadingCreateUser
  );
  const statusCreateUser = useAppSelector(
    (state) => state.usersManagement.status
  );

  const statusCreateLostData = useAppSelector(
    (state) => state.inventory.status
  );

  //* ADD VOUCHER
  const childVoucherData = useAppSelector(
    (state) => state.voucher?.voucherChildData
  );
  const childVoucherError = useAppSelector(
    (state) => state.voucher?.childVoucherError
  );
  const statusCreateVoucherChild = useAppSelector(
    (state) => state.voucher.status
  );

  //* EXPENSES
  const expensesData = useAppSelector((state) => state.expenses?.expensesData);
  const error = useAppSelector((state) => state.expenses?.error);
  const addExpensesError = useAppSelector(
    (state) => state.expenses?.addExpensesError
  );
  const addExpensesLoading = useAppSelector(
    (state) => state.expenses?.addExpensesLoading
  );
  const statusCreateExpenses = useAppSelector((state) => state.expenses.status);

  const statusAddInventoryLost = useAppSelector(
    (state) => state.inventory.status
  );

  const statusAddInventoryFound = useAppSelector(
    (state) => state.inventory.status
  );

  const statusAddInventoryRestock = useAppSelector(
    (state) => state.inventory.status
  );

  const loadingAddLost = useAppSelector(
    (state) => state.inventory.loadingAddLost
  );

  const loadingAddFound = useAppSelector(
    (state) => state.inventory.loadingAddFound
  );

  const loadingAddRestock = useAppSelector(
    (state) => state.inventory.loadingAddRestock
  );

  console.log(loadingAddLost);

  const inventoryLost = useAppSelector(
    (state) => state.inventory.inventoryLostData
  );

  const inventoryFound = useAppSelector(
    (state) => state.inventory.inventoryFoundData
  );

  const inventoryRestock = useAppSelector(
    (state) => state.inventory.inventoryRestockData
  );

  const inventoryErrorMessage = useAppSelector(
    (state) => state.inventory.error
  );

  console.log(inventoryErrorMessage);

  const [getLocationCode, setGetLocationCode] = useState({});
  const [locationsData, setLocationsData] = useState({
    regions: [],
    provinces: [],
    cities: [],
    barangays: [],
  });

  const formValues = watch();

  const { region_name, province_name, city_or_municipality_name } = formValues;

  const onChangeSelect = (type: any, values: any) => {
    console.log(values.code);
    setGetLocationCode((prevState) => ({
      ...prevState,
      [`${type.replace(/\s+/g, "_").toLowerCase()}`]: values.name,
    }));
  };

  const handleFormSubmit =
    (url: string, formType: string, values): SubmitHandler<FormSubmit> =>
    async (data) => {
      const euDevice = Cookies.get("eu");

      switch (formType) {
        case "inventory":
          const payload = {
            inventory_id: id,
            eu_device: euDevice,
            item_code: data.item_code,
            image: data.image[0] || null,
            refundable: data.refundable,
            item_expiration_at: data.item_expiration_at,
            product_name: data.product_name,
            retail_price: data.retail_price,
            discounted_price: data.discounted_price,
            stocks: data.stocks,
            moderate_stocks: data.moderate_stocks,
            low_stocks: data.low_stocks,
            supplier_name: data.supplier_name,
          };
          dispatch(
            createInventoryChildData({
              url: url,
              method: "POST",
              data: payload,
            })
          );

          break;

        case "users":
          const formValues = getValues();

          const regionName =
            values.details.find((detail: any) => detail.label === "Region Name")
              ?.value_name || getLocationCode.region_name;
          const barangayName =
            values.details.find(
              (detail: any) => detail.label === "Barangay Name"
            )?.value_name || getLocationCode.barangay_name;
          const citiesName =
            values.details.find(
              (detail: any) => detail.label === "City Or Municipality Name"
            )?.value_name || getLocationCode.city_or_municipality_name;
          const provinceName =
            values.details.find(
              (detail: any) => detail.label === "Province Name"
            )?.value_name || getLocationCode.province_name;

          console.log(regionName);

          const usersPayload = {
            user_id: data.user_id,
            first_name: formValues.first_name,
            middle_name: formValues.middle_name,
            last_name: formValues.last_name,
            contact_number: formValues.contact_number,
            email: formValues.email,
            password: formValues.password,
            password_confirmation: formValues.password_confirmation,
            address_1: formValues.address_1,
            address_2: formValues.address_2,
            phone_number: formValues.phone_number,
            role: formValues.role,
            status: formValues.status,
            region_code: formValues.region_name,
            province_code: formValues.province_name,
            city_or_municipality_code: formValues.city_or_municipality_name,
            barangay_code: formValues.barangay_name,
            barangay_name: barangayName,
            city_or_municipality_name: citiesName,
            province_name: provinceName,
            region_name: regionName,
            eu_device: Cookies.get("eu"),
          };

          console.log(usersPayload);

          dispatch(
            addUser({
              url: url,
              method: "POST",
              data: usersPayload,
            })
          );

          break;

        case "voucher":
          const payloadVoucher = {
            voucher_id: id,
            voucher_code: data.voucher_code,
            eu_device: Cookies.get("eu"),
          };

          console.log(data);

          dispatch(
            addChildVoucherData({
              url: url,
              method: "POST",
              data: payloadVoucher,
            })
          );
          break;

        case "expenses":
          const payloadExpenses = {
            name: data.name,
            amount: data.amount,
            date_of_expense: data.date_expenses,
            eu_device: Cookies.get("eu"),
          };

          console.log(data);

          dispatch(
            addExpensesData({
              url: url,
              method: "POST",
              data: payloadExpenses,
            })
          );
          break;

        case "inventoryLost":
          console.log("inventory lost ", data);
          const payloadLost = {
            inventory_product_id: id,
            image: data.image[0],
            count: data.count,
            remarks: data.remarks,
            eu_device: Cookies.get("eu"),
          };

          dispatch(
            addInventoryLost({
              url: url,
              method: "POST",
              data: payloadLost,
            })
          );
          break;

        case "inventoryFound":
          const payloadFound = {
            inventory_product_id: id,
            image: data.image[0],
            count: data.count,
            remarks: data.remarks,
            eu_device: Cookies.get("eu"),
          };

          dispatch(
            addInventoryFound({
              url: url,
              method: "POST",
              data: payloadFound,
            })
          );
          break;

        case "inventoryRestock":
          const payloadRestock = {
            inventory_product_id: id,
            image: data.image[0],
            count: data.count,
            remarks: data.remarks,
            eu_device: Cookies.get("eu"),
          };

          dispatch(
            addInventoryRestock({
              url: url,
              method: "POST",
              data: payloadRestock,
            })
          );
          break;
        default:
          break;
      }
    };

  useEffect(() => {
    axios
      .get("https://psgc.gitlab.io/api/regions/", {
        headers: {
          Authorization: undefined,
        },
      })
      .then((res) =>
        setLocationsData((prevState) => ({ ...prevState, regions: res.data }))
      );
  }, []);

  useEffect(() => {
    if (region_name) {
      axios
        .get(`https://psgc.gitlab.io/api/regions/${region_name}/provinces/`, {
          headers: {
            Authorization: undefined,
          },
        })
        .then((res) =>
          setLocationsData((prevState) => ({
            ...prevState,
            provinces: res.data,
          }))
        );
    }
  }, [region_name]);

  useEffect(() => {
    if (province_name) {
      axios
        .get(
          `https://psgc.gitlab.io/api/provinces/${province_name}/cities-municipalities/`,
          {
            headers: {
              Authorization: undefined,
            },
          }
        )
        .then((res) =>
          setLocationsData((prevState) => ({
            ...prevState,
            cities: res.data,
          }))
        );
    }
  }, [province_name]);

  useEffect(() => {
    if (city_or_municipality_name) {
      axios
        .get(
          `https://psgc.gitlab.io/api/cities-municipalities/${city_or_municipality_name}/barangays/`,
          {
            headers: {
              Authorization: undefined,
            },
          }
        )
        .then((res) =>
          setLocationsData((prevState) => ({
            ...prevState,
            barangays: res.data,
          }))
        );
    }
  }, [city_or_municipality_name]);

  console.log("statusCreateUser", statusCreateUser);

  //* RESET FORMS
  useEffect(() => {
    if (statusInventoryChild === "createInventoryChild/success") {
      reset();
    }

    if (statusCreateUser === "addUser/success") {
      reset();
    }

    if (statusCreateVoucherChild === "addChildVoucherData/success") {
      reset();
    }
    if (statusCreateExpenses === "addExpensesData/success") {
      reset();
    }
    if (statusAddInventoryLost === "addInventoryLost/success") {
      reset();
    }
    if (statusAddInventoryFound === "addInventoryFound/success") {
      reset();
    }
    if (statusAddInventoryRestock === "addInventoryRestock/success") {
      reset();
    }
  }, [
    statusInventoryChild,
    statusCreateUser,
    statusCreateVoucherChild,
    statusCreateExpenses,
    inventoryLost,
  ]);

  console.log("initialPage", initialPage);

  switch (initialPage) {
    case "Inventory":
      placeHolder = "Search product";
      columnName = "name";
      jsx = (
        <>
          {Array.isArray(inventoryChildData.data?.buttons) &&
            inventoryChildData.data?.buttons.map((btn: any, index: any) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Button size="sm" className="font-semibold bg-bgrjavancena">
                    {btn.button_name}
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader className="sm:px-5">
                    <DialogTitle>Insert New Product</DialogTitle>
                    <DialogDescription>
                      This form allows you to seamlessly add a new product to
                      your inventory. Please fill out the required fields to
                      proceed.
                    </DialogDescription>
                  </DialogHeader>
                  <ScrollArea className="h-72 w-full">
                    <form
                      onSubmit={handleSubmit(
                        handleFormSubmit(btn.url, "inventory")
                      )}
                      className="grid py-4 gap-6 px-2 sm:px-5"
                    >
                      {btn?.details.map((detail: any, index: number) => (
                        <div key={index} className="flex flex-col gap-2">
                          {detail.type !== "file" &&
                            detail.type !== "select" && (
                              <>
                                <Label className="font-semibold text-xs">
                                  {detail?.label}
                                </Label>
                                <>
                                  <Input
                                    type={detail.type}
                                    {...register(
                                      detail.label
                                        .replace(/\s+/g, "_")
                                        .toLowerCase()
                                    )}
                                    className="col-span-4"
                                  />
                                </>
                              </>
                            )}
                          {detail.type === "file" && (
                            <>
                              <Label className="text-xs font-semibold">
                                {detail.label}
                              </Label>
                              <Input
                                id="productImg"
                                type="file"
                                className="col-span-4"
                                ref={imageInputRef}
                                {...register(
                                  detail.label
                                    .replace(/\s+/g, "_")
                                    .toLowerCase()
                                )}
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
                                  <SelectValue placeholder="Select option" />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectGroup>
                                    <SelectItem value="yes">Yes</SelectItem>
                                    <SelectItem value="no">No</SelectItem>
                                  </SelectGroup>
                                </SelectContent>
                              </Select>
                            </>
                          )}
                          <small className="text-red-500 w-full col-span-3">
                            {inventoryChildError &&
                              inventoryChildError[
                                _.replace(_.lowerCase(detail.label), " ", "_")
                              ]}
                          </small>
                        </div>
                      ))}

                      <DialogFooter>
                        <Button
                          className="bg-bgrjavancena disabled:opacity-100"
                          type="submit"
                          disabled={loadingCreateChild}
                        >
                          {loadingCreateChild && (
                            <span className="flex items-center gap-1">
                              Inserting...
                              <IconReload className="animate-spin" size={16} />
                            </span>
                          )}
                          {!loadingCreateChild && "Insert to table"}
                        </Button>
                        <DialogClose asChild>
                          <Button type="button" variant="secondary">
                            Close
                          </Button>
                        </DialogClose>
                      </DialogFooter>
                    </form>
                  </ScrollArea>
                </DialogContent>
              </Dialog>
            ))}
        </>
      );
      rowsSelection = false;
      break;
    case "Dashboard":
      placeHolder = "Search transaction";
      columnName = "customerId";
      rowsSelection = false;
      break;
    case "Users":
      placeHolder = "Search Users";
      columnName = "email";
      jsx = (
        <>
          {Array.isArray(usersParentData.data?.buttons) &&
            usersParentData.data?.buttons?.map((btn: any, index: any) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Button size="sm" className="font-semibold bg-bgrjavancena">
                    {btn.button_name}
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader className="sm:px-5">
                    <DialogTitle>Add New User</DialogTitle>
                    <DialogDescription>
                      Enter the details below to add a new user to the system.
                      Ensure all required fields are completed accurately.
                    </DialogDescription>
                  </DialogHeader>
                  <ScrollArea className="h-72 w-full">
                    <form
                      onSubmit={handleSubmit(
                        handleFormSubmit(btn.url, "users", btn)
                      )}
                      className="grid py-4 gap-6 px-2 sm:px-5"
                    >
                      {btn?.details?.map((detail: any) => {
                        return (
                          <>
                            {detail.type === "input" && (
                              <div className="flex flex-col gap-2">
                                <Label className="text-sm font-semibold">
                                  {detail?.label}
                                </Label>
                                <Input
                                  type={detail?.type}
                                  placeholder={`Enter your ${detail?.label}`}
                                  className="col-span-3"
                                  {...register(
                                    detail.label
                                      .replace(/\s+/g, "_")
                                      .toLowerCase()
                                  )}
                                />
                                {usersParentError?.message[
                                  _.replace(_.lowerCase(detail.label), " ", "_")
                                ] && (
                                  <small className="text-xs text-red-500">
                                    {
                                      usersParentError?.message[
                                        _.replace(
                                          _.lowerCase(detail.label),
                                          " ",
                                          "_"
                                        )
                                      ]
                                    }
                                  </small>
                                )}
                              </div>
                            )}

                            {detail.type === "email" && (
                              <div className="flex flex-col gap-2">
                                <Label className="text-sm font-semibold">
                                  {detail?.label}
                                </Label>
                                <Input
                                  type={detail?.type}
                                  placeholder={`Enter your ${detail?.label}`}
                                  className="col-span-3"
                                  {...register(
                                    detail.label
                                      .replace(/\s+/g, "_")
                                      .toLowerCase()
                                  )}
                                />
                                {usersParentError?.message[
                                  _.replace(_.lowerCase(detail.label), " ", "_")
                                ] && (
                                  <small className="text-xs text-red-500">
                                    {
                                      usersParentError?.message[
                                        _.replace(
                                          _.lowerCase(detail.label),
                                          " ",
                                          "_"
                                        )
                                      ]
                                    }
                                  </small>
                                )}
                              </div>
                            )}

                            {detail.type === "password" && (
                              <div className="flex flex-col gap-2">
                                <Label className="text-sm font-semibold">
                                  {detail?.label}
                                </Label>
                                <Input
                                  type={detail?.type}
                                  placeholder={`Enter your ${detail?.label}`}
                                  className="col-span-3"
                                  {...register(
                                    detail.label
                                      .replace(/\s+/g, "_")
                                      .toLowerCase()
                                  )}
                                />
                                {usersParentError?.message[
                                  _.replace(_.lowerCase(detail.label), " ", "_")
                                ] && (
                                  <small className="text-xs text-red-500">
                                    {
                                      usersParentError?.message[
                                        _.replace(
                                          _.lowerCase(detail.label),
                                          " ",
                                          "_"
                                        )
                                      ]
                                    }
                                  </small>
                                )}
                              </div>
                            )}

                            {detail.type === "number" && (
                              <div className="flex flex-col gap-2 relative">
                                <Label className="text-sm font-semibold">
                                  {detail?.label}
                                </Label>
                                <div className="flex items-center">
                                  <img
                                    className="w-5 absolute right-7"
                                    src={phFlag}
                                    alt=""
                                  />
                                  <span className="absolute right-14 text-xs">
                                    +63
                                  </span>
                                  <Input
                                    maxLength={10}
                                    type="text"
                                    placeholder={`Enter your ${detail?.label}`}
                                    className="col-span-3"
                                    {...register(
                                      detail.label
                                        .replace(/\s+/g, "_")
                                        .toLowerCase()
                                    )}
                                  />
                                </div>
                                {usersParentError?.message[
                                  _.replace(_.lowerCase(detail.label), " ", "_")
                                ] && (
                                  <small className="text-xs text-red-500">
                                    {
                                      usersParentError?.message[
                                        _.replace(
                                          _.lowerCase(detail.label),
                                          " ",
                                          "_"
                                        )
                                      ]
                                    }
                                  </small>
                                )}
                              </div>
                            )}

                            {detail.type === "select" &&
                              detail.label === "Region Name" && (
                                <div className="grid w-full items-center gap-1.5">
                                  <Label className="text-sm font-semibold">
                                    {detail.label}
                                  </Label>
                                  <Select
                                    onValueChange={(value) => {
                                      const selected =
                                        locationsData.regions.find(
                                          (region: any) => region.code === value
                                        );
                                      setValue(
                                        detail.label
                                          .replace(/\s+/g, "_")
                                          .toLowerCase(),
                                        value
                                      );
                                      onChangeSelect(detail.label, selected);
                                    }}
                                  >
                                    <SelectTrigger>
                                      <SelectValue
                                        placeholder={
                                          detail.value_name || "Select a region"
                                        }
                                      />
                                    </SelectTrigger>
                                    <SelectContent>
                                      <SelectGroup>
                                        <SelectLabel>Region</SelectLabel>
                                        {Array.isArray(locationsData.regions) &&
                                          locationsData.regions.map(
                                            (region: any) => (
                                              <SelectItem
                                                key={region.code}
                                                value={region.code}
                                              >
                                                {region.regionName}
                                              </SelectItem>
                                            )
                                          )}
                                      </SelectGroup>
                                    </SelectContent>
                                  </Select>
                                  <small className="text-red-500">
                                    {/* {updateSettingsProfileData &&
                                        updateSettingsProfileData?.message?.[
                                          "region_name"
                                        ]} */}
                                  </small>
                                </div>
                              )}

                            {detail.type === "select" &&
                              detail.label === "Province Name" && (
                                <div className="grid w-full items-center gap-1.5">
                                  <Label className="text-sm font-semibold">
                                    {detail.label}
                                  </Label>
                                  <Select
                                    onValueChange={(value) => {
                                      const selected =
                                        locationsData?.provinces.find(
                                          (province: any) =>
                                            province.code === value
                                        );
                                      setValue(
                                        detail.label
                                          .replace(/\s+/g, "_")
                                          .toLowerCase(),
                                        value
                                      );
                                      onChangeSelect(detail.label, selected);
                                    }}
                                  >
                                    <SelectTrigger>
                                      <SelectValue
                                        placeholder={
                                          detail.value_name ||
                                          "Select a province"
                                        }
                                      />
                                    </SelectTrigger>
                                    <SelectContent>
                                      <SelectGroup>
                                        <SelectLabel>Province</SelectLabel>
                                        {Array.isArray(
                                          locationsData.provinces
                                        ) &&
                                          locationsData.provinces.map(
                                            (prov: any) => (
                                              <SelectItem
                                                key={prov.code}
                                                value={prov.code}
                                              >
                                                {prov.name}
                                              </SelectItem>
                                            )
                                          )}
                                      </SelectGroup>
                                    </SelectContent>
                                  </Select>
                                  <small className="text-red-500">
                                    {/* {updateSettingsProfileData &&
                                        updateSettingsProfileData?.message?.[
                                          "province_name"
                                        ]} */}
                                  </small>
                                </div>
                              )}

                            {detail.type === "select" &&
                              detail.label === "City Or Municipality Name" && (
                                <div className="grid w-full items-center gap-1.5">
                                  <Label className="text-sm font-semibold">
                                    {detail.label}
                                  </Label>
                                  <Select
                                    onValueChange={(value) => {
                                      const selected =
                                        locationsData.cities.find(
                                          (municipal: any) =>
                                            municipal.code === value
                                        );
                                      setValue(
                                        detail.label
                                          .replace(/\s+/g, "_")
                                          .toLowerCase(),
                                        value
                                      );
                                      onChangeSelect(detail.label, selected);
                                    }}
                                  >
                                    <SelectTrigger>
                                      <SelectValue
                                        placeholder={
                                          detail.value_name ||
                                          "Select a City/Municipalities"
                                        }
                                      />
                                    </SelectTrigger>
                                    <SelectContent>
                                      <SelectGroup>
                                        <SelectLabel>
                                          City/Municipalities
                                        </SelectLabel>
                                        {Array.isArray(locationsData.cities) &&
                                          locationsData.cities.map(
                                            (mun: any) => (
                                              <SelectItem
                                                key={mun.code}
                                                value={mun.code}
                                              >
                                                {mun.name}
                                              </SelectItem>
                                            )
                                          )}
                                      </SelectGroup>
                                    </SelectContent>
                                  </Select>
                                  <small className="text-red-500">
                                    {/* {updateSettingsProfileData &&
                                        updateSettingsProfileData?.message?.[
                                          "city_or_municipality_name"
                                        ]} */}
                                  </small>
                                </div>
                              )}

                            {detail.type === "select" &&
                              detail.label === "Barangay Name" && (
                                <div className="grid w-full items-center gap-1.5">
                                  <Label className="text-sm font-semibold">
                                    {detail.label}
                                  </Label>
                                  <Select
                                    onValueChange={(value) => {
                                      const selected =
                                        locationsData.barangays.find(
                                          (brgy: any) => brgy.code === value
                                        );
                                      setValue(
                                        detail.label
                                          .replace(/\s+/g, "_")
                                          .toLowerCase(),
                                        value
                                      );
                                      onChangeSelect(detail.label, selected);
                                    }}
                                  >
                                    <SelectTrigger>
                                      <SelectValue
                                        placeholder={
                                          detail.value_name ||
                                          "Select a barangay"
                                        }
                                      />
                                    </SelectTrigger>
                                    <SelectContent>
                                      <SelectGroup>
                                        <SelectLabel>Barangay</SelectLabel>
                                        {Array.isArray(
                                          locationsData.barangays
                                        ) &&
                                          locationsData.barangays.map(
                                            (mun: any) => (
                                              <SelectItem
                                                key={mun.code}
                                                value={mun.code}
                                              >
                                                {mun.name}
                                              </SelectItem>
                                            )
                                          )}
                                      </SelectGroup>
                                    </SelectContent>
                                  </Select>
                                  <small className="text-red-500">
                                    {/* {updateSettingsProfileData &&
                                        updateSettingsProfileData?.message?.[
                                          "city_or_municipality_name"
                                        ]} */}
                                  </small>
                                </div>
                              )}

                            {detail?.label === "Role" && (
                              <div className="flex flex-col gap-2">
                                <Label className="text-sm font-semibold">
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
                                >
                                  <SelectTrigger>
                                    <SelectValue
                                      placeholder={`Select ${_.lowerCase(
                                        detail?.label
                                      )}`}
                                    />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectGroup>
                                      {detail?.option?.map((opt) => (
                                        <SelectItem
                                          key={opt?.value}
                                          value={opt?.value}
                                        >
                                          {opt?.label}
                                        </SelectItem>
                                      ))}
                                    </SelectGroup>
                                  </SelectContent>
                                </Select>
                                {usersParentError?.message[
                                  _.replace(_.lowerCase(detail.label), " ", "_")
                                ] && (
                                  <small className="text-xs text-red-500">
                                    {
                                      usersParentError?.message[
                                        _.replace(
                                          _.lowerCase(detail.label),
                                          " ",
                                          "_"
                                        )
                                      ]
                                    }
                                  </small>
                                )}
                              </div>
                            )}

                            {detail?.label === "Status" && (
                              <div className="flex flex-col gap-2">
                                <Label className="text-sm font-semibold">
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
                                >
                                  <SelectTrigger>
                                    <SelectValue
                                      placeholder={`Select ${_.lowerCase(
                                        detail?.label
                                      )}`}
                                    />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectGroup>
                                      {detail?.option?.map((opt) => (
                                        <SelectItem
                                          key={opt?.value}
                                          value={opt?.value}
                                        >
                                          {opt?.label}
                                        </SelectItem>
                                      ))}
                                    </SelectGroup>
                                  </SelectContent>
                                </Select>
                                {usersParentError?.message[
                                  _.replace(_.lowerCase(detail.label), " ", "_")
                                ] && (
                                  <small className="text-xs text-red-500">
                                    {
                                      usersParentError?.message[
                                        _.replace(
                                          _.lowerCase(detail.label),
                                          " ",
                                          "_"
                                        )
                                      ]
                                    }
                                  </small>
                                )}
                              </div>
                            )}
                          </>
                        );
                      })}
                      <DialogFooter>
                        <Button
                          className="bg-bgrjavancena disabled:opacity-100"
                          type="submit"
                          disabled={!isDirty || loadingCreateUser}
                        >
                          {loadingCreateUser && (
                            <span className="flex items-center gap-1">
                              Creating...
                              <IconReload className="animate-spin" size={16} />
                            </span>
                          )}
                          {!loadingCreateUser && "Create User"}
                        </Button>
                        <DialogClose asChild>
                          <Button type="button" variant="secondary">
                            Close
                          </Button>
                        </DialogClose>
                      </DialogFooter>
                    </form>
                  </ScrollArea>
                </DialogContent>
              </Dialog>
            ))}
        </>
      );
      rowsSelection = false;
      break;
    case "CustomerOrder":
      placeHolder = "Search users";
      columnName = "customerName";
      rowsSelection = false;
      break;
    case "Customer":
      placeHolder = "Search customer";
      columnName = "user_id";
      rowsSelection = false;
      break;
    case "Logs":
      placeHolder = "Search log";
      columnName = "log_id";
      rowsSelection = false;
      break;
    case "Voucher":
      placeHolder = "Search voucher";
      columnName = "name";
      jsx = (
        <>
          {Array.isArray(childVoucherData.data?.buttons) &&
            childVoucherData.data?.buttons.map((btn: any, index: any) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Button size="sm" className="font-semibold bg-bgrjavancena">
                    {btn.button_name}
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader className="sm:px-5">
                    <DialogTitle>Create new voucher</DialogTitle>
                    <DialogDescription>
                      This form allows you to seamlessly add a new voucher.
                      Please fill out the required fields to proceed.
                    </DialogDescription>
                  </DialogHeader>
                  <form
                    onSubmit={handleSubmit(
                      handleFormSubmit(btn.url, "voucher")
                    )}
                    className="grid py-4 gap-6 px-2 sm:px-5"
                  >
                    {btn?.details?.map((detail: any, index: number) => (
                      <div className="flex flex-col gap-2" key={index}>
                        <Label className="text-sm font-semibold">
                          {detail.label}
                        </Label>
                        <Input
                          type={detail.type}
                          placeholder={`Enter ${detail.label}`}
                          className="col-span-3"
                          {...register(
                            detail?.label.replace(/\s+/g, "_").toLowerCase()
                          )}
                        />
                        {childVoucherError?.message?.voucher_code && (
                          <small className="text-xs text-red-500">
                            {childVoucherError?.message?.voucher_code}
                          </small>
                        )}
                      </div>
                    ))}

                    <DialogFooter>
                      <Button
                        className="bg-bgrjavancena disabled:opacity-100"
                        type="submit"
                        disabled={loadingCreateVoucherChild}
                      >
                        {loadingCreateVoucherChild && (
                          <span className="flex items-center gap-1">
                            Inserting...
                            <IconReload className="animate-spin" size={16} />
                          </span>
                        )}
                        {!loadingCreateVoucherChild && "Insert to table"}
                      </Button>
                      <DialogClose asChild>
                        <Button type="button" variant="secondary">
                          close
                        </Button>
                      </DialogClose>
                    </DialogFooter>
                  </form>
                </DialogContent>
              </Dialog>
            ))}
        </>
      );
      rowsSelection = true;
      break;
    case "Expenses":
      placeHolder = "Search expenses";
      columnName = "name";
      jsx = (
        <>
          {Array.isArray(expensesData.data?.buttons) &&
            expensesData.data?.buttons.map((btn: any, index: any) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Button size="sm" className="font-semibold bg-bgrjavancena">
                    {btn.button_name}
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader className="px-5">
                    <DialogTitle>Add expenses</DialogTitle>
                    <DialogDescription>
                      This form allows you to seamlessly add a expenses to your
                      expenses. Please fill out the required fields to proceed.
                    </DialogDescription>
                  </DialogHeader>
                  <ScrollArea className="h-72 w-full">
                    <form
                      onSubmit={handleSubmit(
                        handleFormSubmit(btn.url, "expenses")
                      )}
                      className="grid py-4 gap-6 px-2 sm:px-5"
                    >
                      {btn?.details?.map((detail: any, index: number) => (
                        <div className="flex flex-col gap-2" key={index}>
                          <Label className="text-sm font-semibold">
                            {detail.label}
                          </Label>
                          <Input
                            type={detail.type}
                            placeholder={`Enter ${detail.label}`}
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                          {addExpensesError?.message && (
                            <small className="text-xs text-red-500">
                              {addExpensesError?.message &&
                                addExpensesError?.message[
                                  _.replace(_.lowerCase(detail.label), " ", "_")
                                ]}
                            </small>
                          )}
                        </div>
                      ))}

                      <DialogFooter>
                        <Button
                          className="bg-bgrjavancena disabled:opacity-100"
                          type="submit"
                          disabled={addExpensesLoading}
                        >
                          {addExpensesLoading && (
                            <span className="flex items-center gap-1">
                              Inserting...
                              <IconReload className="animate-spin" size={16} />
                            </span>
                          )}
                          {!addExpensesLoading && "Insert to table"}
                        </Button>
                        <DialogClose asChild>
                          <Button type="button" variant="secondary">
                            Back
                          </Button>
                        </DialogClose>
                      </DialogFooter>
                    </form>
                  </ScrollArea>
                </DialogContent>
              </Dialog>
            ))}
        </>
      );
      rowsSelection = true;
      break;
    case "Expenses View":
      placeHolder = "Search file";
      columnName = "file";
      break;
    case "inventory-lost":
      placeHolder = "Search inventory lost";
      jsx = (
        <>
          {Array.isArray(inventoryLost.data?.buttons) &&
            inventoryLost.data?.buttons?.map((btn: any, index: any) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Button size="sm" className="font-semibold bg-bgrjavancena">
                    {btn.button_name}
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader className="px-5">
                    <DialogTitle>Add lost item</DialogTitle>
                    <DialogDescription>
                      This form allows you to seamlessly add a lost item to your
                      table. Please fill out the required fields to proceed.
                    </DialogDescription>
                  </DialogHeader>
                  <form
                    onSubmit={handleSubmit(
                      handleFormSubmit(btn.url, "inventoryLost")
                    )}
                    className="grid py-4 gap-6 px-2 sm:px-5"
                  >
                    {btn?.details?.map((detail: any) => (
                      <div key={detail.label} className="flex flex-col gap-2">
                        <Label className="text-sm font-semibold">
                          {detail.label}
                        </Label>

                        {detail.type === "number" ? (
                          // Number Input Field
                          <Input
                            type="number"
                            placeholder={detail.placeholder || ""}
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : detail.type === "textarea" ? (
                          // Textarea Field
                          <Textarea
                            placeholder={
                              detail.placeholder || "Type your remarks here."
                            }
                            id="message"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : detail.type === "file" ? (
                          // Image Input Field
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
                        ) : (
                          // Default Input Field if type is not matched
                          <Input
                            type={detail.type || "text"}
                            placeholder={detail.placeholder || ""}
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        )}

                        {/* Display Error Message */}
                        {inventoryErrorMessage && (
                          <small className="text-xs text-red-500">
                            {
                              inventoryErrorMessage[
                                _.replace(_.lowerCase(detail.label), " ", "_")
                              ]
                            }
                          </small>
                        )}
                      </div>
                    ))}

                    <DialogFooter>
                      <Button
                        className="bg-bgrjavancena disabled:opacity-100"
                        type="submit"
                        disabled={loadingAddLost}
                      >
                        {loadingAddLost && (
                          <span className="flex items-center gap-1">
                            Inserting...
                            <IconReload className="animate-spin" size={16} />
                          </span>
                        )}
                        {!loadingAddLost && "Add lost item"}
                      </Button>
                      <DialogClose asChild>
                        <Button type="button" variant="secondary">
                          Close
                        </Button>
                      </DialogClose>
                    </DialogFooter>
                  </form>
                </DialogContent>
              </Dialog>
            ))}
        </>
      );
      break;
    case "inventory-found":
      placeHolder = "Search inventory found";
      jsx = (
        <>
          {Array.isArray(inventoryFound.data?.buttons) &&
            inventoryFound.data?.buttons?.map((btn: any, index: any) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Button size="sm" className="font-semibold bg-bgrjavancena">
                    {btn.button_name}
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader className="px-5">
                    <DialogTitle>Add found item</DialogTitle>
                    <DialogDescription>
                      This form allows you to seamlessly add a found item to
                      your table. Please fill out the required fields to
                      proceed.
                    </DialogDescription>
                  </DialogHeader>
                  <form
                    onSubmit={handleSubmit(
                      handleFormSubmit(btn.url, "inventoryFound")
                    )}
                    className="grid py-4 gap-6 px-2 sm:px-5"
                  >
                    {btn?.details?.map((detail: any) => (
                      <div key={detail.label} className="flex flex-col gap-2">
                        <Label className="text-sm font-semibold">
                          {detail.label}
                        </Label>

                        {detail.type === "number" ? (
                          // Number Input Field
                          <Input
                            type="number"
                            placeholder={detail.placeholder || ""}
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : detail.type === "textarea" ? (
                          // Textarea Field
                          <Textarea
                            placeholder={
                              detail.placeholder || "Type your remarks here."
                            }
                            id="message"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : detail.type === "image" ? (
                          // Image Input Field
                          <Input
                            type="file"
                            accept="image/*"
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : (
                          // Default Input Field if type is not matched
                          <Input
                            type={detail.type || "text"}
                            placeholder={detail.placeholder || ""}
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        )}

                        {/* Display Error Message */}
                        {inventoryErrorMessage && (
                          <small className="text-xs text-red-500">
                            {
                              inventoryErrorMessage[
                                _.replace(_.lowerCase(detail.label), " ", "_")
                              ]
                            }
                          </small>
                        )}
                      </div>
                    ))}

                    <DialogFooter>
                      <Button
                        className="bg-bgrjavancena disabled:opacity-100"
                        type="submit"
                        disabled={loadingAddFound}
                      >
                        {loadingAddFound && (
                          <span className="flex items-center gap-1">
                            Inserting...
                            <IconReload className="animate-spin" size={16} />
                          </span>
                        )}
                        {!loadingAddFound && "Add found item"}
                      </Button>
                      <DialogClose asChild>
                        <Button type="button" variant="secondary">
                          Close
                        </Button>
                      </DialogClose>
                    </DialogFooter>
                  </form>
                </DialogContent>
              </Dialog>
            ))}
        </>
      );
      break;
    case "inventory-restock":
      placeHolder = "Search restock item";
      jsx = (
        <>
          {Array.isArray(inventoryRestock.data?.buttons) &&
            inventoryRestock.data?.buttons?.map((btn: any, index: any) => (
              <Dialog key={index}>
                <DialogTrigger asChild>
                  <Button size="sm" className="font-semibold bg-bgrjavancena">
                    {btn.button_name}
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader className="px-5">
                    <DialogTitle>Add found item</DialogTitle>
                    <DialogDescription>
                      This form allows you to seamlessly add a found item to
                      your table. Please fill out the required fields to
                      proceed.
                    </DialogDescription>
                  </DialogHeader>
                  <form
                    onSubmit={handleSubmit(
                      handleFormSubmit(btn.url, "inventoryRestock")
                    )}
                    className="grid py-4 gap-6 px-2 sm:px-5"
                  >
                    {btn?.details?.map((detail: any) => (
                      <div key={detail.label} className="flex flex-col gap-2">
                        <Label className="text-sm font-semibold">
                          {detail.label}
                        </Label>

                        {detail.type === "number" ? (
                          // Number Input Field
                          <Input
                            type="number"
                            placeholder={detail.placeholder || ""}
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : detail.type === "textarea" ? (
                          // Textarea Field
                          <Textarea
                            placeholder={
                              detail.placeholder || "Type your remarks here."
                            }
                            id="message"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : detail.type === "image" ? (
                          // Image Input Field
                          <Input
                            type="file"
                            accept="image/*"
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        ) : (
                          // Default Input Field if type is not matched
                          <Input
                            type={detail.type || "text"}
                            placeholder={detail.placeholder || ""}
                            className="col-span-3"
                            {...register(
                              detail?.label.replace(/\s+/g, "_").toLowerCase()
                            )}
                          />
                        )}

                        {/* Display Error Message */}
                        {inventoryErrorMessage && (
                          <small className="text-xs text-red-500">
                            {
                              inventoryErrorMessage[
                                _.replace(_.lowerCase(detail.label), " ", "_")
                              ]
                            }
                          </small>
                        )}
                      </div>
                    ))}
                    <DialogFooter>
                      <Button
                        className="bg-bgrjavancena disabled:opacity-100"
                        type="submit"
                        disabled={loadingAddRestock}
                      >
                        {loadingAddRestock && (
                          <span className="flex items-center gap-1">
                            Inserting...
                            <IconReload className="animate-spin" size={16} />
                          </span>
                        )}
                        {!loadingAddRestock && "Add found item"}
                      </Button>
                      <DialogClose asChild>
                        <Button type="button" variant="secondary">
                          Close
                        </Button>
                      </DialogClose>
                    </DialogFooter>
                  </form>
                </DialogContent>
              </Dialog>
            ))}
        </>
      );
      break;
    default:
      placeHolder = null;
      columnName = null;
      jsx = null;
      break;
  }

  return (
    <TableContext.Provider
      value={{
        page,
        setPage,
        jsx,
        placeHolder,
        columnName,
        rowsSelection,
        selectedOption,
        setSelectedOption,
      }}
    >
      {children}
    </TableContext.Provider>
  );
};

export const useTableContext = () => {
  return useContext(TableContext);
};
