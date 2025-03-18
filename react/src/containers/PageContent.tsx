import React, { useEffect, useState } from "react";
import Header from "./Header";
import { Routes, Route, useNavigate } from "react-router-dom";
import Menu from "../pages/admin/protected/Menu";
import Dashboard from "../pages/admin/protected/Dashboard";
import Inventory from "../pages/admin/protected/Inventory";
import ProductList from "../pages/admin/protected/ProductList";
import Users from "../pages/admin/protected/Users";
import Customer from "../pages/admin/protected/Customer";
import Logs from "../pages/admin/protected/Logs";
import Voucher from "../pages/admin/protected/Voucher";
import { headerUser, setNavbar } from "@/app/slice/userSlice";
import { RouteType } from "@/interface/InterfaceType";
import { useAppDispatch, useAppSelector } from "@/app/hooks";

//* SKELETON PAGES
import {
  MenuSkeleton,
  DashboardSkeleton,
  InventorySkeleton,
  UsersManagementSkeleton,
} from "../pages/admin/skeleton/SkeletonPage";
import useAxiosClient from "@/axios-client";
import VoucherChildList from "@/pages/admin/protected/VoucherChildList";
import Expenses from "@/pages/admin/protected/Expenses";
import ExpensesView from "@/pages/admin/protected/ExpensesView";
import LostList from "@/features/admin/inventory/components/LostList";

const PageContent: React.FC = () => {
  const navigate = useNavigate();
  const dispatch = useAppDispatch();
  const [routes, setRoutes] = useState([]);
  const pageLoading = useAppSelector((state) => state?.user?.loading);

  const axiosClient = useAxiosClient();

  const requestRoutes = async () => {
    try {
      const res = await axiosClient.get("/role-nav-links");

      setRoutes(res.data.nav_links);
      dispatch(setNavbar(res.data.nav_links));
      dispatch(headerUser(res.data.user));
    } catch (error) {
      navigate("/");
    }
  };

  const routeComponent = (key: string, routeData: RouteType) => {
    console.log(key);
    switch (key) {
      case "parent-Menu":
        return <Menu {...routeData} />;
      case "parent-Dashboard":
        return <Dashboard {...routeData} />;
      case "parent-Inventory":
        return <Inventory {...routeData} />;
      case "child-Inventory":
        return <ProductList />;
      case "child-Inventory/view-lost":
        return <LostList />;
      case "parent-Users":
        return <Users {...routeData} />;
      case "parent-Customer":
        return <Customer {...routeData} />;
      case "parent-Logs":
        return <Logs {...routeData} />;
      case "parent-Voucher":
        return <Voucher {...routeData} />;
      case "child-Voucher":
        return <VoucherChildList />;
      case "parent-Expenses":
        return <Expenses {...routeData} />;
      case "child-Expenses":
        return <ExpensesView />;
      default:
        return null;
    }
  };

  useEffect(() => {
    requestRoutes();
  }, []);

  return (
    <>
      <div className="w-full flex flex-col gap-5">
        <Header />
        <main className="bg-white px-4 lg:px-3">
          {pageLoading && <MenuSkeleton />}
          {pageLoading && <DashboardSkeleton />}
          {pageLoading && <InventorySkeleton />}
          {pageLoading && <UsersManagementSkeleton />}
          <Routes>
            {routes &&
              routes.map(
                (route: RouteType, index: number) =>
                  route && (
                    <Route
                      key={index}
                      path={`${route.path}`}
                      element={routeComponent(route.title, route)}
                    >
                      <Route
                        index
                        element={routeComponent(`parent-${route.title}`, route)}
                      />
                      {route.title === "Inventory" && (
                        <Route
                          key={`${index}-child`}
                          path="inventory-child/:id"
                          element={routeComponent(
                            `child-${route.title}`,
                            route
                          )}
                        />
                      )}

                      {route.title === "Inventory" && (
                        <Route
                          key={`${index}-child/view-lost`}
                          path="inventory-child/view-lost/:id"
                          element={routeComponent(
                            `child-${route.title}/view-lost`,
                            route
                          )}
                        />
                      )}
                      {route.title === "Voucher" && (
                        <Route
                          key={`${index}-child`}
                          path="voucher-child/:id"
                          element={routeComponent(
                            `child-${route.title}`,
                            route
                          )}
                        />
                      )}
                      {route.title === "Expenses" && (
                        <Route
                          key={`${index}-child`}
                          path="view/:id"
                          element={routeComponent(
                            `child-${route.title}`,
                            route
                          )}
                        />
                      )}
                    </Route>
                  )
              )}
          </Routes>
        </main>
      </div>
    </>
  );
};

export default PageContent;
