import React from "react";
import {
  IconCashBanknoteFilled,
  IconCreditCardFilled,
  IconQrcode,
} from "@tabler/icons-react";
import { RadioGroupItem, RadioGroup } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";

const PaymentMethod: React.FC = () => {
  const paymentMethod = [
    {
      icon: <IconCashBanknoteFilled />,
      label: "Cash",
    },
    {
      icon: <IconCreditCardFilled />,
      label: "Debit",
    },
    {
      icon: <IconQrcode />,
      label: "E-Wallet",
    },
  ];

  return (
    <div className="w-full">
      <h1 className="font-semibold">Payment Method</h1>
      <div className="pt-4">
        <RadioGroup
          defaultValue="Cash"
          className="grid grid-cols-3 gap-2 w-full"
        >
          {paymentMethod?.map((item, index) => (
            <div key={index}>
              <RadioGroupItem
                value={item?.label}
                id={item?.label}
                className="peer sr-only"
              />
              <Label
                htmlFor={item?.label === "Cash"}
                className={`flex cursor-pointer flex-col text-xs items-center font-semibold justify-between rounded-md border-2 border-neutral-300 bg-popover p-3 hover:bg-primary hover:text-white peer-data-[state=checked]:bg-primary peer-data-[state=checked]:text-white [&:has([data-state=checked])]:text-white ${
                  item?.label === "Debit" && "bg-neutral-200 text-neutral-500"
                } ${
                  item?.label === "E-Wallet" &&
                  "bg-neutral-200 text-neutral-500"
                }`}
              >
                {item?.icon}
                {item?.label}
              </Label>
            </div>
          ))}
        </RadioGroup>
      </div>
    </div>
  );
};

export default PaymentMethod;
