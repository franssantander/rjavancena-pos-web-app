import { configureStore, combineReducers } from "@reduxjs/toolkit";
import { persistStore, persistReducer, FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER } from "redux-persist";
import storage from "redux-persist/lib/storage";
import appReducer from "../common/appSlice";
import userReducer from "@/app/slice/userSlice"
import inventoryReducer from "@/app/slice/inventorySlice"
import menuReducer from "@/app/slice/menuSlice"
import usersManagementReducer from "@/app/slice/usersManagementSlice"
import dashboardReducer from "@/app/slice/dashboardSlice"
import customerReducer from "@/app/slice/customerSlice"
import logsReducer from "@/app/slice/LogsSlice"
import voucherReducer from "@/app/slice/voucherSlice"
import expensesReducer from "@/app/slice/expensesSlice"
import notificationReducer from "@/app/slice/notificationSlice"

const persistConfig = {
  key: "root",
  storage,
};

const rootReducer = combineReducers({
  app: appReducer,
  user: userReducer,
  menu: menuReducer,
  inventory: inventoryReducer,
  usersManagement: usersManagementReducer,
  dashboard: dashboardReducer,
  customer: customerReducer,
  logs: logsReducer,
  voucher: voucherReducer,
  expenses: expensesReducer,
  notification: notificationReducer,
});

const persistedReducer = persistReducer<any>(persistConfig, rootReducer);

const store = configureStore({
  reducer: persistedReducer,
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
      },
    }),
});

export { store };

export const persistor = persistStore(store);

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;

export default store;
