import {
  ColumnDef,
  flexRender,
  getCoreRowModel,
  useReactTable,
  getPaginationRowModel,
  ColumnFiltersState,
  getFilteredRowModel,
  SortingState,
  getSortedRowModel,
} from "@tanstack/react-table";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useEffect, useState } from "react";
import { DataTablePagination } from "./data-table-pagination";
import { DataTableToolbar } from "./data-table-toolbar";
import { useTableContext } from "@/hooks/TableContext";
import { useAppDispatch } from "@/app/hooks";
import { getInventoryDataChild } from "@/app/slice/inventorySlice";

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  title: string;
}

export function DataTable<TData, TValue>({
  columns,
  data,
  title,
  url,
  fetchData,
}: DataTableProps<TData, TValue>) {
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [rowSelection, setRowSelection] = useState({});
  const [sorting, setSorting] = useState<SortingState>([]);
  const { page, jsx, placeHolder, columnName } = useTableContext();
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [search, setSearch] = useState("");
  const [sliceData, setSliceData] = useState([]);

  console.log("sliceData", sliceData?.pagination);

  const dispatch = useAppDispatch();

  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    onColumnFiltersChange: setColumnFilters,
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    getSortedRowModel: getSortedRowModel(),
    manualPagination: true,
    state: {
      sorting,
      columnFilters,
      rowSelection,
      pagination: { pageIndex, pageSize },
    },
  });

  useEffect(() => {
    const fetchDataAndSetState = async () => {
      const response = await dispatch(
        fetchData({
          url: url,
          page: pageIndex + 1,
          limit: pageSize,
          search: search,
        })
      );

      if (fetchData.fulfilled.match(response)) {
        setSliceData(response.payload.data);
      }
    };

    fetchDataAndSetState();
  }, [dispatch, fetchData, pageIndex, pageSize, url, search]);

  return (
    <>
      {page && (
        <div className="flex gap-4 pb-5 justify-between">
          <div className="yw-full yrelative yflex yitems-center lg:yw-96">
            <DataTableToolbar
              table={table}
              placeHolder={placeHolder}
              columnName={columnName}
              search={search}
              setSearch={setSearch}
            />
          </div>
          {jsx}
        </div>
      )}

      <div className="rounded-md border my-4">
        <h1 className="font-bold pt-3 px-4 tracking-tight text-xs">{title}</h1>
        <Table>
          <TableHeader className="text-xs">
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  return (
                    <TableHead key={header.id}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  );
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody className="text-xs">
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="h-24 text-center"
                >
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <DataTablePagination
        table={table}
        sliceData={sliceData}
        pageIndex={pageIndex}
        pageSize={pageSize}
        onPageChange={(newPageIndex) => setPageIndex(newPageIndex)}
        onPageSizeChange={(newPageSize) => {
          setPageSize(newPageSize);
          setPageIndex(0);
          table.setPageSize(newPageSize);
        }}
      />
    </>
  );
}
