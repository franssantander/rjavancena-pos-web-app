import { useAppDispatch, useAppSelector } from "@/app/hooks";
import { TableProvider } from "@/hooks/TableContext";
import useColumnsProduct from "@/components/ui/columns";
import { DataTable } from "@/components/ui/data-table";
import { getLogsData, logsData } from "@/app/slice/LogsSlice";
import React, { useEffect, useState } from "react";
import { useToast } from "@/components/ui/use-toast";

const Logs: React.FC = (props: any) => {
  const columnsTable = useColumnsProduct("logs");
  // const logsData = useAppSelector((state) => state?.logs?.logsData?.data?.logs);
  const [data, setData] = useState([]);

  const dispatch = useAppDispatch();
  const { toast } = useToast();
  const logsTableData = useAppSelector(logsData);
  const status = useAppSelector((state) => state.logs.status);
  const logsMessage = useAppSelector((state) => state.logs.logsMessage);

  useEffect(() => {
    dispatch(getLogsData({ url: props.path_key, method: "GET" }));
  }, []);

  useEffect(() => {
    if (status === "logs/success") {
      setData(logsTableData?.data?.logs);
    }

    if (status === "logs/failed") {
      toast({
        variant: "destructive",
        title: logsMessage?.message,
      });
    }
  }, [status]);

  return (
    <div className="w-full">
      <TableProvider page={props.title}>
        <DataTable
          url={props.path_key}
          fetchData={getLogsData}
          title="Logs table"
          columns={columnsTable}
          data={data}
        />
      </TableProvider>
    </div>
  );
};

export default Logs;
