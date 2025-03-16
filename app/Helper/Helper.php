<?php

namespace App\Helper;

use App\Models\AuthModel;
use App\Models\LogsModel;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use App\Models\HistoryModel;
use App\Models\PaymentModel;
use App\Models\VoucherModel;
use Illuminate\Http\Request;
use App\Models\PurchaseModel;
use App\Models\UserInfoModel;
use Illuminate\Support\Carbon;
use App\Models\VoucherItemModel;
use App\Models\NotificationModel;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use App\Models\UserPersonalAccessToken;
use Illuminate\Support\Facades\Storage;
use App\Models\UserPersonalAccessTokenModel;
use hisorange\BrowserDetect\Facade as Browser;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Encryption\DecryptException;


class Helper
{
    // Authentication
    public function authorizeUser($request)
    {
        try {
            // Authenticate the user with the provided token
            $user = JWTAuth::parseToken()->authenticate();

            $auth = UserPersonalAccessTokenModel::where('user_id', $user->user_id)
                ->where('status', 'ACTIVE')
                ->where('token', $request->bearerToken())
                ->whereNull('last_used_at')
                ->where('expires_at', '>', Carbon::now())
                ->where('name',  'LOGIN')
                ->latest()
                ->first();

            if (!$auth) {
                JWTAuth::invalidate(JWTAuth::getToken());
            }

            return $auth;
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], Response::HTTP_UNAUTHORIZED);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Failed to authenticate'], Response::HTTP_UNAUTHORIZED);
        }
    }

    // Unset column dont want to include to save on database
    public function unsetColumn($unsets, $fillableAttr)
    {
        foreach ($unsets as $unset) {
            // Find the key associated with the field and unset it
            $key = array_search($unset, $fillableAttr);
            if ($key !== false) {
                unset($fillableAttr[$key]);
            }
        }

        return $fillableAttr;
    }

    // Uppercase specific data base on validated
    public function upperCaseSpecific($validatedData, $colUpperCase)
    {
        foreach ($validatedData as $key => $value) {
            // Check if the field should be transformed to uppercase
            if (in_array($key, $colUpperCase)) {
                $validatedData[$key] = strtoupper($value);
            }
        }

        return $validatedData;
    }

    public function upperCaseValueSelectTagFilter($datas)
    {
        $arr_select_fields = [];

        foreach ($datas as $key => $value) {
            // Replace underscores with spaces and capitalize each word
            $label = ucwords(str_replace('_', ' ', strtolower($value)));
            $arr_select_fields[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $arr_select_fields;
    }

    // Uppercase the Word with dash -
    public function upperCase($buttonName)
    {
        if ($buttonName == '' || $buttonName == null) {
            return null;
        }
        // Remove hyphens and uppercase the string
        $upperCaseString = str_replace('-', ' ', ucfirst($buttonName));

        return $upperCaseString;
    }

    // Uppercase All the Word with dash - 
    public function transformColumnName($columns)
    {
        $arr_column = [];

        if (is_array($columns)) {
            foreach ($columns as $column) {
                $arr_column[] = ucwords(str_replace('_', ' ', $column));
            }

            return $arr_column;
        } else {
            return ucwords(str_replace('_', ' ', strtolower($columns)));
        }
    }

    public function formatApi($prefix, $payloads, $method, $button_name, $icon, $container)
    {
        $arr_action = [];

        foreach ($payloads as $key => $payload) {
            $action = [
                'url' => $prefix . $key,
                'payload' => $payload,
                'method' => $method[$key] ?? null,
                'icon' => $icon[$key] ?? null,
                'button_name' => $this->upperCase($button_name[$key] ?? null),
                'container' => $container[$key] ?? null,
            ];

            $arr_action[] = $action;
        }

        return $arr_action;
    }

    public function log(Request $request, $arr_data_logs = [], $arr_data_format = [], $is_create_update_delete = 1, $path_image = null)
    {
        $user_id_new = '';
        $password_new = '';

        DB::beginTransaction();
        try {
            // Log formatter
            $result_create_log_format = $this->logFormat(
                $arr_data_format,
                $is_create_update_delete,
                $path_image,
            );

            // Invalid datas to logformat
            if (
                is_object($result_create_log_format)
                && method_exists($result_create_log_format, 'getStatusCode')
                && $result_create_log_format->getStatusCode() == Response::HTTP_INTERNAL_SERVER_ERROR
            ) {
                return $result_create_log_format;
            }

            // Final log holder
            $arr_logs_holder['fields'] = $result_create_log_format;
            $arr_data_logs['log_details'] = $arr_logs_holder;

            if ($arr_data_logs['is_history'] == 1) {

                foreach ($arr_data_logs['log_details'] as $fields) {
                    foreach ($fields as $field) {
                        if ($field['label'] == 'user_id') {
                            $user_id_new = $field['new'];
                        }

                        if ($field['label'] == 'password') {
                            $password_new = $field['new'];
                        }

                        if ($field['label'] == 'new_password') {
                            $password_new = $field['new'];
                        }
                    }
                }

                if (!empty($user_id_new) && !empty($password_new)) {
                    $history = HistoryModel::create([
                        'tbl_id' => $user_id_new,
                        'tbl_name' => 'users_tbl',
                        'column_name' => 'password',
                        'value' => $password_new,
                    ]);

                    // Check if history creation failed
                    if (!$history) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to store history'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // Update history ID
                    $history->history_id = 'history_id-' . Str::uuid();
                    $history->save();

                    // Check if history update failed
                    if (!$history->wasChanged('history_id')) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to update history ID'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
            }

            $user_device = null;
            if (!empty($arr_data_logs['user_device'])) {
                $user_device = json_encode(Crypt::decrypt($arr_data_logs['user_device']), JSON_PRETTY_PRINT);
            }

            $log = LogsModel::create([
                'user_id' => $arr_data_logs['user_id'],
                'ip_address' => $request->ip(),
                'user_action' => strtoupper($arr_data_logs['user_action']),
                'user_device' => $user_device,
                'details' => json_encode($arr_data_logs['log_details'], JSON_PRETTY_PRINT),
            ]);

            if (!$log) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to store logs'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $log->log_id = 'log_id-' . Str::uuid();
            $log->save();

            // Check if log update failed
            if (!$log->wasChanged('log_id')) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to update log ID'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            DB::commit();
            return response()->json([
                'message' => $arr_data_logs['is_history'] == 1 ? 'Successfully stored logs and history' : 'Successfully stored logs'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logFormat($datas = [], $is_create_update_delete = 0, $path_image)
    {
        $arr_date = [
            'created_at',
            'updated_at',
            'phone_verified_at',
            'email_verified_at',
            'update_password_at',
            'paid_at',
            'last_used_at',
            'expires_at',
            'expiration_start_at',
            'expiration_end_at',
            'date_of_expense'
        ];

        $arr_fields_path = [
            'image',
            'file'
        ];

        if (!is_array($datas)) {
            return response()->json(['message' => 'Datas not an array'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!in_array($is_create_update_delete, [1, 2, 3, 4, 5])) {
            return response()->json(['message' => 'Invalid is create, update, delete, merge_arr, merge_obj'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $arr_result = [];

        if ($is_create_update_delete == 1) {
            foreach ($datas as $key => $data) {
                $arr_result[] = [
                    'label' => $key,
                    'new' => in_array($key, $arr_date)
                        ? $this->convertReadableTimeDate($data)
                        : (in_array($key, $arr_fields_path)
                            ? ($data != "" ? $path_image . $data : $data)
                            : $data),
                ];
            }
        } elseif ($is_create_update_delete == 2) {
            foreach ($datas as $key => $data) {
                $arr_result[] = [
                    'label' => $key,
                    'old' => in_array($key, $arr_date)
                        ? ($data['old'] !== null
                            ? $this->convertReadableTimeDate($data['old'])
                            : $data['old'])
                        : (in_array($key, $arr_fields_path)
                            ? ($data['old'] != "" ? $path_image . $data['old'] : $data['old'])
                            : $data['old']),

                    'new' => in_array($key, $arr_date)
                        ? ($data['new'] !== null
                            ? $this->convertReadableTimeDate($data['new'])
                            : $data['new'])
                        : (in_array($key, $arr_fields_path)
                            ? ($data['new'] != "" ? $path_image . $data['new'] : $data['new'])
                            : $data['new']),
                ];
            }
        } elseif ($is_create_update_delete == 3) {
            foreach ($datas as $key => $data) {
                $arr_result[] = [
                    'label' => $key,
                    'current' => in_array($key, $arr_date)
                        ? $this->convertReadableTimeDate($data)
                        : (in_array($key, $arr_fields_path)
                            ? ($data != "" ? $path_image . $data : $data)
                            : $data),
                ];
            }
        } else if ($is_create_update_delete == 4) {
            foreach ($datas as $key => $data) {
                $arr_result[] = [
                    'label' => $key,
                    'merge_arr' => in_array($key, $arr_date)
                        ? $this->convertReadableTimeDate($data) :  $data,
                ];
            }
        } else if ($is_create_update_delete == 5) {
            foreach ($datas as $key => $data) {
                $arr_result[] = [
                    'label' => $key,
                    'merge_obj' => in_array($key, $arr_date)
                        ? $this->convertReadableTimeDate($data) :  $data,
                ];
            }
        }

        return $arr_result;
    }

    public function userDevice(Request $request)
    {
        $isp_provider = $this->getUserISP($request->ip());
        $device = Browser::deviceType();
        $browser_type = Browser::isChrome() ? "Chrome" : (Browser::isFirefox() ? "Firefox" : (Browser::isOpera() ? "Opera" : (Browser::isSafari() ? "Safari" : (Browser::isIE() ? "IE" : (Browser::isEdge() ? "Edge" : "Unknown")))));
        $operating_system_type = Browser::isWindows() ? "Windows" : (Browser::isLinux() ? "Linux" : (Browser::isMac() ? "Mac" : (Browser::isAndroid() ? "Android" : "Unknown")));

        // Array of keys to check
        $keys = [
            'ip',
            'device_use',
            'eu_device',
            'browser_type',
            'operating_system_type',
            'browser_name',
            'platform_name',
            'browser_family',
            'browser_version',
            'browser_version_major',
            'browser_version_minor',
            'browser_version_patch',
            'browser_engine',
            'platform_family',
            'platform_version',
            'platform_version_major',
            'platform_version_minor',
            'platform_version_patch',
            'device_family',
            'device_model',
            'is_in_app',
        ];

        // Function to get the value based on key
        $getValue = function ($key) use ($request, $isp_provider, $device, $browser_type, $operating_system_type) {
            switch ($key) {
                case 'ip':
                    return $request->ip();
                case 'device_use':
                    return $device;
                case 'eu_device':
                    return json_encode($isp_provider, JSON_PRETTY_PRINT);
                case 'browser_type':
                    return $browser_type;
                case 'operating_system_type':
                    return $operating_system_type;
                case 'browser_name':
                    return Browser::browserName();
                case 'platform_name':
                    return Browser::platformName();
                case 'browser_family':
                    return Browser::browserFamily();
                case 'browser_version':
                    return Browser::browserVersion();
                case 'browser_version_major':
                    return Browser::browserVersionMajor();
                case 'browser_version_minor':
                    return Browser::browserVersionMinor();
                case 'browser_version_patch':
                    return Browser::browserVersionPatch();
                case 'browser_engine':
                    return Browser::browserEngine();
                case 'platform_family':
                    return Browser::platformFamily();
                case 'platform_version':
                    return Browser::platformVersion();
                case 'platform_version_major':
                    return Browser::platformVersionMajor();
                case 'platform_version_minor':
                    return Browser::platformVersionMinor();
                case 'platform_version_patch':
                    return Browser::platformVersionPatch();
                case 'device_family':
                    return Browser::deviceFamily();
                case 'device_model':
                    return Browser::deviceModel();
                case 'is_in_app':
                    return Browser::isInApp();
                default:
                    return null;
            }
        };

        // Create the device_infos array in the desired format
        $device_infos = array_map(function ($key) use ($getValue) {
            return [
                'label' => $key,
                'value' => $getValue($key),
            ];
        }, $keys);

        return response()->json([
            'message' => 'Successfully retrieved device information',
            'eu' => Crypt::encrypt($device_infos),
        ], Response::HTTP_OK);
    }

    private function getUserISP($ip)
    {
        $response = Http::get("http://ip-api.com/json/{$ip}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    // Validate Eu device
    public function validateEuDevice($eu_device)
    {
        try {
            // Decrypt the eu_device string
            $decrypt_eu_device = Crypt::decrypt($eu_device);

            // Ensure the decrypted value is an array
            if (!is_array($decrypt_eu_device)) {
                return response()->json(['message' => 'Decryption failed or invalid data format'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Array of expected labels
            $expected_labels = [
                'ip',
                'device_use',
                'eu_device',
                'browser_type',
                'operating_system_type',
                'browser_name',
                'platform_name',
                'browser_family',
                'browser_version',
                'browser_version_major',
                'browser_version_minor',
                'browser_version_patch',
                'browser_engine',
                'platform_family',
                'platform_version',
                'platform_version_major',
                'platform_version_minor',
                'platform_version_patch',
                'device_family',
                'device_model',
                'is_in_app',
            ];

            // Collect actual labels from decrypted data
            $actual_labels = array_column($decrypt_eu_device, 'label');

            // Check for missing labels
            $missing_labels = array_diff($expected_labels, $actual_labels);
            if (!empty($missing_labels)) {
                // return response()->json(['message' => 'Incorrect eu device: Missing labels ' . implode(', ', $missing_labels)], Response::HTTP_UNPROCESSABLE_ENTITY);
                return response()->json(['message' => 'Incorrect eu device: Missing labels '], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check for unexpected labels
            $unexpected_labels = array_diff($actual_labels, $expected_labels);
            if (!empty($unexpected_labels)) {
                return response()->json(['message' => 'Incorrect eu device: Unexpected labels '], Response::HTTP_UNPROCESSABLE_ENTITY);
                // return response()->json(['message' => 'Incorrect eu device: Unexpected labels ' . implode(', ', $unexpected_labels)], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check for correct structure (label and value)
            foreach ($decrypt_eu_device as $item) {
                if (!isset($item['label']) || !isset($item['value'])) {
                    return response()->json(['message' => 'Incorrect eu device: Each item must have a label and a value'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        } catch (\Exception $e) {
            // Handle any decryption or other errors
            return response()->json(['message' => 'Decryption error: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function handleUploadFile($arr_data_file, $is_original_name = 0)
    {
        $file_name = '';

        if ($is_original_name == 1) {
            $image_actual_name_without_extension = preg_replace('/[^a-zA-Z0-9]/', '_', $arr_data_file['image_actual_name_without_extension']);

            // Generate File Name
            $file_name =  $image_actual_name_without_extension . "_" . Carbon::now()->timestamp . "." . $arr_data_file['image_actual_extension'];

            // Generate the file path within the custom folder
            $file_path = $arr_data_file['custom_folder'] . '/' . $file_name;

            // Save on Storage
            Storage::disk('public')->put($file_path, file_get_contents($arr_data_file['file_image']));
        } else {
            // Generate File Name
            $file_name = Str::uuid() . "_" . Str::uuid() . "_" . mt_rand() . "_" . Carbon::now()->timestamp . "." . $arr_data_file['image_actual_extension'];

            // Generate the file path within the custom folder
            $file_path = $arr_data_file['custom_folder'] . '/' . $file_name;

            // Save on Storage
            Storage::disk('public')->put($file_path, file_get_contents($arr_data_file['file_image']));
        }

        return $file_name;
    }

    public function fileFormatSize($size)
    {
        $units = [
            'PB'    => 1024 ** 5,
            'TB'    => 1024 ** 4,
            'GB'    => 1024 ** 3,
            'MB'    => 1024 ** 2,
            'KB'    => 1024,
            'bytes' => 1
        ];
        foreach ($units as $unit => $bytes) {
            if ($size >= $bytes) {
                $formattedSize = $size / $bytes;
                $formattedSize = round($formattedSize, 2);
                return "{$formattedSize} {$unit}";
            }
        }

        return '0 bytes';
    }

    // This is for checking in index to display a button delete
    public function isExistIdOtherTbl($id, $modelAndId)
    {
        $arr_result = [];

        foreach ($modelAndId as $model => $columns) {
            foreach ($columns as $column) {
                if ($model == 'HistoryModel') {
                    $exists = HistoryModel::where($column, $id)->exists();
                    $data = HistoryModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }

                if ($model == 'LogsModel') {
                    $exists = LogsModel::where($column, $id)->exists();
                    $data = LogsModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }

                if ($model == 'PaymentModel') {
                    $exists = PaymentModel::where($column, $id)->exists();
                    $data = PaymentModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }

                if ($model == 'PurchaseModel') {
                    $exists = PurchaseModel::where($column, $id)->exists();
                    $data = PurchaseModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }

                if ($model == 'UserInfoModel') {
                    $exists = UserInfoModel::where($column, $id)->exists();
                    $data = UserInfoModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }

                if ($model == 'InventoryProductModel') {
                    $exists = InventoryProductModel::where($column, $id)->exists();
                    $data = InventoryProductModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }

                if ($model == 'VoucherModel') {
                    $exists = VoucherModel::where($column, $id)->exists();
                    $data = VoucherModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }

                if ($model == 'VoucherItemModel') {
                    $exists = VoucherItemModel::where($column, $id)->exists();
                    $data = VoucherItemModel::where($column, $id)->first();
                    if ($exists) {
                        $arr_result[] = [
                            'is_exist' => 'yes',
                            'model' => $model,
                            // 'data' => $data
                        ];
                    }
                }
            }
        }

        // Return 'notExist' if no match is found
        return $arr_result;
    }

    // Add column and data to store or update 
    public function addColumnAndValue($arr_result_data, $arr_field_appends, $model)
    {

        if (!is_array($arr_result_data)) {
            return 'Result data is not an array';
        } else if (!is_array($arr_field_appends)) {
            return 'Field to append is not an array';
        }

        foreach ($arr_field_appends as $arr_field_append) {
            $arr_result_data[$arr_field_append] = $model->$arr_field_append;
        }

        return $arr_result_data;
    }

    public function arrMergeContentAndFile(
        $request = null,
        $data = null,
        $file_name = '',
        $field_name = '',
    ) {
        $arr_merge_data = $data;

        // Check if the request has a file and if it's valid
        if ($request->hasFile($field_name) && $request->file($field_name)->isValid() && $file_name != '') {
            // Merge the $field_name key with the existing validated data
            $arr_merge_data[$field_name] = $file_name;
        }

        return $arr_merge_data;
    }


    // Store Multiple Data
    public function arrStoreMultipleData(
        $arr_store_fields,
        $user_input_data,
        $arr_decrypt_encrypted_ids = [],
        $arr_fields_make_lowercase = [],
        $arr_fields_make_uppercase = [],
        $arr_fields_to_encrypt = []
    ) {
        $arr_attributes_store = [];

        // Unset fields that don't have values
        $user_input_data = $this->unsetFieldsIsEmpty($user_input_data);

        foreach ($arr_store_fields as $arr_store_field) {
            if (array_key_exists($arr_store_field, $user_input_data)) {
                $value = $user_input_data[$arr_store_field];

                // Check if field should be converted to lowercase
                if (in_array($arr_store_field, $arr_fields_make_lowercase)) {
                    $value = strtolower($value);
                }

                // Check if field should be converted to uppercase
                if (in_array($arr_store_field, $arr_fields_make_uppercase)) {
                    $value = strtoupper($value);
                }

                // Check if field is encrypted and then decrypt it
                if (in_array($arr_store_field, $arr_decrypt_encrypted_ids)) {
                    $value = Crypt::decrypt($value);
                }

                // Check if the field should be encrypted
                if (in_array($arr_store_field, $arr_fields_to_encrypt)) {
                    $value = Crypt::encrypt($value);
                }

                $arr_attributes_store[$arr_store_field] = $value;
            }
        }

        return $arr_attributes_store;
    }


    // public function arrStoreMultipleData(
    //     $arr_store_fields,
    //     $user_input_data,
    //     $file_name = '',
    //     $arr_decrypt_encrypted_ids = [],
    //     $arr_fields_make_lowercase = [],
    //     $arr_fields_make_uppercase = [],
    //     $arr_fields_to_encrypt = [],
    // ) {
    //     $arr_attributes_store = [];
    //     // $arr_encrypted_ids_to_decrypt = [
    //     //     'inventory_id',

    //     //     // purchase and payment tbl
    //     //     'payment_id',
    //     //     'user_id',
    //     //     'purchase_group_id',

    //     //     // Voucher tbl
    //     //     'voucher_id',

    //     //     // Expense image tbl
    //     //     'expenses_id',
    //     // ];

    //     // Unset fields don't have value
    //     $user_input_data = $this->unsetFieldsIsEmpty($user_input_data);

    //     foreach ($arr_store_fields as $arr_store_field) {
    //         if (array_key_exists($arr_store_field, $user_input_data)) {
    //             // Check if to encrypt fields with image
    //             if (in_array($arr_store_field, $arr_fields_to_encrypt)) {
    //                 $value = $user_input_data[$arr_store_field];

    //                 // Check if field should be converted to lowercase
    //                 if (in_array($arr_store_field, $arr_fields_make_lowercase)) {
    //                     $value = strtolower($value);
    //                 }

    //                 // Check if field should be converted to uppercase
    //                 if (in_array($arr_store_field, $arr_fields_make_uppercase)) {
    //                     $value = strtoupper($value);
    //                 }

    //                 $value = Crypt::encrypt($user_input_data[$arr_store_field]);
    //                 $arr_attributes_store[$arr_store_field] = $value;
    //             }

    //             // Normal data
    //             else {
    //                 if ($arr_store_field === 'image') {
    //                     $arr_attributes_store[$arr_store_field] = $file_name;
    //                 } else if ($arr_store_field === 'file') {
    //                     $arr_attributes_store[$arr_store_field] = $file_name;
    //                 } else {
    //                     $value = $user_input_data[$arr_store_field];

    //                     // Check if field should be converted to lowercase
    //                     if (in_array($arr_store_field, $arr_fields_make_lowercase)) {
    //                         $value = strtolower($value);
    //                     }

    //                     // Check if field should be converted to uppercase
    //                     if (in_array($arr_store_field, $arr_fields_make_uppercase)) {
    //                         $value = strtoupper($value);
    //                     }

    //                     // Check if field is encrypted then decrypt it
    //                     if (in_array($arr_store_field, $arr_decrypt_encrypted_ids)) {
    //                         $value = Crypt::decrypt($value);
    //                     }

    //                     $arr_attributes_store[$arr_store_field] = $value;
    //                 }
    //             }
    //         }
    //     }

    //     return $arr_attributes_store;
    // }


    // public function arrStoreMultipleData($arr_store_fields, $user_input_data, $file_name = '', $arr_fields_make_lowercase = [], $arr_ids_exempted_to_decrypt = [])
    // {
    //     Log::info('Starting arrStoreMultipleData function', [
    //         'arr_store_fields' => $arr_store_fields,
    //         'user_input_data' => $user_input_data,
    //         'file_name' => $file_name,
    //         'arr_fields_make_lowercase' => $arr_fields_make_lowercase,
    //         'arr_ids_exempted_to_decrypt' => $arr_ids_exempted_to_decrypt
    //     ]);


    //     $arr_attributes_store = [];
    //     $arr_encrypted_ids_to_decrypt = [
    //         'inventory_id',

    //         // purchase and payment tbl
    //         'payment_id',
    //         'user_id',
    //         'purchase_group_id',

    //         // Voucher tbl
    //         'voucher_id',

    //         // Expense image tbl
    //         'expenses_id',
    //     ];

    //     foreach ($arr_store_fields as $arr_store_field) {
    //         if (array_key_exists($arr_store_field, $user_input_data)) {
    //             Log::info('Processing field', ['field' => $arr_store_field]);

    //             if ($arr_store_field === 'file') {
    //                 $arr_attributes_store[$arr_store_field] = $file_name;
    //                 Log::info('File field detected', ['file_name' => $file_name]);
    //             } else {
    //                 $value = $user_input_data[$arr_store_field];
    //                 Log::info('Initial value', ['field' => $arr_store_field, 'value' => $value]);

    //                 // Check if field should be converted to lowercase
    //                 if (in_array($arr_store_field, $arr_fields_make_lowercase)) {
    //                     $value = strtolower($value);
    //                     Log::info('Converted to lowercase', ['field' => $arr_store_field, 'value' => $value]);
    //                 }

    //                 // Decrypt the value if the field is in the encrypted IDs array and not in the exempted array
    //                 if (!in_array($arr_store_field, $arr_ids_exempted_to_decrypt) && in_array($arr_store_field, $arr_encrypted_ids_to_decrypt)) {
    //                     $value = Crypt::decrypt($value);
    //                     Log::info('Decrypted value', ['field' => $arr_store_field, 'value' => $value]);
    //                 }

    //                 $arr_attributes_store[$arr_store_field] = $value;
    //                 Log::info('Stored value', ['field' => $arr_store_field, 'value' => $value]);
    //             }
    //         } else {
    //             Log::info('Field not found in user input data', ['field' => $arr_store_field]);
    //         }
    //     }

    //     Log::info('Completed arrStoreMultipleData function', ['arr_attributes_store' => $arr_attributes_store]);

    //     return $arr_attributes_store;
    // }


    // Update a unique I.D on store and update
    public function updateUniqueId($model, $id_to_updates, $id)
    {
        DB::beginTransaction();
        try {
            // Update the unique id
            foreach ($id_to_updates as $id_to_updates_key => $id_to_updates_value) {
                $model->update([$id_to_updates_key => $id_to_updates_value . $id]);
            }

            if (!$model->save()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to update unique id'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function arrUpdateMultipleData(
        $model,
        $arr_update_fields,
        $user_input_data,
        $arr_fields_make_lowercase = [],
        $arr_fields_make_uppercase = [],
        $arr_fields_to_encrypt = []
    ) {
        // Unset fields that don't have values
        $user_input_data = $this->unsetFieldsIsEmpty($user_input_data);

        DB::beginTransaction();

        try {
            foreach ($arr_update_fields as $arr_update_field) {
                if (isset($user_input_data[$arr_update_field])) {
                    $value = $user_input_data[$arr_update_field];

                    // Handle lowercase conversion
                    if (in_array($arr_update_field, $arr_fields_make_lowercase)) {
                        $value = strtolower($value);
                    }

                    // Handle uppercase conversion
                    if (in_array($arr_update_field, $arr_fields_make_uppercase)) {
                        $value = strtoupper($value);
                    }

                    // Handle encryption
                    if (in_array($arr_update_field, $arr_fields_to_encrypt)) {
                        $value = Crypt::encrypt($value);
                    }

                    // Assign the processed value to the model
                    $model->$arr_update_field = $value;
                }
            }

            if (!$model->save()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to update'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred', $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // public function arrUpdateMultipleData(
    //     $model,
    //     $arr_update_fields,
    //     $user_input_data,
    //     $arr_fields_make_lowercase = [],
    //     $arr_fields_make_uppercase = [],
    //     $arr_fields_to_encrypt = []
    // ) {
    //     // Unset fields that don't have values
    //     $user_input_data = $this->unsetFieldsIsEmpty($user_input_data);

    //     DB::beginTransaction();

    //     // Capture the original values
    //     foreach ($arr_update_fields as $arr_update_field) {
    //         if (isset($model->$arr_update_field)) {
    //             $original_values[$arr_update_field] = $model->$arr_update_field;
    //         }
    //     }

    //     try {
    //         foreach ($arr_update_fields as $arr_update_field) {

    //             if (isset($user_input_data[$arr_update_field])) {

    //                 $value = $user_input_data[$arr_update_field];

    //                 // Handle lowercase conversion
    //                 if (in_array($arr_update_field, $arr_fields_make_lowercase)) {
    //                     $value = strtolower($value);
    //                 }

    //                 // Handle uppercase conversion
    //                 if (in_array($arr_update_field, $arr_fields_make_uppercase)) {
    //                     $value = strtoupper($value);
    //                 }

    //                 // Handle encryption
    //                 if (in_array($arr_update_field, $arr_fields_to_encrypt)) {
    //                     $value = Crypt::encrypt($value);
    //                 }

    //                 // Assign the processed value to the model
    //                 $model->$arr_update_field = $value;
    //             }
    //         }

    //         if (!$model->save()) {
    //             DB::rollBack();
    //             return response()->json(['message' => 'Failed to update'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }

    //         DB::commit();
    //         return response()->json(['message' => 'Update successful'], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }


    // public function arrUpdateMultipleData(
    //     $model,
    //     $arr_update_fields,
    //     $user_input_data,
    //     $file_name = '',
    // ) {
    //     DB::beginTransaction();

    //     try {
    //         foreach ($arr_update_fields as $arr_update_field) {
    //             if ($arr_update_field == 'image' || $arr_update_field == 'file') {
    //                 if ($file_name !== '') {
    //                     $model->$arr_update_field = $file_name;
    //                 } else {
    //                     unset($model->$arr_update_field); // Unset the image or file field if $file_name is empty
    //                 }
    //             } elseif (!empty($user_input_data[$arr_update_field])) {
    //                 $model->$arr_update_field = $user_input_data[$arr_update_field];
    //             }
    //         }
    //         if (!$model->save()) {
    //             DB::rollBack();
    //             return response()->json(['message' => 'Failed to update inventory'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }

    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }


    // public function updateLogsOldNew($model, $arr_update_fields, $user_input_data, $file_name)
    // {
    //     $changes_item_for_logs = [];

    //     foreach ($arr_update_fields as $arr_update_field) {
    //         $existing_value = $model->$arr_update_field ?? null;
    //         $new_value = isset($user_input_data[$arr_update_field]) ? $user_input_data[$arr_update_field] : null;

    //         if ($arr_update_field != 'file') {
    //             // Check if the value has changed
    //             if ($existing_value != $new_value) {
    //                 $changes_item_for_logs[$arr_update_field] = [
    //                     'old' => $existing_value,
    //                     'new' => $new_value,
    //                 ];
    //             }
    //         } else {
    //             $new_value =  $file_name != '' ? $file_name : null;

    //             if ($existing_value == null && $new_value != null) {
    //                 $changes_item_for_logs['file'] = [
    //                     'old' => $existing_value,
    //                     'new' => $file_name,
    //                 ];
    //             } else if ($existing_value != null && $new_value != null) {
    //                 $changes_item_for_logs['file'] = [
    //                     'old' => $existing_value,
    //                     'new' => $file_name,
    //                 ];
    //             }

    //             $model->{$arr_update_field} = $new_value; // Set the new value
    //         }
    //     }


    //     return $changes_item_for_logs;
    // }

    public function updateLogsOldNew(
        $model = null,
        $arr_update_fields = [],
        $user_input_data = [],
        $is_lower_case_result_merge_data = 0,
        $is_upper_case_result_merge_data = 0,
    ) {
        $changes_item_for_logs = [];

        foreach ($arr_update_fields as $arr_update_field) {
            $existing_value = isset($model->$arr_update_field)
                ? ($this->isEncrypted($model->$arr_update_field) ? Crypt::decrypt($model->$arr_update_field) : $model->$arr_update_field)
                : null;
            $new_value = $user_input_data[$arr_update_field] ?? null;

            // Apply transformations based on flags
            if ($is_upper_case_result_merge_data === 1) {
                $new_value = strtoupper($new_value);
            }

            if ($is_lower_case_result_merge_data === 1) {
                $new_value = strtolower($new_value);
            }

            if ($existing_value != $new_value) {
                $changes_item_for_logs[$arr_update_field] = [
                    'old' => $existing_value,
                    'new' => $new_value,
                ];
            }
        }

        // unset the data if new is null
        foreach ($changes_item_for_logs as $key => $values) {
            if (is_null($values['new'])) {
                unset($changes_item_for_logs[$key]);
            } else {
                $fillable_column = $model->getFillable()[0];
                $changes_item_for_logs[$fillable_column] = [
                    'old' => $model->{$fillable_column},
                    'new' => $model->{$fillable_column},
                ];
            }
        }

        if (empty($changes_item_for_logs)) {
            return response()->json(['message' => 'No changes have been made'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $changes_item_for_logs;
    }

    // public function updateLogsOldNew($model, $arr_update_fields, $user_input_data, $file_name)
    // {
    //     $changes_item_for_logs = [];

    //     foreach ($arr_update_fields as $arr_update_field) {
    //         $existing_value = $model->$arr_update_field ?? null;
    //         $new_value = $user_input_data[$arr_update_field] ?? null;

    //         if ($arr_update_field !== 'file' && $arr_update_field !== 'image') {
    //             // Non-file fields: check if the value has changed
    //             if ($existing_value != $new_value) {
    //                 $changes_item_for_logs[$arr_update_field] = [
    //                     'old' => $existing_value,
    //                     'new' => $new_value,
    //                 ];
    //             }
    //         } else {
    //             // File or Image field handling
    //             $new_value = $file_name !== '' ? $file_name : null;

    //             // Check if the file or image has been added or changed
    //             if ($existing_value !== $new_value) {
    //                 $changes_item_for_logs[$arr_update_field] = [
    //                     'old' => $existing_value,
    //                     'new' => $new_value,
    //                 ];
    //                 // Update the model with the new file/image name
    //                 $model->$arr_update_field = $new_value;
    //             }
    //         }
    //     }

    //     return $changes_item_for_logs;
    // }


    public function checkIfTheresChangesLogs($datas)
    {
        foreach ($datas as $data) {
            if (array_key_exists('fields', $data) && is_array($data['fields']) && empty($data['fields'])) {
                return response()->json(['message' => 'No changes have been made'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
    }

    public function convertReadableTimeDate($data)
    {
        // Set the timezone for Carbon to 'Asia/Manila'
        // Carbon::setToStringFormat('F j, Y g:i a');
        $carbon_date = Carbon::parse($data)->setTimezone('Asia/Manila');
        $value = $carbon_date->format('F j, Y g:i:s a');
        // $value = $carbon_date->format('F j, Y');


        return $value;
    }


    public function convertReadableDate($data)
    {
        // Set the timezone for Carbon to 'Asia/Manila'
        // Carbon::setToStringFormat('F j, Y g:i a');
        $carbon_date = Carbon::parse($data)->setTimezone('Asia/Manila');
        $value = $carbon_date->format('F j, Y');
        // $value = $carbon_date->format('F j, Y');


        return $value;
    }


    // Unset the fields if empty
    public function unsetFieldsIsEmpty($data)
    {
        foreach ($data as $key => $value) {
            if ($value === "") {
                unset($data[$key]);
            }
        }

        return $data;
    }

    // public function convertReadableTimeDate($data)
    // {
    //     // Set the timezone for Carbon to 'Asia/Manila'
    //     $carbon_date = Carbon::parse($data)->setTimezone('Asia/Manila');
    //     $value = $carbon_date->format('d-m-Y'); // Correct date format

    //     return $value;
    // }

    public function faker12DigitNumber()
    {
        $faker = Faker::create();

        return $faker->numberBetween(100000000000, 999999999999);
    }

    public function faker6DigitNumber()
    {
        $faker = Faker::create();

        return $faker->numberBetween(100000, 999999);
    }

    public function fakerLoremIpsum()
    {
        $faker = Faker::create();

        return $faker->sentence();
    }

    public function fakerName()
    {
        $faker = Faker::create();

        return $faker->name();
    }

    public function isEncrypted($value)
    {
        try {
            Crypt::decrypt($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }

    // Invalidate the jwt token
    public function invalidateTokenJwt()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    // Generate 6 random string
    public function generateRandomString($length = 6)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }


    public function strToLowerCaseFirstUpperCase($data)
    {
        return ucfirst(strtolower($data));
    }

    public function timeAgo($timestamp)
    {
        $time = strtotime($timestamp);
        $diff = time() - $time;

        if ($diff < 60) {
            return $diff . 's ago';
        } elseif ($diff < 3600) {
            return round($diff / 60) . 'm ago';
        } elseif ($diff < 86400) {
            return round($diff / 3600) . 'h ago';
        } elseif ($diff < 604800) {
            return round($diff / 86400) . 'd ago';
        } elseif ($diff < 2592000) { // 30 days
            return round($diff / 604800) . 'w ago';
        } elseif ($diff < 31536000) { // 365 days
            return date('F j, Y g:i:s a', $time); // Format: August 17, 2024 9:07:35 pm
        } else {
            return date('F j, Y g:i:s a', $time); // Format: August 17, 2024 9:07:35 pm
        }
    }

    public function formatLogsEncDataOldNew($changes_for_logs)
    {
        $arr = [];
        foreach ($changes_for_logs as $field => $change) {
            // Check if 'oldEnc' and 'newEnc' exist in $change before encrypting
            $encryptedOldValue = isset($change['old']) ? Crypt::encrypt($change['old']) : null;
            $encryptedNewValue = isset($change['new']) ? Crypt::encrypt($change['new']) : null;

            // Store the original field name and its encrypted values in $arr['fields']
            $arr[$field] = [
                'old' => $encryptedOldValue,
                'new' => $encryptedNewValue,
            ];
        }

        return $arr;
    }

    public function formatLogsEncDataNew($changes_for_logs)
    {
        $arr = [];
        foreach ($changes_for_logs as $field => $change) {
            $encryptedNewValue = isset($change['new']) ? Crypt::encrypt($change['new']) : null;

            // Store the original field name and its encrypted values in $arr['fields']
            $arr[$field] = [
                'new' => $encryptedNewValue,
            ];
        }

        return $arr;
    }
}
