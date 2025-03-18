import { useAuth } from "../app/AuthProvider";

import SignIn from "../pages/user/public/SignIn";
import Welcome from "../pages/admin/protected/Welcome";
import SignUp from "../pages/user/public/SignUp";
import ForgotPassword from "../pages/user/public/ForgotPassword";

import { RouterProvider, createBrowserRouter } from "react-router-dom";
import Layout from "../containers/Layout";
import PublicLayout from "../pages/user/PublicLayout";
import VerifyEmail from "../pages/user/protected/VerifyEmail";
import UpdatePassword from "../pages/user/protected/UpdatePassword";
import CashierCustomer from "@/features/admin/cashier/CashierCustomer";

//* Admin Pages Element

const Routes: React.FC = () => {
  const { authUser } = useAuth();

  //* Public Routes
  const publicRoutes = [
    {
      path: "/",
      element: <PublicLayout />,
      children: [
        {
          path: "/",
          element: <SignIn />,
        },
        {
          path: "/welcome",
          element: <Welcome />,
        },
        {
          path: "/signin",
          element: <SignIn />,
        },
        {
          path: "/signup",
          element: <SignUp />,
        },
        {
          path: "/forgot-password",
          element: <ForgotPassword />,
        },
      ],
    },
  ];

  //* Register Auth Routes
  const registerAuthUser = [
    {
      path: "/signup/verify-email",
      element: <VerifyEmail />,
    },
    {
      path: "/update-password",
      element: <UpdatePassword />,
    },
  ];

  //* Authenticated Admin Routes
  const authAdmin = [
    {
      path: "/app/*",
      element: <Layout />,
    },
    {
      path: "/app/customer-payment",
      element: <CashierCustomer />,
    },
  ];

  const noAuthUserRoutes = [
    {
      path: "/signin",
      element: <SignIn />,
    },
  ];

  const router = createBrowserRouter([
    ...publicRoutes,
    ...(!authUser.token ? noAuthUserRoutes : []),
    ...authAdmin,
    ...registerAuthUser,
  ]);

  return <RouterProvider router={router} />;
};

export default Routes;
