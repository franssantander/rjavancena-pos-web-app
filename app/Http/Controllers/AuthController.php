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
use App\Mail\ResetPasswordMail;

use App\Models\UserUserIdModel;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\ResendVerificationMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPersonalAccessTokenModel;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    protected $helper, $fillable_attr_auth;

    public function __construct(Helper $helper, AuthModel $fillable_attr_auth)
    {
        $this->helper = $helper;
        $this->fillable_attr_auth = $fillable_attr_auth;
    }


    public function indexHistory()
    {
        $decryptedData = [];

        $historys = HistoryModel::orderBy('created_at', 'desc')->get();

        foreach ($historys as $history) {
            $decryptedHistory = [
                'history_id' => $history && $history->history_id ? $history->history_id : null,
                'tbl_id' => $history && $history->tbl_id ? $history->tbl_id : null,
                'table_name' => $history && $history->tbl_name ? $history->tbl_name : null,
                'column_name' => $history && $history->column_name ? $history->column_name : null,
                'value' => $history && $history->value ? Crypt::decrypt($history->value) : null,
            ];

            $decryptedData[] = $decryptedHistory;
        }

        return response()->json(
            [
                'message' => 'Successfully Retrieve Data',
                'result' => $decryptedData,
            ],
            Response::HTTP_OK
        );
    }

    public function checkToken(Request $request)
    {
        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'token' => $user->session_token,
        ], Response::HTTP_OK);
    }

    public function roleNavLinks(Request $request)
    {
        // Initialize response_user_info with default values
        $response_user_info = [
            'name' => '',
            'email' => '',
            'role' => '',
        ];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        $user_info = UserInfoModel::where('user_id', $user->user_id)->first();

        if ($user_info) {

            $response_user_info = [
                'name' => (isset($user_info->first_name) ? Crypt::decrypt($user_info->first_name) : '') . ' ' .
                    (isset($user_info->last_name) ? Crypt::decrypt($user_info->last_name) : ''),
                'image' => $user_info->image ? env('PATH_FILE_USER_ACCOUNT') . Crypt::decrypt($user_info->image) : null,
                'role' => $user->role ?? '',
            ];
        } else {
            return response()->json([
                'message' => 'User information not provided',
            ], Response::HTTP_UNAUTHORIZED);
        }


        // Check the role of user
        $user_role = AuthModel::where('user_id', $user->user_id)->first();


        if ($user_role->role === env('ROLE_SUPER_ADMIN')) {
            return response()->json(
                [
                    'nav_links' => $this->fillable_attr_auth->getNavLinksRoleSuperAdmin(),
                    'user' => $response_user_info,
                ],
                Response::HTTP_OK
            );
        } else if ($user_role->role === env('ROLE_ADMIN')) {
            return response()->json(
                [
                    'nav_links' => $this->fillable_attr_auth->getNavLinksRoleAdmin(),
                    'user' => $response_user_info,
                ],
                Response::HTTP_OK
            );
        } else if ($user_role->role === env('ROLE_CASHIER')) {
            return response()->json(
                [
                    'nav_links' => $this->fillable_attr_auth->getNavLinksRoleCashier(),
                    'user' => $response_user_info,
                ],
                Response::HTTP_OK
            );
        } else {
            return response()->json([
                'message' => 'Invalid role',
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function logout(Request $request)
    {
        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        DB::beginTransaction();

        try {
            $user->status = 'INACTIVE';
            $user->last_used_at = Carbon::now();

            if (!$user->save()) {
                return response()->json(
                    ['message' => 'Failed to invalidate the token'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            JWTAuth::invalidate(JWTAuth::getToken());

            // *********************************** //
            // Start log
            // data to format on logs
            $arr_log_details = [
                'ip_address' => $request->ip(),
            ];

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 1,
                    'user_action' => 'LOGOUT',
                ],
                $arr_log_details,
                1
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            DB::commit();

            return response()->json([
                'message' => 'User logout successfully',
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * PARENT LOGIN
     * Login
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function login(Request $request)
    {
        $arr_data = [];

        // Check if phone number is not empty
        if ($request->has('phone_number') && ($request->input('phone_number') !== '' || $request->input('phone_number') !== null)) {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|numeric',
                'password' => 'required|string',
            ]);

            $arr_data['phone_number'] = $request->phone_number;
            $arr_data['password'] = $request->password;

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()],  Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
        // Check if Email is not empty
        else if ($request->has('email') && ($request->input('email') !== '' || $request->input('email') !== null)) {
            // Validate Password
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
                'eu_device' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], Response::HTTP_NOT_FOUND);
            }

            // Validate Eu Device
            // $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
            // if ($result_validate_eu_device) {
            //     return $result_validate_eu_device;
            // }

            $arr_data['email'] = $request->email;
            $arr_data['password'] = $request->password;
            $arr_data['eu_device'] = $request->eu_device;

            return $this->loginEmail($request, $arr_data);
        }
    }

    /**
     * CHILD LOGIN
     * Login
     *
     * @param array $arr_data
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginEmail($request, $arr_data)
    {
        $verification_number = mt_rand(100000, 999999);

        // Decrypt al email first
        $users = AuthModel::all();

        foreach ($users as $user) {
            $decrypted_email = Crypt::decrypt($user->email);

            // Check if Verified Email
            if ($decrypted_email == $arr_data['email'] && Hash::check($arr_data['password'], $user->password) && $user->email_verified_at !== null) {

                // Fetch the model holder of jwt
                $user_record = UserUserIdModel::where('user_id', $user->user_id)->first();

                // Generate jwt expiration time and token
                // $expirationTime = Carbon::now()->addSeconds(30);
                // Expiration Time 1month
                $expiration_time = Carbon::now()->addMinutes(2592000);
                $new_token = JWTAuth::claims(['exp' => $expiration_time->timestamp])->fromUser($user_record);
                if (!$new_token) {
                    return response()->json([
                        'message' => 'Unable to generate a token from user'
                    ], Response::HTTP_OK);
                }

                // update old token to status inactive
                UserPersonalAccessTokenModel::where('user_id', $user->user_id)
                    ->where('name', 'LOGIN')
                    ->where('status', 'ACTIVE')
                    ->update([
                        'status' => 'INACTIVE',
                        'expires_at' => Carbon::now(),
                    ]);

                // Create a new personal access token
                $user_personal_access_token = UserPersonalAccessTokenModel::create([
                    'user_id' => $user_record->user_id,
                    'tokenable_type' => 'App\Models\UserUserIdModel',
                    'name' => 'LOGIN',
                    'token' => $new_token,
                    'abilities' => ['*'],
                    'status' => 'ACTIVE',
                    'expires_at' => $expiration_time,
                ]);

                if (!$user_personal_access_token->save()) {
                    return response()->json(
                        ['message' => 'Failed to update session token and expiration'],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // Check If users_info_tbl exist 
                $user_info_exist = UserInfoModel::where('user_id', $user->user_id)
                    ->exists();

                // *********************************** //
                // Start log
                // data to format on logs
                $arr_log_details = [
                    'ip_address' => $request->ip(),
                ];

                // Logs
                $log_result = $this->helper->log(
                    $request,
                    [
                        'user_device' => $arr_data['eu_device'],
                        'user_id' => $user->user_id,
                        'is_history' => 1,
                        'user_action' => 'LOGIN',
                    ],
                    $arr_log_details,
                    1
                );
                if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                    DB::rollBack();
                    return $log_result;
                }
                // End log
                // *********************************** //

                return response()->json([
                    // 'user' => $user,
                    'user_info' => $user_info_exist ? 'Existing User' : 'New User',
                    'token' => $new_token,
                    'token_expire_at' => $expiration_time->diffInSeconds(Carbon::now()),
                    'message' => 'Login Successfully',
                    // 'log_message' => $log_result
                ], Response::HTTP_OK);
            }
            // Check if Not Verified then redirect to Verify Email
            else if ($decrypted_email == $arr_data['email'] && Hash::check($arr_data['password'], $user->password) && $user->email_verified_at === null) {
                // Generate a new token for the user
                $expiration_time = Carbon::now()->addMinutes(120);
                $new_token = JWTAuth::claims(['exp' => $expiration_time->timestamp])->fromUser($user);

                if (!$new_token) {
                    return response()->json([
                        'message' => 'Failed to generate a token from user'
                    ], Response::HTTP_OK);
                }

                // Update verification_number | password | verify email token
                $user->verification_number = $verification_number;
                $user->verify_email_token = $new_token;
                $user->verify_email_token_expire_at = $expiration_time;

                // Save
                if (!$user->save()) {
                    return response()->json(
                        [
                            'message' => 'Failed To update to verification number, token and expiration time'
                        ],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // Arr Logs details
                $arr_log_details = [
                    'fields' => [
                        'user_id' => $user->user_id,
                        'ip_address' => $request->ip(),
                    ]
                ];

                // Logs
                $log_result = $this->helper->log(
                    $request,
                    [
                        'user_device' => $arr_data['eu_device'],
                        'user_id' => $user->user_id,
                        'is_history' => 0,
                        'user_action' => 'USER_ATTEMPTED_TO_LOG_IN_BUT_HAS_NOT_YET_BEEN_VERIFIED._REDIRECTING_TO_VERIFY_EMAIL',
                    ]
                );

                // Get the Name of Gmail
                $emailParts = explode('@', $decrypted_email);
                $name = [$emailParts[0]];

                // Send the new token to the user via email
                Mail::to($decrypted_email)->send(new VerificationMail($verification_number, $name));

                return response()->json(
                    [
                        'message' => 'Verify your account',
                        'token' => $new_token,
                        'url_token' => 'verification/' . $new_token,
                        'expire_at' => $expiration_time->diffInSeconds(Carbon::now()),
                    ],
                    Response::HTTP_OK
                );
            }
        }

        return response()->json([
            'message' => 'Invalid credential'
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * PARENT REGISTER
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function register(Request $request)
    {
        // Declare Value
        $verification_number = mt_rand(100000, 999999);
        $account_role = env('ROLE_CASHIER');
        $status = 'PENDING';
        $arr_data = [];

        do {
            $user_id = Str::uuid()->toString();
        } while (AuthModel::where('user_id', $user_id)->exists());

        $arr_data = [
            'user_id' => $user_id,
            'verification_number' => $verification_number,
            'account_role' => $account_role,
            'status' => $status,
        ];

        // Check if phone number is not empty
        if ($request->has('phone_number') && ($request->input('phone_number') !== '' || $request->input('phone_number') !== null)) {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|numeric',
                'password' => 'required|string|min:8|confirmed:password_confirmation',
                'eu_device' => 'required|string',
            ]);

            $arr_data['phone_number'] = $request->phone_number;
            $arr_data['password'] = $request->password;

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()],  Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
            if ($result_validate_eu_device) {
                return $result_validate_eu_device;
            }
        }
        // Check if Email is not empty
        else if ($request->has('email') && ($request->input('email') !== '' || $request->input('email') !== null)) {
            // Validate Password
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed:password_confirmation',
                'eu_device' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], Response::HTTP_NOT_FOUND);
            }

            $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
            if ($result_validate_eu_device) {
                return $result_validate_eu_device;
            }

            $arr_data['email'] = $request->email;
            $arr_data['password'] = $request->password;
            $arr_data['eu_device'] = $request->eu_device;

            return $this->emailRegister($request, $arr_data);
        }

        return response()->json(['message' => 'Please Input on Phone Number or Email', Response::HTTP_UNPROCESSABLE_ENTITY], 0);
    }

    /**
     * CHILD REGISTER EMAIL
     * Register a new user for email.
     *
     * @param array $arr_data
     * @return \Illuminate\Http\JsonResponse
     */
    public function emailRegister($request, $arr_data)
    {
        // Begin a transaction
        DB::beginTransaction();

        try {
            // Generate a new token for the user
            $expiration_time = Carbon::now()->addMinutes(5);
            // $expiration_time = Carbon::now()->addSecond();
            // Get All Users and Decrypt
            $users = AuthModel::all();

            // Decrypt | For existing email but not verified
            foreach ($users as $user) {
                // Start Decrypt
                $decrypted_email = Crypt::decrypt($user->email);

                // Check if the requested email exists in the decrypted emails and email_verified_at is null then send verification code
                if ($decrypted_email === $arr_data['email'] && $user->email_verified_at === null) {

                    $user->password = Hash::make($arr_data['password']);
                    $user->verification_number = $arr_data['verification_number'];
                    $user->save();

                    // Get the user record and put the token
                    $user_record = UserUserIdModel::where('user_id', $user->user_id)->first();

                    // Generate a JWT for the token
                    $new_token = JWTAuth::claims(['exp' => $expiration_time->timestamp])->fromUser($user_record);

                    // update old token to status inactive
                    UserPersonalAccessTokenModel::where('user_id', $user->user_id)
                        ->where('status', 'ACTIVE')
                        ->where(function ($query) {
                            $query->where('name', 'VERIFY_ACCOUNT_TOKEN_EXISTING_ACCOUNT')
                                ->orWhere('name', 'VERIFY_ACCOUNT');
                        })
                        ->update([
                            'status' => 'INACTIVE',
                            'expires_at' => Carbon::now(),
                        ]);

                    // Create a new personal access token
                    $user_personal_access_token_create = UserPersonalAccessTokenModel::create([
                        'user_id' => $user->user_id,
                        'tokenable_type' => 'App\Models\UserUserIdModel',
                        'name' => 'VERIFY_ACCOUNT_TOKEN_EXISTING_ACCOUNT',
                        'abilities' => ['*'],
                        'status' => 'ACTIVE',
                        'expires_at' => $expiration_time,
                    ]);

                    if (!$user_personal_access_token_create) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to create token'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // Update and save the token with the generated JWT
                    $user_personal_access_token_create->token = $new_token;
                    $user_personal_access_token_create->save();


                    // *********************************** //
                    // Start log
                    // data to format on logs
                    $arr_log_details = [
                        'user_id' => $user->user_id,
                        'email' => Crypt::encrypt($arr_data['email']),
                        'password' => Crypt::encrypt($arr_data['password']),
                    ];

                    // Logs
                    $log_result = $this->helper->log(
                        $request,
                        [
                            'user_device' => $arr_data['eu_device'],
                            'user_id' => $user->user_id,
                            'is_history' => 1,
                            'user_action' => 'EXISTING_ACCOUNT_REDIRECTED_TO_VERIFICATION_PAGE',
                        ],
                        $arr_log_details,
                        1
                    );
                    if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                        DB::rollBack();
                        return $log_result;
                    }
                    // End log
                    // *********************************** //

                    // Get the Name of Gmail
                    $email_parts = explode('@', $arr_data['email']);
                    $name = [$email_parts[0]];

                    // Send the new token to the user via email
                    $email = Mail::to($arr_data['email'])->send(new VerificationMail($arr_data['verification_number'], $name));
                    if (!$email) {
                        // Rollback the transaction
                        DB::rollBack();

                        return response()->json(['message' => 'Failed to send the verification number to your email'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // Commit the transaction
                    DB::commit();

                    return response()->json([
                        'message' => 'Successfully register email',
                        'token' => $new_token,
                        'url_token' => 'verification/' . $new_token,
                        'expire_at' => $expiration_time->diffInSeconds(Carbon::now()),
                        // 'log_message' => $log_result
                    ], Response::HTTP_OK);
                }

                // If same email exist and email_verified_at not null send error message
                else if ($decrypted_email === $arr_data['email'] && $user->email_verified_at !== null) {
                    // Rollback the transaction
                    DB::rollBack();

                    return response()->json(
                        [
                            'message' => 'Email already exist'
                        ],
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }

            // User with the given email does not exist, create a new user
            $user_create = AuthModel::create([
                'user_id' => $arr_data['user_id'],
                'email' => Crypt::encrypt($arr_data['email']),
                'password' => Hash::make($arr_data['password']),
                'role' => $arr_data['account_role'],
                'status' => $arr_data['status'],
                'verification_number' => $arr_data['verification_number'],
            ]);

            if (!$user_create) {
                // Rollback the transaction
                DB::rollBack();

                return response()->json(['message' => 'Failed to create user'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Create a new user id
            $users_user_id = UserUserIdModel::create([
                'user_id' => $user_create->user_id,
            ]);

            // Generate a JWT for the token
            $new_token = JWTAuth::claims(['exp' => $expiration_time->timestamp])->fromUser($users_user_id);

            // Create a new personal access token
            $user_personal_access_token = UserPersonalAccessTokenModel::create([
                'user_id' => $user_create->user_id,
                'tokenable_type' => 'App\Models\UserUserIdModel',
                'name' => 'VERIFY_ACCOUNT',
                'abilities' => ['*'],
                'status' => 'ACTIVE',
                'expires_at' => $expiration_time,
            ]);

            // Update and save the token with the generated JWT
            $user_personal_access_token->token = $new_token;
            $user_personal_access_token->save();


            // *********************************** //
            // Start log
            // data to format on logs
            $arr_log_details = [
                'user_id' => $arr_data['user_id'],
                'email' => Crypt::encrypt($arr_data['email']),
                'password' => Crypt::encrypt($arr_data['password']),
            ];

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $arr_data['eu_device'],
                    'user_id' => $arr_data['user_id'],
                    'is_history' => 1,
                    'user_action' => 'REGISTER_AN_ACCOUNT_USING_EMAIL',
                ],
                $arr_log_details,
                1
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Get the Name of Gmail
            $emailParts = explode('@', $arr_data['email']);
            $name = $emailParts[0];

            // Send an email to the user with the new token
            $email = Mail::to($arr_data['email'])->send(new VerificationMail($arr_data['verification_number'], $name));
            if (!$email) {
                // Rollback the transaction
                DB::rollBack();

                return response()->json(['message' => 'Failed to send the verification number to your email'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Successfully register email',
                'token' => $new_token,
                'url_token' => 'verification/' . $new_token,
                'expire_at' => $expiration_time->diffInSeconds(Carbon::now()),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * CHILD EMAIL REGISTER
     * Verify email
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function verifyEmail(Request $request)
    {
        $verification_number = mt_rand(100000, 999999);

        // Authorize the user
        $user = $this->authorizeUserAuthVerifyEmailAndResendCode($request);

        // Check if authenticated user
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validate
        $validator = Validator::make($request->all(), [
            'verification_number' => 'required|numeric|min:6',
            'eu_device' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_NOT_FOUND);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        // Begin a transaction
        DB::beginTransaction();

        try {
            $auth = AuthModel::where('user_id', $user->user_id ?? null)
                ->first();

            // Check if the provided verification number matches the stored one
            if ($auth->verification_number != $request->verification_number) {
                // Rollback the transaction
                DB::rollBack();

                return response()->json(['message' => 'Invalid verification number'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update user status and set email_verified_at to the current timestamp
            $auth->status = env('ACCOUNT_ACTIVATE');
            $auth->email_verified_at = Carbon::now();
            $auth->verification_number = $verification_number;
            if (!$auth->save()) {
                // Rollback the transaction
                DB::rollBack();

                return response()->json(
                    [
                        'message' => 'Failed to verify email',
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // *********************************** //
            // Start log
            // data to format on logs
            $arr_log_details = [
                'user_id' => $auth->user_id,
                'verification_number' => $request->verification_number,
                'email_verified_at' =>  Carbon::parse($auth->email_verified_at)->format("F j, Y g:i a"),
            ];

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $auth->user_id,
                    'is_history' => 0,
                    'user_action' => 'SUCCESS_VERIFY_EMAIL',
                ],
                $arr_log_details,
                1
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            $user->status = 'INACTIVE';
            $user->last_used_at = Carbon::now();
            if (!$user->save()) {
                return response()->json(
                    [
                        'message' => 'Email verified successfully',
                    ],
                    Response::HTTP_OK
                );
            }

            // Invalidate the token once verify
            $this->helper->invalidateTokenJwt();

            // Commit the transaction
            DB::commit();

            return response()->json(
                [
                    'message' => 'Email verified successfully',
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();

            return response()->json(
                [
                    'message' => $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    /**
     * SIGN UP | VERIFY EMAIL RESEND CODE
     * Resend Code
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationAuth(Request $request)
    {
        $arr_log_details = [];

        $verification_number = mt_rand(100000, 999999);

        // Authorize the user
        $user = $this->authorizeUserAuthVerifyEmailAndResendCode($request);

        // Check if authenticated user
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validate
        $validator = Validator::make($request->all(), [
            'eu_device' => 'required|string',
        ]);
        if ($validator->fails()) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => $validator->errors()], Response::HTTP_NOT_FOUND);
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
            $auth = AuthModel::where('user_id', $user->user_id ?? null)
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
                    'user_id' => $user->user_id ?? null,
                    'is_history' => 0,
                    'user_action' => 'RESEND_NEW_VERIFICATION_CODE_AT_VERIFY_EMAIL',
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
     * SIGN UP | VERIFY EMAIL RESEND CODE
     * Resend Code
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        // Validate
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
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
            // Get All Users and Decrypt
            $users = AuthModel::all();

            // Decrypt
            foreach ($users as $user) {
                // Start Decrypt
                $decrypted_email = Crypt::decrypt($user->email);

                // Check if the requested email exists in the decrypted emails and email_verified_at is null then send verification code
                if ($decrypted_email === $request->email && $user->email_verified_at !== null) {
                    // Get the model holder of jwt token
                    $user_record = UserUserIdModel::where('user_id', $user->user_id)->first();

                    // 2hrs expiration to verified Email
                    $expiration_time = Carbon::now()->addMinutes(5);
                    // Put the token
                    $new_token = JWTAuth::claims(['exp' => $expiration_time->timestamp])->fromUser($user_record);

                    // Update old token status to INACTIVE
                    $user_personal_access_token_create = UserPersonalAccessTokenModel::where('user_id', $user_record->user_id)->update([
                        'status' => 'INACTIVE',
                    ]);

                    // Create a new personal access token
                    $user_personal_access_token_create = UserPersonalAccessTokenModel::create([
                        'user_id' => $user_record->user_id,
                        'tokenable_type' => 'App\Models\UserUserIdModel',
                        'name' => 'FORGOT_PASSWORD',
                        'token' => $new_token,
                        'abilities' => ['*'],
                        'status' => 'ACTIVE',
                        'expires_at' => $expiration_time,
                    ]);

                    if (!$user_personal_access_token_create) {
                        // Rollback the transaction
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to create token'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // Send to Email Now
                    $mail = Mail::to($request->email)->send(new ResetPasswordMail($new_token, $request->email, $expiration_time));
                    if (!$mail) {
                        // Rollback the transaction
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to send reset password link on your email'], Response::HTTP_OK);
                    }

                    // *********************************** //
                    // Start log
                    // data to format on logs
                    $arr_log_details = [
                        'user_id' => $user->user_id,
                        'email' => Crypt::encrypt($request->email),
                    ];

                    // Logs
                    $log_result = $this->helper->log(
                        $request,
                        [
                            'user_device' => $request->eu_device,
                            'user_id' => $user->user_id,
                            'is_history' => 0,
                            'user_action' => 'SUCCESSFULLY_SENT_RESET_LINK_FOR_PASSWORD_UPDATE',
                        ],
                        $arr_log_details,
                        1
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
                        'message' => 'Successfully sent a reset password link to your email ' . $decrypted_email,
                        // 'log_message' => $log_result
                    ], Response::HTTP_OK);
                }
                // If same email exist and email_verified_at equal null send error message
                else if ($decrypted_email === $request->email && $user->email_verified_at === null) {
                    // Rollback the transaction
                    DB::rollBack();
                    return response()->json(['message' => 'Email not found or not verified'], Response::HTTP_NOT_FOUND);
                }
            }

            // Rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'Email not found or not verified'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            // Rollback the transaction on any exception
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * FORGOT PASSWORD | UPDATE PASSWORD
     * Update Password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        // Authorize the user
        $user = $this->authorizeUserUpdatePassword($request);

        // Check if authenticated user
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validate Password
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Return the validation errors
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }


        // Begin a transaction
        DB::beginTransaction();

        try {
            // Fetch the user from the database
            $user_auth = AuthModel::where('user_id', $user->user_id ?? null)->first();

            // Check if user exists
            if (!$user_auth) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            if (Hash::check($request->input('password'), $user_auth->password)) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'The new password cannot be the same as the old password. Please choose a different one'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $history = HistoryModel::where('tbl_id', $user_auth->user_id)->where('tbl_name', 'users_tbl')->where('column_name', 'password')->latest()->first();
            if (!$history) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Update the user's password
            $user_auth->password =  Hash::make($request->input('password'));

            // Saving
            if (!$user_auth->save()) {
                // Rollback the transaction
                DB::rollBack();
                return response()->json(['message' => 'Failed to update new password'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // *********************************** //
            // Start log
            // data to format on logs
            $arr_log_details = [
                'user_id' => $user->user_id ?? null,
                'new_password' => Crypt::encrypt($request->input('password')),
                'old_password' => $history->value,
            ];

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id ?? null,
                    'is_history' => 1,
                    'user_action' => 'UPDATE_PASSWORD_ON_FORGOT_PASSWORD',
                ],
                $arr_log_details,
                1
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //


            $user->status = 'INACTIVE';
            $user->last_used_at = Carbon::now();
            if (!$user->save()) {
                return response()->json(
                    [
                        'message' => 'Email verified successfully',
                    ],
                    Response::HTTP_OK
                );
            }

            // invalidate the token once success update password
            $this->helper->invalidateTokenJwt();

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
     * AUTHENTICATE TOKEN
     * Auth Verify email
     *
     * @param  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authorizeUserAuthVerifyEmailAndResendCode(Request $request)
    {
        try {

            // Authenticate the user with the provided token
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }

            $auth = UserPersonalAccessTokenModel::where('user_id', $user->user_id)
                ->where('status', 'ACTIVE')
                ->where('token', $request->bearerToken())
                ->where('expires_at', '>', Carbon::now())
                ->where(function ($query) {
                    $query->where('name', 'VERIFY_ACCOUNT')
                        ->orWhere('name', 'VERIFY_ACCOUNT_TOKEN_EXISTING_ACCOUNT')
                        ->orWhere('name', 'FORGOT_PASSWORD');
                })
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
            return response()->json(['message' => 'Failed to authenticate'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * AUTHENTICATE TOKEN
     * Update Password
     *
     * @param  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authorizeUserUpdatePassword($request)
    {
        try {
            // Authenticate the user with the provided token
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }

            $auth = UserPersonalAccessTokenModel::where('user_id', $user->user_id)
                ->where('status', 'ACTIVE')
                ->where('token', $request->bearerToken())
                ->where('expires_at', '>', Carbon::now())
                ->where('name',  'FORGOT_PASSWORD')
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
            return response()->json(['message' => 'Failed to authenticate'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
