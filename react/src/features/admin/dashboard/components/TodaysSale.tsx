import React, { useEffect, useState } from "react";
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from "recharts";
import { IconShoppingCart } from "@tabler/icons-react";
import { Card } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { dashboardData } from "@/app/slice/dashboardSlice";
import { useAppSelector } from "@/app/hooks";

const TodaysSale: React.FC = () => {
  const todaysSaleData = useAppSelector(dashboardData);
  const COLORS = ["#0088FE", "#00C49F", "#FFBB28", "#FF8042"];

  const formatted = new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  });

  const [data, setData] = useState([]);

  useEffect(() => {
    const transformedData =
      todaysSaleData?.chart &&
      todaysSaleData?.chart["week"].map((item: any) => ({
        name: item.day,
        value: item.total,
      }));
    setData(transformedData);
  }, [todaysSaleData]);

  return (
    <>
      <div className="lg:px-3 lg:rounded-md lg:border lg:relative">
        <h1 className="py-3 font-bold text-muted-foreground text-sm">
          Todays Sale
        </h1>
        <div className="sm:grid sm:grid-cols-2 lg:grid-cols-none">
          <div>
            <ResponsiveContainer style={{ left: 0 }} width="100%" height={210}>
              <PieChart style={{ margin: "auto", position: "relative" }}>
                <Pie
                  data={data}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={80}
                  fill="#8884d8"
                  paddingAngle={5}
                  dataKey="value"
                >
                  {todaysSaleData?.chart &&
                    todaysSaleData?.chart["week"]?.map(
                      (_: any, index: number) => (
                        <Cell
                          key={`cell-${index}`}
                          fill={COLORS[index % COLORS.length]}
                        />
                      )
                    )}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
            <h1 className="font-bold text-2xl text-center">
              {formatted.format(todaysSaleData?.today_sale?.current)}
            </h1>
            <p className="text-xs text-muted-foreground text-center">
              Total Earnings
            </p>
          </div>
          <div className="py-5">
            <p className="text-sm text-muted-foreground lg:text-xs">
              Top Selling Products
            </p>
            <div className="flex flex-col">
              {todaysSaleData?.today_sale?.top_products?.map(
                (item: any, index: number) => (
                  <Card
                    key={index}
                    className="p-4 my-3 border-none shadow-none relative bg-none "
                  >
                    <div className="flex items-center gap-4 lg:gap-3">
                      <Skeleton className="h-12 w-12 rounded-3xl  bg-neutral-500 lg:w-10 lg:h-10" />
                      <div>
                        <h1 className="text-sm lg:text-xs">{item.name}</h1>
                        <p className="text-xs text-muted-foreground">
                          {item.category}
                        </p>
                        <p className="text-xs font-semibold">
                          {formatted.format(item.price)}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-4 bottom-0 right-0 justify-end lg:pt-4">
                      <span className="flex items-center text-xs text-muted-foreground gap-2">
                        <IconShoppingCart color="black" size="14" />
                        {item.count}
                      </span>
                    </div>
                  </Card>
                )
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default TodaysSale;
