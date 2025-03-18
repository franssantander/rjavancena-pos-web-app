import { ColumnDef } from "@tanstack/react-table";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { ArrowUpIcon } from "@radix-ui/react-icons";
import {
  RowInventoryActions,
  RowPackOrdersAction,
  RowTransactionActions,
  RowReturnOrderAction,
  RowShippingAction,
  RowFailedDeliverAction,
  RowCancelledDeliverAction,
  RowHandoverAction,
  RowUsersActions,
  RowCustomerTransactionActions,
  RowLogsActions,
  RowVoucherActions,
  RowExpensesActions,
  RowExpensesChildActions,
  LostChildActions,
  InventoryLostChildActions,
  InventoryFoundChildActions,
  InventoryRestockChildActions,
} from "./data-table-actions-row";
import { Icon } from "@iconify/react";
import {
  TransactionType,
  UsersType,
  CustomerOrderType,
  PackOrderType,
  HandOverType,
  ShipmentOrderType,
  ShippingType,
  DeliveredType,
  FailedDeliverType,
  ReturnOrderType,
  CancelledDeliverType,
} from "@/interface/InterfaceType";
import _ from "lodash";

// This type is used to define the shape of our data.
// You can use a Zod schema here if you want.

import { inventoryData } from "@/app/slice/inventorySlice";
import { useSelector } from "react-redux";
import { useMemo } from "react";
import { usersData } from "@/app/slice/usersManagementSlice";
import { dashboardData } from "@/app/slice/dashboardSlice";
import { useAppSelector } from "@/app/hooks";

const useColumnsProduct = (dataSource: any) => {
  console.log(dataSource);
  // inventory-found
  const inventoryChild = useSelector(inventoryData);
  const usersParent = useSelector(usersData);
  const dashboardTransaction = useSelector(dashboardData);
  const customerCashierData = useAppSelector(
    (state) => state.customer?.customerCashierData
  );
  const logsData = useAppSelector((state) => state.logs?.logsData);
  const childVoucherData = useAppSelector(
    (state) => state.voucher?.voucherChildData
  );
  const expensesData = useAppSelector((state) => state.expenses?.expensesData);
  const childExpensesData = useAppSelector(
    (state) => state.expenses?.childExpensesData
  );

  const childInventoryLostData = useAppSelector(
    (state) => state.inventory?.inventoryLostData
  );

  const childInventoryFoundData = useAppSelector(
    (state) => state.inventory?.inventoryFoundData
  );

  const getInventoryRestockDataChild = useAppSelector(
    (state) => state.inventory?.inventoryRestockData
  );

  console.log(getInventoryRestockDataChild);

  const baseColumns: ColumnDef<any>[] = [];

  const dynamicColumns = useMemo(() => {
    const getColumns = () => {
      console.log(dataSource);
      switch (dataSource) {
        case "inventory":
          return inventoryChild;
        case "users":
          return usersParent;
        case "transaction":
          return dashboardTransaction;
        case "customer":
          return customerCashierData;
        case "logs":
          return logsData;
        case "voucher":
          return childVoucherData;
        case "expenses":
          return expensesData;
        case "expensesChild":
          return childExpensesData;
        case "inventory-lost":
          return childInventoryLostData;
        case "inventory-found":
          return childInventoryFoundData;
        case "inventory-restock":
          return getInventoryRestockDataChild;
        default:
          break;
      }
    };

    const currentData = getColumns();

    return (
      currentData?.data?.columns?.map((column: string) => {
        const accessorKey = column.trim().toLowerCase().replace(/\s+/g, "_");

        return {
          accessorKey: accessorKey,
          header: ({ column }) => {
            const columnHeader = column.id;
            return (
              <h1
                className="flex items-center gap-2 cursor-pointer"
                onClick={() =>
                  column.toggleSorting(column.getIsSorted() === "asc")
                }
              >
                {_.startCase(columnHeader)}
                <Icon icon="radix-icons:caret-sort" />
              </h1>
            );
          },
          cell: ({ row }: { row: any }) => {
            const columnHeader = _.lowerCase(column);
            if (columnHeader.includes("retailprice")) {
              const value = parseFloat(row.getValue(accessorKey));
              const formatted = new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
              }).format(value);
              return <div className="text-right font-medium">{formatted}</div>;
            }
            if (columnHeader.includes("image")) {
              const imageUrl = row.getValue(accessorKey);

              return (
                <>
                  {_.isEmpty(imageUrl) ? (
                    <Skeleton className="h-11 w-11 bg-neutral-200 rounded-xl" />
                  ) : (
                    <img
                      className="h-11 w-11 bg-cover bg-no-repeat"
                      src={imageUrl}
                      alt=""
                    />
                  )}
                </>
              );
            }
            if (columnHeader.includes("status_color")) {
              console.log(row);

              return <Badge>{row.getValue(accessorKey)}</Badge>;
            }
            if (columnHeader.includes("status")) {
              console.log(row.getValue(accessorKey));

              const badgeColor = (row: string) => {
                switch (row) {
                  case "Activate":
                    return "successStatus";
                  case "Done":
                    return "successStatus";
                  case "Paid":
                    return "successStatus";
                  case "Not Paid":
                    return "destructiveStatus";
                  case "Inactive":
                    return "dimmedStatus";
                  case "Available":
                    return "successStatus";
                  case "Used":
                    return "warning";
                  case "Pending":
                    return "warning";
                  case "Banned":
                    return "destructiveStatus";
                  case "Restricted":
                    return "destructiveStatus";
                  case "High":
                    return "successStatus";
                  case "Moderate":
                    return "orange";
                  case "Low":
                    return "warning";
                  case "Empty":
                    return "destructiveStatus";

                  default:
                    break;
                }
                console.log(row);
              };

              const statusColor = row.getValue(accessorKey);

              return (
                <Badge variant={badgeColor(statusColor)}>
                  {row.getValue(accessorKey)}
                </Badge>
              );
            }
            if (columnHeader.includes("user id")) {
              return <>{row.getValue(accessorKey)}</>;
            }
            if (columnHeader.includes("total amount")) {
              const value = parseFloat(row.getValue(accessorKey));
              const formatted = new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
              }).format(value);
              return <>{formatted}</>;
            }
            if (columnHeader.includes("actions")) {
              switch (dataSource) {
                case "inventory":
                  return <RowInventoryActions row={row} />;
                case "users":
                  return <RowUsersActions row={row} />;
                case "transaction":
                  return <RowTransactionActions row={row} />;
                case "customer":
                  return <RowCustomerTransactionActions row={row} />;
                case "logs":
                return <RowLogsActions row={row} />;
                case "voucher":
                  return <RowVoucherActions row={row} />;
                case "expenses":
                  return <RowExpensesActions row={row} />;
                case "expensesChild":
                  return <RowExpensesChildActions row={row} />;
                case "inventory-lost":
                  return <InventoryLostChildActions row={row} />;
                case "inventory-found":
                  return <InventoryFoundChildActions row={row} />;
                case "inventory-restock":
                  return <InventoryRestockChildActions row={row} />;
                default:
                  return null;
              }
            }
            return <>{row.getValue(accessorKey)}</>;
          },
        };
      }) || []
    );
  }, [dataSource, inventoryChild, usersParent, dashboardTransaction]);

  // Combine base columns and dynamic columns
  const columns = useMemo(
    () => [...baseColumns, ...dynamicColumns],
    [baseColumns, dynamicColumns]
  );

  return columns;
};

export default useColumnsProduct;
