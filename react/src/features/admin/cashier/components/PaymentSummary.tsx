import React from "react";
import { Separator } from "@/components/ui/separator";
import { Icon } from "@iconify/react/dist/iconify.js";

const PaymentSummary: React.FC = (props) => {
  const formatted = new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  });

  const { payment } = props.customerData;

  return (
    <div className="w-full">
      <div className="pt-7 flex flex-col gap-2 items-center">
        <h1 className="font-medium text-sm text-neutral-400">Total Amount</h1>
        {payment.map((item: any) => (
          <h1 className="text-3xl font-bold">
            {formatted.format(item.total_amount)}
          </h1>
        ))}
        <Separator />
        <h1 className="text-xs text-green-500 flex items-center">
          <Icon icon="tabler:lock-dollar" /> Secure payment
        </h1>
      </div>
      <div className="pt-7">
        <h1 className="font-semibold">Payment Summary</h1>
        {payment.map((item: any, index: number) => (
          <div key={index} className="grid gap-2 pt-6">
            <div className="flex items-center justify-between">
              <p className="text-sm text-neutral-500">Discount</p>
              <p className="text-sm text-neutral-500">
                {formatted.format(item.total_discounted_amount)}
              </p>
            </div>
            <div className="flex items-center justify-between">
              <p className="text-sm text-neutral-500">Total Discount</p>
              <p className="text-sm text-neutral-500">
                {formatted.format(item.total_discounted_amount)}
              </p>
            </div>
            <div className="flex items-center justify-between">
              <p className="text-sm text-neutral-500">Sub Total</p>
              <p className="text-sm text-neutral-500">
                {formatted.format(item.total_amount)}
              </p>
            </div>
            <div className="flex items-center justify-between">
              <p className="text-sm text-neutral-500">Tax</p>
              <p className="text-sm text-neutral-500">{formatted.format(0)}</p>
            </div>
            <Separator />
            <div className="flex items-center justify-between">
              <p className="font-semibold">Total Amount</p>
              <p className="font-semibold">
                {formatted.format(item.total_amount)}
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default PaymentSummary;
