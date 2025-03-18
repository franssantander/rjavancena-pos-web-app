import React, { useState } from "react";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

const WelcomeSignup: React.FC = () => {
  const [startDate, setStartDate] = useState(new Date());

  return (
    <>
      <div className="w-full my-32 md:h-screen md:flex md:items-center max-w-[1200px] m-auto">
        <div className="p-4 md:p-0 m-auto md:w-2/3">
          <div>
            <h1 className="text-2xl font-bold">
              Welcome Back! Let's Complete Your Profile
            </h1>
            <p className="text-sm mt-4">
              Enhance your experience by providing some additional details.
            </p>
          </div>
          <div className="mt-14">
            <h1 className="font-bold text-2xl">Profile Information</h1>
            <form className="mt-9 flex flex-col gap-5 md:grid md:grid-cols-2">
              <label className="form-control w-full">
                <input
                  type="text"
                  placeholder="First Name"
                  className="input input-bordered w-full"
                />
              </label>
              <label className="form-control w-full">
                <input
                  type="text"
                  placeholder="Last Name"
                  className="input input-bordered w-full"
                />
              </label>

              <DatePicker
                className="w-full input input-bordered"
                showIcon
                selected={startDate}
                onChange={(date) => setStartDate(date)}
                icon={
                  <svg
                    className="mt-2"
                    xmlns="http://www.w3.org/2000/svg"
                    width="1em"
                    height="1em"
                    viewBox="0 0 24 24"
                  >
                    <path
                      fill="none"
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2"
                    />
                  </svg>
                }
              />

              <label className="form-control w-full">
                <input
                  type="text"
                  placeholder="Phone Number"
                  className="input input-bordered w-full"
                />
              </label>
            </form>
          </div>
          <div className="mt-14">
            <h1 className="font-bold text-2xl">Address Information</h1>
            <form className="mt-9 flex flex-col gap-5 md:grid md:grid-cols-2">
              <label className="form-control w-full">
                <input
                  type="text"
                  placeholder="Street Address"
                  className="input input-bordered w-full"
                />
              </label>
              <label className="form-control w-full">
                <input
                  type="text"
                  placeholder="City"
                  className="input input-bordered w-full"
                />
              </label>

              <label className="form-control w-full">
                <input
                  type="text"
                  placeholder="Brgy"
                  className="input input-bordered w-full"
                />
              </label>

              <label className="form-control w-full">
                <input
                  type="text"
                  placeholder="Zip Code"
                  className="input input-bordered w-full"
                />
              </label>
            </form>
          </div>
          <div className="pt-10">
            <button className="btn btn-md bg-btnprimary text-white w-full md:w-32">Finish Setup</button>
          </div>
        </div>
      </div>
    </>
  );
};

export default WelcomeSignup;
