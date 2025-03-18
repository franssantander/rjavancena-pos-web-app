import axiosClient from "@/axios-client";
import useRefreshToken from "./useRefreshToken";
import { useAuth } from "@/app/AuthProvider";
import { useEffect } from "react";
import useAxiosClient from "@/axios-client";

const useAxiosPrivate = () => {
  const refresh = useRefreshToken();
  const { token } = useAuth();

  const axiosClient = useAxiosClient();

  useEffect(() => {
    const requestIntercept = axiosClient.interceptors.request.use(
      (config) => {
        if (!config.headers["Authorization"]) {
          config.headers["Authorization"] = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    const responseIntercept = axiosClient.interceptors.response.use(
      (response) => response,
      async (error) => {
        const prevRequest = error?.config;
        if (error) {
          prevRequest.sent = true;
          const newAccessToken = await refresh();
          prevRequest.headers["Authorization"] = `Bearer ${newAccessToken}`;
          return axiosClient(prevRequest);
        }
        return Promise.reject(error);
      }
    );

    return () => {
      axiosClient.interceptors.request.eject(requestIntercept);
      axiosClient.interceptors.response.eject(responseIntercept);
    };
  }, [token, refresh]);
};

export default useAxiosPrivate;
