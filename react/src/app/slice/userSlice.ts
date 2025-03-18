import useAxiosClient from "@/axios-client";
import { createAsyncThunk, createSlice } from "@reduxjs/toolkit";
import { UserState, ApiConfig } from "@/interface/InterfaceType";
import Cookies from "js-cookie";
import axios from "axios";


const axiosClient = useAxiosClient();


const initialState: UserState = {
  sidebar: [],
  headerUser: [],
  data: {},
  user: {},
  userInfo: {},
  status: "",
  navbarStatus: "",
  profileStatus: "idle",
  profileData: "",
  loading: false,
  loadingEudevice: false,
  loadingSignIn: false,
  loadingUpdateProfile: false,
  loadingUpdateEmail: false,
  loadingUpdatePassword: false,
  error: false,
  errorNavbar: false,
  getRegions: {},
  getProvinces: {},
  getMunicipality: {},
  getBarangay: {},
  completeProfile: {},
  errorCompleteProfile: false,
  settingsProfileData: {},
  updateSettingsProfileData: {},
  updateEmailProfileData: {},
  resendCodeEmailData: {},
  resendCodePasswordData: {},
  updatePasswordProfileData: {},
  uploadImageData: {},
}


const userSlice = createSlice({
  name: "user",
  initialState,
  reducers: {
    resetCompleteProfileStatus(state) {
      state.profileStatus = 'idle';
    },
    mockCompleteProfileSuccess(state) {
      state.profileStatus = 'completeProfile/success';
    },
    setNavbar: (state, action) => {
      return {
        ...state,
        sidebar: action.payload
      }
    },
    headerUser: (state, action) => {
      return {
        ...state,
        headerUser: action.payload
      }
    },
  },
  extraReducers(builder) {

    // builder
    //   .addCase(setNavbar.pending, (state) => {
    //     state.status = "setNavbar/loading";
    //     state.loading = true;

    //   })
    //   .addCase(setNavbar.fulfilled, (state, action) => {
    //     state.navbarStatus = "setNavbar/success";
    //     state.loading = false;
    //     state.data = action.payload;
    //     state.sidebar = action.payload;
    //   })
    //   .addCase(setNavbar.rejected, (state, action) => {
    //     state.navbarStatus = "setNavbar/failed";
    //     state.loading = false;
    //     state.errorNavbar = action.payload;

    //   })

    builder
      .addCase(setEudevice.pending, (state) => {
        state.status = "setEudevice/loading";
        state.loadingEudevice = true;
        state.error = null
      })
      .addCase(setEudevice.fulfilled, (state, action) => {
        state.status = "setEudevice/success";
        state.loadingEudevice = false;
        state.data = action.payload;
      })
      .addCase(setEudevice.rejected, (state, action) => {
        state.status = "setEudevice/failed";
        state.loadingEudevice = false;
        state.error = action.payload;
      })

    builder
      .addCase(signIn.pending, (state) => {
        state.status = "signIn/loading";
        state.loadingSignIn = true;
        state.error = null
      })
      .addCase(signIn.fulfilled, (state, action) => {
        state.status = "signIn/success";
        state.loadingSignIn = false;
        state.userInfo = action.payload;
        state.data = action.payload;
      })
      .addCase(signIn.rejected, (state, action) => {
        state.status = "signIn/failed";
        state.loadingSignIn = false;
        state.error = action.payload;
      })

    builder
      .addCase(getRegions.pending, (state) => {
        state.status = "getRegions/loading";
        state.error = null
      })
      .addCase(getRegions.fulfilled, (state, action) => {
        state.status = "getRegions/success";
        state.getRegions = action.payload;
      })
      .addCase(getRegions.rejected, (state, action) => {
        state.status = "getRegions/failed";
        state.error = action.payload;
      })

    builder
      .addCase(getProvinces.pending, (state) => {
        state.status = "getProvinces/loading";
        state.error = null
      })
      .addCase(getProvinces.fulfilled, (state, action) => {
        state.status = "getProvinces/success";
        state.getProvinces = action.payload;
      })
      .addCase(getProvinces.rejected, (state, action) => {
        state.status = "getProvinces/failed";
        state.error = action.payload;
      })

    builder
      .addCase(getMunicipality.pending, (state) => {
        state.status = "getMunicipality/loading";
        state.error = null
      })
      .addCase(getMunicipality.fulfilled, (state, action) => {
        state.status = "getMunicipality/success";
        state.getMunicipality = action.payload;
      })
      .addCase(getMunicipality.rejected, (state, action) => {
        state.status = "getMunicipality/failed";
        state.error = action.payload;
      })

    builder
      .addCase(getBarangay.pending, (state) => {
        state.status = "getBarangay/loading";
        state.error = null
      })
      .addCase(getBarangay.fulfilled, (state, action) => {
        state.status = "getBarangay/success";
        state.getBarangay = action.payload;
      })
      .addCase(getBarangay.rejected, (state, action) => {
        state.status = "getBarangay/failed";
        state.error = action.payload;
      })

    builder
      .addCase(completeProfile.pending, (state) => {
        state.profileStatus = "completeProfile/loading";
        state.errorCompleteProfile = null
      })
      .addCase(completeProfile.fulfilled, (state, action) => {
        state.profileStatus = "completeProfile/success";
        state.errorCompleteProfile = null
        state.completeProfile = action.payload;
      })
      .addCase(completeProfile.rejected, (state, action) => {
        state.profileStatus = "completeProfile/failed";
        state.errorCompleteProfile = action.payload;
      })

    builder
      .addCase(userInfo.pending, (state) => {
        state.status = "userInfo/loading";
        state.loadingSignIn = true;
        state.error = null
      })
      .addCase(userInfo.fulfilled, (state, action) => {
        state.status = "userInfo/success";
        state.loadingSignIn = false;
        state.data = action.payload;
      })
      .addCase(userInfo.rejected, (state, action) => {
        state.status = "userInfo/failed";
        state.loadingSignIn = false;
        state.error = action.payload;
      })

    builder
      .addCase(logout.pending, (state) => {
        state.status = "logout/loading";
        state.loading = true;
        state.error = null
      })
      .addCase(logout.fulfilled, (state, action) => {
        state.status = "logout/success";
        state.loading = false;
        state.data = action.payload;
      })
      .addCase(logout.rejected, (state, action) => {
        state.status = "logout/failed";
        state.loading = false;
        state.error = action.payload;
      })

    builder
      .addCase(settingsProfile.pending, (state) => {
        state.status = "settingsProfile/loading";
        state.loadingUpdateProfile = true;
        state.error = null
      })
      .addCase(settingsProfile.fulfilled, (state, action) => {
        state.status = "settingsProfile/success";
        state.loadingUpdateProfile = false;
        state.settingsProfileData = action.payload;
      })
      .addCase(settingsProfile.rejected, (state, action) => {
        state.status = "settingsProfile/failed";
        state.loadingUpdateProfile = false;
        state.error = action.payload;
      })

    builder
      .addCase(uploadImage.pending, (state) => {
        state.status = "uploadImage/loading";

        state.error = null
      })
      .addCase(uploadImage.fulfilled, (state, action) => {
        state.status = "uploadImage/success";
        state.uploadImageData = action.payload;
      })
      .addCase(uploadImage.rejected, (state, action) => {
        state.status = "uploadImage/failed";
        state.uploadImageData = action.payload;
      })

    builder
      .addCase(updateSettingsProfile.pending, (state) => {
        state.status = "updateSettingsProfile/loading";
        state.loadingUpdateProfile = true;
        state.error = null
      })
      .addCase(updateSettingsProfile.fulfilled, (state, action) => {
        state.status = "updateSettingsProfile/success";
        state.loadingUpdateProfile = false;
        state.updateSettingsProfileData = action.payload;
      })
      .addCase(updateSettingsProfile.rejected, (state, action) => {
        state.status = "updateSettingsProfile/failed";
        state.loadingUpdateProfile = false;
        state.error = action.payload;
        state.updateSettingsProfileData = action.payload;
      })

    builder
      .addCase(updateEmailProfile.pending, (state) => {
        state.status = "updateEmailProfile/loading";
        state.loadingUpdateEmail = true;
        state.error = null
      })
      .addCase(updateEmailProfile.fulfilled, (state, action) => {
        state.status = "updateEmailProfile/success";
        state.loadingUpdateEmail = false;
        state.updateEmailProfileData = action.payload;
      })
      .addCase(updateEmailProfile.rejected, (state, action) => {
        state.status = "updateEmailProfile/failed";
        state.loadingUpdateEmail = false;
        state.updateEmailProfileData = action.payload;
      })

    builder
      .addCase(resendCodeEmail.pending, (state) => {
        state.status = "resendCodeEmail/loading";
        state.error = null
      })
      .addCase(resendCodeEmail.fulfilled, (state, action) => {
        state.status = "resendCodeEmail/success";
        state.resendCodeEmailData = action.payload;
      })
      .addCase(resendCodeEmail.rejected, (state, action) => {
        state.status = "resendCodeEmail/failed";
        state.error = action.payload;
      })

    builder
      .addCase(resendCodePassword.pending, (state) => {
        state.status = "resendCodePassword/loading";
        state.error = null
      })
      .addCase(resendCodePassword.fulfilled, (state, action) => {
        state.status = "resendCodePassword/success";
        state.resendCodePasswordData = action.payload;
      })
      .addCase(resendCodePassword.rejected, (state, action) => {
        state.status = "resendCodePassword/failed";
        state.error = action.payload;
      })

    builder
      .addCase(updatePasswordProfile.pending, (state) => {
        state.status = "updatePasswordProfile/loading";
        state.loadingUpdatePassword = true;
        state.error = null
      })
      .addCase(updatePasswordProfile.fulfilled, (state, action) => {
        state.status = "updatePasswordProfile/success";
        state.loadingUpdatePassword = false;
        state.updatePasswordProfileData = action.payload;
      })
      .addCase(updatePasswordProfile.rejected, (state, action) => {
        state.status = "updatePasswordProfile/failed";
        state.loadingUpdatePassword = false;
        state.updatePasswordProfileData = action.payload;
      })
  },
});

export const setEudevice = createAsyncThunk("user/setEudevice", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {
    const res = await axiosClient({
      url: ApiConfig.url,
      method: ApiConfig.method,
    })
    console.log(res);

    Cookies.set("eu", res.data.eu);
    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

// export const setNavbar = createAsyncThunk("user/getNavbar", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
//   try {
//     const res = await axiosClient({
//       url: ApiConfig.url,
//       method: ApiConfig.method,
//       data: ApiConfig.data
//     })
//     return res.data
//   } catch (error: any) {
//     console.log(error)
//     return rejectWithValue(error.response.data)
//   }
// })

export const signIn = createAsyncThunk("user/setSignin", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {

    const res = await axiosClient({
      url: ApiConfig.url,
      method: ApiConfig.method,
      data: ApiConfig.data
    })

    // if (res.data.user_info !== "New User") {
    // }
    Cookies.set("token", res.data.token);

    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

export const getRegions = createAsyncThunk("user/getRegions", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {

    const res = await axios.get(ApiConfig.url, {
      headers: {
        Authorization: undefined,
      },
    })

    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})


export const getProvinces = createAsyncThunk("user/getProvinces", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {

    const res = await axios.get(ApiConfig.url, {
      headers: {
        Authorization: undefined,
      },
    })

    console.log(res)

    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})


export const getMunicipality = createAsyncThunk("user/getMunicipality", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  console.log(ApiConfig.url)
  try {

    const res = await axios.get(ApiConfig.url, {
      headers: {
        Authorization: undefined,
      },
    })

    console.log(res)

    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

export const getBarangay = createAsyncThunk("user/getBarangay", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  console.log(ApiConfig.url)
  try {

    const res = await axios.get(ApiConfig.url, {
      headers: {
        Authorization: undefined,
      },
    })

    console.log(res)

    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

export const completeProfile = createAsyncThunk("user/completeProfile", async (ApiConfig: ApiConfig, { rejectWithValue }) => {

  try {

    const res = await axiosClient({ headers: { "Content-Type": "multipart/form-data", }, url: ApiConfig.url, method: ApiConfig.method, data: ApiConfig.data })

    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

export const userInfo = createAsyncThunk("user/setUserInfo", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {

    const res = await axiosClient({
      url: ApiConfig.url,
      method: ApiConfig.method,
    })

    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

export const logout = createAsyncThunk("user/getLogout", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

//* USER PROFILE
export const uploadImage = createAsyncThunk("user/uploadImage", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {
    const res = await axiosClient({
      headers: { "Content-Type": "multipart/form-data", },
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


export const settingsProfile = createAsyncThunk("user/settingsProfile", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {
    const res = await axiosClient({
      url: ApiConfig.url,
      method: ApiConfig.method,
    })
    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

export const updateSettingsProfile = createAsyncThunk("user/updateSettingsProfile", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const updateEmailProfile = createAsyncThunk("user/updateEmailProfile", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const updatePasswordProfile = createAsyncThunk("user/updatePasswordProfile", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
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

export const resendCodeEmail = createAsyncThunk("user/resendCodeEmail", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {
    const res = await axiosClient({
      url: ApiConfig.url,
      method: ApiConfig.method,
      data: ApiConfig.data,
    })
    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})

export const resendCodePassword = createAsyncThunk("user/resendCodePassword", async (ApiConfig: ApiConfig, { rejectWithValue }) => {
  try {
    const res = await axiosClient({
      url: ApiConfig.url,
      method: ApiConfig.method,
      data: ApiConfig.data,
    })
    return res.data
  } catch (error: any) {
    console.log(error)
    return rejectWithValue(error.response.data)
  }
})


export const navbarData = (state: any) => state.user.data;
export const selectCompleteProfileStatus = (state: any) => state.user.profileStatus;
export const loading = (state: any) => state.user.loading;
export const { resetCompleteProfileStatus, mockCompleteProfileSuccess, setNavbar, headerUser } = userSlice.actions;

export default userSlice.reducer