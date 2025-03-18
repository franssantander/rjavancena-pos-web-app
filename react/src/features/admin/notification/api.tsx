import useAxiosClient from "@/axios-client";
import { useInfiniteQuery } from "@tanstack/react-query";

export const useNotification = () => {
  const axiosClient = useAxiosClient();
  const {
    data,
    isLoading,
    refetch,
    hasNextPage,
    fetchNextPage,
    isFetchingNextPage,
  } = useInfiniteQuery({
    queryKey: ["notifications"],
    queryFn: async ({ pageParam = 1 }) => { // Default to page 1
      const response = await axiosClient.get(
        `notification/get-notification?pages=${pageParam}&limit=5`
      );
      return response.data;
    },
    // getNextPageParam: (lastPage: any, allPages: any) => {
    //   // Check if there are notifications in the last fetched page
    //   if (lastPage.data.notification.length > 0) {
    //     return allPages.length + 1; // Load the next page
    //   } else {
    //     return undefined; // No more pages to fetch
    //   }
    // },
    getNextPageParam: (lastPage, pages) => lastPage.nextCursor,
  });

  return {
    data,
    isLoading,
    refetch,
    hasNextPage,
    fetchNextPage,
    isFetchingNextPage,
  };
};
