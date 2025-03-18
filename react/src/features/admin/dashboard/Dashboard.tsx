import React, { useEffect, useState } from "react";
import { DataTable } from "@/components/ui/data-table";
import TodaysSale from "./components/TodaysSale";
import CardSales from "./components/CardSales";
import SalesOverview from "./components/SalesOverview";
import {
  getDashboardData,
  dashboardData,
  dashboardStatus,
} from "@/app/slice/dashboardSlice";
import { useSelector } from "react-redux";
import useColumnsProduct from "@/components/ui/columns";
import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { RouteType } from "@/interface/InterfaceType";
import { useToast } from "@/components/ui/use-toast";

const Dashboard: React.FC<RouteType> = (props) => {
  const status = useSelector(dashboardStatus);
  const dashData = useSelector(dashboardData);
  const [data, setData] = useState({});
  const [transactionData, setTransactionData] = useState([]);
  const columnsProduct = useColumnsProduct("transaction");
  const dispatch = useAppDispatch();
  const voidMessage = useAppSelector((state) => state.dashboard.voidMessage);
  const { toast } = useToast();

  useEffect(() => {
    if (status === "getDashboardData/success") {
      setData(dashData);
      setTransactionData(dashData?.data?.recent_transactions);
    }
    if (status === "voidPaid/success") {
      dispatch(getDashboardData({ url: props.path_key, method: "GET" }));
      toast({
        variant: "success",
        title: voidMessage?.message,
      });
    }
    if (status === "voidPaid/failed") {
      toast({
        variant: "destructive",
        title: voidMessage?.message,
      });
    }
  }, [status, dashData]);

  return (
    <>
      <div className="lg:grid lg:grid-cols-5 lg:grid-rows-5 lg:gap-3">
        <div className="lg:col-span-4 lg:row-span-5">
          <CardSales />
          <div className="py-6">
            <SalesOverview data={data} />
          </div>

          <div>
            <DataTable
              url={props.path_key}
              fetchData={getDashboardData}
              search="transaction"
              title="Recent Transaction"
              columns={columnsProduct}
              data={transactionData}
            />
          </div>
        </div>
        <div className="lg:row-span-5 lg:w-full lg:h-full lg:col-start-5">
          <TodaysSale />
        </div>
      </div>
    </>
  );
};

export default Dashboard;
