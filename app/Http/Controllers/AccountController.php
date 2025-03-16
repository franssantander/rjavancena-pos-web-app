<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\AuthModel;
use Illuminate\Support\Str;
use App\Models\HistoryModel;
use Illuminate\Http\Request;
use App\Models\UserInfoModel;
use App\Mail\VerificationMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{

    protected $helper, $fillable_attr_auth, $fillable_attr_user_info;

    public function __construct(Helper $helper, AuthModel $fillable_attr_auth, UserInfoModel $fillable_attr_user_info)
    {
        $this->helper = $helper;
        $this->fillable_attr_auth = $fillable_attr_auth;
        $this->fillable_attr_user_info = $fillable_attr_user_info;
    }

    /**
     * GET ALL USER ACCOUNT AND USER INFORMATION | ADMIN SIDE
     * Fetch all data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Add action
        $crud_settings = $this->fillable_attr_auth->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_auth->getApiRelativeSettings();
        $view_settings = $this->fillable_attr_auth->getViewRowTable();
        $arr_parent_items = [];
        $arr_all_items = [];
        $arr_checking = [];

        $filter = [];

        // Filter Column
        $filter_status = $this->helper->upperCaseValueSelectTagFilter($this->fillable_attr_auth->arrEnvAccountStatus());
        $filter_status_add_user = $this->helper->upperCaseValueSelectTagFilter($this->fillable_attr_auth->arrEnvAccountStatus());
        $filter_role = $this->helper->upperCaseValueSelectTagFilter($this->fillable_attr_auth->arrEnvAccountRole());
        $filter[] = [
            'status' => [
                'type' => 'select',
                'option' => $filter_status
            ],
            'role' => [
                'type' => 'select',
                'option' => $filter_role
            ]
        ];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Retrieve all AuthModel records
        // $auth_users = AuthModel::orderBy('created_at', 'desc')->get();

        // Fetch paginated inventory items for the current page
        $auth_users = AuthModel::orderBy('created_at', 'desc'); // Ensure results are ordered for consistent pagination

        // Apply status filter if provided
        if ($request->query('status') != '') {
            $auth_users = $auth_users->where('status', $request->query('status'));
        }

        // Apply pagination
        $auth_users = $auth_users->paginate(
            $request->query('limit', 10), // Items per page
            ['*'], // Select all columns
            'page', // Pagination parameter name
            $request->query('page', 1) // Current page
        );


        $ctr = 1;
        // Data
        foreach ($auth_users as $auth_user) {
            foreach ($this->fillable_attr_auth->columnHeader() as $columnHeader) {
                // Default value for non-existing columns
                $arr_parent_items[$columnHeader] = null;
                if ($columnHeader == 'user_id') {
                    if (isset($auth_user->$columnHeader)) {
                        $arr_parent_items[$columnHeader] = $auth_user->$columnHeader;
                    }
                } elseif ($columnHeader == 'image') {
                    $user_info = UserInfoModel::where('user_id', $auth_user->user_id)->first();
                    if ($user_info && isset($user_info->$columnHeader) && $this->helper->isEncrypted($user_info->$columnHeader)) {
                        $arr_parent_items[$columnHeader] = $user_info->$columnHeader ? env("PATH_FILE_USER_ACCOUNT") . Crypt::decrypt($user_info->$columnHeader) : null;
                    }
                } elseif ($columnHeader == 'name') {
                    $user_info = UserInfoModel::where('user_id', $auth_user->user_id)->first();
                    if ($user_info) {
                        $first_name = isset($user_info->first_name) && $this->helper->isEncrypted($user_info->first_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->first_name)) : $user_info->first_name;
                        $last_name = isset($user_info->last_name) && $this->helper->isEncrypted($user_info->last_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->last_name)) : $user_info->last_name;
                        $arr_parent_items[$columnHeader] = trim("{$first_name} {$last_name}");
                    }
                } elseif ($columnHeader == 'role') {
                    $value = strtolower($this->helper->transformColumnName($auth_user->$columnHeader));
                    $arr_parent_items[$columnHeader] = ucwords($value);
                } elseif ($columnHeader == 'status') {
                    // Initialize status_color if not already an array
                    if (!isset($arr_parent_items['status_color'])) {
                        $arr_parent_items['status_color'] = null;
                    }
                    // Loop through status_colors to find the matching status
                    foreach ($this->fillable_attr_auth->statusColor() as $key => $statusColor) {
                        if ($auth_user->$columnHeader == $key) {
                            $arr_parent_items['status_color'] = $statusColor;
                            break; // Exit the loop once the matching status is found
                        }
                    }
                    $value = strtolower($auth_user->$columnHeader);
                    $arr_parent_items[$columnHeader] = ucfirst($value);
                } elseif ($columnHeader == 'password') {
                    $history = HistoryModel::where('tbl_id', $auth_user->user_id ?? null)
                        ->where('tbl_name', 'users_tbl')
                        ->where('column_name', 'password')
                        ->latest()
                        ->first();
                    if ($history) {
                        $arr_parent_items[$columnHeader] = Crypt::decrypt($history->value);
                    } else {
                        // Handle the case where the history does not exist
                        $arr_parent_items[$columnHeader] = null; // or any default value you want to set
                    }
                } else if (in_array($columnHeader, $this->fillable_attr_auth->arrToConvertToReadableDateTime())) {
                    $arr_parent_items[$columnHeader] = $this->helper->convertReadableTimeDate($auth_user->$columnHeader);
                } else {
                    if (isset($auth_user->$columnHeader) && $this->helper->isEncrypted($auth_user->$columnHeader)) {
                        $arr_parent_items[$columnHeader] = Crypt::decrypt($auth_user->$columnHeader);
                    } else {
                        if (isset($auth_user->$columnHeader) && $auth_user->$columnHeader) {
                            $arr_parent_items[$columnHeader] = $auth_user->$columnHeader;
                        }
                    }

                    // Add the email_verified_at for condition not display pending on option select tag update 
                    $arr_checking['email_verified_at'] = $auth_user->email_verified_at;
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

            // Checking Id on other tbl if exist unset the api
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($auth_user->user_id, $this->fillable_attr_auth->arrModelWithId());
            // Unset actions based on conditions
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                foreach ($this->fillable_attr_auth->unsetActions() as $unsetAction) {
                    $crud_action = array_filter($crud_action, function ($action) use ($unsetAction) {
                        return $action['button_name'] !== ucfirst($unsetAction);
                    });
                }
            }

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

                // Populate details for each attribute auth account
                if ($action['button_name'] == 'Edit account') {
                    $fieldTypes = [
                        'phone_number' => 'number',
                        'email' => 'email',
                        'password' => 'password',
                        'role' => 'select',
                        'status' => 'select'
                    ];

                    $selectOptions = [
                        'role' => $filter_role,
                        'status' => $filter_status
                    ];

                    foreach ($this->fillable_attr_auth->arrDetails() as $arrDetails) {
                        if ($arrDetails == 'password') {
                            $action['details'][] = [
                                'label' => 'Password',
                                'type' => 'password',
                                'value' => null,
                                'option' => null
                            ];
                            $action['details'][] = [
                                'label' => 'Password Confirmation',
                                'type' => 'password',
                                'value' => null,
                                'option' => null
                            ];
                        } else {
                            $type = $fieldTypes[$arrDetails] ?? 'input';
                            $option = $selectOptions[$arrDetails] ?? null;

                            // Specific logic for 'status'
                            if ($arrDetails == 'status') {
                                if (isset($arr_checking['email_verified_at']) && $arr_checking['email_verified_at'] != null) {
                                    foreach ($filter_status as $key => $value) {
                                        if ($value['value'] == 'PENDING') {
                                            unset($filter_status[$key]);
                                        }
                                    }
                                }
                                $option = array_values($filter_status); // Reindex the array to ensure it does not have numeric keys
                            }

                            $action['details'][] = [
                                'label' => $this->helper->transformColumnName($arrDetails),
                                'type' => $type,
                                'value' => $arr_parent_items[$arrDetails] ?? null,
                                'option' => $option
                            ];
                        }
                    }
                }

                // Populate details for each attribute user info
                else if ($action['button_name'] == 'Edit user information') {
                    // Get the user id and get the value on user info tbl
                    $user_info = UserInfoModel::where('user_id', $arr_parent_items['user_id'])->first();

                    $fieldTypes = [
                        'image' => 'file',
                        // 'contact_number' => 'number',
                        // 'contact_email' => 'email',
                        'region_name' => 'select',
                        'province_name' => 'select',
                        'city_or_municipality_name' => 'select',
                        'barangay_name' => 'select',
                        'description_location' => 'textarea'
                    ];

                    $codeToNameMapping = [
                        'region_name' => 'region_code',
                        'province_name' => 'province_code',
                        'city_or_municipality_name' => 'city_or_municipality_code',
                        'barangay_name' => 'barangay_code'
                    ];

                    foreach ($this->fillable_attr_user_info->arrDetails() as $arrDetails) {
                        $type = $fieldTypes[$arrDetails] ?? 'input'; // Default to 'input' if not found in $fieldTypes

                        if ($arrDetails == 'image') {
                            $value = null; // Set value to null for image field
                        } else {
                            $value = $user_info ? $user_info->$arrDetails : null;
                            if ($value && $this->helper->isEncrypted($value)) {
                                $value = Crypt::decrypt($value); // Decrypt value if it's encrypted
                            }
                        }

                        $value_name = null;

                        // Check if this field has a code and needs to be mapped to a name
                        if (array_key_exists($arrDetails, $codeToNameMapping)) {
                            $codeField = $codeToNameMapping[$arrDetails];
                            $codeValue = $user_info ? $user_info->$codeField : null;
                            if ($codeValue && $this->helper->isEncrypted($codeValue)) {
                                $codeValue = Crypt::decrypt($codeValue); // Decrypt value if it's encrypted
                            }
                            $value_name = $value;
                            $value = $codeValue;
                        }

                        $action['details'][] = [
                            'label' => $this->helper->transformColumnName($arrDetails),
                            'type' => $type,
                            'value' => $value,
                            'value_name' => $this->helper->transformColumnName($value_name),
                            'option' => null
                        ];
                    }
                } else if ($action['button_name'] == 'Delete') {
                    // Check if 'details' key doesn't exist, then add it
                    if (!isset($action['name'])) {
                        $action['name'] = [];
                    }

                    $action['name'] = $arr_parent_items['name'];
                }

                // $action['user_id'] =  Crypt::encrypt($arr_parent_items['user_id']);
                $action['user_id'] =  Crypt::encrypt($arr_parent_items['user_id']);
            }
            // ***************************** //

            // Add view on row item
            $arr_parent_items['view'] = [[
                'url' => $view_settings['url'] . Crypt::encrypt($arr_parent_items['user_id']) ?? null,
                'method' => $view_settings['method']
            ]];

            // Add the decrypted user data to the array
            $arr_all_items[] = $arr_parent_items;
            $ctr++;
        }

        // Final response structure
        $response = [
            'account' => $arr_all_items,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_auth->columnHeader()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'filter' => $filter,
            'pagination' => [
                'count' => $auth_users->count(),
                'has_page' => $auth_users->hasPages(),
                'has_more_pages' => $auth_users->hasMorePages(),
                'current_page' => $auth_users->currentPage(),
                'last_page' => $auth_users->lastPage(),
                'per_page' => $auth_users->perPage(),
                'next_page_url' => $auth_users->nextPageUrl(),
                'previous_page_url' => $auth_users->previousPageUrl(),
            ],
        ];

        // ***************************** //
        // Add details on action buttons
        foreach ($response['buttons'] as &$buttons) {
            // Check if 'details' key doesn't exist, then add it
            if (!isset($buttons['details'])) {
                $buttons['details'] = [];
            }

            // Populate details for each attribute
            $fieldTypes = [
                'phone_number' => 'number',
                'email' => 'email',
                'password' => 'password',
                'role' => 'select',
                'status' => 'select',
                'image' => 'file',
                // 'contact_number' => 'number',
                // 'contact_email' => 'email',
                // 'region_code' => 'hidden',
                // 'province_code' => 'hidden',
                // 'city_or_municipality_code' => 'hidden',
                // 'barangay_code' => 'hidden',
                'region_name' => 'select',
                'province_name' => 'select',
                'city_or_municipality_name' => 'select',
                'barangay_name' => 'select',
                'description_location' => 'textarea'
            ];

            $selectOptions = [
                'role' => $filter_role,
                'status' => $filter_status_add_user,
                'region_name' => null,
                'province_name' => null,
                'city_or_municipality_name' => null,
                'barangay_name' => null
            ];

            foreach ($this->fillable_attr_auth->arrDetailsAccountWithUserInfo() as $arrDetailsAccountWithUserInfo) {
                $type = $fieldTypes[$arrDetailsAccountWithUserInfo] ?? 'input'; // Default to 'input' if not found in $fieldTypes

                if ($arrDetailsAccountWithUserInfo == 'password') {
                    $buttons['details'][] = [
                        'label' => 'Password',
                        'type' => 'password',
                        'value' => null,
                        'option' => null
                    ];
                    $buttons['details'][] = [
                        'label' => 'Password Confirmation',
                        'type' => 'password',
                        'value' => null,
                        'option' => null
                    ];
                } else {
                    $buttons['details'][] = [
                        'label' => $this->helper->transformColumnName($arrDetailsAccountWithUserInfo),
                        'type' => $type,
                        'value' => null,
                        'option' => $selectOptions[$arrDetailsAccountWithUserInfo] ?? null
                    ];
                }
            }
        }
        // ***************************** //

        // Display or use the decrypted attributes as needed
        return response()->json([
            'message' => "Successfully retrieve data",
            'data' => $response
        ], Response::HTTP_OK);
    }

    /**
     * GET SPECIFIC USER ACCOUNT | ADMIN SIDE
     * Fetch specific data
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id)
    {
        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        if (empty($id) || $id == null || $id == '') {
            return response()->json(['message' => 'Invalid I.D'], Response::HTTP_NOT_FOUND);
        }

        // Decrypt all emails and other attributes
        $decrypted_user_auth = [];
        $column_name = [];

        // Unset Column not needed
        $unset_results = $this->helper->unsetColumn($this->fillable_attr_auth->unsetForRetrieves(), $this->fillable_attr_auth->getFillableAttributes());

        // Retrieve AuthModel record
        $auth_user = AuthModel::where('user_id', Crypt::decrypt($id))->first();

        if (!$auth_user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        foreach ($unset_results as $column) {
            if ($column == 'user_id') {
                $user_info = UserInfoModel::where('user_id', $auth_user->user_id)->first();
                $decrypted_user_auth['data']['userInfo'] = [
                    'image' => $user_info && $user_info->image ? Crypt::decrypt($user_info->image) : null,
                ];
                $decrypted_user_auth['data']['id'] = Crypt::encrypt($auth_user->{$column});
                $column_name[] = $this->helper->transformColumnName('Id');
            } else if ($column == 'email') {
                $decrypted_user_auth['data'][$column] = $auth_user->{$column} ? Crypt::decrypt($auth_user->{$column}) : null;
                $history = HistoryModel::where('tbl_id', $auth_user->user_id)->where('tbl_name', 'users_tbl')->where('column_name', 'password')->latest()->first();
                $decrypted_user_auth['data']['password'] = $history ? Crypt::decrypt($history->value) : null;
                $column_name[] = $this->helper->transformColumnName($column);
                $column_name[] = 'password';
            } else if ($column == 'role') {
                $column_name[] = $this->helper->transformColumnName($column);
                foreach ($this->fillable_attr_auth->arrEnvRoles() as $roleEnv => $roleLabel) {
                    if ($auth_user->{$column} == env($roleEnv)) {
                        $decrypted_user_auth['data'][$column] = $roleLabel;
                        break;
                    }
                }
            } else {
                // Keep other columns as they are
                $column_name[] = $this->helper->transformColumnName($column);

                if (in_array($column, $this->fillable_attr_auth->arrToConvertToReadableDateTime()) && $auth_user->{$column} !== null) {
                    $decrypted_auth_user[$column] = $this->helper->convertReadableTimeDate($auth_user->{$column});
                }
            }
        }

        // Add new columns
        $new_columns = [
            "User Info",
            "Action"
        ];
        $transformedColumns = array_map(function ($column_name) {
            return $this->helper->transformColumnName($column_name);
        }, $column_name);
        array_unshift($transformedColumns, $this->helper->transformColumnName($new_columns[0]));
        array_push($transformedColumns, $this->helper->transformColumnName($new_columns[1]));

        // Display or use the decrypted attributes as needed
        return response()->json([
            'message' => "Successfully retrieve data",
            'data' => [$decrypted_user_auth]
        ], Response::HTTP_OK);
    }

    /**
     * STORE USER ACCOUNT | ADMIN SIDE
     * store
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $verification_number = mt_rand(100000, 999999);
        $arr_log_details = [];

        do {
            $user_id = Str::uuid()->toString();
        } while (AuthModel::where('user_id', $user_id)->exists());

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'phone_number' => $request->input('phone_number') != '' ? 'numeric|min:11' : 'nullable',
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        // Begin transaction
        DB::beginTransaction();
        try {
            $accounts = AuthModel::all();
            // Decrypt and validate email if exist
            foreach ($accounts as $account_item) {
                // Validate if exist phone number or email
                if ($request->filled('phone_number')) {
                    $decrypted_phone_number = Crypt::decrypt($account_item->email);
                    // Check if the requested email exists in the decrypted emails and email_verified_at is null then send verification code
                    if ($decrypted_phone_number == $request->phone_number && $account_item->phone_number_verified_at != null && Crypt::decrypt($request->user_id) != $account_item->user_id) {
                        return response()->json(
                            [
                                'phone_number' => 'Phone number already exist'
                            ],
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }
                }
                if ($request->filled('email')) {
                    $decrypted_email = Crypt::decrypt($account_item->email);
                    if ($decrypted_email == $request->email && $account_item->email_verified_at != null && Crypt::decrypt($request->user_id) != $account_item->user_id) {
                        return response()->json(
                            [
                                'message' => [
                                    'email' => [
                                        'Email already exists'
                                    ]
                                ]
                            ],
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }
                }
            }

            // Store only have value   
            foreach ($this->fillable_attr_auth->arrToStores() as $arrStoreField) {
                if ($arrStoreField == 'user_id') {
                    $arr_validates[$arrStoreField] = $user_id;
                    $arr_log_details[$arrStoreField] = $user_id;
                } else if ($arrStoreField == 'phone_number' && $request->filled('phone_number')) {
                    $arr_validates[$arrStoreField] = Crypt::encrypt($request->phone_number);
                    $arr_log_details[$arrStoreField] = Crypt::encrypt($request->phone_number);
                } else if ($arrStoreField == 'email' && $request->filled('email')) {
                    $arr_validates[$arrStoreField] = Crypt::encrypt($request->email);
                    $arr_log_details[$arrStoreField] = Crypt::encrypt($request->email);
                } else if ($arrStoreField == 'password') {
                    $arr_validates[$arrStoreField] = Hash::make($request->password);
                    $arr_log_details[$arrStoreField] = Crypt::encrypt($request->password);
                } else if ($arrStoreField == 'verification_number') {
                    $arr_validates[$arrStoreField] = $verification_number;
                    $arr_log_details[$arrStoreField] = $verification_number;
                } else if ($arrStoreField == 'phone_verified_at' && $request->filled('phone_number')) {
                    $arr_validates[$arrStoreField] = Carbon::now();
                    $arr_log_details[$arrStoreField] = Carbon::now();
                } else if ($arrStoreField == 'email_verified_at' && $request->filled('email')) {
                    $arr_validates[$arrStoreField] = Carbon::now();
                    $arr_log_details[$arrStoreField] = Carbon::now();
                } else {
                    $arr_validates[$arrStoreField] = $request->$arrStoreField;
                    $arr_log_details[$arrStoreField] = $request->$arrStoreField;
                }
            }

            // Create the user
            $created = AuthModel::create($arr_validates);
            if (!$created) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to store'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 1,
                    'user_action' => 'STORE_USER_ACCOUNT',
                ],
                $arr_log_details,
                1
            );

            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Successfully created user',
                // 'log_message' => $log_result
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * STORE USER ACCOUNT AND USER INFORMATION | ADMIN SIDE
     * storeAuthUserInfo
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAuthUserInfoAdmin(Request $request)
    {
        $verification_number = mt_rand(100000, 999999);
        $file_name = '';

        do {
            $user_id = Str::uuid()->toString();
        } while (AuthModel::where('user_id', $user_id)->exists());

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'phone_number' => $request->input('phone_number') != '' ? 'numeric|min:11' : 'nullable',
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|max:255',
            'status' => 'required|string|max:255',

            'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
            'first_name' => $request->input('first_name') != "" ? 'required|string|max:255' : 'nullable',
            'middle_name' => $request->input('middle_name') != "" ? 'required|string|max:255' : 'nullable',
            'last_name' => $request->input('last_name') != "" ? 'required|string|max:255' : 'nullable',
            // 'contact_number' => $request->input('contact_number') != "" ? 'required|string|max:255' : 'nullable',
            // 'contact_email' => $request->input('contact_email') != "" ? 'required|string|max:255' : 'nullable',
            'address_1' => $request->input('address_1') != "" ? 'required|string|max:255' : 'nullable',
            'address_2' => $request->input('address_2') != "" ? 'required|string|max:255' : 'nullable',
            'region_code' => $request->input('region_code') != "" ? 'required|string|max:255' : 'nullable',
            'province_code' => $request->input('province_code') != "" ? 'required|string|max:255' : 'nullable',
            'city_or_municipality_code' => $request->input('city_or_municipality_code') != "" ? 'required|string|max:255' : 'nullable',
            'barangay_code' => $request->input('barangay_code') != "" ? 'required|string|max:255' : 'nullable',
            'region_name' => $request->input('region_name') != "" ? 'required|string|max:255' : 'nullable',
            'province_name' => $request->input('province_name') != "" ? 'required|string|max:255' : 'nullable',
            'city_or_municipality_name' => $request->input('city_or_municipality_name') != "" ? 'required|string|max:255' : 'nullable',
            'barangay_name' => $request->input('barangay_name') != "" ? 'required|string|max:255' : 'nullable',
            'description_location' => $request->input('description_location') != "" ? 'required|string|max:255' : 'nullable',

            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }


        // ************ START AUTH ACC ************ // 
        $accounts = AuthModel::all();
        // Decrypt and validate email if exist
        foreach ($accounts as $account_item) {
            // Validate if exist phone number or email
            if ($request->filled('phone_number') && isset($account_item->phone_number)) {
                // Check if the phone number is encrypted
                if ($this->helper->isEncrypted($account_item->phone_number)) {
                    $decrypted_phone_number = Crypt::decrypt($account_item->phone_number);
                    // Check if the requested phone number exists in the decrypted phone numbers and phone_number_verified_at is not null then send verification code
                    if ($decrypted_phone_number == $request->phone_number && $account_item->phone_number_verified_at != null) {
                        return response()->json(
                            [
                                'phone_number' => 'Phone number already exists'
                            ],
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }
                }
            }
            if ($request->filled('email') && isset($account_item->email)) {
                // Check if the email is encrypted
                if ($this->helper->isEncrypted($account_item->email)) {
                    $decrypted_email = Crypt::decrypt($account_item->email);
                    if ($decrypted_email == $request->email && $account_item->email_verified_at != null) {
                        return response()->json(
                            [
                                'message' => [
                                    'email' => [
                                        'Email already exists'
                                    ]
                                ]
                            ],
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }
                }
            }
        }

        // Begin transaction
        DB::beginTransaction();
        try {
            // Store only have value   
            foreach ($this->fillable_attr_auth->arrToStores() as $arrStoreField) {
                if ($arrStoreField == 'user_id') {
                    $arr_validates[$arrStoreField] = $user_id;
                    $arr_log_details[$arrStoreField] = $user_id;
                } else if ($arrStoreField == 'phone_number' && $request->filled('phone_number')) {
                    $arr_validates[$arrStoreField] = Crypt::encrypt($request->phone_number);
                    $arr_log_details[$arrStoreField] = Crypt::encrypt($request->phone_number);
                } else if ($arrStoreField == 'email' && $request->filled('email')) {
                    $arr_validates[$arrStoreField] = Crypt::encrypt($request->email);
                    $arr_log_details[$arrStoreField] = Crypt::encrypt($request->email);
                } else if ($arrStoreField == 'password') {
                    $arr_validates[$arrStoreField] = Hash::make($request->password);
                    $arr_log_details[$arrStoreField] = Crypt::encrypt($request->password);
                } else if ($arrStoreField == 'verification_number') {
                    $arr_validates[$arrStoreField] = $verification_number;
                    $arr_log_details[$arrStoreField] = $verification_number;
                } else if ($arrStoreField == 'phone_verified_at' && $request->filled('phone_number')) {
                    $arr_validates[$arrStoreField] = Carbon::now();
                    $arr_log_details[$arrStoreField] = Carbon::now();
                } else if ($arrStoreField == 'email_verified_at' && $request->filled('email')) {
                    $arr_validates[$arrStoreField] = Carbon::now();
                    $arr_log_details[$arrStoreField] = Carbon::now();
                } else {
                    $arr_validates[$arrStoreField] = $request->$arrStoreField;
                    $arr_log_details[$arrStoreField] = $request->$arrStoreField;
                }
            }

            // Create the user
            $created = AuthModel::create($arr_validates);
            if (!$created) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to store'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 1,
                    'user_action' => 'STORE_USER_ACCOUNT_AND_USER_INFO_IN_ADMIN_PAGE',
                ],
                $arr_log_details,
                1
            );

            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //
            // ************ END AUTH ACC ************ // 


            // ************ START STORE USER INFO ************ // 
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
            $user_info_create = UserInfoModel::create(array_merge(['user_id' => $created->user_id], $result_to_create));
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
                    'user_action' => 'STORE_PERSONAL_INFORMATION_IN_ADMIN_PAGE',
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
            // ************ END STORE USER INFO ************ // 

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Successfully created user authentication and user information',
                // 'log_message' => $log_result
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * UPDATE USER ACCOUNT | ADMIN SIDE
     * update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $arr_log_details = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'phone_number' => $request->filled('phone_number') ? 'numeric|min:11' : 'nullable',
            'email' => $request->filled('email') ? 'required|string|max:255' : 'nullable',
            'password' => $request->input('password') != '' ? 'required|string|min:8|confirmed' : 'nullable',
            'role' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        // Begin a transaction
        DB::beginTransaction();

        try {
            $decrypted_user_id = Crypt::decrypt($request->user_id);

            $account = AuthModel::where('user_id', $decrypted_user_id)->first();
            // Check if inventory record exists
            if (!$account) {
                return response()->json(['message' => 'Data not found on auth'], Response::HTTP_NOT_FOUND);
            }

            $history = HistoryModel::where('tbl_id', $account->user_id)->where('tbl_name', 'users_tbl')->where('column_name', 'password')->latest()->first();
            if (!$history) {
                return response()->json(['message' => 'Data not found on history'], Response::HTTP_NOT_FOUND);
            }

            $accounts = AuthModel::get();
            foreach ($accounts as $account_item) {
                // Validate if exist phone number or email
                if ($request->filled('phone_number')) {
                    $decrypted_phone_number = Crypt::decrypt($account_item->email);
                    // Check if the requested email exists in the decrypted emails and email_verified_at is null then send verification code
                    if ($decrypted_phone_number == $request->phone_number && $account_item->phone_number_verified_at != null && Crypt::decrypt($request->user_id) != $account_item->user_id) {
                        return response()->json(
                            [
                                'phone_number' => 'Phone number already exist'
                            ],
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }
                }
                if ($request->filled('email')) {
                    $decrypted_email = Crypt::decrypt($account_item->email);
                    if ($decrypted_email == $request->email && $account_item->email_verified_at != null && Crypt::decrypt($request->user_id) != $account_item->user_id) {
                        return response()->json(
                            [
                                'message' => [
                                    'email' => [
                                        'Email already exists'
                                    ]
                                ]
                            ],
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }
                }
            }

            // Put on logs not equal value then put on update
            foreach ($this->fillable_attr_auth->arrToUpdates() as $arrToUpdates) {
                if ($arrToUpdates == 'phone_number') {
                    if ($request->filled('phone_number')) {
                        $existing_value = $account->$arrToUpdates != '' ? Crypt::decrypt($account->$arrToUpdates) : null;
                        $new_value = $request->arrToUpdates ?? null;
                        // Check if the value has changed
                        if ($existing_value !== $new_value) {

                            $arr_log_details[$arrToUpdates] = [
                                'old' => Crypt::encrypt($existing_value),
                                'new' => Crypt::encrypt($new_value),
                            ];

                            $arr_validates[$arrToUpdates] = Crypt::encrypt($request->phone_number);
                        }
                    }
                } else if ($arrToUpdates == 'email') {
                    if ($request->filled('email')) {
                        $existing_value = $account->$arrToUpdates != '' ? Crypt::decrypt($account->$arrToUpdates) : null;
                        $new_value = $request->$arrToUpdates != '' ? $request->$arrToUpdates : null;

                        // Check if the value has changed
                        if ($existing_value !== $new_value) {
                            $arr_log_details[$arrToUpdates] = [
                                'old' => Crypt::encrypt($existing_value),
                                'new' => Crypt::encrypt($new_value),
                            ];
                            $arr_validates[$arrToUpdates] = Crypt::encrypt($request->email);
                        }
                    }
                } else if ($arrToUpdates == 'password') {
                    $existing_value = $account->$arrToUpdates != '' ? Crypt::decrypt($history->value) : null;
                    $new_value = $request->$arrToUpdates != '' ? $request->$arrToUpdates : null;

                    // Check if the value has changed
                    if ($existing_value !== $new_value) {
                        $arr_log_details[$arrToUpdates] = [
                            'old' => $history->value,
                            'new' => Crypt::encrypt($new_value),
                        ];
                        $arr_validates[$arrToUpdates] = Hash::make($new_value);

                        $arr_log_details['user_id'] = [
                            'old' => $decrypted_user_id,
                            'new' => $decrypted_user_id,
                        ];
                    }
                } else {
                    $existing_value = $account->$arrToUpdates != '' ? $account->$arrToUpdates : null;
                    $new_value = $request->$arrToUpdates != '' ? $request->$arrToUpdates : null;

                    // Check if the value has changed
                    if ($existing_value !== $new_value) {
                        $arr_log_details[$arrToUpdates] = [
                            'old' => $existing_value,
                            'new' => $new_value,
                        ];
                    }
                    $arr_validates[$arrToUpdates] = $request->$arrToUpdates;
                }
            }

            // Update the user
            $update = $account->update($arr_validates);
            if (!$update) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to update account'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 1,
                    'user_action' => 'UPDATE_USER_ACCOUNT',
                ],
                $arr_log_details,
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
                'message' => 'Successfully update user account',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while processing the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // TODO: Check this please 
    /**
     * UPDATE USER ACCOUNT AND USER INFORMATION | ADMIN SIDE
     * updateAuthUserInfoAdmin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserInfoAdmin(Request $request)
    {
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',

            'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,JPG|max:10240' : 'nullable',
            'first_name' => $request->input('first_name') != "" ? 'required|string|max:255' : 'nullable',
            'middle_name' => $request->input('middle_name') != "" ? 'required|string|max:255' : 'nullable',
            'last_name' => $request->input('last_name') != "" ? 'required|string|max:255' : 'nullable',
            // 'contact_number' => $request->input('contact_number') != "" ? 'required|string|max:255' : 'nullable',
            // 'contact_email' => $request->input('contact_email') != "" ? 'required|string|max:255' : 'nullable',
            'address_1' => $request->input('address_1') != "" ? 'required|string|max:255' : 'nullable',
            'address_2' => $request->input('address_2') != "" ? 'required|string|max:255' : 'nullable',
            'region_code' => $request->input('region_code') != "" ? 'required|string|max:255' : 'nullable',
            'province_code' => $request->input('province_code') != "" ? 'required|string|max:255' : 'nullable',
            'city_or_municipality_code' => $request->input('city_or_municipality_code') != "" ? 'required|string|max:255' : 'nullable',
            'barangay_code' => $request->input('barangay_code') != "" ? 'required|string|max:255' : 'nullable',
            'region_name' => $request->input('region_name') != "" ? 'required|string|max:255' : 'nullable',
            'province_name' => $request->input('province_name') != "" ? 'required|string|max:255' : 'nullable',
            'city_or_municipality_name' => $request->input('city_or_municipality_name') != "" ? 'required|string|max:255' : 'nullable',
            'barangay_name' => $request->input('barangay_name') != "" ? 'required|string|max:255' : 'nullable',
            'description_location' => $request->input('description_location') != "" ? 'required|string|max:255' : 'nullable',

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
            $user_info = UserInfoModel::where('user_id', Crypt::decrypt($request->user_id))->first();

            // Check if user information exists
            if (!$user_info) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload and update
            if ($request->hasFile('image')) {
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
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $user_info, // the model to update
                $this->fillable_attr_user_info->arrToUpdatesAdmin(), // fields to update
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
                $this->fillable_attr_user_info->arrToUpdatesAdmin(),
                $result_merge_data,
                [],
                $this->fillable_attr_user_info->arrToUpdatesAdmin(),
                $this->fillable_attr_user_info->arrToUpdatesAdmin(),
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
            $result_format_logs_encrypted_data = $this->helper->formatLogsEncDataOldNew(
                $result_update_logs_old_new
            );

            //Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_PERSONAL_INFORMATION_IN_ADMIN_PAGE',
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


    /**
     * DESTROY USER ACCOUNT | ADMIN SIDE
     * destroy
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $arr_log_details = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            // Rollback the transaction
            DB::rollBack();
            return $result_validate_eu_device;
        }

        // Begin a transaction
        DB::beginTransaction();

        try {
            $account = AuthModel::where('user_id', Crypt::decrypt($request->user_id))->first();
            if (!$account) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $account->toArray();

            $history = HistoryModel::where('tbl_id', $arr_log_details['user_id'])->where('tbl_name', 'users_tbl')->where('column_name', 'password')->latest()->first();
            if (!$history) {
                return response()->json(['message' => 'Data not found on history'], Response::HTTP_NOT_FOUND);
            }

            // update the key password hash value to encrypted
            $arr_log_details['password'] = $history->value;

            // Check if the user_id to delete is exist on other tables
            foreach ($this->fillable_attr_auth->getFillableAttributes() as $getFillableAttributes) {
                if ($getFillableAttributes == 'password') {
                    $is_exist_id_other_tbls = $this->helper->isExistIdOtherTbl($account->user_id, $this->fillable_attr_auth->arrModelWithId());
                    // Check if exist on other tbl
                    foreach ($is_exist_id_other_tbls as $is_exist_id_other_tbl) {
                        // Ensure $is_exist_id_other_tbl is an array before accessing its elements
                        if (is_array($is_exist_id_other_tbl) && isset($is_exist_id_other_tbl['is_exist']) && $is_exist_id_other_tbl['is_exist'] == 'yes') {
                            return response()->json([
                                'message' => "Failed to delete because this ID exists in another table.",
                                // 'result_is_exist_other_tbl' => $is_exist_id_other_tbls,
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }
                    }
                }
            }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'DELETE_USER_ACCOUNT_IN_ADMIN_PAGE',
                ],
                $arr_log_details,
                3
            );

            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Delete the user
            if (!$account->delete()) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Successfully delete user',
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * UPDATE EMAIL | CLIENT SIDE
     * Update email
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEmailOnSettingUser(Request $request)
    {
        $arr_log_details = [];
        $verification_number = mt_rand(100000, 999999);

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'new_email' => 'required|email',
            'current_password' => 'required|string',
            'verification_number' => 'required|numeric|min:6',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            // Rollback the transaction
            DB::rollBack();
            return $result_validate_eu_device;
        }


        // Begin a transaction
        DB::beginTransaction();

        try {
            // Fetch the user from the database
            $user_auth = AuthModel::where('user_id', $user->user_id)->first();
            if (!$user_auth) {
                return response()->json(['message' => 'Intruder'], Response::HTTP_NOT_FOUND);
            }

            if (Crypt::decrypt($user_auth->email) == $request->new_email) {
                return response()->json(['message' => 'The new email cannot be the same as the old email. Please choose a different one'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (Crypt::decrypt($user_auth->email) != $request->new_email && !Hash::check($request->input('current_password'), $user_auth->password)) {
                return response()->json(['message' => 'Incorrect password'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (Crypt::decrypt($user_auth->email) != $request->new_email && Hash::check($request->input('current_password'), $user_auth->password) && $user_auth->verification_number != $request->verification_number) {
                return response()->json(['message' => 'Incorrect verification number'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update the user's email
            $user_auth->email = Crypt::encrypt($request->new_email);
            $user_auth->verification_number = $verification_number;

            // Saving
            if (!$user_auth->save()) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to update email'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Log details
            $arr_log_details = [
                'user_id' => [
                    "old" =>  $user->user_id,
                    "new" =>  $user->user_id,
                ],
                'email' => [
                    "old" => $user_auth->email,
                    "new" => Crypt::encrypt($request->new_email),
                ],
            ];

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_PERSONAL_INFORMATION',
                ],
                $arr_log_details,
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
                'message' => 'Email updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * UPDATE PASSWORD | CLIENT SIDE
     * Update password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePasswordOnSettingUser(Request $request)
    {
        $verification_number = mt_rand(100000, 999999);

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'verification_number' => 'required|numeric|min:6',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            // Rollback the transaction
            DB::rollBack();
            return $result_validate_eu_device;
        }

        // Begin a transaction
        DB::beginTransaction();

        try {
            // Fetch the user from the database
            $user_auth = AuthModel::where('user_id', $user->user_id)->first();
            // Check if user exists
            if (!$user_auth) {
                return response()->json(['message' => 'Intruder'], Response::HTTP_NOT_FOUND);
            }

            if (Hash::check($request->input('password'), $user_auth->password)) {
                return response()->json(['message' => 'The new password cannot be the same as the old password. Please choose a different one'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!Hash::check($request->input('current_password'), $user_auth->password)) {
                return response()->json(['message' => 'Incorrect current password'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($user_auth->verification_number != $request->input('verification_number')) {
                return response()->json(['message' => 'Incorrect Verification Number'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update the user's password
            $user_auth->password =  Hash::make($request->input('password'));
            $user_auth->verification_number = $verification_number;

            // Saving
            if (!$user_auth->save()) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to update new password'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // *********************************** //
            // Start log
            // Log details
            $arr_log_details = [
                'user_id' => [
                    "old" =>  $user->user_id,
                    "new" =>  $user->user_id,
                ],
                'password' => [
                    'old' => Crypt::encrypt($request->input('current_password')),
                    'new' => Crypt::encrypt($request->input('password')),
                ],
            ];

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 1,
                    'user_action' => 'UPDATE_PASSWORD_ON_USER_SETTING',
                ],
                $arr_log_details,
                2
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Password updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * CHILD updatePasswordOnSettingUser | CLIENT SIDE
     * Resend code password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationCodeEmail(Request $request)
    {
        $arr_log_details = [];
        $verification_number = mt_rand(100000, 999999);

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            // Rollback the transaction
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validate
        $validator = Validator::make($request->all(), [
            'eu_device' => 'required|string',
        ]);
        if ($validator->fails()) {
            // Rollback the transaction
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            // Rollback the transaction
            return $result_validate_eu_device;
        }

        // Begin a transaction
        DB::beginTransaction();

        try {
            $auth = AuthModel::where('user_id', $user->user_id)
                ->first();

            // data to format on logs
            $arr_log_details = [
                'user_id' => [
                    'old' => $auth->user_id,
                    'new' => $auth->user_id,
                ],
                'verification_number' => [
                    'old' => $auth->verification_number,
                ],
            ];

            // Update user's verification number
            $update_user_verification_number = $auth->update([
                'verification_number' => $verification_number,
            ]);

            if (!$update_user_verification_number) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to generate verification number',
                ], Response::HTTP_OK);
            }

            // data to format on logs
            $arr_log_details['verification_number']['new'] = $auth->verification_number;

            // Get user's email
            $userEmail = Crypt::decrypt($auth->email);
            $email_parts = explode('@', $userEmail);
            $name = [$email_parts[0]];

            // Send email with the new verification code
            $email =  Mail::to($userEmail)->send(new VerificationMail($verification_number, $name));
            if (!$email) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to send the verification number to your email'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'RESEND_NEW_VERIFICATION_CODE_UPON_USER_SETTINGS_EMAIL_UPDATE',
                ],
                $arr_log_details,
                2
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'A new verification code has been sent to your email',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * CHILD updateEmailOnSettingUser | CLIENT SIDE
     * Resend code email
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationCodePassword(Request $request)
    {
        $verification_number = mt_rand(100000, 999999);

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            // Rollback the transaction
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validate
        $validator = Validator::make($request->all(), [
            'eu_device' => 'required|string',
        ]);
        if ($validator->fails()) {
            // Rollback the transaction
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            // Rollback the transaction
            return $result_validate_eu_device;
        }

        // Begin a transaction
        DB::beginTransaction();

        try {
            $auth = AuthModel::where('user_id', $user->user_id)
                ->first();

            // data to format on logs
            $arr_log_details = [
                'user_id' => [
                    'old' => $auth->user_id,
                    'new' => $auth->user_id,
                ],
                'verification_number' => [
                    'old' => $auth->verification_number,
                ],
            ];

            // Update user's verification number
            $update_user_verification_number = $auth->update([
                'verification_number' => $verification_number,
            ]);

            if (!$update_user_verification_number) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to generate verification number',
                ], Response::HTTP_OK);
            }

            // data to format on logs
            $arr_log_details['verification_number']['new'] = $auth->verification_number;

            // Get user's email
            $userEmail = Crypt::decrypt($auth->email);
            $email_parts = explode('@', $userEmail);
            $name = [$email_parts[0]];

            // Send email with the new verification code
            $email =  Mail::to($userEmail)->send(new VerificationMail($verification_number, $name));
            if (!$email) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to send the verification number to your email'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'RESEND NEW VERIFICATION CODE UPON USER SETTINGS PASSWORD UPDATE',
                ],
                $arr_log_details,
                2
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'A new verification code has been sent to your email',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
