import React from "react";
import { Button } from "@/components/ui/button";
import element1 from "../../../../assets/element1.svg";
import circles from "../../../../assets/circles.svg";

const Hero: React.FC = () => {
  return (
    <>
      <div className="w-full h-screen px-4 flex flex-col justify-center items-center max-w-[1200px] m-auto">
        <div className="text-center flex flex-col justify-center items-center h-screen">
          <h1 className="text-4xl text-center md:text-6xl md:text-center uppercase font-black sm:leading-loose md:w-5/6">
            Your Trusted Partner in Quality Hardware Solutions
          </h1>
          <p className="md:text-center m-auto my-6 md:w-1/2">
            Discover a World of Tools, Building Materials, and Hardware
            Essentials at RJ Avancena Enterprises.
          </p>

          <Button className="font-bold">Shop Now</Button>
        </div>
      </div>
    </>
  );
};

export default Hero;
