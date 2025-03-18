import { motion, AnimatePresence } from "framer-motion";
import { useEffect, useState } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Progress } from "@/components/ui/progress";
import { Button } from "@/components/ui/button";
import { useForm } from "react-hook-form";
import { IconCircleCheckFilled, IconReload } from "@tabler/icons-react";
import phFlag from "@/assets/images/phflag.svg";
import _ from "lodash";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import {
  getRegions,
  getProvinces,
  getMunicipality,
  getBarangay,
} from "@/app/slice/userSlice";
import Cookies from "js-cookie";
import React from "react";
import logo from "@/assets/svg/rjlogo.svg";
import { Icon } from "@iconify/react";
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";
import { useNavigate } from "react-router-dom";
import useAxiosClient from "@/axios-client";
import { useToast } from "@/components/ui/use-toast";

const Welcome: React.FC = () => {
  const [isVisible, setIsVisible] = useState(true);
  const [fadeLeft, setFadeLeft] = useState(true);
  const [formSetIn, setFormSetIn] = useState(true);
  const dispatch = useAppDispatch();
  const regionsData = useAppSelector((state) => state.user.getRegions);
  const provincesData = useAppSelector((state) => state.user.getProvinces);
  const municipalityData = useAppSelector(
    (state) => state.user.getMunicipality
  );
  const brgyData = useAppSelector((state) => state.user.getBarangay);
  const errorCompleteProfile = useAppSelector(
    (state) => state.user.errorCompleteProfile
  );

  const [progress, setProgress] = useState(0);
  const navigate = useNavigate();
  const { register, setValue, getValues, watch } = useForm();

  const axiosClient = useAxiosClient();

  const inputFields = [
    { label: "Profile", type: "file", section: "profile", required: false },
    { label: "First name", type: "text", section: "profile", required: true },
    { label: "Middle name", type: "text", section: "profile", required: true },
    { label: "Last name", type: "text", section: "profile", required: true },
    { label: "Region", type: "select", section: "address", required: true },
    { label: "Province", type: "select", section: "address", required: true },
    {
      label: "City/Municipalities",
      type: "select",
      section: "address",
      required: true,
    },
    { label: "Barangay", type: "select", section: "address", required: true },
    { label: "Address 1", type: "text", section: "address", required: true },
    { label: "Address 2", type: "text", section: "address", required: false }, // Optional
  ];

  useEffect(() => {
    const timer = setTimeout(() => {
      setIsVisible(false);
    }, 2000);

    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      setFadeLeft(false);
    }, 4000);

    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      setFormSetIn(false);
    }, 5000);

    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    dispatch(
      getRegions({
        url: "https://psgc.gitlab.io/api/regions/",
        method: "GET",
      })
    );
  }, []);

  const formValues = watch();
  const { region, province, city_municipalities } = formValues;

  const { toast } = useToast();

  useEffect(() => {
    if (region) {
      dispatch(
        getProvinces({
          url: `https://psgc.gitlab.io/api/regions/${region}/provinces/`,
          method: "GET",
        })
      );
    }
  }, [region]);

  useEffect(() => {
    if (province) {
      dispatch(
        getMunicipality({
          url: `https://psgc.gitlab.io/api/provinces/${province}/cities-municipalities/`,
          method: "GET",
        })
      );
    }
  }, [province]);

  useEffect(() => {
    if (city_municipalities) {
      dispatch(
        getBarangay({
          url: `https://psgc.gitlab.io/api/cities-municipalities/${city_municipalities}/barangays/`,
          method: "GET",
        })
      );
    }
  }, [city_municipalities]);

  useEffect(() => {
    const completedFields = Object.entries(formValues).filter(
      ([key, value]) =>
        value &&
        inputFields.find(
          (field) =>
            field.label.replace(/\//g, "_").replace(/ /g, "_").toLowerCase() ===
              key && field.required
        )
    ).length;

    const totalRequiredFields = inputFields.filter(
      (field) => field.required
    ).length;

    setProgress((completedFields / totalRequiredFields) * 100);
  }, [formValues]);

  const [getLocationCode, setGetLocationCode] = useState({});

  const onChangeSelect = (type: any, values: any) => {
    setGetLocationCode((prevState) => ({
      ...prevState,
      [`${type.replace(/\//g, "_").toLowerCase()}Name`]: values.name,
    }));
  };

  const sideNavRoutes = [
    {
      icon: "heroicons-outline:view-grid",
    },
    {
      icon: "heroicons-outline:chart-pie",
    },
    {
      icon: "heroicons-outline:cube",
    },
    {
      icon: "heroicons-outline:user-group",
    },
  ];

  const [isLoading, setIsLoading] = useState(false);

  const handleCompleteProfile = async () => {
    try {
      const formData = getValues();
      setIsLoading(true);
      console.log(formData);

      const payload = {
        image: formValues?.profile?.[0] ?? null,
        first_name: formData.first_name,
        middle_name: formData.middle_name,
        last_name: formData.last_name,
        address_1: formData.address_1,
        address_2: formData.address_2,
        region_name: getLocationCode.regionName,
        region_code: formData.region,
        province_name: getLocationCode.provinceName,
        province_code: formData.province,
        city_or_municipality_name: getLocationCode.city_municipalitiesName,
        city_or_municipality_code: formData.city_municipalities,
        barangay_name: getLocationCode.barangayName,
        barangay_code: formData.barangay,
        eu_device: Cookies.get("eu"),
      };

      const res = await axiosClient.post("user-info/store", payload, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      if (res.status === 200) {
        navigate("/app/menu");
      }
    } catch (error: any) {
      setIsLoading(false);
      if (error.response.data.message === "Token not provided") {
        toast({
          variant: "destructive",
          title: "Uh oh! Something went wrong.",
          description: error.response.data.message,
        });
      }
    }
  };

  return (
    <>
      <div className="w-full h-screen bg-neutral-200">
        {formSetIn && (
          <div className="flex flex-col items-center h-screen justify-center gap-3">
            <AnimatePresence>
              {isVisible && (
                <>
                  <motion.h1
                    initial={{ opacity: 0, x: 100 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{
                      delay: 0.3,
                      ease: "easeInOut",
                      duration: 1.2,
                    }}
                    exit={{
                      opacity: 0,
                      transition: { delay: 2, duration: 1.2 },
                    }}
                    className="font-black text-2xl sm:text-5xl"
                  >
                    Welcome to RJ AVANCENA
                  </motion.h1>
                </>
              )}
            </AnimatePresence>
            <AnimatePresence>
              {fadeLeft && (
                <>
                  <motion.p
                    initial={{ opacity: 0, y: 100 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                      delay: 0.9,
                      ease: "easeInOut",
                      duration: 1.2,
                    }}
                    exit={{
                      opacity: 0,
                      x: -100,
                      transition: { duration: 1 },
                    }}
                    className="text-neutral-400 sm:text-lg"
                  >
                    Your Personalized Dashboard Awaits
                  </motion.p>
                </>
              )}
            </AnimatePresence>
          </div>
        )}
        {!formSetIn && (
          <AnimatePresence>
            <motion.div
              initial={{ opacity: 0, y: 0 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{
                delay: 0.5,
                ease: "easeInOut",
                duration: 1.2,
              }}
              className={`mx-auto h-full w-full flex lg:h-screen lg:flex-cols-2 lg:flex-1 overflow-auto`}
            >
              {/* Navbar */}
              <div
                className={`min-h-screen hidden border-r p-2 lg:flex flex-col bg-white transition-all relative w-14`}
              >
                <div className="flex items-center justify-between">
                  <img src={logo} alt="RJ Avancena Logo" />
                </div>
                <nav className="gap-2 flex flex-col pt-10 ease-in-out transition-all">
                  {sideNavRoutes &&
                    sideNavRoutes.map((menu: any, index: number) => (
                      <React.Fragment key={index}>
                        <div
                          className={cn(
                            buttonVariants({
                              size: "sm",
                              variant: "ghost",
                            }),
                            `justify-between font-medium text-neutral-400 border-[1px] border-neutral-300 right-0  ${
                              index === 0 &&
                              "text-[#f66359] hover:bg-bgrjavancena/90 hover:text-white"
                            }`
                          )}
                        >
                          <div className={`flex items-center gap-3 text-start`}>
                            <Icon fontSize={17} icon={menu.icon} />
                          </div>
                        </div>
                      </React.Fragment>
                    ))}
                </nav>
              </div>
              <div className="w-full overflow-auto">
                <div className="sticky top-0 z-40 border-b-[1px] py-1 w-full bg-white">
                  <div className="p-1 px-7 flex items-center justify-between lg:px-3">
                    <h1 className="font-bold text-lg lg:ml-0">
                      <span className="flex flex-col gap-1">
                        <div className="h-3 w-20 bg-neutral-200" />
                        <div className="h-3 w-16 bg-neutral-200" />
                      </span>
                    </h1>
                    <div className="flex items-center gap-6">
                      <div className="h-7 w-7 bg-neutral-200 rounded-full" />
                      <div className="h-7 w-7 bg-neutral-200 rounded-full" />
                      <div className="h-7 w-7 bg-neutral-200 rounded-full" />
                    </div>
                  </div>
                </div>
                <motion.div
                  initial={{ opacity: 0, y: 100 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{
                    delay: 0.3,
                    ease: "easeInOut",
                    duration: 1.2,
                  }}
                  className="p-4 lg:p-10 w-full flex flex-col m-auto max-w-[64rem]"
                >
                  <div className="flex flex-col gap-3 pb-10">
                    <h1 className="text-2xl font-bold">
                      Lets get you started!
                    </h1>
                    <p className="text-sm text-neutral-500 max-w-[44rem]">
                      Welcome to RJ AVANCENA, where we aim to provide you with a
                      seamless and personalized experience. Below is your
                      profile information. Feel free to review and update any
                      details as necessary.
                    </p>
                  </div>
                  <div className="bg-white rounded-lg w-full  border-2 py-5 border-neutral-300 ">
                    <h1 className="font-medium px-6 mb-4 flex items-center gap-3">
                      Complete your profile{" "}
                      {progress === 100 && (
                        <IconCircleCheckFilled color="green" />
                      )}
                    </h1>
                    <Progress
                      className="rounded-none h-[3px]"
                      value={progress}
                    />
                    <div className="px-6">
                      {/* Profile Information */}
                      <div className="pt-10">
                        <h1 className="text-sm font-bold">
                          Profile Information
                        </h1>
                        <div className="pt-6 gap-6 w0f lg:grid">
                          <div className="grid gap-5 lg:grid-cols-2">
                            {inputFields
                              .filter((field) => field.section === "profile")
                              .map((field, index) => (
                                <div
                                  key={index}
                                  className="grid w-full lg:max-w-sm items-center gap-1.5"
                                >
                                  {field.label !== "Contact number" && (
                                    <>
                                      <Label className="text-neutral-600">
                                        {field.label}
                                      </Label>
                                      <Input
                                        id={field.label}
                                        placeholder={`Enter your ${field.label}`}
                                        type={field.type}
                                        {...register(
                                          field.label
                                            .replace(/\s+/g, "_")
                                            .toLowerCase()
                                        )}
                                      />
                                      <small className="text-red-500">
                                        {errorCompleteProfile &&
                                          errorCompleteProfile?.message[
                                            _.replace(
                                              _.lowerCase(field?.label),
                                              " ",
                                              "_"
                                            )
                                          ]}
                                      </small>
                                    </>
                                  )}

                                  {field.label == "Contact number" && (
                                    <>
                                      <Label className="text-neutral-600">
                                        {field.label}
                                      </Label>
                                      <span className="flex items-center relative">
                                        <div className="flex items-center">
                                          <img
                                            className="w-5 absolute right-4"
                                            src={phFlag}
                                            alt=""
                                          />
                                          <span className="absolute right-10 text-xs">
                                            +63
                                          </span>
                                        </div>
                                        <Input
                                          id={field.label}
                                          type="text mr-4"
                                          placeholder={`${field.label}`}
                                          maxLength={10}
                                          {...register(
                                            field.label
                                              .replace(/\s+/g, "_")
                                              .toLowerCase()
                                          )}
                                        />
                                      </span>
                                      <small className="text-red-500">
                                        {errorCompleteProfile &&
                                          errorCompleteProfile?.message[
                                            _.replace(
                                              _.lowerCase(field?.label),
                                              " ",
                                              "_"
                                            )
                                          ]}
                                      </small>
                                    </>
                                  )}
                                </div>
                              ))}
                          </div>
                        </div>
                      </div>

                      {/* Address Information */}
                      <div className="pt-10">
                        <h1 className="text-sm font-bold">
                          Address Information
                        </h1>
                        <div className="pt-6 gap-6 lg:grid">
                          <div className="grid gap-5 lg:grid-cols-2">
                            {inputFields
                              .filter((field) => field?.section === "address")
                              .map((field, index) => (
                                <div
                                  key={index}
                                  className="grid w-full lg:max-w-sm items-center gap-1.5"
                                >
                                  <Label
                                    className="text-neutral-600"
                                    htmlFor={field.label}
                                  >
                                    {field.label}
                                  </Label>
                                  {field.type === "select" &&
                                    field.label === "Region" && (
                                      <>
                                        <Select
                                          onValueChange={(value) => {
                                            const selected = regionsData.find(
                                              (region: any) =>
                                                region.code === value
                                            );
                                            setValue(
                                              field.label
                                                .replace(/\s+/g, "_")
                                                .toLowerCase(),
                                              value
                                            );
                                            onChangeSelect(
                                              field.label,
                                              selected
                                            );
                                          }}
                                        >
                                          <SelectTrigger>
                                            <SelectValue placeholder="Select a region" />
                                          </SelectTrigger>
                                          <SelectContent>
                                            <SelectGroup>
                                              <SelectLabel>Region</SelectLabel>
                                              {Array.isArray(regionsData) &&
                                                regionsData.map(
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
                                          {errorCompleteProfile &&
                                            errorCompleteProfile?.message[
                                              "region_name"
                                            ]}
                                        </small>
                                      </>
                                    )}

                                  {field.type === "select" &&
                                    field.label === "Province" && (
                                      <>
                                        <Select
                                          onValueChange={(value) => {
                                            const selected = provincesData.find(
                                              (province: any) =>
                                                province.code === value
                                            );
                                            setValue(
                                              field.label
                                                .replace(/\s+/g, "_")
                                                .toLowerCase(),
                                              value
                                            );
                                            onChangeSelect(
                                              field.label,
                                              selected
                                            );
                                          }}
                                        >
                                          <SelectTrigger>
                                            <SelectValue placeholder="Select a province" />
                                          </SelectTrigger>
                                          <SelectContent>
                                            <SelectGroup>
                                              <SelectLabel>
                                                Province
                                              </SelectLabel>
                                              {Array.isArray(provincesData) &&
                                                provincesData.map(
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
                                          {errorCompleteProfile &&
                                            errorCompleteProfile?.message[
                                              "province_name"
                                            ]}
                                        </small>
                                      </>
                                    )}

                                  {field.type === "select" &&
                                    field.label === "City/Municipalities" && (
                                      <>
                                        <Select
                                          onValueChange={(value) => {
                                            const selected =
                                              municipalityData.find(
                                                (municipal: any) =>
                                                  municipal.code === value
                                              );
                                            setValue(
                                              field.label
                                                .replace(/\//g, "_")
                                                .toLowerCase(),
                                              value
                                            );
                                            onChangeSelect(
                                              field.label,
                                              selected
                                            );
                                          }}
                                        >
                                          <SelectTrigger>
                                            <SelectValue placeholder="Select a City/Municipalities" />
                                          </SelectTrigger>
                                          <SelectContent>
                                            <SelectGroup>
                                              <SelectLabel>
                                                City/Municipalities
                                              </SelectLabel>
                                              {Array.isArray(
                                                municipalityData
                                              ) &&
                                                municipalityData.map(
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
                                          {errorCompleteProfile &&
                                            errorCompleteProfile?.message[
                                              "city_or_municipality_name"
                                            ]}
                                        </small>
                                      </>
                                    )}

                                  {field.type === "select" &&
                                    field.label === "Barangay" && (
                                      <>
                                        <Select
                                          onValueChange={(value) => {
                                            const selected = brgyData.find(
                                              (brgy: any) => brgy.code === value
                                            );
                                            setValue(
                                              field.label
                                                .replace(/\s+/g, "_")
                                                .toLowerCase(),
                                              value
                                            );
                                            onChangeSelect(
                                              field.label,
                                              selected
                                            );
                                          }}
                                        >
                                          <SelectTrigger>
                                            <SelectValue placeholder="Select a barangay" />
                                          </SelectTrigger>
                                          <SelectContent>
                                            <SelectGroup>
                                              <SelectLabel>
                                                Barangay
                                              </SelectLabel>
                                              {Array.isArray(brgyData) &&
                                                brgyData.map((brgy: any) => (
                                                  <SelectItem
                                                    key={brgy.code}
                                                    value={brgy.code}
                                                  >
                                                    {brgy.name}
                                                  </SelectItem>
                                                ))}
                                            </SelectGroup>
                                          </SelectContent>
                                        </Select>
                                        <small className="text-red-500">
                                          {errorCompleteProfile &&
                                            errorCompleteProfile?.message[
                                              "city_or_municipality_name"
                                            ]}
                                        </small>
                                      </>
                                    )}

                                  {field.type !== "select" && (
                                    <>
                                      <Input
                                        id={field.label}
                                        type={field.type}
                                        placeholder={`Enter your ${field.label}`}
                                        {...register(
                                          field.label
                                            .replace(/\s+/g, "_")
                                            .toLowerCase()
                                        )}
                                      />
                                      <small className="text-red-500">
                                        {errorCompleteProfile &&
                                          errorCompleteProfile?.message[
                                            _.replace(
                                              _.lowerCase(field?.label),
                                              " ",
                                              "_"
                                            )
                                          ]}
                                      </small>
                                    </>
                                  )}
                                </div>
                              ))}
                          </div>
                        </div>
                      </div>

                      <div className="pt-10">
                        <Button
                          onClick={handleCompleteProfile}
                          disabled={progress !== 100 || isLoading}
                          className="bg-bgrjavancena"
                        >
                          {isLoading && (
                            <span className="flex items-center gap-2">
                              Loading...
                              <IconReload className="animate-spin" size={16} />
                            </span>
                          )}
                          {!isLoading && "Submit"}
                        </Button>
                      </div>
                    </div>
                  </div>
                </motion.div>
              </div>
            </motion.div>
          </AnimatePresence>
        )}
      </div>
    </>
  );
};

export default Welcome;
