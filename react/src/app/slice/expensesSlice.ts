import useAxiosClient from "@/axios-client";
import { ApiConfig, expensesState, } from "@/interface/InterfaceType";
import { createAsyncThunk, createSlice } from "@reduxjs/toolkit";


const axiosClient = useAxiosClient();

const initialState: expensesState = {
    expensesData: {},
    childExpensesData: {},
    status: "",
    loading: false,
    message: "",
    error: false,
    addExpensesLoading: false,
    addExpensesMessage: "",
    addExpensesError: false,
    updateExpensesLoading: false,
    updateExpensesMessage: "",
    updateExpensesError: false,
    deleteExpensesLoading: false,
    deleteExpensesMessage: "",
    deleteExpensesError: false,
    getChildExpensesLoading: false,
    getChildExpensesMessage: "",
    getChildExpensesError: false,
    addFileExpensesLoading: false,
    addFileExpensesMessage: "",
    addFileExpensesError: false,
    deleteImgExpensesLoading: false,
    deleteImgExpensesMessage: "",
    deleteImgExpensesError: false,
    deleteFileExpensesLoading: false,
    deleteFileExpensesMessage: "",
    deleteFileExpensesError: false,
    downloadFileExpensesLoading: false,
    downloadFileExpensesMessage: "",
    downloadFileExpensesError: false,
}

const expensesSlice = createSlice({
    name: "expenses",
    initialState,
    reducers: {

    },
    extraReducers: (builder) => {
        builder
            .addCase(getExpensesData.pending, (state) => {
                state.status = "expenses/loading";
                state.loading = true;
                state.error = null;
            })
            .addCase(getExpensesData.fulfilled, (state, action) => {
                state.status = "expenses/success";
                state.loading = false;
                state.message = action.payload
                state.expensesData = action.payload
            })
            .addCase(getExpensesData.rejected, (state, action) => {
                state.status = "expenses/failed";
                state.loading = false;
                state.error = action.payload
            })

        builder
            .addCase(addExpensesData.pending, (state) => {
                state.status = "addExpensesData/loading";
                state.addExpensesLoading = true;
                state.addExpensesError = false;
                state.error = null;
            })
            .addCase(addExpensesData.fulfilled, (state, action) => {
                state.status = "addExpensesData/success";
                state.addExpensesLoading = false;
                state.addExpensesError = false;
                state.addExpensesMessage = action.payload
                state.expensesData = action.payload
            })
            .addCase(addExpensesData.rejected, (state, action) => {
                state.status = "addExpensesData/failed";
                state.addExpensesLoading = false;
                state.addExpensesError = action.payload
            })

        builder
            .addCase(updateExpensesData.pending, (state) => {
                state.status = "updateExpensesData/loading";
                state.updateExpensesLoading = true;
                state.error = null;
            })
            .addCase(updateExpensesData.fulfilled, (state, action) => {
                state.status = "updateExpensesData/success";
                state.updateExpensesLoading = false;
                state.updateExpensesMessage = action.payload
                state.expensesData = action.payload
            })
            .addCase(updateExpensesData.rejected, (state, action) => {
                state.status = "updateExpensesData/failed";
                state.updateExpensesLoading = false;
                state.updateExpensesError = action.payload
            })

        builder
            .addCase(deleteExpensesData.pending, (state) => {
                state.status = "deleteExpensesData/loading";
                state.deleteExpensesLoading = true;
                state.error = null;
            })
            .addCase(deleteExpensesData.fulfilled, (state, action) => {
                state.status = "deleteExpensesData/success";
                state.deleteExpensesLoading = false;
                state.deleteExpensesMessage = action.payload
                state.expensesData = action.payload
            })
            .addCase(deleteExpensesData.rejected, (state, action) => {
                state.status = "deleteExpensesData/failed";
                state.deleteExpensesLoading = false;
                state.deleteExpensesError = action.payload
            })

        builder
            .addCase(getChildExpensesData.pending, (state) => {
                state.status = "getChildExpensesData/loading";
                state.getChildExpensesLoading = true;
                state.error = null;
            })
            .addCase(getChildExpensesData.fulfilled, (state, action) => {
                state.status = "getChildExpensesData/success";
                state.getChildExpensesLoading = false;
                state.getChildExpensesMessage = action.payload
                state.childExpensesData = action.payload
            })
            .addCase(getChildExpensesData.rejected, (state, action) => {
                state.status = "getChildExpensesData/failed";
                state.getChildExpensesLoading = false;
                state.getChildExpensesError = action.payload
            })

        builder
            .addCase(addFile.pending, (state) => {
                state.status = "addFile/loading";
                state.addFileExpensesLoading = true;
                state.error = null;
            })
            .addCase(addFile.fulfilled, (state, action) => {
                state.status = "addFile/success";
                state.addFileExpensesLoading = false;
                state.addFileExpensesMessage = action.payload
                state.childExpensesData = action.payload
            })
            .addCase(addFile.rejected, (state, action) => {
                state.status = "addFile/failed";
                state.addFileExpensesLoading = false;
                state.addFileExpensesError = action.payload
            })

        builder
            .addCase(deleteImg.pending, (state) => {
                state.status = "deleteImg/loading";
                state.deleteImgExpensesLoading = true;
                state.error = null;
            })
            .addCase(deleteImg.fulfilled, (state, action) => {
                state.status = "deleteImg/success";
                state.deleteImgExpensesLoading = false;
                state.deleteImgExpensesMessage = action.payload
                state.childExpensesData = action.payload
            })
            .addCase(deleteImg.rejected, (state, action) => {
                state.status = "deleteImg/failed";
                state.deleteImgExpensesLoading = false;
                state.deleteImgExpensesError = action.payload
            })

        builder
            .addCase(deleteFile.pending, (state) => {
                state.status = "deleteFile/loading";
                state.deleteFileExpensesLoading = true;
                state.error = null;
            })
            .addCase(deleteFile.fulfilled, (state, action) => {
                state.status = "deleteFile/success";
                state.deleteFileExpensesLoading = false;
                state.deleteFileExpensesMessage = action.payload
                state.childExpensesData = action.payload
            })
            .addCase(deleteFile.rejected, (state, action) => {
                state.status = "deleteFile/failed";
                state.deleteFileExpensesLoading = false;
                state.deleteFileExpensesError = action.payload
            })

    }

})

export const getExpensesData = createAsyncThunk("expenses/getExpensesData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    const { page = 1, limit = 10, search = "" } = ApiConfig;

    try {
        const res = await axiosClient({
            url: `${ApiConfig.url}/?page=${page}&limit=${limit}&search=${search}`,
            method: ApiConfig.method
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const addExpensesData = createAsyncThunk("expenses/addExpensesData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const updateExpensesData = createAsyncThunk("expenses/updateExpensesData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const deleteExpensesData = createAsyncThunk("expenses/deleteExpensesData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const getChildExpensesData = createAsyncThunk("expenses/getChildExpensesData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    const { page = 1, limit = 10, search = "" } = ApiConfig;

    try {
        const res = await axiosClient({
            url: `expenses/expense/show/${ApiConfig.url}/?page=${page}&limit=${limit}&search=${search}`,
            method: ApiConfig.method,
        })
        console.log(res)
        return res.data
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response.data)
    }
})

export const addFile = createAsyncThunk("expenses/addFile", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    console.log(ApiConfig.url);

    try {
        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
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

export const deleteImg = createAsyncThunk("expenses/deleteImg", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
    console.log(ApiConfig.url);

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

export const deleteFile = createAsyncThunk("expenses/deleteFile", async (ApiConfig: ApiConfig, { rejectWithValue }) => {

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


// export const logsData = (state: any) => state?.logs?.logsData

export default expensesSlice.reducer