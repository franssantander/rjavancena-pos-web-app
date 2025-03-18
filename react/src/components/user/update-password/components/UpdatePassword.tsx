import axios from "axios";
import { SubmitHandler, useForm } from "react-hook-form";
import { Navigate, useNavigate, useParams } from "react-router-dom";
import { useAuth } from "../../../../app/AuthProvider";
import Cookies from "js-cookie";

interface newPasswordForm {
  password: string;
  password_confirmation: string;
}

const UpdatePassword: React.FC = () => {
  const form = useForm();
  const navigate = useNavigate();
  const { token } = useAuth();

  if (!token) {
    return <Navigate to="/" />;
  }

  const { register, handleSubmit, formState } = form;

  const onSubmit: SubmitHandler<newPasswordForm> = async (data) => {
    const updatePassEndpoint =
      import.meta.env.VITE_BASE_URL + "new-password/update-password";
    const currentToken = Cookies.get("token");

    try {
      console.log("currentToken", currentToken);
      const res = await axios.post(updatePassEndpoint, data, {
        headers: {
          Authorization: `Bearer ${currentToken}`,
        },
      });


      if (res.status === 200) {
        navigate("/");
      }

      console.log(res);
    } catch (error) {
      console.log(error);
    }
  };

  return (
    <>
      <div className="w-full h-screen p-4 py-44 md:flex items-center max-w-[1200px] m-auto">
        <form
          onSubmit={handleSubmit(onSubmit)}
          className="m-auto lg:p-8 flex flex-col gap-4 lg:w-2/6 rounded-md"
        >
          <div className="flex flex-col items-center m-auto gap-3 mb-10">
            <h1 className="text-2xl font-bold">Forgot Your Password?</h1>
            <p className="text-sm text-center">
              No worries, it happens to the best of us. Just follow the steps
              below to reset your password.
            </p>
          </div>
          <label className="form-control w-full">
            <input
              type="password"
              placeholder="Current Password"
              className="input input-bordered w-full"
              {...register("password", {
                required: {
                  value: true,
                  message: "Current Password is required",
                },
              })}
            />
            <input
              type="password"
              placeholder="Current Password"
              className="input input-bordered w-full"
              {...register("password_confirmation", {
                required: {
                  value: true,
                  message: "Password Confirmation is required",
                },
              })}
            />
          </label>

          <button className="btn btn-md bg-btnprimary w-full text-white mt-10">
            Reset Password
          </button>
        </form>
      </div>
    </>
  );
};

export default UpdatePassword;
