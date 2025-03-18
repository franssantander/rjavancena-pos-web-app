import React from "react";
import Routes from "../src/routes/index.tsx";
import AuthProvider from "./app/AuthProvider.tsx";
import {
  useQuery,
  useMutation,
  useQueryClient,
  QueryClient,
  QueryClientProvider,
} from "@tanstack/react-query";
import { TableProvider } from "./hooks/TableContext.tsx";

const queryClient = new QueryClient();

const App: React.FC = () => {
  return (
    <>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <TableProvider>
            <Routes />
          </TableProvider>
        </AuthProvider>
      </QueryClientProvider>
    </>
  );
};

export default App;
