import React from "react";
import Navbar from "../../components/user/navbar/components/Navbar";
import Footer from "../../components/user/footer/Components/Footer";
import { Outlet, useLocation } from "react-router-dom";

const PublicLayout: React.FC = () => {
  const location = useLocation();

  return (
    <>
      {location.pathname !== "/welcome" && <Navbar />}
      <Outlet />
      {location.pathname !== "/welcome" && <Footer />}
    </>
  );
};

export default PublicLayout;
