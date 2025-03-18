// useRefreshToken.ts
import { useAuth } from "@/app/AuthProvider";
import useAxiosClient from "@/axios-client";
import Cookies from "js-cookie";

const useRefreshToken = () => {
  const { authentication, setAuthentication } = useAuth();
  const axiosClient = useAxiosClient();

  const refresh = async () => {
    try {
      const token = authentication.accessToken || Cookies.get("token");
      const res = await axiosClient.get("/check-token", {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const newAccessToken = res.data.token;

      // Update state and cookies
      setAuthentication((prev) => ({
        ...prev,
        accessToken: newAccessToken,
      }));
      Cookies.set("token", newAccessToken);

      return newAccessToken; // Return the new token
    } catch (error) {
      console.error("Failed to refresh token", error);
      throw error;
    }
  };

  return refresh;
};

export default useRefreshToken;
