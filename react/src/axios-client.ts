import axios from "axios";
import Cookies from "js-cookie";


const useAxiosClient = () => {


  const axiosClient = axios.create({
    baseURL: import.meta.env.VITE_BASE_URL,
  });

  axiosClient.interceptors.request.use(
    (config) => {
      const authUser = Cookies.get("authUser");
      if (authUser) {
        const parsedAuthUser = JSON.parse(authUser);
        const token = parsedAuthUser.token;
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
      }
      return config;
    },
    (error) => {
      return Promise.reject(error);
    }
  );

  return axiosClient;
};

export default useAxiosClient;
