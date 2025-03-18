import React from "react";
import { Skeleton } from "@/components/ui/skeleton";
import { useAppSelector } from "@/app/hooks";
import { useLocation } from "react-router-dom";

export const MenuSkeleton: React.FC = () => {
  const interfaceLoading = useAppSelector((state) => state.user.loading);
  const location = useLocation();

  return (
    <>
      {location.pathname === "/app/menu" && (
        <div className="py-7 lg:grid lg:grid-cols-4 lg:grid-rows-4 lg:py-4 lg:gap-4">
          <div className="lg:col-span-3 lg:row-span-4">
            {interfaceLoading && (
              <Skeleton className="h-10 max-w-[24rem] bg-neutral-200" />
            )}
            <div className="py-4">
              {interfaceLoading && (
                <Skeleton className="h-6 max-w-[7rem] bg-neutral-200 mb-3" />
              )}
              {interfaceLoading && (
                <Skeleton className="h-8 w-full bg-neutral-200" />
              )}
              {interfaceLoading && (
                <Skeleton className="h-6 max-w-[9rem] bg-neutral-200 my-8" />
              )}
            </div>
            <div className="gap-y-5 grid grid-cols-2 sm:grid-cols-3 sm:flex-row lg:grid lg:grid-cols-4 lg:gap-3 relative">
              {interfaceLoading && (
                <>
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                  <Skeleton className="h-64 bg-card bg-neutral-200" />
                </>
              )}
            </div>
          </div>
          <div className="py-4 lg:py-0 lg:row-span-4 lg:w-full lg:h-full lg:col-start-4">
            {interfaceLoading && <Skeleton className="h-12 bg-neutral-200" />}
          </div>
        </div>
      )}
    </>
  );
};

export const DashboardSkeleton: React.FC = () => {
  const interfaceLoading = useAppSelector((state) => state.user.loading);

  return (
    <>
      {location.pathname === "/app/dashboard" && (
        <div className="lg:grid lg:grid-cols-5 lg:grid-rows-5 lg:gap-3">
          <div className="lg:col-span-4 lg:row-span-5">
            {interfaceLoading && (
              <div className="flex flex-col gap-4 sm:grid-flow-row sm:grid sm:grid-cols-3">
                <Skeleton className="h-32 max-w-[24rem] bg-neutral-200" />
                <Skeleton className="h-32 max-w-[24rem] bg-neutral-200" />
                <Skeleton className="h-32 max-w-[24rem] bg-neutral-200" />
              </div>
            )}

            <div className="py-6">
              {interfaceLoading && (
                <div>
                  <Skeleton className="h-96 w-full bg-neutral-200" />
                </div>
              )}
            </div>

            <div>
              <Skeleton className="h-10 w-72 bg-neutral-200 mb-5" />
              {interfaceLoading && (
                <div className="pb-10">
                  <Skeleton className="h-96 w-full bg-neutral-200" />
                </div>
              )}
            </div>
          </div>
          <div className="lg:row-span-5 lg:w-full lg:h-full lg:col-start-5">
            {interfaceLoading && (
              <Skeleton className="h-[24rem] w-full bg-neutral-200" />
            )}
          </div>
        </div>
      )}
    </>
  );
};

export const InventorySkeleton: React.FC = () => {
  const interfaceLoading = useAppSelector((state) => state.user.loading);

  return (
    <>
      {location.pathname === "/app/inventory" && (
        <>
          <div className="flex flex-col gap-6 md:flex-row lg:items-center justify-between">
            <div className="flex flex-col md:flex-row lg:items-center gap-4">
              {interfaceLoading && (
                <Skeleton className="h-10 w-[24rem] bg-neutral-200" />
              )}
              {interfaceLoading && (
                <Skeleton className="h-10 w-[7rem] bg-neutral-200" />
              )}
            </div>
            {interfaceLoading && (
              <Skeleton className="h-10 w-[6rem] bg-neutral-200" />
            )}
          </div>

          <div className="flex flex-col gap-4 py-4">
            {interfaceLoading && (
              <>
                <Skeleton className="h-24 w-full bg-neutral-200" />
                <Skeleton className="h-24 w-full bg-neutral-200" />
                <Skeleton className="h-24 w-full bg-neutral-200" />
                <Skeleton className="h-24 w-full bg-neutral-200" />
                <Skeleton className="h-24 w-full bg-neutral-200" />
                <Skeleton className="h-24 w-full bg-neutral-200" />
              </>
            )}
            {/* {inventoryResData && <InventoryList filteredData={filteredData} />} */}
          </div>
        </>
      )}
    </>
  );
};

export const UsersManagementSkeleton: React.FC = () => {
  const interfaceLoading = useAppSelector((state) => state.user.loading);

  return (
    <>
      {location.pathname === "/app/users" && (
        <>
          <div className="flex justify-between">
            {interfaceLoading && (
              <Skeleton className="h-10 w-[24rem] bg-neutral-200" />
            )}
            {interfaceLoading && (
              <Skeleton className="h-10 w-[7rem] bg-neutral-200" />
            )}
          </div>

          {interfaceLoading && (
            <Skeleton className="h-[96rem] w-full bg-neutral-200 mt-7" />
          )}
        </>
      )}
    </>
  );
};
