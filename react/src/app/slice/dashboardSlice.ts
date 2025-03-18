import { createAsyncThunk, createSlice } from "@reduxjs/toolkit";
import { ApiConfig, DashboardState } from "@/interface/InterfaceType"
import useAxiosClient from "@/axios-client";

const axiosClient = useAxiosClient();



const initialState: DashboardState = {
    data: {},
    status: "",
    error: false,
    voidLoading: false,
    voidMessage: ""
}


const dashboardSlice = createSlice({
    name: "dashboard",
    initialState,
    reducers: {
        resetLoading: (state) => {
            state.status = "idle";
        }
    },
    extraReducers: (builder) => {

        //* GET INVENTORY 
        builder
            .addCase(getDashboardData.pending, (state) => {
                state.status = "getDashboardData/loading";
                state.error = null
            })
            .addCase(getDashboardData.fulfilled, (state, action) => {
                state.status = "getDashboardData/success";
                state.data = action.payload
            })
            .addCase(getDashboardData.rejected, (state, action) => {
                state.status = "getDashboardData/failed";
                state.error = action.payload
            })

        //* VOID 
        builder
            .addCase(voidPaid.pending, (state) => {
                state.status = "voidPaid/loading";
                state.voidLoading = true;
                state.error = null
            })
            .addCase(voidPaid.fulfilled, (state, action) => {
                state.status = "voidPaid/success";
                state.voidMessage = action.payload;
                state.voidLoading = false;
                state.data = action.payload
            })
            .addCase(voidPaid.rejected, (state, action) => {
                state.status = "voidPaid/failed";
                state.voidLoading = false;
                state.error = action.payload
            })

    }
})

//* GET INVENTORY CHILD
export const getDashboardData = createAsyncThunk("dashboard/getDashboardData", async (apiconfig: ApiConfig, { rejectWithValue }) => {
    const { page = 1, limit = 10, search = "" } = apiconfig;
    try {
        const res = await axiosClient({
            url: `${apiconfig.url}/?page=${page}&limit=${limit}&search=${search}`,
            method: "GET"
        })

        return res.data
    } catch (error: any) {
        console.log(error);
        return rejectWithValue(error.response.data)
    }
});

//* VOICE PAID
export const voidPaid = createAsyncThunk("dashboard/voidPaid", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            url: apiconfig.url,
            method: apiconfig.method,
            data: apiconfig.data
        })

        return res.data
    } catch (error: any) {
        console.log(error);
        return rejectWithValue(error.response.data)
    }
})



export const dashboardData = (state: any) => state.dashboard.data
export const dashboardStatus = (state: any) => state.dashboard.status;
export const dashboardError = (state: any) => state.dashboard.error



export default dashboardSlice.reducer