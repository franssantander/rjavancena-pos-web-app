import { Link, useNavigate } from "react-router-dom";
import Cookies from "js-cookie";
import { Input } from "@/components/ui/input";
import { SubmitHandler, useForm } from "react-hook-form";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import bgSignin from "@/assets/images/bgsignin.jpg";
import { useEffect, useState } from "react";
import { setEudevice } from "@/app/slice/userSlice";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { Skeleton } from "@/components/ui/skeleton";
import { IconReload } from "@tabler/icons-react";
import useAxiosClient from "@/axios-client";
import { useAuth } from "@/app/AuthProvider";

interface FormValues {
  email: String;
  password: String;
}

const SignIn: React.FC = () => {
  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
  } = useForm<FormValues>();

  const dispatch = useAppDispatch();
  const loadingEudevice = useAppSelector((state) => state.user.loadingEudevice);
  const [loading, setLoading] = useState(false);
  const { setToken } = useAuth();

  const axiosClient = useAxiosClient();

  const fetchEudevice = async () => {
    try {
      await dispatch(
        setEudevice({
          url: "eu-device",
          method: "GET",
        })
      );
    } catch (error) {
      console.log(error);
    }
  };

  useEffect(() => {
    const checkEu = Cookies.get("eu");
    if (!checkEu) {
      fetchEudevice();
    }
  }, []);

  const navigate = useNavigate();

  const onSubmit: SubmitHandler<FormValues> = async (data) => {
    try {
      setLoading(true);
      const euDevice = Cookies.get("eu");
      if (!euDevice) {
        await fetchEudevice();
      }
      const payload = {
        ...data,
        eu_device: Cookies.get("eu"),
      };

      const res = await axiosClient.post("/login", payload);
      // Cookies.set("authUser", res.data.token);

      const authUser = { token: res.data.token };
      Cookies.set("authUser", JSON.stringify(authUser));
      setToken(authUser.token);

      const newUser = res.data.user_info;

      if (newUser === "New User") {
        navigate("/welcome");
      } else if (authUser.token && newUser === "Existing User") {
        navigate("/app/menu");
      }
    } catch (error: any) {
      setLoading(false);
      let errorMessage = error?.response?.data?.message;

      console.log(error.response.data.message);

      setError("errorMessage", {
        type: "custom",
        message: error.response.data.message,
      });

      setError("email", {
        type: "custom",
        message: errorMessage?.email,
      });
      setError("password", {
        type: "custom",
        message: errorMessage?.password,
      });
    }
  };

  return (
    <>
      {loadingEudevice && (
        <div className="w-full h-screen flex p-4 md:flex md:flex-col items-center m-auto">
          <div className="m-auto md:w-96 lg:p-7 flex flex-col gap-3 lg:gap-8">
            <div className="flex flex-col items-center m-auto gap-2">
              <Skeleton className="m-auto h-6 w-32 flex flex-col gap-3 lg:gap-8 rounded-md" />
            </div>
            <div className="flex flex-col gap-5">
              <div className="grid w-full items-center gap-1.5">
                <Skeleton className="m-auto h-10 w-96 md:w-full flex flex-col gap-3 lg:gap-8 rounded-md" />
              </div>
              <div className="grid w-full items-center gap-1.5">
                <Skeleton className="m-auto h-10 w-full flex flex-col gap-3 lg:gap-8 rounded-md" />
              </div>
              <div className="flex flex-col gap-4 mt-2 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-x-1"></div>
                <div></div>
              </div>
              <div className="flex flex-col w-full mt-5 gap-4">
                <Skeleton className="m-auto h-10 md:h-6 w-full lg:p-7 flex flex-col gap-3 lg:gap-8" />
                <div className="text-sm text-center m-auto"></div>
              </div>
            </div>
          </div>
        </div>
      )}
      {!loadingEudevice && (
        <div className="w-full h-screen flex md:grid md:grid-cols-2 p-4 md:p-0 items-center m-auto">
          <div
            className="hidden h-full w-full bg-no-repeat bg-cover md:flex items-center justify-center"
            style={{ backgroundImage: `url(${bgSignin})` }}
          >
            <div className="flex flex-col gap-4">
              <h1 className="text-white font-bold text-3xl text-center w-[34rem]">
                Everything you need, to make anything you want.
              </h1>
              <p className="w-[20rem] text-white/70 text-center text-xs mx-auto">
                Effortlessly manage your sales and inventory with our seamless
                Point of Sale system.
              </p>
            </div>
          </div>
          <form
            onSubmit={handleSubmit(onSubmit)}
            className="mx-auto  lg:p-7 w-96 flex flex-col gap-3 lg:gap-8 rounded-md lg:shadow-sm"
          >
            <div className="flex flex-col items-center m-auto gap-2">
              <h1 className="text-xl font-black w-full">
                Welcome to{" "}
                <span className="text-bgrjavancena">RJ AVANCENA</span>
              </h1>
            </div>
            <div className="flex flex-col gap-5">
              <div className="grid w-full items-center gap-1.5">
                <Input
                  className="h-12 bg-none"
                  type="email"
                  placeholder="Enter your email address"
                  {...register("email", {
                    required: {
                      value: true,
                      message: "Email is required",
                    },
                  })}
                />
                {errors.email && (
                  <small className="text-red-500 text-xs  max-w-96">
                    {errors.email.message}
                  </small>
                )}
              </div>
              <div className="grid w-full items-center gap-1.5">
                <Input
                  className="h-12 bg-none"
                  type="password"
                  placeholder="Enter your password"
                  {...register("password", {
                    required: {
                      value: true,
                      message: "Password is required",
                    },
                  })}
                />
                {errors.password && (
                  <small className="text-red-500 text-xs  max-w-96">
                    {errors.password.message}
                  </small>
                )}
                {errors.errorMessage && (
                  <small className="text-red-500 text-xs  max-w-96">
                    {errors.errorMessage.message}
                  </small>
                )}
              </div>
              <div className="flex flex-col gap-4 mt-2 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-x-1">
                  <Checkbox className="checkbox" />
                  <label className="text-sm">Remember me</label>
                </div>
                <div>
                  <label className="text-sm sm:text-md">
                    <Link to="/forgot-password">Forgot Password?</Link>
                  </label>
                </div>
              </div>
              <div className="flex flex-col w-full mt-5 gap-4">
                <Button
                  className="font-bold bg-bgrjavancena disabled:opacity-100"
                  disabled={loading}
                >
                  {loading && (
                    <span className="flex items-center gap-2">
                      Signing in...
                      <IconReload className="animate-spin" size={16} />
                    </span>
                  )}
                  {!loading && "Sign in"}
                </Button>
              </div>
            </div>
          </form>
        </div>
      )}
    </>
  );
};

export default SignIn;
