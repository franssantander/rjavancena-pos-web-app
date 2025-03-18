import { Link, useNavigate } from "react-router-dom";
import { SubmitHandler, useForm } from "react-hook-form";
import { useAuth } from "../../../../app/AuthProvider";
import Cookies from "js-cookie";
import axios from "axios";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

interface FormValues {
  email: string;
  password: string;
  password_confirmation: string;
}

const SignUp: React.FC = () => {
  const navigate = useNavigate();
  const { token, setToken } = useAuth();

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
  } = useForm<FormValues>();

  useForm({
    defaultValues: {
      email: "",
      password: "",
      password_confirmation: "",
    },
  });

  const onSubmit: SubmitHandler<FormValues> = async (data) => {
    const euDevice = Cookies.get("eu");
    try {
      const res = await axios.post(import.meta.env.VITE_BASE_URL + "register", {
        ...data,
        eu_device: euDevice,
      });

      if (res.status === 200 && res.data.url_token) {
        Cookies.set("token", res.data.token);
        setToken(res.data.token);
        console.log(res.data.url_token);

        if (token) {
          navigate(`/signup/verify-email/?${res.data.url_token}`);
          console.log(res.data.url_token);
        } else {
          console.error("Invalid token format");
        }
      } else {
        console.error("Invalid response format");
      }

      console.log("Form submit", data);
    } catch (error: any) {
      let emailError = error.response.data.message.email || "";
      let passwordError = error.response.data.message.password || "";

      setError("email", { type: "custom", message: emailError });
      setError("password", { type: "custom", message: passwordError });
    }
  };

  return (
    <>
      <div className="w-full h-screen p-4 md:flex md:flex-col items-center max-w-[75em] m-auto">
        <form
          onSubmit={handleSubmit(onSubmit)}
          className="m-auto pt-20 lg:p-7 flex flex-col gap-3 lg:gap-8 rounded-md lg:border-[1px] lg:shadow-sm"
        >
          <div className="flex flex-col items-center m-auto gap-2">
            <h1 className="text-2xl font-bold">Create Your Account</h1>
            <p className="text-xs max-w-96 m-auto text-center text-neutral-500">
              Get started with RJ Avancena Enterprises and enjoy a seamless
              shopping experience.
            </p>
          </div>
          <div className="flex flex-col gap-4">
            <div className="grid w-full items-center gap-1.5">
              <Label>Email</Label>
              <Input
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
              <Label>Password</Label>
              <Input
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
            </div>
            <div className="grid w-full items-center gap-1.5">
              <Label>Confirm Password</Label>
              <Input
                type="password"
                placeholder="Enter your confirm password"
                {...register("password_confirmation", {
                  required: {
                    value: true,
                    message: "Confirm Password not matche",
                  },
                })}
              />
            </div>
            <div className="flex flex-col w-full mt-5 gap-4">
              <Button className="font-bold">Sign Up</Button>
              <div className="text-sm text-center m-auto">
                Already have an account?{" "}
                <span className="font-bold">
                  <Link to="/signin">Sign In</Link>
                </span>
              </div>
            </div>
          </div>
        </form>
      </div>
    </>
  );
};

export default SignUp;
