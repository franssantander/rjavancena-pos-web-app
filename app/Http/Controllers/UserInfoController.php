<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\AuthModel;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UserInfoModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UserInfoController extends Controller
{
    protected $fillable_attr_user_info, $helper;
    public function __construct(Helper $helper, UserInfoModel $fillable_attr_user_info)
    {
        $this->helper = $helper;
        $this->fillable_attr_user_info = $fillable_attr_user_info;
    }

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $decrypted_user_infos = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        $user_infos = UserInfoModel::orderBy('created_at', 'desc')->get();
        foreach ($user_infos as $user_info) {
            if ($user_info) {
                $arr_user_info = $user_info->toArray();

                foreach ($this->fillable_attr_user_info->arrToDecrypt() as $arrToDecrypt) {
                    if (isset($arr_user_info[$arrToDecrypt])) {
                        $arr_user_info[$arrToDecrypt] = $user_info->{$arrToDecrypt} ? Crypt::decrypt($user_info->{$arrToDecrypt}) : null;
                    }
                }
                $decrypted_user_infos = $arr_user_info;
            }
        }

        $response = [
            'user_information' => $decrypted_user_infos,
        ];

        return response()->json([
            'message' => "Successfully retrieve data",
            'data' => $response,
        ], Response::HTTP_OK);
    }


    /**
     * Display a listing of the resource.
     */
    public function getPersonalInfo(Request $request)
    {
        $crud_settings = $this->fillable_attr_user_info->getApiCrudSettings();
        $arr_parent_items = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        $user_info = UserInfoModel::where('user_id', $user->user_id)->first();
        if (!$user_info) {
            return response()->json([
                'message' => "Failed to retrieve data",
            ], Response::HTTP_NOT_FOUND);
        }

        // Get the role
        $auth = AuthModel::where('user_id', $user->user_id)->first();
        if (!$auth) {
            return response()->json([
                'message' => "Failed to retrieve data",
            ], Response::HTTP_NOT_FOUND);
        }
        // Add role on arr
        $arr_parent_items['role'] = $this->helper->transformColumnName($auth->role);

        // Get the fields needed
        $arrDetails = $this->fillable_attr_user_info->arrDetails();
        $arrToDecrypt = $this->fillable_attr_user_info->arrToDecrypt();
        $arrFieldsToFormatUcFirst = $this->fillable_attr_user_info->arrFieldsToFormatUcFirst();

        foreach ($arrDetails as $field) {
            if ($field === 'image' && $this->helper->isEncrypted($user_info->$field)) {
                $arr_parent_items[$field] = env("PATH_FILE_USER_ACCOUNT") .  Crypt::decrypt($user_info->$field);
            } elseif (in_array($field, $arrToDecrypt) && $this->helper->isEncrypted($user_info->$field)) {
                $decryptedValue = Crypt::decrypt($user_info->$field);
                if (in_array($field, $arrFieldsToFormatUcFirst)) {
                    $arr_parent_items[$field] = $this->helper->transformColumnName($decryptedValue);
                } else {
                    $arr_parent_items[$field] = $decryptedValue;
                }
            } else {
                $arr_parent_items[$field] = $user_info->$field;
            }
        }

        // ***************************** //
        // Format Api
        $crud_action = $this->helper->formatApi(
            $crud_settings['prefix'],
            $crud_settings['payload'],
            $crud_settings['method'],
            $crud_settings['button_name'],
            $crud_settings['icon'],
            $crud_settings['container']
        );

        // Add the format Api Crud
        $arr_parent_items['actions'] = array_values($crud_action);
        // ***************************** //

        // ***************************** //
        // Add details on action crud
        foreach ($arr_parent_items['actions'] as &$action) {
            // Check if 'details' key doesn't exist, then add it
            if (!isset($action['details'])) {
                $action['details'] = [];
            }
            // Populate details for each attribute user info
            if ($action['button_name'] == 'Edit account') {
                $field_types = [
                    // 'image' => 'file',
                    // 'contact_number' => 'number',
                    // 'contact_email' => 'email',
                    'region_name' => 'select',
                    'province_name' => 'select',
                    'city_or_municipality_name' => 'select',
                    'barangay_name' => 'select',
                    'description_location' => 'textarea'
                ];

                $code_to_mapping = [
                    'region_name' => 'region_code',
                    'province_name' => 'province_code',
                    'city_or_municipality_name' => 'city_or_municipality_code',
                    'barangay_name' => 'barangay_code'
                ];

                foreach ($this->fillable_attr_user_info->arrDetailsGetUserInfo() as $arrDetailsGetUserInfo) {
                    $type = $field_types[$arrDetailsGetUserInfo] ?? 'input'; // Default to 'input' if not found in $field_types

                    if ($arrDetailsGetUserInfo == 'image') {
                        $value = null; // Set value to null for image field
                    } else {
                        $value = $user_info ? $user_info->$arrDetailsGetUserInfo : null;
                        if ($value && $this->helper->isEncrypted($value)) {
                            $value = Crypt::decrypt($value); // Decrypt value if it's encrypted
                        }
                    }

                    $value_name = null;

                    // Check if this field has a code and needs to be mapped to a name
                    if (array_key_exists($arrDetailsGetUserInfo, $code_to_mapping)) {
                        $code_field = $code_to_mapping[$arrDetailsGetUserInfo];
                        $code_value = $user_info ? $user_info->$code_field : null;
                        if ($code_value && $this->helper->isEncrypted($code_value)) {
                            $code_value = Crypt::decrypt($code_value); // Decrypt value if it's encrypted
                        }
                        $value_name = $value;
                        $value = $code_value;
                    }

                    $action['details'][] = [
                        'label' => $this->helper->transformColumnName($arrDetailsGetUserInfo),
                        'type' => $type,
                        'value' => $value,
                        'value_name' => $this->helper->transformColumnName($value_name),
                        'option' => null
                    ];
                }
            }            // Populate details for each attribute user info
            else if ($action['button_name'] == 'Edit image') {
                $field_types = [
                    'image' => 'file',
                ];

                foreach ($this->fillable_attr_user_info->arrToUpdatesImage() as $arrToUpdatesImage) {
                    $type = $field_types[$arrToUpdatesImage] ?? 'input'; // Default to 'input' if not found in $field_types

                    if ($arrToUpdatesImage == 'image') {
                        $value = null; // Set value to null for image field
                    } else {
                        $value = $user_info ? $user_info->$arrToUpdatesImage : null;
                        if ($value && $this->helper->isEncrypted($value)) {
                            $value = Crypt::decrypt($value); // Decrypt value if it's encrypted
                        }
                    }

                    $value_name = null;
                    $action['details'][] = [
                        'label' => $this->helper->transformColumnName($arrToUpdatesImage),
                        'type' => $type,
                        'value' => $value,
                        'value_name' => $this->helper->transformColumnName($value_name),
                        'option' => null
                    ];
                }
            }
        }
        // ***************************** //

        $response = [
            'user_information' => $arr_parent_items,
        ];

        return response()->json([
            'message' => "Successfully retrieved data",
            'data' => $response,
        ], Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $file_name = '';
        $result_validate_eu_device = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if exist user
        $exist_user_id = UserInfoModel::where('user_id', $user->user_id)->exists();
        if ($exist_user_id) {
            return response()->json(
                [
                    'message' => 'User i.d hash already exist',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            // 'contact_number' => 'required|string|max:11',
            // 'contact_email' => 'required|email|max:255',
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'region_code' => 'required|string|max:255',
            'province_code' => 'required|string|max:255',
            'city_or_municipality_code' => 'required|string|max:255',
            'barangay_code' => 'required|string|max:255',
            'region_name' => 'required|string|max:255',
            'province_name' => 'required|string|max:255',
            'city_or_municipality_name' => 'required|string|max:255',
            'barangay_name' => 'required|string|max:255',
            'description_location' => 'nullable|string|max:1500',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        DB::beginTransaction(); // Begin transaction

        try {
            // Handle image upload and update
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'user-info',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    0, // 1 is original name | 0 is system generated name
                );
            }

            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $request->all(),
                $file_name,
                'image',
            );

            // *********************************** //
            // Start Store
            // Format the content
            $result_to_create = $this->helper->arrStoreMultipleData(
                $this->fillable_attr_user_info->arrToStores(),
                $result_merge_data,
                [],
                [],
                [],
                $this->fillable_attr_user_info->arrToStores(),
            );
            // Create UserInfoModel with encrypted data
            $user_info_create = UserInfoModel::create(array_merge(['user_id' => $user->user_id], $result_to_create));
            if (!$user_info_create) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store user information'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId($user_info_create, $this->fillable_attr_user_info->idToUpdate(), Str::uuid());
            if ($update_unique_id) {
                DB::rollBack();
                return $update_unique_id;
            }
            // End Store
            // *********************************** //

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'STORE_PERSONAL_INFORMATION',
                ],
                $user_info_create->toArray(),
                1
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Successfully stored user information',
                'parameter' => 'success'
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CREATED);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            // 'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            // 'contact_number' => 'required|string|max:11',
            // 'contact_email' => 'required|email|max:255',
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'region_code' => 'required|string|max:255',
            'province_code' => 'required|string|max:255',
            'city_or_municipality_code' => 'required|string|max:255',
            'barangay_code' => 'required|string|max:255',
            'region_name' => 'required|string|max:255',
            'province_name' => 'required|string|max:255',
            'city_or_municipality_name' => 'required|string|max:255',
            'barangay_name' => 'required|string|max:255',
            'description_location' => 'nullable|string|max:1500',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }


        DB::beginTransaction(); // Begin transaction

        try {
            // Retrieve the user information
            $user_info = UserInfoModel::where('user_id', $user->user_id)->first();

            // Check if user information exists
            if (!$user_info) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload and update
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'user-info',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                    ],
                    0, // 1 is original name | 0 is system generated name
                );
            }

            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $request->all(),
                $file_name,
                'image',
            );

            // *********************************** //
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $user_info, // the model to update
                $this->fillable_attr_user_info->arrToUpdates(), // fields to update
                $result_merge_data, // the merge of file and user input
                0, // if the database value is all lower case must 1 here to match the old new
                0, // if the database value is all capital case must 1 here to match the old new
            );
            // No changes return error
            if (
                is_object($result_update_logs_old_new)
                && method_exists($result_update_logs_old_new, 'getStatusCode')
                && $result_update_logs_old_new->getStatusCode() == Response::HTTP_UNPROCESSABLE_ENTITY
            ) {
                return $result_update_logs_old_new;
            }
            // End checking changes
            // *********************************** //

            // *********************************** //
            // Start updating
            // Update Multiple Data
            $result_update_multi_data = $this->helper->arrUpdateMultipleData(
                $user_info,
                $this->fillable_attr_user_info->arrToUpdates(),
                $result_merge_data,
                [],
                $this->fillable_attr_user_info->arrFieldsToUppercase(),
                $this->fillable_attr_user_info->arrToUpdates(),
            );
            // Return error if not save the update
            if ($result_update_multi_data) {
                DB::rollBack();
                return $result_update_multi_data;
            }
            // End updating
            // *********************************** //

            // *********************************** //
            // Start log
            // Format the logs to encrypted
            $result_format_logs_encrypted_data = $this->helper->formatLogsEncDataOldNew($result_update_logs_old_new);
            // dd($result_format_logs_encrypted_data);
            // Store Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_PERSONAL_INFORMATION',
                ],
                $result_format_logs_encrypted_data,
                2
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Successfully update',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => 'An error occurred', $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function updateImage(Request $request)
    {
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }


        DB::beginTransaction(); // Begin transaction

        try {
            // Retrieve the user information
            $user_info = UserInfoModel::where('user_id', $user->user_id)->first();

            // Check if user information exists
            if (!$user_info) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload and update
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'user-info',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                    ],
                    0, // 1 is original name | 0 is system generated name
                );
            }

            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $request->all(),
                $file_name,
                'image',
            );

            // *********************************** //
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $user_info, // the model to update
                $this->fillable_attr_user_info->arrToUpdatesImage(), // fields to update
                $result_merge_data, // the merge of file and user input
                0, // if the database value is all lower case must 1 here to match the old new
                0, // if the database value is all capital case must 1 here to match the old new
                [] // array fields nullable. if nullable then dont check the old new
            );
            // No changes return error
            if (
                is_object($result_update_logs_old_new)
                && method_exists($result_update_logs_old_new, 'getStatusCode')
                && $result_update_logs_old_new->getStatusCode() == Response::HTTP_UNPROCESSABLE_ENTITY
            ) {
                return $result_update_logs_old_new;
            }
            // End checking changes
            // *********************************** //

            // *********************************** //
            // Start updating
            // Update Multiple Data
            $result_update_multi_data = $this->helper->arrUpdateMultipleData(
                $user_info,
                $this->fillable_attr_user_info->arrToUpdatesImage(),
                $result_merge_data,
                [],
                [],
                [],
            );
            // Return error if not save the update
            if ($result_update_multi_data) {
                DB::rollBack();
                return $result_update_multi_data;
            }
            // End updating
            // *********************************** //

            // *********************************** //
            // Start log
            // Format the logs to encrypted
            $result_format_logs_encrypted_data = $this->formatLogsEncDataOldNew($result_update_logs_old_new);

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_PERSONAL_IMAGE',
                ],
                $result_format_logs_encrypted_data,
                2
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Successfully update',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // public function updateUserInfoAdmin(Request $request)
    // {
    //     // Initialize
    //     $changes_for_logs = [];
    //     $file_name = '';

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     // Validation rules
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|string',

    //         'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
    //         'first_name' => $request->input('first_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'middle_name' => $request->input('middle_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'last_name' => $request->input('last_name') != "" ? 'required|string|max:255' : 'nullable',
    //         // 'contact_number' => $request->input('contact_number') != "" ? 'required|string|max:255' : 'nullable',
    //         // 'contact_email' => $request->input('contact_email') != "" ? 'required|string|max:255' : 'nullable',
    //         'address_1' => $request->input('address_1') != "" ? 'required|string|max:255' : 'nullable',
    //         'address_2' => $request->input('address_2') != "" ? 'required|string|max:255' : 'nullable',
    //         'region_code' => $request->input('region_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'province_code' => $request->input('province_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'city_or_municipality_code' => $request->input('city_or_municipality_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'barangay_code' => $request->input('barangay_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'region_name' => $request->input('region_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'province_name' => $request->input('province_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'city_or_municipality_name' => $request->input('city_or_municipality_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'barangay_name' => $request->input('barangay_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'description_location' => $request->input('description_location') != "" ? 'required|string|max:255' : 'nullable',

    //         'eu_device' => 'required|string',
    //     ]);

    //     // Check if validation fails
    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
    //     }

    //     // Validate Eu Device
    //     $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
    //     if ($result_validate_eu_device) {
    //         return $result_validate_eu_device;
    //     }


    //     DB::beginTransaction(); // Begin transaction

    //     try {
    //         // UpperCase Specific Field
    //         $validated_data = $this->helper->upperCaseSpecific($validator->validated(), $this->fillable_attr_user_info->arrToUpdatesAdmin());

    //         // Retrieve the user information
    //         $user_info = UserInfoModel::where('user_id', Crypt::decrypt($request->user_id))->first();

    //         // Check if user information exists
    //         if (!$user_info) {
    //             return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
    //         }

    //         // Handle image upload and update
    //         if ($request->hasFile('image')) {
    //             $file_name = $this->helper->handleUploadFile(
    //                 [
    //                     'custom_folder' => 'user-info',
    //                     'file_image' => $request->file('image'),
    //                     'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
    //                     'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
    //                 ],
    //                 0,
    //             );
    //         }

    //         // Loop through the fields for encryption and decryption
    //         foreach ($this->fillable_attr_user_info->arrToUpdatesAdmin() as $arrToUpdatesAdmin) {
    //             // Check if the key exists in the $validated_data array
    //             if (isset($validated_data[$arrToUpdatesAdmin])) {
    //                 $existing_value = $user_info->$arrToUpdatesAdmin !== null ? Crypt::decrypt($user_info->$arrToUpdatesAdmin) : null;

    //                 if ($arrToUpdatesAdmin != 'image') {
    //                     $new_value = Crypt::encrypt($validated_data[$arrToUpdatesAdmin]);

    //                     // Check if the value has changed for logs
    //                     if ($existing_value != $validated_data[$arrToUpdatesAdmin]) {
    //                         $changes_for_logs[$arrToUpdatesAdmin] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $validated_data[$arrToUpdatesAdmin],
    //                         ];
    //                         $user_info->{$arrToUpdatesAdmin} = $new_value; // Set the new value
    //                     }
    //                 } else {
    //                     $new_value =  $file_name != '' ? Crypt::encrypt($file_name) : null;

    //                     if ($existing_value == null && $new_value != null) {
    //                         $changes_for_logs['image'] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $file_name,
    //                         ];
    //                     } else if ($existing_value != null && $new_value != null) {
    //                         $changes_for_logs['image'] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $file_name,
    //                         ];
    //                     }

    //                     $user_info->{$arrToUpdatesAdmin} = $new_value; // Set the new value
    //                 }
    //             }
    //         }

    //         // Check if there are changes before logging
    //         if (empty($changes_for_logs)) {
    //             return response()->json(['message' => 'No changes have been made'], Response::HTTP_UNPROCESSABLE_ENTITY);
    //         }

    //         // Save the changes
    //         if (!$user_info->save()) {
    //             DB::rollBack(); // Rollback transaction
    //             // If the code reaches here, there was an issue saving the changes
    //             return response()->json(['message' => 'Failed to update user information'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }

    //         $result_format_logs = $this->formatLogsEncDataOldNew($changes_for_logs);

    //         $log_details = [
    //             'user_id' => $user->user_id,
    //             'fields' => $result_format_logs
    //         ];

    //         // Logs
    //         $log_result = $this->helper->log(
    //             $request,
    //             [
    //                 'user_device' => $request->eu_device,
    //                 'user_id' => $user->user_id,
    //                 'is_history' => 0,
    //                 'user_action' => 'UPDATE PERSONAL INFORMATION',
    //             ]
    //         );
    //         if ($log_result->getStatusCode() !== Response::HTTP_OK) {
    //             DB::rollBack();
    //             return $log_result;
    //         }

    //         // Commit the transaction
    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Successfully update user information',
    //             // 'log_message' => $log_result
    //         ], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         // Rollback the transaction on any exception
    //         DB::rollBack();
    //         return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }


    // public function updateUserInfoAdmin(Request $request)
    // {
    //     // Initialize
    //     $changes_for_logs = [];
    //     $file_name = '';

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     // Validation rules
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|string',

    //         'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
    //         'first_name' => $request->input('first_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'middle_name' => $request->input('middle_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'last_name' => $request->input('last_name') != "" ? 'required|string|max:255' : 'nullable',
    //         // 'contact_number' => $request->input('contact_number') != "" ? 'required|string|max:255' : 'nullable',
    //         // 'contact_email' => $request->input('contact_email') != "" ? 'required|string|max:255' : 'nullable',
    //         'address_1' => $request->input('address_1') != "" ? 'required|string|max:255' : 'nullable',
    //         'address_2' => $request->input('address_2') != "" ? 'required|string|max:255' : 'nullable',
    //         'region_code' => $request->input('region_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'province_code' => $request->input('province_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'city_or_municipality_code' => $request->input('city_or_municipality_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'barangay_code' => $request->input('barangay_code') != "" ? 'required|string|max:255' : 'nullable',
    //         'region_name' => $request->input('region_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'province_name' => $request->input('province_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'city_or_municipality_name' => $request->input('city_or_municipality_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'barangay_name' => $request->input('barangay_name') != "" ? 'required|string|max:255' : 'nullable',
    //         'description_location' => $request->input('description_location') != "" ? 'required|string|max:255' : 'nullable',

    //         'eu_device' => 'required|string',
    //     ]);

    //     // Check if validation fails
    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
    //     }

    //     // Validate Eu Device
    //     $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
    //     if ($result_validate_eu_device) {
    //         return $result_validate_eu_device;
    //     }


    //     DB::beginTransaction(); // Begin transaction

    //     try {
    //         // UpperCase Specific Field
    //         $validated_data = $this->helper->upperCaseSpecific($validator->validated(), $this->fillable_attr_user_info->arrToUpdatesAdmin());

    //         // Retrieve the user information
    //         $user_info = UserInfoModel::where('user_id', Crypt::decrypt($request->user_id))->first();

    //         // Check if user information exists
    //         if (!$user_info) {
    //             return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
    //         }

    //         // Handle image upload and update
    //         if ($request->hasFile('image')) {
    //             $file_name = $this->helper->handleUploadFile(
    //                 [
    //                     'custom_folder' => 'user-info',
    //                     'file_image' => $request->file('image'),
    //                     'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
    //                     'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
    //                 ],
    //                 0,
    //             );
    //         }

    //         // Loop through the fields for encryption and decryption
    //         foreach ($this->fillable_attr_user_info->arrToUpdatesAdmin() as $arrToUpdatesAdmin) {
    //             // Check if the key exists in the $validated_data array
    //             if (isset($validated_data[$arrToUpdatesAdmin])) {
    //                 $existing_value = $user_info->$arrToUpdatesAdmin !== null ? Crypt::decrypt($user_info->$arrToUpdatesAdmin) : null;

    //                 if ($arrToUpdatesAdmin != 'image') {
    //                     $new_value = Crypt::encrypt($validated_data[$arrToUpdatesAdmin]);

    //                     // Check if the value has changed for logs
    //                     if ($existing_value != $validated_data[$arrToUpdatesAdmin]) {
    //                         $changes_for_logs[$arrToUpdatesAdmin] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $validated_data[$arrToUpdatesAdmin],
    //                         ];
    //                         $user_info->{$arrToUpdatesAdmin} = $new_value; // Set the new value
    //                     }
    //                 } else {
    //                     $new_value =  $file_name != '' ? Crypt::encrypt($file_name) : null;

    //                     if ($existing_value == null && $new_value != null) {
    //                         $changes_for_logs['image'] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $file_name,
    //                         ];
    //                     } else if ($existing_value != null && $new_value != null) {
    //                         $changes_for_logs['image'] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $file_name,
    //                         ];
    //                     }

    //                     $user_info->{$arrToUpdatesAdmin} = $new_value; // Set the new value
    //                 }
    //             }
    //         }

    //         // Check if there are changes before logging
    //         if (empty($changes_for_logs)) {
    //             return response()->json(['message' => 'No changes have been made'], Response::HTTP_UNPROCESSABLE_ENTITY);
    //         }

    //         // Save the changes
    //         if (!$user_info->save()) {
    //             DB::rollBack(); // Rollback transaction
    //             // If the code reaches here, there was an issue saving the changes
    //             return response()->json(['message' => 'Failed to update user information'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }

    //         $result_format_logs = $this->formatLogsEncDataOldNew($changes_for_logs);

    //         $log_details = [
    //             'user_id' => $user->user_id,
    //             'fields' => $result_format_logs
    //         ];

    //         // Logs
    //         $log_result = $this->helper->log(
    //             $request,
    //             [
    //                 'user_device' => $request->eu_device,
    //                 'user_id' => $user->user_id,
    //                 'is_history' => 0,
    //                 'user_action' => 'UPDATE PERSONAL INFORMATION',
    //             ]
    //         );
    //         if ($log_result->getStatusCode() !== Response::HTTP_OK) {
    //             DB::rollBack();
    //             return $log_result;
    //         }

    //         // Commit the transaction
    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Successfully update user information',
    //             // 'log_message' => $log_result
    //         ], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         // Rollback the transaction on any exception
    //         DB::rollBack();
    //         return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }


    // public function updateImage(Request $request)
    // {
    //     // Initialize
    //     $changes_for_logs = [];
    //     $file_name = '';

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     // Validation rules
    //     $validator = Validator::make($request->all(), [
    //         'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
    //         'eu_device' => 'required|string',
    //     ]);

    //     // Check if validation fails
    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
    //     }

    //     // Validate Eu Device
    //     $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
    //     if ($result_validate_eu_device) {
    //         return $result_validate_eu_device;
    //     }


    //     DB::beginTransaction(); // Begin transaction

    //     try {
    //         // Retrieve the user information
    //         $user_info = UserInfoModel::where('user_id', $user->user_id)->first();

    //         // Check if user information exists
    //         if (!$user_info) {
    //             return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
    //         }

    //         // Handle image upload and update
    //         if ($request->hasFile('image')) {
    //             $file_name = $this->helper->handleUploadFile(
    //                 [
    //                     'custom_folder' => 'user-info',
    //                     'file_image' => $request->file('image'),
    //                     'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
    //                     'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
    //                 ],
    //                 0,
    //             );
    //         }

    //         // Loop through the fields for encryption and decryption
    //         foreach ($this->fillable_attr_user_info->arrToUpdatesImage() as $arrToUpdatesImage) {
    //             // Check if the key exists in the $validated_data array
    //             if (isset($validated_data[$arrToUpdatesImage])) {
    //                 $existing_value = $user_info->$arrToUpdatesImage !== null ? Crypt::decrypt($user_info->$arrToUpdatesImage) : null;

    //                 if ($arrToUpdatesImage != 'image') {
    //                     $new_value = Crypt::encrypt($validated_data[$arrToUpdatesImage]);

    //                     // Check if the value has changed for logs
    //                     if ($existing_value != $validated_data[$arrToUpdatesImage]) {
    //                         $changes_for_logs[$arrToUpdatesImage] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $validated_data[$arrToUpdatesImage],
    //                         ];
    //                         $user_info->{$arrToUpdatesImage} = $new_value; // Set the new value
    //                     }
    //                 } else {
    //                     $new_value =  $file_name != '' ? Crypt::encrypt($file_name) : null;

    //                     if ($existing_value == null && $new_value != null) {
    //                         $changes_for_logs['image'] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $file_name,
    //                         ];
    //                     } else if ($existing_value != null && $new_value != null) {
    //                         $changes_for_logs['image'] = [
    //                             'oldEnc' => $existing_value,
    //                             'newEnc' => $file_name,
    //                         ];
    //                     }

    //                     $user_info->{$arrToUpdatesImage} = $new_value; // Set the new value
    //                 }
    //             }
    //         }

    //         // Check if there are changes before logging
    //         if (empty($changes_for_logs)) {
    //             return response()->json(['message' => 'No changes have been made'], Response::HTTP_UNPROCESSABLE_ENTITY);
    //         }

    //         // Save the changes
    //         if (!$user_info->save()) {
    //             DB::rollBack(); // Rollback transaction
    //             // If the code reaches here, there was an issue saving the changes
    //             return response()->json(['message' => 'Failed to update image'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }

    //         $result_format_logs = $this->formatLogsEncDataOldNew($changes_for_logs);

    //         $log_details = [
    //             'user_id' => $user->user_id,
    //             'fields' => $result_format_logs
    //         ];

    //         // Logs
    //         $log_result = $this->helper->log(
    //             $request,
    //             [
    //                 'user_device' => $request->eu_device,
    //                 'user_id' => $user->user_id,
    //                 'is_history' => 0,
    //                 'user_action' => 'UPDATE PERSONAL IMAGE',
    //             ]
    //         );
    //         if ($log_result->getStatusCode() !== Response::HTTP_OK) {
    //             DB::rollBack();
    //             return $log_result;
    //         }

    //         // Commit the transaction
    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Successfully update image',
    //             // 'log_message' => $log_result
    //         ], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         // Rollback the transaction on any exception
    //         DB::rollBack();
    //         return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

}
