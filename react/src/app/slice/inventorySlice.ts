import { createAsyncThunk, createSlice } from "@reduxjs/toolkit";
import { ApiConfig, InventoryState } from "@/interface/InterfaceType"
import useAxiosClient from "@/axios-client";

const axiosClient = useAxiosClient();

const initialState: InventoryState = {
    data: {},
    status: "",
    error: false,
    inventoryToastMessage: false,
    updateParentErrorMessage: false,
    updateChildMessage: false,
    updateChildLoading: false,
    loadingTable: false,
    loadingCreate: false,
    loadingUpdate: false,
    loadingCreateChild: false,
    inventoryLostData: {},
    inventoryFoundData: {},
    inventoryRestockData: {},
    loadingAddLost: false,
    loadingAddFound: false,
    loadingAddRestock: false,
    loadingUpdateLost: false,
    loadingDeleteLost: false,
    loadingUpdateFound: false,
    loadingDeleteFound: false,
    loadingUpdateRestock: false,
    loadingDeleteRestock: false,
}

const inventorySlice = createSlice({
    name: "inventory",
    initialState,
    reducers: {
        resetLoading: (state) => {
            state.status = "idle";
        },
        setInventoryLostData: (state, action: any) => {
            state.inventoryLostData = action.payload;
        },
    },
    extraReducers: (builder) => {

        //* GET INVENTORY 
        builder
            .addCase(getInventoryData.pending, (state) => {
                state.status = "getInventoryData/loading";
                state.error = null
            })
            .addCase(getInventoryData.fulfilled, (state, action) => {
                state.status = "getInventoryData/success";
                state.data = action.payload
            })
            .addCase(getInventoryData.rejected, (state, action) => {
                state.status = "getInventoryData/failed";
                state.error = action.payload
            })

        //* GET INVENTORY CHILD
        builder
            .addCase(getInventoryDataChild.pending, (state) => {
                state.status = "getInventoryDataChild/loading";
                state.loadingTable = true;
                state.error = null;
            })
            .addCase(getInventoryDataChild.fulfilled, (state, action) => {
                state.status = "getInventoryDataChild/success";
                state.loadingTable = false;
                state.data = action.payload;
            })
            .addCase(getInventoryDataChild.rejected, (state, action) => {
                state.status = "getInventoryDataChild/failed";
                state.loadingTable = false;
                state.error = action.payload
            })

        //* GET INVENTORY LOST CHILD
        builder
            .addCase(getInventoryLostDataChild.pending, (state) => {
                state.status = "getInventoryLostDataChild/loading";
                state.loadingTable = true;
                state.error = null;
            })
            .addCase(getInventoryLostDataChild.fulfilled, (state, action) => {
                state.status = "getInventoryLostDataChild/success";
                state.loadingTable = false;
                state.inventoryLostData = action.payload;
            })
            .addCase(getInventoryLostDataChild.rejected, (state, action) => {
                state.status = "getInventoryLostDataChild/failed";
                state.loadingTable = false;
                state.error = action.payload
            })

        //* GET INVENTORY FOUND CHILD
        builder
            .addCase(getInventoryFoundDataChild.pending, (state) => {
                state.status = "getInventoryFoundDataChild/loading";
                state.loadingTable = true;
                state.error = null;
            })
            .addCase(getInventoryFoundDataChild.fulfilled, (state, action) => {
                state.status = "getInventoryFoundDataChild/success";
                state.loadingTable = false;
                state.inventoryFoundData = action.payload;
            })
            .addCase(getInventoryFoundDataChild.rejected, (state, action) => {
                state.status = "getInventoryFoundDataChild/failed";
                state.loadingTable = false;
                state.error = action.payload
            })


        //* GET INVENTORY RESTOCK CHILD
        builder
            .addCase(getInventoryRestockDataChild.pending, (state) => {
                state.status = "getInventoryRestockDataChild/loading";
                state.loadingTable = true;
                state.error = null;
            })
            .addCase(getInventoryRestockDataChild.fulfilled, (state, action) => {
                state.status = "getInventoryRestockDataChild/success";
                state.loadingTable = false;
                state.inventoryRestockData = action.payload;
            })
            .addCase(getInventoryRestockDataChild.rejected, (state, action) => {
                state.status = "getInventoryRestockDataChild/failed";
                state.loadingTable = false;
                state.error = action.payload
            })

        //* UPDATE INVENTORY 
        builder
            .addCase(updateInventoryParent.pending, (state) => {
                state.status = "updateInventoryParent/loading";
                state.loadingUpdate = true;
                state.error = null;
            })
            .addCase(updateInventoryParent.fulfilled, (state, action) => {
                state.status = "updateInventoryParent/success";
                state.loadingUpdate = false;
                state.data = action.payload;
            })
            .addCase(updateInventoryParent.rejected, (state, action) => {
                state.status = "updateInventoryParent/failed";
                state.loadingUpdate = false;
                state.updateParentErrorMessage = action.payload;
            })

        //* CREATE INVENTORY 
        builder
            .addCase(createInventoryData.pending, (state) => {
                state.status = "createInventoryParent/loading";
                state.loadingCreate = true;
                state.error = null;
            })
            .addCase(createInventoryData.fulfilled, (state, action) => {
                state.status = "createInventoryParent/success";
                state.loadingCreate = false;
                state.data = action.payload;
            })
            .addCase(createInventoryData.rejected, (state, action) => {
                state.status = "createInventoryParent/failed";
                state.loadingCreate = false;
                state.error = action.payload;
            })

        //* CREATE INVENTORY CHILD
        builder
            .addCase(createInventoryChildData.pending, (state) => {
                state.status = "createInventoryChild/loading";
                state.loadingCreateChild = true;
                state.error = null;
            })
            .addCase(createInventoryChildData.fulfilled, (state, action) => {
                state.status = "createInventoryChild/success";
                state.loadingCreateChild = false;
                state.data = action.payload;
            })
            .addCase(createInventoryChildData.rejected, (state, action) => {
                state.status = "createInventoryChild/failed";
                state.loadingCreateChild = false;
                state.error = action.payload;
            })

        //* UPDATE INVENTORY CHILD
        builder
            .addCase(updateInventoryChild.pending, (state) => {
                state.status = "updateInventoryChild/loading";
                state.error = null;
                state.updateChildLoading = true;
            })
            .addCase(updateInventoryChild.fulfilled, (state, action) => {
                state.status = "updateInventoryChild/success";
                state.data = action.payload;
                state.updateChildMessage = action.payload;
                state.updateChildLoading = false;
            })
            .addCase(updateInventoryChild.rejected, (state, action) => {
                state.status = "updateInventoryChild/failed";
                state.error = action.payload;
                state.updateChildLoading = false;
            })

        //* DELETE INVENTORY 
        builder
            .addCase(deleteInventoryData.pending, (state) => {
                state.status = "deleteInventoryData/loading";
                state.error = null;
            })
            .addCase(deleteInventoryData.fulfilled, (state, action) => {
                state.status = "deleteInventoryData/success";
                state.data = action.payload;
            })
            .addCase(deleteInventoryData.rejected, (state, action) => {
                state.status = "deleteInventoryData/failed";
                state.error = action.payload
            })

        //* DELETE INVENTORY CHILD
        builder
            .addCase(deleteInventoryChildData.pending, (state) => {
                state.status = "deleteInventoryChildData/loading";
                state.error = null;
            })
            .addCase(deleteInventoryChildData.fulfilled, (state, action) => {
                state.status = "deleteInventoryChildData/success";
                state.data = action.payload;
            })
            .addCase(deleteInventoryChildData.rejected, (state, action) => {
                state.status = "deleteInventoryChildData/failed";
                state.error = action.payload
            })

        //* ADD INVENTORY LOST
        builder
            .addCase(addInventoryLost.pending, (state) => {
                state.status = "addInventoryLost/loading";
                state.loadingAddLost = true;
                state.error = null;
            })
            .addCase(addInventoryLost.fulfilled, (state, action) => {
                state.status = "addInventoryLost/success";
                state.loadingAddLost = false;
                state.data = action.payload;
                state.inventoryToastMessage = action.payload;
            })
            .addCase(addInventoryLost.rejected, (state, action) => {
                state.status = "addInventoryLost/failed";
                state.loadingAddLost = false;
                state.error = action.payload
            })


        //* ADD INVENTORY FOUND
        builder
            .addCase(addInventoryFound.pending, (state) => {
                state.status = "addInventoryFound/loading";
                state.loadingAddFound = true;
                state.error = null;
            })
            .addCase(addInventoryFound.fulfilled, (state, action) => {
                state.status = "addInventoryFound/success";
                state.loadingAddFound = false;
                state.data = action.payload;
                state.inventoryToastMessage = action.payload;
            })
            .addCase(addInventoryFound.rejected, (state, action) => {
                state.status = "addInventoryFound/failed";
                state.loadingAddFound = false;
                state.error = action.payload
            })

        //* ADD INVENTORY RESTOCK
        builder
            .addCase(addInventoryRestock.pending, (state) => {
                state.status = "addInventoryRestock/loading";
                state.loadingAddRestock = true;
                state.error = null;
            })
            .addCase(addInventoryRestock.fulfilled, (state, action) => {
                state.status = "addInventoryRestock/success";
                state.loadingAddRestock = false;
                state.data = action.payload;
                state.inventoryToastMessage = action.payload;
            })
            .addCase(addInventoryRestock.rejected, (state, action) => {
                state.status = "addInventoryRestock/failed";
                state.loadingAddRestock = false;
                state.error = action.payload
            })

        //* UPDATE INVENTORY LOST
        builder
            .addCase(updateInventoryLost.pending, (state) => {
                state.status = "updateInventoryLost/loading";
                state.error = null;
                state.loadingUpdateLost = true;
            })
            .addCase(updateInventoryLost.fulfilled, (state, action) => {
                state.status = "updateInventoryLost/success";
                state.data = action.payload;
                state.loadingUpdateLost = false;
            })
            .addCase(updateInventoryLost.rejected, (state, action) => {
                state.status = "updateInventoryLost/failed";
                state.error = action.payload;
                state.loadingUpdateLost = false;
            })


        //* DELETE INVENTORY LOST
        builder
            .addCase(deleteInventoryLost.pending, (state) => {
                state.status = "deleteInventoryLost/loading";
                state.error = null;
                state.loadingDeleteLost = true;
            })
            .addCase(deleteInventoryLost.fulfilled, (state, action) => {
                state.status = "deleteInventoryLost/success";
                state.data = action.payload;
                state.loadingDeleteLost = false;
            })
            .addCase(deleteInventoryLost.rejected, (state, action) => {
                state.status = "deleteInventoryLost/failed";
                state.error = action.payload;
                state.loadingDeleteLost = false;
            })

        //* UPDATE INVENTORY FOUND
        builder
            .addCase(updateInventoryFound.pending, (state) => {
                state.status = "updateInventoryFound/loading";
                state.error = null;
                state.loadingUpdateFound = true;
            })
            .addCase(updateInventoryFound.fulfilled, (state, action) => {
                state.status = "updateInventoryFound/success";
                state.data = action.payload;
                state.loadingUpdateFound = false;
            })
            .addCase(updateInventoryFound.rejected, (state, action) => {
                state.status = "updateInventoryFound/failed";
                state.error = action.payload;
                state.loadingUpdateFound = false;
            })

        //* DELETE INVENTORY FOUND
        builder
            .addCase(deleteInventoryFound.pending, (state) => {
                state.status = "deleteInventoryFound/loading";
                state.error = null;
                state.loadingDeleteFound = true;
            })
            .addCase(deleteInventoryFound.fulfilled, (state, action) => {
                state.status = "deleteInventoryFound/success";
                state.data = action.payload;
                state.loadingDeleteFound = false;
            })
            .addCase(deleteInventoryFound.rejected, (state, action) => {
                state.status = "deleteInventoryFound/failed";
                state.error = action.payload;
                state.loadingDeleteFound = false;
            })

        //* UPDATE INVENTORY RESTOCK
        builder
            .addCase(updateInventoryRestock.pending, (state) => {
                state.status = "updateInventoryRestock/loading";
                state.error = null;
                state.loadingUpdateRestock = true;
            })
            .addCase(updateInventoryRestock.fulfilled, (state, action) => {
                state.status = "updateInventoryRestock/success";
                state.data = action.payload;
                state.loadingUpdateRestock = false;
            })
            .addCase(updateInventoryRestock.rejected, (state, action) => {
                state.status = "updateInventoryRestock/failed";
                state.error = action.payload;
                state.loadingUpdateRestock = false;
            })

        //* DELETE INVENTORY RESTOCK
        builder
            .addCase(deleteInventoryRestock.pending, (state) => {
                state.status = "deleteInventoryRestock/loading";
                state.error = null;
                state.loadingDeleteRestock = true;
            })
            .addCase(deleteInventoryRestock.fulfilled, (state, action) => {
                state.status = "deleteInventoryRestock/success";
                state.data = action.payload;
                state.loadingDeleteRestock = false;
            })
            .addCase(deleteInventoryRestock.rejected, (state, action) => {
                state.status = "deleteInventoryRestock/failed";
                state.error = action.payload;
                state.loadingDeleteRestock = false;
            })
    }
})


//* GET INVENTORY
export const getInventoryData = createAsyncThunk("inventory/getInventoryData", async (apiconfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            url: apiconfig.url,
            method: "GET"
        })

        return res.data
    } catch (error: any) {
        console.log(error);
        return rejectWithValue(error.response.data)
    }
})

//* GET INVENTORY CHILD
export const getInventoryDataChild = createAsyncThunk(
    "inventory/getInventoryDataChild",
    async (apiconfig: ApiConfig, { rejectWithValue }) => {
        const { page = 1, limit = 10, search = "" } = apiconfig;

        try {
            const res = await axiosClient({
                url: `inventory/parent/product/show/${apiconfig.url}/?page=${page}&limit=${limit}&search=${search}`,
                method: "GET",
            });

            return res.data;
        } catch (error: any) {
            return rejectWithValue(error.response.data);
        }
    }
);

export const getInventoryLostDataChild = createAsyncThunk("inventory/getInventoryLostDataChild", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    console.log(apiconfig)
    try {
        const res = await axiosClient({
            url: apiconfig.url,
            method: "GET"
        })

        return res.data
    } catch (error: any) {
        console.log(error);
        return rejectWithValue(error.response.data)
    }
})

export const getInventoryFoundDataChild = createAsyncThunk("inventory/getInventoryFoundDataChild", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    console.log(apiconfig)
    try {
        const res = await axiosClient({
            url: apiconfig.url,
            method: "GET"
        })

        return res.data
    } catch (error: any) {
        console.log(error);
        return rejectWithValue(error.response.data)
    }
})

export const getInventoryRestockDataChild = createAsyncThunk("inventory/getInventoryRestockDataChild", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    console.log(apiconfig)
    try {
        const res = await axiosClient({
            url: apiconfig.url,
            method: "GET"
        })

        return res.data
    } catch (error: any) {
        console.log(error);
        return rejectWithValue(error.response.data)
    }
})

//* CREATE INVENTORY
export const createInventoryData = createAsyncThunk("inventory/createInventoryData", async (apiconfig: ApiConfig, { rejectWithValue }) => {
    try {

        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
            url: apiconfig.url,
            method: apiconfig.method,
            data: apiconfig.data
        })

        return res.data;
    } catch (error: any) {
        console.log(error)
        return rejectWithValue(error.response?.data?.message || error.response.data.message);
    }
})

//* CREATE INVENTORY CHILD
export const createInventoryChildData = createAsyncThunk("inventory/createInventoryChildData", async (apiconfig: ApiConfig, { rejectWithValue }) => {
    console.log(apiconfig.data)
    try {
        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
            url: apiconfig.url,
            method: apiconfig.method,
            data: apiconfig.data
        })

        return res.data;
    } catch (error: any) {
        return rejectWithValue(error.response?.data?.message || error.response.data.message);
    }
})


//* UPDATE EDIT INVENTORY DATA
export const updateInventoryParent = createAsyncThunk("inventory/updateInventoryParent", async (apiconfig: ApiConfig, { rejectWithValue }) => {
    try {
        console.log("apiconfig: ", apiconfig)
        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig.data
        });

        return res.data;
    } catch (error: any) {
        console.log(error);
        return rejectWithValue(error.response?.data?.message || error.message);
    }
});

export const updateInventoryChild = createAsyncThunk("inventory/updateInventoryChildData", async (apiconfig: ApiConfig, { rejectWithValue }) => {
    try {
        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
            method: apiconfig?.method,
            url: apiconfig?.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        return rejectWithValue(error.response?.data?.message || error.message);
    }
})

//* DELETE INVENTORY DATA
export const deleteInventoryData = createAsyncThunk("inventory/deleteInventoryData", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* DELETE INVENTORY CHILD DATA
export const deleteInventoryChildData = createAsyncThunk("inventory/deleteInventoryChildData", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* ADD INVENTORY LOST
export const addInventoryLost = createAsyncThunk("inventory/addInventoryLost", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* ADD INVENTORY FOUND
export const addInventoryFound = createAsyncThunk("inventory/addInventoryFound", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* ADD INVENTORY RESTOCK
export const addInventoryRestock = createAsyncThunk("inventory/addInventoryRestock", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            headers: { "Content-Type": "multipart/form-data", },
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* UPDATE INVENTORY LOST
export const updateInventoryLost = createAsyncThunk("inventory/updateInventoryLost", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* DELETE INVENTORY LOST
export const deleteInventoryLost = createAsyncThunk("inventory/deleteInventoryLost", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* UPDATE INVENTORY FOUND
export const updateInventoryFound = createAsyncThunk("inventory/updateInventoryFound", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* DELETE INVENTORY FOUND
export const deleteInventoryFound = createAsyncThunk("inventory/deleteInventoryFound", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* UPDATE INVENTORY RESTOCK
export const updateInventoryRestock = createAsyncThunk("inventory/updateInventoryRestock", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})

//* DELETE INVENTORY RESTOCK
export const deleteInventoryRestock = createAsyncThunk("inventory/deleteInventoryRestock", async (apiconfig: ApiConfig, { rejectWithValue }) => {

    try {
        const res = await axiosClient({
            method: apiconfig.method,
            url: apiconfig.url,
            data: apiconfig?.data
        })

        return res.data
    } catch (error: any) {
        console.log(error.response.data.message);
        return rejectWithValue(error.response?.data?.message || error.message);
    }

})



export const { resetLoading, setInventoryLostData } = inventorySlice.actions;

export const inventoryData = (state: any) => state?.inventory?.data
export const loadingStatus = (state: any) => state?.inventory?.status;
export const inventoryError = (state: any) => state?.inventory?.error



export default inventorySlice.reducer