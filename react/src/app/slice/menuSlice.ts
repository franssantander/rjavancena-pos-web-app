import useAxiosClient from "@/axios-client";
import { menuState, ApiConfig } from "@/interface/InterfaceType";
import { createAsyncThunk, createSlice } from "@reduxjs/toolkit";


const axiosClient = useAxiosClient();

const initialState: menuState = {
    data: {},
    customerData: {},
    voucherData: {},
    status: "",
    loading: false,
    loadingAddMenuCart: false,
    addMenuCartMessage: "",
    loadingAddCart: false,
    loadingPurchase: false,
    updateCustomer: {},
    removeCustomer: {},
    removeProduct: {},
    error: false,
    getVoucherMessage: "",
    getVoucherLoading: false,
    getVoucherError: "",
    addVoucherMessage: "",
    addVoucherLoading: false,
    addVoucherError: "",
    editVoucherMessage: "",
    editVoucherLoading: false,
    editVoucherError: "",
    deleteVoucherMessage: "",
    deleteVoucherLoading: false,
    deleteVoucherError: "",
}

const menuSlice = createSlice({
    name: "menu",
    initialState,
    reducers: {
        setCustomerDisplay(state, action) {
            state.customerData = action.payload;
        },
        setAddCartLoading(state, action) {
            state.loadingAddCart = action.payload;
        }
    },
    extraReducers: (builder) => {
        builder
            .addCase(getMenuData.pending, (state) => {
                state.status = "menuData/loading";
                state.loading = true;
                state.error = null;
            })
            .addCase(getMenuData.fulfilled, (state, action) => {
                state.status = "menuData/success";
                state.loading = false;
                state.data = action.payload
            })
            .addCase(getMenuData.rejected, (state, action) => {
                state.status = "menuData/failed";
                state.loading = false;
                state.error = action.payload
            })

        builder
            .addCase(menuAddCart.pending, (state) => {
                state.status = "menuAddCart/loading";
                state.loadingAddCart = true;
                state.loadingAddMenuCart = true;
                state.error = null
            })
            .addCase(menuAddCart.fulfilled, (state, action) => {
                state.status = "menuAddCart/success";
                state.loadingAddCart = false;
                state.loadingAddMenuCart = false;
                state.addMenuCartMessage = action.payload;
                state.data = action.payload;
            })
            .addCase(menuAddCart.rejected, (state, action) => {
                state.status = "menuAddCart/failed";
                state.loadingAddCart = false;
                state.loadingAddMenuCart = false;
                state.addMenuCartMessage = action.payload;
                state.error = action.payload;
            })

        builder
            .addCase(getCustomerData.pending, (state) => {
                state.status = "customerData/loading"
                state.loading = true;
                state.error = null
            })
            .addCase(getCustomerData.fulfilled, (state, action) => {
                state.status = "customerData/success";
                state.loading = false;
                state.data = action.payload;
                state.customerData = action.payload;
            })
            .addCase(getCustomerData.rejected, (state, action) => {
                state.status = "customerData/failed";
                state.loading = false;
                state.error = action.payload;
            })

        builder
            .addCase(incrementQty.pending, (state) => {
                state.status = "incrementQty/loading"
                state.error = null
            })
            .addCase(incrementQty.fulfilled, (state, action) => {
                state.status = "incrementQty/success"
                state.data = action.payload
            })
            .addCase(incrementQty.rejected, (state, action) => {
                state.status = "incrementQty/failed",
                    state.error = action.payload
            })

        builder
            .addCase(decrementQty.pending, (state) => {
                state.status = "decrementQty/loading"
                state.error = null
            })
            .addCase(decrementQty.fulfilled, (state, action) => {
                state.status = "decrementQty/success"
                state.data = action.payload
            })
            .addCase(decrementQty.rejected, (state, action) => {
                state.status = "decrementQty/failed",
                    state.error = action.payload
            })

        builder
            .addCase(editCustomerName.pending, (state) => {
                state.status = "editCustomerName/loading"
                state.error = null
            })
            .addCase(editCustomerName.fulfilled, (state, action) => {
                state.status = "editCustomerName/success"
                state.data = action.payload
                state.updateCustomer = action.payload;
            })
            .addCase(editCustomerName.rejected, (state, action) => {
                state.status = "editCustomerName/failed";
                state.error = action.payload;
            })

        builder
            .addCase(removeCustomerName.pending, (state) => {
                state.status = "removeCustomerName/loading"
                state.error = null
            })
            .addCase(removeCustomerName.fulfilled, (state, action) => {
                state.status = "removeCustomerName/success";
                state.removeCustomer = action.payload;
                state.data = action.payload;
            })
            .addCase(removeCustomerName.rejected, (state, action) => {
                state.status = "removeCustomerName/failed";
                state.error = action.payload;
            })

        builder
            .addCase(deleteProduct.pending, (state) => {
                state.status = "deleteProduct/loading";
                state.error = null;
            })
            .addCase(deleteProduct.fulfilled, (state, action) => {
                state.status = "deleteProduct/success";
                state.removeProduct = action.payload;
                state.data = action.payload;
            })
            .addCase(deleteProduct.rejected, (state, action) => {
                state.status = "deleteProduct/failed";
                state.error = action.payload;
            })

        builder
            .addCase(placeOrder.pending, (state) => {
                state.status = "placeOrder/loading";
                state.loadingPurchase = true;
                state.error = null;
            })
            .addCase(placeOrder.fulfilled, (state, action) => {
                state.status = "placeOrder/success"
                state.loadingPurchase = false;
                state.data = action.payload
            })
            .addCase(placeOrder.rejected, (state, action) => {
                state.status = "placeOrder/failed";
                state.loadingPurchase = false;
                state.error = action.payload;
            })

        builder
            .addCase(getVoucherData.pending, (state) => {
                state.status = "getVoucherData/loading";
                state.addVoucherLoading = true;
                state.addVoucherError = null;
            })
            .addCase(getVoucherData.fulfilled, (state, action) => {
                state.status = "getVoucherData/success"
                state.addVoucherLoading = false;
                state.voucherData = action.payload
                state.addVoucherMessage = action.payload
            })
            .addCase(getVoucherData.rejected, (state, action) => {
                state.status = "getVoucherData/failed";
                state.addVoucherLoading = false;
                state.addVoucherError = action.payload;
            })

        builder
            .addCase(addVoucher.pending, (state) => {
                state.status = "addVoucher/loading";
                state.addVoucherLoading = true;
                state.addVoucherError = null;
            })
            .addCase(addVoucher.fulfilled, (state, action) => {
                state.status = "addVoucher/success"
                state.addVoucherLoading = false;
                state.voucherData = action.payload
                state.addVoucherMessage = action.payload
            })
            .addCase(addVoucher.rejected, (state, action) => {
                state.status = "addVoucher/failed";
                state.addVoucherLoading = false;
                state.addVoucherError = action.payload;
            })

        builder
            .addCase(editVoucherData.pending, (state) => {
                state.status = "editVoucherData/loading";
                state.editVoucherLoading = true;
                state.editVoucherError = null;
            })
            .addCase(editVoucherData.fulfilled, (state, action) => {
                state.status = "editVoucherData/success"
                state.editVoucherLoading = false;
                state.voucherData = action.payload
                state.editVoucherMessage = action.payload
            })
            .addCase(editVoucherData.rejected, (state, action) => {
                state.status = "editVoucherData/failed";
                state.editVoucherLoading = false;
                state.editVoucherError = action.payload;
            })

        builder
            .addCase(deleteVoucher.pending, (state) => {
                state.status = "deleteVoucher/loading";
                state.deleteVoucherLoading = true;
                state.deleteVoucherError = null;
            })
            .addCase(deleteVoucher.fulfilled, (state, action) => {
                state.status = "deleteVoucher/success"
                state.deleteVoucherLoading = false;
                state.data = action.payload
                state.deleteVoucherMessage = action.payload
            })
            .addCase(deleteVoucher.rejected, (state, action) => {
                state.status = "deleteVoucher/failed";
                state.deleteVoucherLoading = false;
                state.deleteVoucherError = action.payload;
            })
    }

})

export const getMenuData = createAsyncThunk("menu/getMenuData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const menuAddCart = createAsyncThunk("menu/menuAddCart", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const getCustomerData = createAsyncThunk("menu/getCustomerData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const incrementQty = createAsyncThunk("menu/incrementQty", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const decrementQty = createAsyncThunk("menu/decrementQty", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const editCustomerName = createAsyncThunk("menu/editCustomerName", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)

        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const removeCustomerName = createAsyncThunk("menu/removeCustomerName", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const deleteProduct = createAsyncThunk("menu/deleteProduct", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const placeOrder = createAsyncThunk("menu/placerOrder", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const getVoucherData = createAsyncThunk("menu/getVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const addVoucher = createAsyncThunk("menu/addVoucher", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const editVoucherData = createAsyncThunk("voucher/editVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })

        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const deleteVoucher = createAsyncThunk("menu/deleteVoucher", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method,
            data: ApiConfig.data
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const menuData = (state: any) => state.menu.data
export const loadingStatus = (state: any) => state.menu.status;
export const loading = (state: any) => state.menu.loading;
export const menuError = (state: any) => state.menu.error;
export const { setCustomerDisplay, setAddCartLoading } = menuSlice.actions;

export default menuSlice.reducer