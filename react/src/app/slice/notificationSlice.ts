import useAxiosClient from "@/axios-client";
import { ApiConfig, notificationState, } from "@/interface/InterfaceType";
import { createAsyncThunk, createSlice } from "@reduxjs/toolkit";


const axiosClient = useAxiosClient();

const initialState: notificationState = {
    notificationData: {},
    status: "",
    loading: false,
    notificationMessage: "",
    error: false,
}

const notificationSlice = createSlice({
    name: "notification",
    initialState,
    reducers: {

    },
    extraReducers: (builder) => {
        builder
            .addCase(getNotificationData.pending, (state) => {
                state.status = "notification/loading";
                state.loading = true;
                state.error = null;
            })
            .addCase(getNotificationData.fulfilled, (state, action) => {
                state.status = "notification/success";
                state.loading = false;
                state.notificationData = action.payload
                state.notificationMessage = action.payload
            })
            .addCase(getNotificationData.rejected, (state, action) => {
                state.status = "notification/failed";
                state.loading = false;
                state.error = action.payload
            })

    }

})

export const getNotificationData = createAsyncThunk("notification/getNotificationData", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export default notificationSlice.reducer