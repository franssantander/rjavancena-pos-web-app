import React from "react";
import { Link } from "react-router-dom";
import logo from "@/assets/svg/rjlogo.svg";

const Navbar: React.FC<{ token: string }> = () => {
  return (
    <>
      <div className="flex items-center justify-between px-4 md:px-10 absolute top-10 z-50">
        <div className="flex items-center gap-20">
          <Link to="/" className="font-black text-md uppercase">
            <img className="w-14" src={logo} alt="" />
          </Link>
        </div>
      </div>
    </>
  );
};

export default Navbar;
