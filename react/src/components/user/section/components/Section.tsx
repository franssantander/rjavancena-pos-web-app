import React from "react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";

const Section: React.FC = () => {
  return (
    <>
      <div className="relative w-full h-80 m-auto mt-20">
        <img
          className="w-full h-full object-cover"
          src="https://images.unsplash.com/photo-1589939705384-5185137a7f0f?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
          alt=""
        />
        <div className="absolute inset-0 bg-black opacity-80"></div>
        <div className="p-4 absolute inset-0 flex flex-col gap-y-4 items-center justify-center">
          <h1 className="text-lg sm:text-3xl text-white font-bold">
            Ready to Get Started?
          </h1>
          <p className="text-xs text-white sm:w-[480px] text-center">
            Browse our products, fill your cart, and experience the convenience
            of RJ Avancena Enterprises. Your hardware journey begins here.
          </p>
          <div className="flex items-center gap-6">
            <Button className="font-bold bg-white text-black">Shop Now</Button>
            <Link className="text-white underline" to="/">Contact Us</Link>
          </div>
        </div>
      </div>
    </>
  );
};

export default Section;
