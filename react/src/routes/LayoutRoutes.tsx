import Menu from "../pages/admin/protected/Menu";
import Dashboard from "../pages/admin/protected/Dashboard";
import Inventory from "../pages/admin/protected/Inventory";
import ProductList from "../pages/admin/protected/ProductList";
import Users from "../pages/admin/protected/Users";
import CustomerOrder from "../pages/admin/protected/CustomerOrder";
import ReturnOrder from "../pages/admin/protected/ReturnOrder";
import FailedDelivery from "../pages/admin/protected/FailedDelivery";
import Cancellation from "../pages/admin/protected/Cancellation";

const routes = [
  {
    path: "/menu",
    title: "Menu",
    component: Menu,
  },
  {
    path: "/dashboard",
    title: "Dashboard",
    component: Dashboard,
  },
  {
    path: "/inventory",
    title: "Inventory",
    component: Inventory,
  },
  {
    path: "/product-list",
    title: "Product List",
    component: ProductList,
  },
  {
    path: "/users",
    title: "Users",
    component: Users,
  },
  {
    path: "/customer-order",
    title: "Customer Order",
    component: CustomerOrder,
  },
  {
    path: "/return-order",
    title: "Return Order",
    component: ReturnOrder,
  },
  {
    path: "/failed-delivery",
    title: "Failed Delivery",
    component: FailedDelivery,
  },
  {
    path: "/cancellation",
    title: "Cancellation",
    component: Cancellation,
  },
];

export default routes;
