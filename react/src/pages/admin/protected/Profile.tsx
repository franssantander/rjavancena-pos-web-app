import React, { useEffect } from "react";
import Profile from "@/features/admin/profile/Profile";
import { setTitle } from "@/common/appSlice";
import { userInfo } from "@/app/slice/userSlice";
import { useAppDispatch } from "@/app/hooks";

const Internal: React.FC = () => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(userInfo({ url: "user-info/index", method: "GET" }));
    dispatch(setTitle("User Profile"));
  }, []);

  return (
    <>
      <Profile />
    </>
  );
};

export default Internal;
