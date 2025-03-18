import React, { useEffect, useState } from "react";
import ItemsList from "./components/ItemsList";
import PaymentMethod from "./components/PaymentMethod";
import PaymentSummary from "./components/PaymentSummary";

const CashierCustomer: React.FC = () => {
  const [customerData, setCustomerData] = useState<any>(null);

  useEffect(() => {
    const savedCustomerData = localStorage.getItem("customerData");

    if (savedCustomerData) {
      try {
        const customer = JSON.parse(savedCustomerData);
        setCustomerData(customer);
      } catch (error) {
        setCustomerData(null);
      }
    }

    const handleStorageChange = (event: StorageEvent) => {
      if (event.key === "customerData" && event.newValue) {
        try {
          const newCustomerData = JSON.parse(event.newValue);
          setCustomerData(newCustomerData);
        } catch (error) {
          console.error("Failed to parse new customer data:", error);
          setCustomerData(null);
        }
      }
    };

    window.addEventListener("storage", handleStorageChange);

    return () => {
      window.removeEventListener("storage", handleStorageChange);
    };
  }, []);

  return (
    <>
      {customerData ? (
        <div className="w-full p-4 md:grid md:grid-cols-2 md:gap-20 md:items-center lg:max-w-[1200px] lg:h-screen m-auto">
          <div className="grid gap-1">
            <div>
              <h1 className="font-semibold">Order's Summary</h1>
              <span className="text-neutral-400 text-sm">
                #{customerData?.customer_id}
              </span>
            </div>
            {customerData && <ItemsList customerData={customerData} />}
            <PaymentMethod />
          </div>
          <div className="grid gap-4">
            {customerData && <PaymentSummary customerData={customerData} />}
          </div>
        </div>
      ) : (
        <h1 className="text-2xl m-auto items-center h-screen flex justify-center">
          No customer data available.
        </h1>
      )}
    </>
  );
};

export default CashierCustomer;
