import useAxiosClient from "@/axios-client";
import { ApiConfig, voucherState, } from "@/interface/InterfaceType";
import { createAsyncThunk, createSlice } from "@reduxjs/toolkit";


const axiosClient = useAxiosClient();

const initialState: voucherState = {
    voucherData: {},
    voucherChildData: {},
    status: "",
    message: "",
    loading: false,
    error: false,
    addVoucherLoading: false,
    addVoucherMessage: "",
    addVoucherError: "",
    editVoucherLoading: false,
    editVoucherMessage: "",
    editVoucherError: false,
    deleteVoucherLoading: false,
    deleteVoucherMessage: "",
    deleteVoucherError: false,
    childVoucherLoading: false,
    childVoucherMessage: "",
    childVoucherError: false,
    addChildVoucherLoading: false,
    addChildVoucherMessage: "",
    addChildVoucherError: false,
    updateChildVoucherLoading: false,
    updateChildVoucherMessage: "",
    updateChildVoucherError: false,
    deleteChildVoucherLoading: false,
    deleteChildVoucherMessage: "",
    deleteChildVoucherError: false,
}

const voucherSlice = createSlice({
    name: "voucher",
    initialState,
    reducers: {

    },
    extraReducers: (builder) => {

        builder
            .addCase(getVoucherData.pending, (state) => {
                state.status = "getVoucherData/loading";
                state.loading = true;
                state.error = false;
            })
            .addCase(getVoucherData.fulfilled, (state, action) => {
                state.status = "getVoucherData/success";
                state.loading = false;
                state.message = action.payload;
                state.voucherData = action.payload
            })
            .addCase(getVoucherData.rejected, (state, action) => {
                state.status = "getVoucherData/failed";
                state.loading = false;
                state.error = action.payload
            })

        builder
            .addCase(addVoucherData.pending, (state) => {
                state.status = "addVoucherData/loading";
                state.addVoucherLoading = true;
                state.error = false;
                state.addVoucherError = null;
            })
            .addCase(addVoucherData.fulfilled, (state, action) => {
                state.status = "addVoucherData/success";
                state.addVoucherLoading = false;
                state.addVoucherMessage = action.payload;
                state.voucherData = action.payload;
                state.addVoucherError = null;
            })
            .addCase(addVoucherData.rejected, (state, action) => {
                state.status = "addVoucherData/failed";
                state.addVoucherLoading = false;
                state.addVoucherError = action.payload
            })
        builder
            .addCase(editVoucherData.pending, (state) => {
                state.status = "editVoucherData/loading";
                state.editVoucherLoading = true;
                state.error = false;
            })
            .addCase(editVoucherData.fulfilled, (state, action) => {
                state.status = "editVoucherData/success";
                state.editVoucherLoading = false;
                state.editVoucherMessage = action.payload;
                state.voucherData = action.payload
            })
            .addCase(editVoucherData.rejected, (state, action) => {
                state.status = "editVoucherData/failed";
                state.editVoucherLoading = false;
                state.editVoucherError = action.payload
            })

        builder
            .addCase(deleteVoucherData.pending, (state) => {
                state.status = "deleteVoucherData/loading";
                state.deleteVoucherLoading = true;
                state.deleteVoucherError = false;
            })
            .addCase(deleteVoucherData.fulfilled, (state, action) => {
                state.status = "deleteVoucherData/success";
                state.deleteVoucherLoading = false;
                state.deleteVoucherMessage = action.payload;
                state.voucherData = action.payload
            })
            .addCase(deleteVoucherData.rejected, (state, action) => {
                state.status = "deleteVoucherData/failed";
                state.deleteVoucherLoading = false;
                state.deleteVoucherError = action.payload
            })

        builder
            .addCase(getChildVoucherData.pending, (state) => {
                state.status = "getChildVoucherData/loading";
                state.childVoucherLoading = true;
                state.childVoucherError = false;
            })
            .addCase(getChildVoucherData.fulfilled, (state, action) => {
                state.status = "getChildVoucherData/success";
                state.childVoucherLoading = false;
                state.childVoucherMessage = action.payload;
                state.voucherChildData = action.payload
            })
            .addCase(getChildVoucherData.rejected, (state, action) => {
                state.status = "getChildVoucherData/failed";
                state.childVoucherLoading = false;
                state.childVoucherError = action.payload
            })

        builder
            .addCase(addChildVoucherData.pending, (state) => {
                state.status = "addChildVoucherData/loading";
                state.addChildVoucherLoading = true;
                state.childVoucherError = false;
            })
            .addCase(addChildVoucherData.fulfilled, (state, action) => {
                state.status = "addChildVoucherData/success";
                state.addChildVoucherLoading = false;
                state.addChildVoucherMessage = action.payload;
                state.voucherChildData = action.payload
            })
            .addCase(addChildVoucherData.rejected, (state, action) => {
                state.status = "addChildVoucherData/failed";
                state.addChildVoucherLoading = false;
                state.addChildVoucherError = action.payload
            })

        builder
            .addCase(updateChildVoucherData.pending, (state) => {
                state.status = "updateChildVoucherData/loading";
                state.updateChildVoucherLoading = true;
                state.updateChildVoucherError = false;
            })
            .addCase(updateChildVoucherData.fulfilled, (state, action) => {
                state.status = "updateChildVoucherData/success";
                state.updateChildVoucherLoading = false;
                state.updateChildVoucherMessage = action.payload;
                state.voucherChildData = action.payload
            })
            .addCase(updateChildVoucherData.rejected, (state, action) => {
                state.status = "updateChildVoucherData/failed";
                state.updateChildVoucherLoading = false;
                state.updateChildVoucherError = action.payload
            })

        builder
            .addCase(deleteChildVoucherData.pending, (state) => {
                state.status = "deleteChildVoucherData/loading";
                state.deleteChildVoucherLoading = true;
                state.deleteChildVoucherError = false;
            })
            .addCase(deleteChildVoucherData.fulfilled, (state, action) => {
                state.status = "deleteChildVoucherData/success";
                state.deleteChildVoucherLoading = false;
                state.deleteChildVoucherMessage = action.payload;
                state.voucherChildData = action.payload
            })
            .addCase(deleteChildVoucherData.rejected, (state, action) => {
                state.status = "deleteChildVoucherData/failed";
                state.deleteChildVoucherLoading = false;
                state.deleteChildVoucherError = action.payload
            })

    }

})

export const getVoucherData = createAsyncThunk("voucher/getVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: ApiConfig.url,
            method: ApiConfig.method
        })

        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const addVoucherData = createAsyncThunk("voucher/addVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const deleteVoucherData = createAsyncThunk("voucher/deleteVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const getChildVoucherData = createAsyncThunk("voucher/getChildVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    const { page = 1, limit = 10, search = "" } = ApiConfig;

    try {
        const res = await axiosClient({
            url: `voucher/parent/items/show/${ApiConfig.url}/?page=${page}&limit=${limit}&search=${search}`,
            method: ApiConfig.method,
            data: ApiConfig.data
        })

        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const addChildVoucherData = createAsyncThunk("voucher/addChildVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const updateChildVoucherData = createAsyncThunk("voucher/updateChildVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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


export const deleteChildVoucherData = createAsyncThunk("voucher/deleteChildVoucherData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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



export default voucherSlice.reducer