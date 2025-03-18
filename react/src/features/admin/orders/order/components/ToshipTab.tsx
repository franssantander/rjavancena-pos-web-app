import React from "react";
import { DropdownMenuTrigger } from "@radix-ui/react-dropdown-menu";
import { MixerHorizontalIcon } from "@radix-ui/react-icons";
import { Button } from "@/components/ui/button";
import { DataTable } from "@/components/ui/data-table";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import packOrder from "@/data/packOrder.json";
import shipmentOrder from "@/data/shipmentOrder.json";
import handOver from "@/data/handOver.json";
import { useTableContext } from "@/hooks/TableContext";
import {
  ColumnsPacksOrder,
  ColumnsShipmentOrder,
  ColumnsHandover,
} from "@/components/ui/columns";

const ToshipTab: React.FC = () => {
  const { selectedOption, setSelectedOption } = useTableContext();

  const tablesSelection = [
    {
      title: "Pack Orders",
      value: "packOrders",
    },
    {
      title: "Shipment",
      value: "shipment",
    },
    {
      title: "Handover",
      value: "handOver",
    },
  ];

  const handleOptionChange = (option: string) => {
    setSelectedOption(option);
  };

  return (
    <>
      <DropdownMenu>
        <DropdownMenuTrigger className="absolute right-0" asChild>
          <Button
            variant="outline"
            size="sm"
            className="ml-auto hidden h-8 lg:flex"
          >
            <MixerHorizontalIcon className="mr-2 h-4 w-4" />
            View
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-[150px]">
          <DropdownMenuLabel>Select Tables</DropdownMenuLabel>
          <DropdownMenuSeparator />
          {tablesSelection.map((table, index) => (
            <DropdownMenuCheckboxItem
              key={index}
              onCheckedChange={() => handleOptionChange(table.value)}
            >
              {table.title}
            </DropdownMenuCheckboxItem>
          ))}
        </DropdownMenuContent>
      </DropdownMenu>

      {selectedOption === "packOrders" && (
        <DataTable
          title="Pack Orders"
          columns={ColumnsPacksOrder}
          data={packOrder}
        />
      )}
      {selectedOption === "shipment" && (
        <DataTable
          title="Shipment"
          columns={ColumnsShipmentOrder}
          data={shipmentOrder}
        />
      )}

      {selectedOption === "handOver" && (
        <DataTable title="Handover" columns={ColumnsHandover} data={handOver} />
      )}
    </>
  );
};

export default ToshipTab;
