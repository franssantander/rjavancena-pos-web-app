import React, {
  createContext,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import Cookies from "js-cookie";
import axios from "axios";

interface AuthContextProps {
  token: string | undefined | any;
  setToken: (newToken: string) => void;
  setAuthUser: (newToken: string) => void;
}

interface AuthProviderProps {
  children: React.ReactNode;
}

const AuthContext = createContext<AuthContextProps>({
  token: undefined,
  setToken: () => {},
  setAuthUser: () => {},
});

const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const storedAuthUser = Cookies.get("authUser");
  const parsedAuthUser = storedAuthUser ? JSON.parse(storedAuthUser) : { token: undefined };

  const [authUser, setAuthUser] = useState<any>(parsedAuthUser);

  const setToken = (newToken: any) => {
    setAuthUser({ token: newToken });
  };

  useEffect(() => {
    if (authUser.token) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + authUser.token;
      Cookies.set("authUser", JSON.stringify(authUser));
    } else {
      delete axios.defaults.headers.common["Authorization"];
      Cookies.remove("authUser");
    }
  }, [authUser.token]);

  const contextValue = useMemo(
    () => ({ authUser, setToken, setAuthUser }),
    [authUser]
  );

  return (
    <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>
  );
};

export const useAuth = () => {
  return useContext(AuthContext);
};

export default AuthProvider;
