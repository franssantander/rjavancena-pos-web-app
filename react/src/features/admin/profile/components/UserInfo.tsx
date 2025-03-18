import React from "react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const UserInfo: React.FC = () => {
  return (
    <div className="w-full">
      <div>
        <div className="flex items-center gap-10">
          <Avatar className="h-20 w-20 border-[1px] border-neutral-500 shadow-md">
            <AvatarImage src="https://github.com/shadcn.png" alt="@shadcn" />
            <AvatarFallback>CN</AvatarFallback>
          </Avatar>
          <div>
            <h1 className="text-md font-semibold">John Doe</h1>
            <h1 className="text-sm text-neutral-500">Super Admin</h1>
          </div>
        </div>
        <div className="pt-10">
          <div className="grid w-full max-w-sm items-center gap-1.5">
            <Label htmlFor="picture">Profile Name</Label>
            <Input type="text" />
          </div>
        </div>
      </div>
    </div>
  );
};

export default UserInfo;
