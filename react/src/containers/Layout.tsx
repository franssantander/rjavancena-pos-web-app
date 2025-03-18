import React, { useEffect } from "react";
import Sidebar from "./Sidebar";
import PageContent from "./PageContent";
import { SidebarProvider } from "../hooks/SidebarContext";
import { useAuth } from "@/app/AuthProvider";
import { Navigate } from "react-router-dom";
import { useAppSelector } from "@/app/hooks";

interface LayoutProps {
  token: string;
}

const Layout: React.FC<LayoutProps> = () => {
  const { token } = useAuth();
  const getUserInfo = useAppSelector((state) => state?.user?.userInfo);

  const newUser = getUserInfo?.user_info;

  console.log(newUser);

  if (!token && newUser === "New User") {
    return <Navigate to="/" />;
  }

  return (
    <div className="flex flex-1 overflow-hidden w-screen h-screen relative">
      <SidebarProvider>
        <Sidebar />
      </SidebarProvider>

      <div className="w-full overflow-y-auto">
        <PageContent />
      </div>
    </div>
  );
};

export default Layout;
