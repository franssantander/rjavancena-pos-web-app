import useAxiosClient from "@/axios-client";
import {
  keepPreviousData,
  useQuery,
  useInfiniteQuery,
} from "@tanstack/react-query";

const axiosClient = useAxiosClient();
export const useFetchApi = (
  apiConfig: { url: string; method?: string },
  queryKey: string[] = ["default"]
) => {
  const { data, isLoading, refetch } = useQuery({
    queryKey: queryKey,
    queryFn: async () => {
      const response = await axiosClient({
        url: apiConfig.url,
        method: apiConfig.method || "GET",
      });
      return response.data;
    },
  });

  return {
    data,
    isLoading,
    refetch,
  };
};

export const useFetchInfiniteQuery = (
  apiConfig: { url: string; method?: string },
  queryKey: string[] = ["default"]
) => {
  const { data, hasNextPage, fetchNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: queryKey,
      queryFn: async ({ pageParam = 1 }) => {
        const response = await axiosClient({
          url: `${apiConfig.url}?page=${pageParam}`,
          method: apiConfig.method || "GET",
        });
        return response.data;
      },
      initialPageParam: 1,
      getNextPageParam: (lastPage: any, allPages: any) => {
        const nextPage = lastPage.length ? allPages.length + 1 : undefined;
        return nextPage;
      },
    });

  return {
    data,
    hasNextPage,
    fetchNextPage,
    isFetchingNextPage,
  };
};
