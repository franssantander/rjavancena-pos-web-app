import React, { useState } from "react";
import { Card, CardHeader, CardTitle } from "@/components/ui/card";
import { Bar, BarChart, ResponsiveContainer, XAxis, YAxis } from "recharts";
import _ from "lodash";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { DashboardInterface } from "@/interface/InterfaceType";

const SalesOverview: React.FC<DashboardInterface> = ({ data }) => {
  const [selectedChart, setSelectedChart] = useState("week");
  const chartKeys = Object.keys(data?.chart || {});

  const handleChartChange = (value: any) => {
    setSelectedChart(value);
  };

  const getDataKey = () => {
    switch (selectedChart) {
      case "month":
        return "day";
      case "today":
        return "hour";
      case "week":
        return "day";
      case "year":
        return "name";
      default:
        return "";
    }
  };

  return (
    <>
      <Card>
        <CardHeader className="pb-10 flex">
          <CardTitle className="tracking-tight">Sales Overview</CardTitle>
          <Select onValueChange={handleChartChange}>
            <SelectTrigger className="w-32">
              <SelectValue placeholder={_.startCase(selectedChart)} />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Select chart</SelectLabel>
                {chartKeys &&
                  chartKeys?.map((key, index) => (
                    <SelectItem value={key} key={index}>
                      {_.startCase(key)}
                    </SelectItem>
                  ))}
              </SelectGroup>
            </SelectContent>
          </Select>
        </CardHeader>
        <ResponsiveContainer width="100%" height={350}>
          <BarChart data={data?.chart && data?.chart[selectedChart]}>
            <XAxis
              dataKey={getDataKey()}
              stroke="#888888"
              fontSize={12}
              tickLine={false}
              axisLine={false}
            />
            <YAxis
              stroke="#888888"
              fontSize={12}
              tickLine={false}
              axisLine={false}
              tickFormatter={(value) => `₱${value}`}
            />
            <Bar
              dataKey="total"
              fill="currentColor"
              radius={[4, 4, 0, 0]}
              className="fill-primary"
            />
          </BarChart>
        </ResponsiveContainer>
      </Card>
    </>
  );
};

export default SalesOverview;
