import React, { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import { selectTitle } from "../common/appSlice";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  IconBell,
  IconMessage,
  IconLogout,
  IconCircleFilled,
  IconPackage,
  IconBuildingWarehouse,
} from "@tabler/icons-react";
import phFlag from "@/assets/images/phflag.svg";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";

export function SeparatorDemo() {
  return (
    <div>
      <div className="space-y-1">
        <h4 className="text-sm font-medium leading-none">Radix Primitives</h4>
        <p className="text-sm text-muted-foreground">
          An open-source UI component library.
        </p>
      </div>
      <Separator className="my-4" />
      <div className="flex h-5 items-center space-x-4 text-sm">
        <div>Blog</div>
        <Separator orientation="vertical" />
        <div>Docs</div>
        <Separator orientation="vertical" />
        <div>Source</div>
      </div>
    </div>
  );
}

import { Icon } from "@iconify/react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useForm } from "react-hook-form";
import { ScrollArea } from "@/components/ui/scroll-area";
import { DialogClose, DialogPortal } from "@radix-ui/react-dialog";
import _, { method } from "lodash";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import Cookies from "js-cookie";
import useAxiosClient from "@/axios-client";
import { useNavigate } from "react-router-dom";
import { Skeleton } from "@/components/ui/skeleton";
import axios from "axios";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useToast } from "@/components/ui/use-toast";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
  resendCodeEmail,
  settingsProfile,
  updateEmailProfile,
  updatePasswordProfile,
  updateSettingsProfile,
  uploadImage,
} from "@/app/slice/userSlice";
import NotificationCard from "@/features/admin/notification/NotificationCard";
import { useInView } from "react-intersection-observer";
import { useFetchInfiniteQuery } from "@/app/api/api";

const Header: React.FC = () => {
  const pageTitle = useSelector(selectTitle);
  const headerUser = useAppSelector((state) => state?.user?.headerUser);
  const userLoading = useAppSelector((state) => state?.user?.loading);
  const navigate = useNavigate();
  const axiosClient = useAxiosClient();
  const dispatch = useAppDispatch();

  const { ref, inView } = useInView();

  const {
    data: notificationsData,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
  } = useFetchInfiniteQuery({
    url: "notification/get-notification",
  });

  useEffect(() => {
    if (inView && hasNextPage) {
      console.log("Fire!");
      fetchNextPage();
    }
  }, [inView, hasNextPage, fetchNextPage]);

  const {
    control,
    handleSubmit,
    getValues,
    setValue,
    register,
    watch,
    formState: { isDirty },
    reset,
  } = useForm({});
  const [modalData, setModalData] = useState({});
  const [funcData, setFuncData] = useState({});
  const [showEditInfoDialog, setShowEditInfoDialog] = useState(false);
  const [showViewProfileDialog, setViewProfileDialog] = useState(false);
  const [selectedTab, setSelectedTab] = useState("profile");

  const settingsProfileData = useAppSelector(
    (state) => state?.user?.settingsProfileData
  );
  const updateEmailProfileData = useAppSelector(
    (state) => state?.user?.updateEmailProfileData
  );

  const updatePasswordProfileData = useAppSelector(
    (state) => state?.user?.updatePasswordProfileData
  );

  const loadingUpdateEmail = useAppSelector(
    (state) => state?.user?.loadingUpdateEmail
  );

  const loadingUpdatePassword = useAppSelector(
    (state) => state?.user?.loadingUpdatePassword
  );

  const settingsActions = settingsProfileData?.data?.user_information?.actions;

  const userStatus = useAppSelector((state) => state?.user?.status);
  const updateSettingsProfileData = useAppSelector(
    (state) => state?.user?.updateSettingsProfileData
  );
  const uploadImageData = useAppSelector(
    (state) => state?.user?.uploadImageData
  );

  const profileData = settingsProfileData?.data?.user_information;

  const { toast } = useToast();

  const [selectedImage, setSelectedImage] = useState(profileData?.image);

  const token = Cookies.get("token");
  const euDevice = Cookies.get("eu");

  const handleClickLogout = async () => {
    try {
      const res = await axiosClient.post(
        "logout",
        {
          eu_device: euDevice,
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );

      if (res.status === 200) {
        Cookies.remove("token");
        navigate("/");
      }
    } catch (error: any) {
      console.log(error);
    }
  };

  const [getLocationCode, setGetLocationCode] = useState({});
  const [locationsData, setLocationsData] = useState({
    regions: [],
    provinces: [],
    cities: [],
    barangays: [],
  });
  const [countdown, setCountdown] = useState({ email: 0, password: 0 });

  const loadingUpdateProfile = useAppSelector(
    (state) => state?.user?.loadingUpdateProfile
  );

  const queryClient = useQueryClient();

  //TODO NOTIFICATIONS
  // const {
  //   data: notificationsData,
  //   isLoading,
  //   refetch,
  // } = useQuery({
  //   queryFn: () => fetchNotifications(),
  //   queryKey: ["notifications"],
  // });

  const handleViewProfile = () => {
    setShowEditInfoDialog(false);
    setViewProfileDialog(true);
  };

  const handleEditInfo = () => {
    setShowEditInfoDialog(true);
    setViewProfileDialog(false);
  };

  const formValues = watch();

  const { region_name, province_name, city_or_municipality_name } = formValues;

  const handleUpdateUser = (values: any) => {
    const formValues = getValues();

    const regionName =
      values.details.find((detail: any) => detail.label === "Region Name")
        ?.value_name || getLocationCode.region_name;
    const barangayName =
      values.details.find((detail: any) => detail.label === "Barangay Name")
        ?.value_name || getLocationCode.barangay_name;
    const citiesName =
      values.details.find(
        (detail: any) => detail.label === "City Or Municipality Name"
      )?.value_name || getLocationCode.city_or_municipality_name;
    const provinceName =
      values.details.find((detail: any) => detail.label === "Province Name")
        ?.value_name || getLocationCode.province_name;

    const payload = {
      user_id: funcData.user_id,
      first_name: formValues.first_name,
      middle_name: formValues.middle_name,
      last_name: formValues.last_name,
      contact_number: formValues.contact_number,
      contact_email: formValues.contact_email,
      address_1: formValues.address_1,
      address_2: formValues.address_2,
      phone_number: formValues.phone_number,
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

    dispatch(
      updateSettingsProfile({
        url: modalData.url,
        method: "POST",
        data: payload,
      })
    );
  };

  const handleUpdateEmail = () => {
    const formValues = getValues();

    const payload = {
      new_email: formValues.email,
      current_password: formValues.password,
      verification_number: formValues.verification_number,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      updateEmailProfile({
        url: "user-info/update-email",
        method: "POST",
        data: payload,
      })
    );
  };

  const handleUpdatePassword = () => {
    const formValues = getValues();

    const payload = {
      current_password: formValues.current_password,
      password: formValues.password,
      password_confirmation: formValues.confirm_password,
      verification_number: formValues.verification_number,
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      updatePasswordProfile({
        url: "user-info/update-password",
        method: "POST",
        data: payload,
      })
    );
  };

  const handleResendCodeEmail = () => {
    setCountdown((prev) => ({ ...prev, email: 32 }));

    const payload = {
      eu_device: Cookies.get("eu"),
    };
    dispatch(
      resendCodeEmail({
        url: "user-info/resend-code-email",
        method: "POST",
        data: payload,
      })
    );
  };

  const handleResendCodePassword = () => {
    setCountdown((prev) => ({ ...prev, password: 32 }));

    const payload = {
      eu_device: Cookies.get("eu"),
    };
    dispatch(
      resendCodeEmail({
        url: "user-info/resend-code-password",
        method: "POST",
        data: payload,
      })
    );
  };

  const onChangeSelect = (type: any, values: any) => {
    setGetLocationCode((prevState) => ({
      ...prevState,
      [`${type.replace(/\s+/g, "_").toLowerCase()}`]: values.name,
    }));
  };

  const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
      const imageUrl = URL.createObjectURL(file);
      const payload = {
        image: file,
        eu_device: Cookies.get("eu"),
      };
      setSelectedImage(imageUrl);
      dispatch(
        uploadImage({
          url: "user-info/update-image",
          method: "POST",
          data: payload,
        })
      );
    }
  };

  const handleDeleteImage = () => {
    const payload = {
      image: "",
      eu_device: Cookies.get("eu"),
    };

    dispatch(
      uploadImage({
        url: "user-info/update-image",
        method: "POST",
        data: payload,
      })
    );
  };

  const [hasUnreadNotifications, setHasUnreadNotifications] = useState(false);

  const postNotificationReadStatus = async (values: any) => {
    if (values.is_read === 1) {
      return;
    }

    const payload = {
      notification_id: values.notification_id,
      is_read: 1,
      eu_device: Cookies.get("eu"),
    };

    const response = await axiosClient.post(values.url_update, {
      ...payload,
    });
    return response.data;
  };

  const mutation = useMutation({
    mutationFn: postNotificationReadStatus,
    onSuccess: () => {
      refetch();
    },
    onError: (error) => {
      // Handle errors
      console.error("Error marking notification as read:", error);
    },
  });

  useEffect(() => {
    if (notificationsData?.pages.map((item: any) => item?.is_read === 0)) {
      setHasUnreadNotifications(true);
    } else {
      setHasUnreadNotifications(false);
    }
  }, [notificationsData]);

  const handleClickNotif = (values: any) => {
    mutation.mutate(values);
    setHasUnreadNotifications(false);
  };

  useEffect(() => {
    dispatch(
      settingsProfile({
        url: "user-info/get-personal-info",
        method: "GET",
      })
    );
  }, []);

  useEffect(() => {
    if (userStatus === "uploadImage/success") {
      toast({
        variant: "success",
        title: uploadImageData?.message,
      });
      dispatch(
        settingsProfile({
          url: "user-info/get-personal-info",
          method: "GET",
        })
      );
    }

    if (userStatus === "uploadImage/failed") {
      if (typeof uploadImageData?.message === "string") {
        toast({
          variant: "destructive",
          title: uploadImageData?.message,
        });
      }
    }

    if (userStatus === "updateSettingsProfile/success") {
      toast({
        variant: "success",
        title: updateSettingsProfileData?.message,
      });
      dispatch(
        settingsProfile({
          url: "user-info/get-personal-info",
          method: "GET",
        })
      );
    }

    if (userStatus === "updateEmailProfile/success") {
      toast({
        variant: "success",
        title: updateEmailProfileData?.message,
      });
      reset();
      dispatch(
        settingsProfile({
          url: "user-info/get-personal-info",
          method: "GET",
        })
      );
    }

    if (userStatus === "updateSettingsProfile/failed") {
      if (typeof updateSettingsProfileData?.message === "string") {
        toast({
          variant: "destructive",
          title: updateSettingsProfileData?.message,
        });
      }
    }

    if (userStatus === "updateEmailProfile/failed") {
      if (typeof updateEmailProfileData?.message === "string") {
        reset();
        toast({
          variant: "destructive",
          title: updateEmailProfileData?.message,
        });
      }
    }

    if (userStatus === "updatePasswordProfile/failed") {
      if (typeof updatePasswordProfileData?.message === "string") {
        toast({
          variant: "destructive",
          title: updatePasswordProfileData?.message,
        });
      }
    }
  }, [userStatus]);

  useEffect(() => {
    const actionProfile =
      settingsActions &&
      settingsActions.find(
        (button: any) => button.button_name === "Edit account"
      );
    if (actionProfile) {
      actionProfile.details.forEach((val: any) => {
        const fieldName = val.label.replace(/\s+/g, "_").toLowerCase();
        setValue(fieldName, val.value);
      });

      setModalData(actionProfile);
    }
  }, [settingsActions]);

  useEffect(() => {
    const savedCountdown = Cookies.get("countdowns");
    if (savedCountdown) {
      const remainingTimes = JSON.parse(savedCountdown);
      setCountdown(remainingTimes);
    }
  }, []);

  useEffect(() => {
    const timerEmail =
      countdown.email > 0
        ? setTimeout(
            () => setCountdown((prev) => ({ ...prev, email: prev.email - 1 })),
            1000
          )
        : null;
    const timerPassword =
      countdown.password > 0
        ? setTimeout(
            () =>
              setCountdown((prev) => ({
                ...prev,
                password: prev.password - 1,
              })),
            1000
          )
        : null;

    if (countdown.email > 0 || countdown.password > 0) {
      Cookies.set("countdowns", JSON.stringify(countdown), { expires: 1 });
    } else {
      Cookies.remove("countdowns");
    }

    return () => {
      clearTimeout(timerEmail);
      clearTimeout(timerPassword);
    };
  }, [countdown]);

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

  const ContentNotifications = notificationsData?.pages.map((notifDat: any) =>
    notifDat.map((item: any) => {
      return (
        <NotificationCard
          innerRef={ref}
          item={item}
          handleClickNotif={handleClickNotif}
        />
      );
    })
  );

  return (
    <>
      <div className="sticky top-0 z-40 border-b-[1px] py-1 w-full bg-white">
        <div className="p-1 px-7 flex items-center justify-between lg:px-3">
          <h1 className="font-bold text-lg ml-14 lg:ml-0">
            {!pageTitle ? (
              <span className="flex flex-col gap-1">
                <Skeleton className="h-3 w-20 bg-neutral-200" />
                <Skeleton className="h-3 w-16 bg-neutral-200" />
              </span>
            ) : (
              <>{pageTitle}</>
            )}
          </h1>
          <div className="flex items-center gap-6">
            {userLoading && <Skeleton className="h-6 w-6 rounded-full" />}
            {userLoading && <Skeleton className="h-6 w-6 rounded-full" />}
            {!userLoading && (
              <IconMessage className="text-muted-foreground" size="20" />
            )}
            {!userLoading && (
              <>
                <DropdownMenu>
                  <DropdownMenuTrigger className="relative">
                    {hasUnreadNotifications && (
                      <IconCircleFilled
                        size={11}
                        className="absolute right-0 text-red-500"
                      />
                    )}
                    <IconBell className="text-muted-foreground" size="20" />
                  </DropdownMenuTrigger>

                  <DropdownMenuContent className="w-80 mr-20">
                    <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <ScrollArea className="h-72">
                      {ContentNotifications}
                      {isFetchingNextPage && (
                        <>
                          <div className="flex items-center px-2 space-x-3">
                            <Skeleton className="h-10 w-10 rounded-full bg-neutral-300" />
                            <div className="space-y-2">
                              <Skeleton className="h-4 w-[217px] bg-neutral-300" />
                              <Skeleton className="h-4 w-[100px] bg-neutral-300" />
                            </div>
                          </div>
                        </>
                      )}
                    </ScrollArea>
                  </DropdownMenuContent>
                </DropdownMenu>
              </>
            )}
            <Dialog>
              <DropdownMenu>
                {userLoading && <Skeleton className="h-10 w-10 rounded-full" />}
                {!userLoading && (
                  <>
                    <DropdownMenuTrigger asChild>
                      <Avatar className="cursor-pointer">
                        <AvatarImage src={profileData?.image} />
                        <AvatarFallback className="bg-stone-900 text-white font-medium">
                          {headerUser?.name?.slice(0, 2).toUpperCase()}
                        </AvatarFallback>
                      </Avatar>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-64 mr-5">
                      <DropdownMenuLabel>My Account</DropdownMenuLabel>
                      <DropdownMenuLabel className="text-xs py-0 font-medium">
                        {headerUser?.email}
                      </DropdownMenuLabel>
                      <DropdownMenuLabel className="text-xs py-0 font-medium lowercase text-neutral-400">
                        {_.startCase(headerUser?.role)}
                      </DropdownMenuLabel>
                      <DropdownMenuSeparator />
                      <DropdownMenuGroup>
                        <DropdownMenuItem className="cursor-pointer">
                          <DialogTrigger
                            className="flex items-center w-full justify-between"
                            onClick={() => handleViewProfile()}
                          >
                            Profile
                          </DialogTrigger>
                        </DropdownMenuItem>
                      </DropdownMenuGroup>
                      <DropdownMenuGroup>
                        <DropdownMenuItem className="cursor-pointer">
                          <DialogTrigger
                            className="flex items-center w-full justify-between"
                            onClick={() => handleEditInfo()}
                          >
                            Settings
                          </DialogTrigger>
                        </DropdownMenuItem>
                      </DropdownMenuGroup>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        onClick={handleClickLogout}
                        className="cursor-pointer"
                      >
                        Log out
                        <DropdownMenuShortcut>
                          <IconLogout size={16} stroke={2} />
                        </DropdownMenuShortcut>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </>
                )}
              </DropdownMenu>
              <DialogPortal>
                {showEditInfoDialog && (
                  <DialogContent className="sm:max-w-[34rem]">
                    <DialogHeader>
                      <DialogTitle>Profile settings</DialogTitle>
                    </DialogHeader>
                    <ScrollArea className="h-72 w-full">
                      <div className="mx-4">
                        <div className="flex items-center gap-4">
                          <div className="relative">
                            <Avatar className="h-20 w-20 border-white border-2 relative">
                              <AvatarImage
                                src={selectedImage || profileData?.image}
                                alt="@shadcn"
                              />
                              <AvatarFallback className="font-bold text-2xl">
                                {headerUser?.name?.slice(0, 2).toUpperCase()}
                              </AvatarFallback>
                            </Avatar>
                          </div>
                          <div>
                            <h1 className="text-md font-semibold">
                              {profileData?.first_name} {""}{" "}
                              {profileData?.middle_name} {""}{" "}
                              {profileData?.last_name}
                            </h1>
                            <h1 className="text-sm text-neutral-500">
                              {profileData?.role}
                            </h1>
                          </div>
                          <div className="ml-3 flex gap-3">
                            <Button className="relative" size="xs">
                              <Input
                                className="absolute bottom-0 h-full opacity-0"
                                type="file"
                                onChange={handleImageChange}
                              />
                              Change picture
                            </Button>
                            <Button
                              variant="secondary"
                              className="relative"
                              size="xs"
                              onClick={handleDeleteImage}
                            >
                              Delete picture
                            </Button>
                          </div>
                        </div>
                        <Tabs defaultValue="profile" className="w-full pt-7">
                          <TabsList className="w-full">
                            <TabsTrigger
                              className="w-full data-[state=active]:font-bold"
                              value="profile"
                              onClick={() => setSelectedTab("profile")}
                            >
                              Profile Information
                            </TabsTrigger>
                            <TabsTrigger
                              className="w-full data-[state=active]:font-bold"
                              value="email"
                              onClick={() => setSelectedTab("email")}
                            >
                              Change Email
                            </TabsTrigger>
                            <TabsTrigger
                              className="w-full data-[state=active]:font-bold"
                              value="password"
                              onClick={() => setSelectedTab("password")}
                            >
                              Change Password
                            </TabsTrigger>
                          </TabsList>
                          <TabsContent value="profile">
                            <div className="py-10 flex flex-col gap-6">
                              {modalData?.details?.map((detail: any) => (
                                <>
                                  {detail.type === "input" && (
                                    <div className="grid w-full items-center gap-1.5">
                                      <Label>{detail.label}</Label>
                                      <Input
                                        type={detail.type}
                                        {...register(
                                          detail.label
                                            .replace(/\s+/g, "_")
                                            .toLowerCase()
                                        )}
                                      />
                                      <small className="text-red-500 w-full">
                                        {updateSettingsProfileData?.message &&
                                          updateSettingsProfileData?.message[
                                            _.replace(
                                              _.lowerCase(detail.label),
                                              " ",
                                              "_"
                                            )
                                          ]}
                                      </small>
                                    </div>
                                  )}

                                  {detail.type === "email" && (
                                    <div className="grid w-full items-center gap-1.5">
                                      <Label>{detail.label}</Label>
                                      <Input
                                        type={detail.type}
                                        {...register(
                                          detail.label
                                            .replace(/\s+/g, "_")
                                            .toLowerCase()
                                        )}
                                      />
                                      <small className="text-red-500 w-full">
                                        {updateSettingsProfileData?.message &&
                                          updateSettingsProfileData?.message[
                                            _.replace(
                                              _.lowerCase(detail.label),
                                              " ",
                                              "_"
                                            )
                                          ]}
                                      </small>
                                    </div>
                                  )}

                                  {detail.type === "number" && (
                                    <div className="grid w-full items-center gap-1.5">
                                      <Label>{detail.label}</Label>
                                      <Input
                                        type={detail.type}
                                        {...register(
                                          detail.label
                                            .replace(/\s+/g, "_")
                                            .toLowerCase()
                                        )}
                                      />
                                      <small className="text-red-500 w-full">
                                        {updateSettingsProfileData?.message &&
                                          updateSettingsProfileData?.message[
                                            _.replace(
                                              _.lowerCase(detail.label),
                                              " ",
                                              "_"
                                            )
                                          ]}
                                      </small>
                                    </div>
                                  )}

                                  {detail.type === "select" &&
                                    detail.label === "Region Name" && (
                                      <div className="grid w-full items-center gap-1.5">
                                        <Label className="text-neutral-600">
                                          {detail.label}
                                        </Label>
                                        <Select
                                          onValueChange={(value) => {
                                            const selected =
                                              locationsData.regions.find(
                                                (region: any) =>
                                                  region.code === value
                                              );
                                            setValue(
                                              detail.label
                                                .replace(/\s+/g, "_")
                                                .toLowerCase(),
                                              value
                                            );
                                            onChangeSelect(
                                              detail.label,
                                              selected
                                            );
                                          }}
                                        >
                                          <SelectTrigger>
                                            <SelectValue
                                              placeholder={
                                                detail.value_name ||
                                                "Select a region"
                                              }
                                            />
                                          </SelectTrigger>
                                          <SelectContent>
                                            <SelectGroup>
                                              <SelectLabel>Region</SelectLabel>
                                              {Array.isArray(
                                                locationsData.regions
                                              ) &&
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
                                          {updateSettingsProfileData &&
                                            updateSettingsProfileData
                                              ?.message?.["region_name"]}
                                        </small>
                                      </div>
                                    )}

                                  {detail.type === "select" &&
                                    detail.label === "Province Name" && (
                                      <div className="grid w-full items-center gap-1.5">
                                        <Label className="text-neutral-600">
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
                                            onChangeSelect(
                                              detail.label,
                                              selected
                                            );
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
                                              <SelectLabel>
                                                Province
                                              </SelectLabel>
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
                                          {updateSettingsProfileData &&
                                            updateSettingsProfileData
                                              ?.message?.["province_name"]}
                                        </small>
                                      </div>
                                    )}

                                  {detail.type === "select" &&
                                    detail.label ===
                                      "City Or Municipality Name" && (
                                      <div className="grid w-full items-center gap-1.5">
                                        <Label className="text-neutral-600">
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
                                            onChangeSelect(
                                              detail.label,
                                              selected
                                            );
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
                                              {Array.isArray(
                                                locationsData.cities
                                              ) &&
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
                                          {updateSettingsProfileData &&
                                            updateSettingsProfileData
                                              ?.message?.[
                                              "city_or_municipality_name"
                                            ]}
                                        </small>
                                      </div>
                                    )}

                                  {detail.type === "select" &&
                                    detail.label === "Barangay Name" && (
                                      <div className="grid w-full items-center gap-1.5">
                                        <Label className="text-neutral-600">
                                          {detail.label}
                                        </Label>
                                        <Select
                                          onValueChange={(value) => {
                                            const selected =
                                              locationsData.barangays.find(
                                                (brgy: any) =>
                                                  brgy.code === value
                                              );
                                            setValue(
                                              detail.label
                                                .replace(/\s+/g, "_")
                                                .toLowerCase(),
                                              value
                                            );
                                            onChangeSelect(
                                              detail.label,
                                              selected
                                            );
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
                                              <SelectLabel>
                                                Barangay
                                              </SelectLabel>
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
                                          {updateSettingsProfileData &&
                                            updateSettingsProfileData
                                              ?.message?.[
                                              "city_or_municipality_name"
                                            ]}
                                        </small>
                                      </div>
                                    )}
                                </>
                              ))}
                            </div>
                          </TabsContent>
                          <TabsContent value="email">
                            <div className="py-10 flex flex-col gap-6">
                              <div className="grid w-full items-center gap-1.5">
                                <Label>New email</Label>
                                <Input
                                  type="email"
                                  placeholder="Your new email"
                                  {...register("email")}
                                />
                                <small className="text-red-500 w-full">
                                  {updateEmailProfileData?.message &&
                                    updateEmailProfileData?.message[
                                      "new_email"
                                    ]}
                                </small>
                              </div>
                              <div className="grid w-full items-center gap-1.5">
                                <Label>Password</Label>
                                <Input
                                  type="password"
                                  placeholder="Enter your new password"
                                  {...register("password")}
                                />
                                <small className="text-red-500 w-full">
                                  {updateEmailProfileData?.message &&
                                    updateEmailProfileData?.message[
                                      "current_password"
                                    ]}
                                </small>
                              </div>
                              <div className="grid w-full items-center gap-1.5">
                                <Label>Verification number</Label>
                                <Input
                                  type="number"
                                  placeholder="Enter verification number"
                                  {...register("verification_number")}
                                />
                                <small className="text-red-500 w-full">
                                  {updateEmailProfileData?.message &&
                                    updateEmailProfileData?.message[
                                      "verification_number"
                                    ]}
                                </small>
                              </div>
                              <div className="grid w-20 items-center gap-1.5">
                                <Button
                                  onClick={handleResendCodeEmail}
                                  disabled={countdown.email > 0}
                                >
                                  {countdown.email > 0
                                    ? `Resend code in ${countdown.email}s`
                                    : "Resend code"}
                                </Button>
                              </div>
                            </div>
                          </TabsContent>
                          <TabsContent value="password">
                            <div className="py-10 flex flex-col gap-6">
                              <div className="grid w-full items-center gap-1.5">
                                <Label>Current password</Label>
                                <Input
                                  type="password"
                                  placeholder="Your current password"
                                  {...register("current_password")}
                                />
                                <small className="text-red-500 w-full">
                                  {updatePasswordProfileData?.message &&
                                    updatePasswordProfileData?.message[
                                      "current_password"
                                    ]}
                                </small>
                              </div>
                              <div className="grid w-full items-center gap-1.5">
                                <Label>New password</Label>
                                <Input
                                  type="password"
                                  placeholder="Enter your new password"
                                  {...register("password")}
                                />
                                <small className="text-red-500 w-full">
                                  {updatePasswordProfileData?.message &&
                                    updatePasswordProfileData?.message[
                                      "password"
                                    ]}
                                </small>
                              </div>
                              <div className="grid w-full items-center gap-1.5">
                                <Label>Confirm password</Label>
                                <Input
                                  type="password"
                                  placeholder="Enter your confirm password"
                                  {...register("confirm_password")}
                                />
                                <small className="text-red-500 w-full">
                                  {updatePasswordProfileData?.message &&
                                    updatePasswordProfileData?.message[
                                      "password_confirmation"
                                    ]}
                                </small>
                              </div>
                              <div className="grid w-full items-center gap-1.5">
                                <Label>Verification number</Label>
                                <Input
                                  type="number"
                                  placeholder="Enter your confirm password"
                                  {...register("verification_number")}
                                />
                                <small className="text-red-500 w-full">
                                  {updatePasswordProfileData?.message &&
                                    updatePasswordProfileData?.message[
                                      "verification_number"
                                    ]}
                                </small>
                              </div>
                              <div className="grid w-20 items-center gap-1.5">
                                <Button
                                  onClick={handleResendCodePassword}
                                  disabled={countdown.password > 0}
                                >
                                  {countdown.password > 0
                                    ? `Resend code in ${countdown.password}s`
                                    : "Resend code"}
                                </Button>
                              </div>
                            </div>
                          </TabsContent>
                        </Tabs>
                      </div>
                    </ScrollArea>
                    <DialogFooter>
                      {selectedTab === "profile" && (
                        <Button
                          className="bg-bgrjavancena"
                          type="submit"
                          disabled={!isDirty || loadingUpdateProfile}
                          onClick={() => handleUpdateUser(modalData)}
                        >
                          {loadingUpdateProfile && "Saving changes..."}
                          {!loadingUpdateProfile && "Save changes"}
                        </Button>
                      )}
                      {selectedTab === "email" && (
                        <Button
                          className="bg-bgrjavancena"
                          type="submit"
                          disabled={!isDirty || loadingUpdateEmail}
                          onClick={handleUpdateEmail}
                        >
                          {loadingUpdateEmail && "Saving changes..."}
                          {!loadingUpdateEmail && "Save changes"}
                        </Button>
                      )}
                      {selectedTab === "password" && (
                        <Button
                          className="bg-bgrjavancena"
                          type="submit"
                          disabled={!isDirty || loadingUpdatePassword}
                          onClick={handleUpdatePassword}
                        >
                          {loadingUpdatePassword && "Saving changes..."}
                          {!loadingUpdatePassword && "Save changes"}
                        </Button>
                      )}
                      <DialogClose asChild>
                        <Button type="button" variant="secondary">
                          Close
                        </Button>
                      </DialogClose>
                    </DialogFooter>
                  </DialogContent>
                )}
                {showViewProfileDialog && (
                  <DialogContent className="sm:max-w-[34rem]">
                    <DialogHeader>
                      <DialogTitle>Profile Information</DialogTitle>
                    </DialogHeader>
                    <ScrollArea className="h-72 w-full">
                      <div className="mx-4">
                        <div className="flex items-center gap-4">
                          <div className="relative">
                            <Avatar className="h-20 w-20 border-[1px] border-neutral-500 shadow-md relative">
                              <AvatarImage
                                src={profileData?.image}
                                alt="@shadcn"
                              />
                              <AvatarFallback className="font-bold text-2xl">
                                {headerUser?.name?.slice(0, 2).toUpperCase()}
                              </AvatarFallback>
                            </Avatar>
                          </div>
                          <div>
                            <h1 className="text-md font-semibold">
                              {profileData?.first_name} {""}{" "}
                              {profileData?.middle_name} {""}{" "}
                              {profileData?.last_name}
                            </h1>
                            <h1 className="text-sm text-neutral-500">
                              {profileData?.role}
                            </h1>
                          </div>
                        </div>
                        <div>
                          <div className="py-10 flex flex-col gap-6">
                            <div className="grid grid-cols-2 gap-6">
                              <div className="grid w-full items-center gap-2">
                                <Label>Full name</Label>
                                <Label className="text-neutral-400 text-sm">
                                  {profileData?.first_name} {""}{" "}
                                  {profileData?.middle_name} {""}{" "}
                                  {profileData?.last_name}
                                </Label>
                              </div>
                              <div className="grid w-full items-center gap-2">
                                <Label>Email</Label>
                                <Label className="text-neutral-400 text-sm">
                                  {profileData?.contact_email}
                                </Label>
                              </div>
                              <div className="grid w-full items-center gap-2">
                                <Label>Contact number</Label>
                                <Label className="text-neutral-400 text-sm">
                                  {profileData?.contact_number}
                                </Label>
                              </div>
                            </div>
                            <div className="flex flex-col gap-6">
                              <h1 className="font-semibold">
                                Address Information
                              </h1>
                              <div className="grid grid-cols-2 gap-6">
                                <div className="grid w-full items-center gap-2">
                                  <Label>Address 1</Label>
                                  <Label className="text-neutral-400 text-sm">
                                    {profileData?.address_1}
                                  </Label>
                                </div>
                                <div className="grid w-full items-center gap-2">
                                  <Label>Address 2</Label>
                                  <Label className="text-neutral-400 text-sm">
                                    {profileData?.address_2}
                                  </Label>
                                </div>
                                <div className="grid w-full items-center gap-2">
                                  <Label>Region</Label>
                                  <Label className="text-neutral-400 text-sm">
                                    {profileData?.region_name}
                                  </Label>
                                </div>
                                <div className="grid w-full items-center gap-2">
                                  <Label>Province</Label>
                                  <Label className="text-neutral-400 text-sm">
                                    {profileData?.province_name}
                                  </Label>
                                </div>
                                <div className="grid w-full items-center gap-2">
                                  <Label>City/Municipalities</Label>
                                  <Label className="text-neutral-400 text-sm">
                                    {profileData?.city_or_municipality_name}
                                  </Label>
                                </div>
                                <div className="grid w-full items-center gap-2">
                                  <Label>Barangay</Label>
                                  <Label className="text-neutral-400 text-sm">
                                    {profileData?.barangay_name}
                                  </Label>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </ScrollArea>
                    <DialogFooter>
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
          </div>
        </div>
      </div>
    </>
  );
};

export default Header;
