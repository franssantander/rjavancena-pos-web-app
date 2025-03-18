import { Navigate, useNavigate } from "react-router-dom";
import { useForm } from "react-hook-form";
import { useAuth } from "../../../../app/AuthProvider";
import Cookies from "js-cookie";
import axios from "axios";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import {
  InputOTP,
  InputOTPGroup,
  InputOTPSlot,
} from "@/components/ui/input-otp";
import FingerprintJS from "@fingerprintjs/fingerprintjs";

interface FormValues {
  verification_number: String;
}

const VerifyEmail: React.FC = () => {
  const navigate = useNavigate();
  const { setToken } = useAuth();
  const { token } = useAuth();
  const [countDownTime, setCountDownTime] = useState(30);
  const [resendAttempts, setResendAttempts] = useState(0);
  const [fingerprint, setFingerprint] = useState<string | null>(null);

  if (!token) {
    return <Navigate to="/" />;
  }
  const generateFingerprint = async (): Promise<string> => {
    const fpPromise = FingerprintJS.load();
    const fpInstance = await fpPromise;
    const result = await fpInstance.get();

    return result.visitorId;
  };

  const {
    formState: { errors },
    setError,
  } = useForm<FormValues>();

  useForm({
    defaultValues: {
      verification_number: "",
    },
  });

  const [value, setValue] = useState("");

  // const onSubmit: SubmitHandler<FormValues> = async (data) => {
  //   try {
  //     const currentToken: any = Cookies.get("token");
  //     const euDevice = Cookies.get("eu");

  //     const payload = {
  //       verification_number: value,
  //       eu_device: euDevice,
  //     };

  //     const res = await axios.post(
  //       import.meta.env.VITE_BASE_URL + "signup/verify-email",
  //       payload,
  //       {
  //         headers: {
  //           Authorization: `Bearer ${currentToken}`,
  //         },
  //       }
  //     );
  //     setToken(currentToken);

  //     if (res.status === 200) {
  //       navigate("/");
  //     }
  //     console.log(data);

  //     console.log(res);
  //   } catch (error: any) {
  //     console.log(error);
  //     let verificationNumberError = error.response.data.message.email || "";

  //     setError("verification_number", {
  //       type: "custom",
  //       message: verificationNumberError,
  //     });
  //   }
  // };

  const handleVerifySubmit = async () => {
    try {
      const currentToken: any = Cookies.get("token");
      const euDevice = Cookies.get("eu");

      const payload = {
        verification_number: value,
        eu_device: euDevice,
      };

      const res = await axios.post(
        import.meta.env.VITE_BASE_URL + "signup/verify-email",
        payload,
        {
          headers: {
            Authorization: `Bearer ${currentToken}`,
          },
        }
      );
      setToken(currentToken);

      if (res.status === 200) {
        navigate("/");
      }
    } catch (error: any) {
      let verificationNumberError = error.response.data.message || "";

      setError("verification_number", {
        type: "custom",
        message: verificationNumberError,
      });
    }
  };

  const handleResendCode = async () => {
    try {
      const currentToken = Cookies.get("token");

      const euDevice = Cookies.get("eu");

      const payload = {
        eu_device: euDevice,
        fingerprint: fingerprint,
      };

      const res = await axios.post(
        import.meta.env.VITE_BASE_URL + "signup/resend-code",
        payload,
        {
          headers: {
            Authorization: `Bearer ${currentToken}`,
          },
        }
      );
      setResendAttempts((prevAttempts) => prevAttempts + 1);

      setCountDownTime(resendAttempts === 0 ? 30 : 60);
      console.log(res);
    } catch (error: any) {
      console.log(error);

      let verificationNumberError = error.response.data.message.email || "";

      setError("verification_number", {
        type: "custom",
        message: verificationNumberError,
      });
    }
  };

  useEffect(() => {
    const intervalId = setInterval(() => {
      setCountDownTime((prevTime) => (prevTime > 0 ? prevTime - 1 : 0));
    }, 1000);

    return () => clearInterval(intervalId);
  }, [countDownTime]);

  useEffect(() => {
    const fetchFingerprint = async () => {
      const fp = await generateFingerprint();
      setFingerprint(fp);
    };

    fetchFingerprint();
  }, []);

  return (
    <>
      <div className="w-full h-screen p-4 py-44 md:block items-center max-w-[75em] m-auto">
        <div className="m-auto lg:px-8 flex flex-col gap-4 max-w-96 rounded-md">
          <div className="flex flex-col items-center m-auto gap-3 mb-3">
            <h1 className="text-2xl font-bold">Verify Your Email</h1>
            <p className="text-xs text-center text-neutral-500">
              A verification code has been sent to your email address. Please
              enter the code to complete the verification process.
            </p>
          </div>
          <div className="flex flex-col w-full items-center gap-1.5 max-w-sm m-auto">
            <InputOTP
              maxLength={6}
              value={value}
              onChange={(value) => setValue(value)}
            >
              <InputOTPGroup>
                <InputOTPSlot index={0} />
                <InputOTPSlot index={1} />
                <InputOTPSlot index={2} />
                <InputOTPSlot index={3} />
                <InputOTPSlot index={4} />
                <InputOTPSlot index={5} />
              </InputOTPGroup>
            </InputOTP>
            {errors.verification_number && (
              <small className="text-red-500 text-xs  max-w-96">
                {errors.verification_number.message}
              </small>
            )}
          </div>
          <Button
            onClick={() => handleVerifySubmit()}
            className="font-medium mt-6 w-full max-w-sm m-auto"
          >
            Verify Email
          </Button>
        </div>
        <div className="m-auto lg:px-8 flex mt-3 flex-col gap-4 max-w-sm rounded-md">
          <Button
            className="font-medium"
            variant="ghost"
            onClick={() => handleResendCode()}
            disabled={countDownTime > 0}
          >
            {countDownTime > 0
              ? `Resend Code (${countDownTime}s)`
              : "Resend Code"}
          </Button>
          <p className="text-xs text-center">
            If you haven't received the code, you can request a new one by
            clicking <span className="font-bold">"Resend Code."</span>
          </p>
        </div>
      </div>
    </>
  );
};

export default VerifyEmail;
