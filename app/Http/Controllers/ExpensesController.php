<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ExpensesModel;
use Illuminate\Support\Carbon;
use App\Models\ExpensesImageModel;
use App\Models\UserInfoModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ExpensesController extends Controller
{
    protected $helper, $fillable_attr_expenses, $fillable_attr_expenses_image;

    public function __construct(Helper $helper, ExpensesModel $fillable_attr_expenses, ExpensesImageModel $fillable_attr_expenses_image)
    {
        $this->helper = $helper;
        $this->fillable_attr_expenses = $fillable_attr_expenses;
        $this->fillable_attr_expenses_image = $fillable_attr_expenses_image;
    }

    public function index(Request $request)
    {
        $crud_settings = $this->fillable_attr_expenses->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_expenses->getApiRelativeSettings();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $expenses = ExpensesModel::orderBy('created_at', 'desc')->get();

        // Fetch paginated inventory items for the current page
        // $expenses = ExpensesModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
        //     ->where(function ($query) use ($request) {
        //         $search = $request->query('search', '');
        //         $query->where('name', 'LIKE', '%' . $search . '%')
        //             ->orWhere('amount', 'LIKE', '%' . $search . '%'); // Search in another column
        //         //  ->orWhere('yet_another_column', 'LIKE', '%' . $search . '%'); // Search in a third column
        //     })
        //     ->paginate($request->query('limit', 10), ['*'], 'page', $request->query('page', 1)); // Correct pagination

        $expenses = ExpensesModel::orderBy('created_at', 'desc');

        // Apply search filter if provided
        if ($request->query('search') != '') {
            $expenses = $expenses->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('name', 'LIKE', '%' . $search . '%');
                // ->orWhere('item_code', 'LIKE', '%' . $search . '%'); // Search in another column
            });
        }

        // Apply date range filters if provided
        if ($request->query('start_date_of_expense') != '' || $request->query('end_date_of_expense') != '') {
            if ($request->query('start_date_of_expense') != '') {
                $expenses = $expenses->where('date_of_expense', '>=', $request->query('start_date_of_expense'));
            }
            if ($request->query('end_date_of_expense') != '') {
                $expenses = $expenses->where('date_of_expense', '<=', $request->query('end_date_of_expense'));
            }
        }

        // Apply date range filters for created_at if provided
        if ($request->query('created_start_at') != '' || $request->query('created_end_at') != '') {
            if ($request->query('created_start_at') != '') {
                $expenses = $expenses->where('created_at', '>=', $request->query('created_start_at'));
            }
            if ($request->query('created_end_at') != '') {
                $expenses = $expenses->where('created_at', '<=', $request->query('created_end_at'));
            }
        }

        // Apply date range filters for updated_at if provided
        if ($request->query('updated_start_at') != '' || $request->query('updated_end_at') != '') {
            if ($request->query('updated_start_at') != '') {
                $expenses = $expenses->where('updated_at', '>=', $request->query('updated_start_at'));
            }
            if ($request->query('updated_end_at') != '') {
                $expenses = $expenses->where('updated_at', '<=', $request->query('updated_end_at'));
            }
        }

        // Apply date range filters for deleted_at if provided
        if ($request->query('deleted_start_at') != '' || $request->query('deleted_end_at') != '') {
            if ($request->query('deleted_start_at') != '') {
                $expenses = $expenses->where('deleted_at', '>=', $request->query('deleted_start_at'));
            }
            if ($request->query('deleted_end_at') != '') {
                $expenses = $expenses->where('deleted_at', '<=', $request->query('deleted_end_at'));
            }
        }

        // Get all data
        foreach ($expenses as $expense) {
            // Store on array the specific data
            foreach ($this->fillable_attr_expenses->getFillableAttributes() as $getFillableAttribute) {
                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_expenses->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($expense->$getFillableAttribute);
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_expenses->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableDate($expense->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $expense->$getFillableAttribute;
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
            $arr_container_datas['actions'] = array_values($crud_action);
            // ***************************** //

            // ***************************** //
            // Add details on action crud
            foreach ($arr_container_datas['actions'] as &$action) {
                // Check if 'details' key doesn't exist, then add it
                if (!isset($action['details'])) {
                    $action['details'] = [];
                }

                if ($action['button_name'] == 'View') {
                    $action['url'] = "expenses/expense/show/" . $arr_container_datas['expenses_id'];
                }

                if ($action['button_name'] == 'Edit') {
                    $action['details'] = []; // Initialize the details array

                    foreach ($this->fillable_attr_expenses->arrFormFieldsUpdate() as $key => $arrFormFieldsUpdate) {
                        // Check if the key is 'expiration_start_at' or 'expiration_end_at' and format the value if it exists
                        if (in_array($key, ['date_of_expense']) && isset($arr_container_datas[$key])) {
                            $formattedValue = Carbon::parse($arr_container_datas[$key])->format('Y-m-d');
                        } else {
                            $formattedValue = $arr_container_datas[$key] ?? null;
                        }

                        // Initialize the detail array for each field
                        $detail = [
                            'label' => $arrFormFieldsUpdate['label'],
                            'type' => $arrFormFieldsUpdate['type'] ?? 'input',
                            'value' => $arrFormFieldsUpdate['value'] ?? $formattedValue ?? null,
                        ];

                        // Only add 'option' if it exists in $arrFormFieldsUpdate and is an array (for select type)
                        if (isset($arrFormFieldsUpdate['option']) && is_array($arrFormFieldsUpdate['option'])) {
                            $detail['option'] = $arrFormFieldsUpdate['option'];
                        }

                        // Add the detail to the details array
                        $action['details'][] = $detail;
                    }
                }

                $action['expenses_id'] = $arr_container_datas['expenses_id'];
            }

            // ***************************** //

            unset($arr_container_datas['expenses_id']);

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Final response structure
        $response = [
            'expenses' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_expenses->arrFieldsColumnHeader()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $expenses->count(),
                'has_page' => $expenses->hasPages(),
                'has_more_pages' => $expenses->hasMorePages(),
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'next_page_url' => $expenses->nextPageUrl(),
                'previous_page_url' => $expenses->previousPageUrl(),
            ],
        ];

        // ***************************** //
        // Add details on action crud
        foreach ($response['buttons'] as &$buttons) {
            // Check if 'details' key doesn't exist, then add it
            if (!isset($buttons['details'])) {
                $buttons['details'] = [];
            }

            // Populate details for each attribute
            foreach ($this->fillable_attr_expenses->arrFormFieldsStore() as $key => $arrFormFieldsStore) {
                $buttons['details'][] = [
                    'label' => $arrFormFieldsStore['label'],
                    'type' => $arrFormFieldsStore['type'] ?? "input",
                    'value' => $arrFormFieldsStore['value'] ?? null,
                    'option' => $arrFormFieldsStore['option']  ?? null,
                ];
            }
        }
        // ***************************** //

        return response()->json(
            [
                'message' => "Successfully retrieve data",
                'data' => $response
            ],
            Response::HTTP_OK
        );
    }

    public function showExpensesImageChild(Request $request, string $id)
    {
        $crud_settings = $this->fillable_attr_expenses_image->getApiCrudSettings();
        // $relative_settings = $this->fillable_attr_expenses_image->getApiRelativeSettings();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $expenses_images = ExpensesImageModel::orderBy('created_at', 'desc')
        //     ->where("expenses_id", Crypt::decrypt($id))
        //     ->get();

        // Fetch paginated inventory items for the current page
        $expenses_images = ExpensesImageModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->where("expenses_id", Crypt::decrypt($id))
            ->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('original_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('size', 'LIKE', '%' . $search . '%'); // Search in another column
                //  ->orWhere('yet_another_column', 'LIKE', '%' . $search . '%'); // Search in a third column
            })
            ->paginate($request->query('limit', 10), ['*'], 'page', $request->query('page', 1)); // Correct pagination



        // Get all data
        foreach ($expenses_images as $expenses_image) {
            // Store on array the specific data
            foreach ($this->fillable_attr_expenses_image->getFillableAttributes() as $getFillableAttribute) {
                // Image then format it to specific path
                if ($getFillableAttribute == 'file') {
                    $arr_container_datas[$getFillableAttribute] = $expenses_image->$getFillableAttribute ? env("PATH_FILE_EXPENSES_CHILD") . $expenses_image->$getFillableAttribute : null;
                    $arr_container_datas['path_download'] = $expenses_image->$getFillableAttribute ? env("PATH_FILE_EXPENSES_CHILD") . $expenses_image->$getFillableAttribute : null;
                }
                // fields to encrypt
                else if (in_array($getFillableAttribute, $this->fillable_attr_expenses_image->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($expenses_image->$getFillableAttribute);
                } else if ($getFillableAttribute == 'user_id') {
                    $first_name = null;
                    $last_name = null;

                    $user_info = UserInfoModel::where('user_id', $expenses_image->$getFillableAttribute)->first();
                    if ($user_info) {
                        if ($this->helper->isEncrypted($user_info->first_name)) {
                            $first_name = Crypt::decrypt($user_info->first_name);
                        } else {
                            $first_name = $user_info->first_name;
                        }

                        if ($this->helper->isEncrypted($user_info->last_name)) {
                            $last_name = Crypt::decrypt($user_info->last_name);
                        } else {
                            $last_name = $user_info->last_name;
                        }
                    }

                    $arr_container_datas['owner'] = $first_name && $last_name  ? $first_name . " " . $last_name : null;
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_expenses_image->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableDate($expenses_image->$getFillableAttribute);
                }

                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $expenses_image->$getFillableAttribute;
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
            $arr_container_datas['actions'] = array_values($crud_action);
            // ***************************** //

            // ***************************** //
            // Add details on action crud
            foreach ($arr_container_datas['actions'] as &$action) {
                // Check if 'details' key doesn't exist, then add it
                if (!isset($action['details'])) {
                    $action['details'] = [];
                }

                if ($action['button_name'] == 'Edit') {
                    $action['details'] = []; // Initialize the details array

                    foreach ($this->fillable_attr_expenses_image->arrFormFieldsUpdate() as $key => $arrFormFieldsUpdate) {
                        // Initialize the detail array for each field
                        $detail = [
                            'label' => $arrFormFieldsUpdate['label'],
                            'type' => $arrFormFieldsUpdate['type'] ?? 'input',
                            'value' => null,
                        ];

                        // Only add 'option' if it exists in $arrFormFieldsUpdate and is an array (for select type)
                        if (isset($arrFormFieldsUpdate['option']) && is_array($arrFormFieldsUpdate['option'])) {
                            $detail['option'] = $arrFormFieldsUpdate['option'];
                        }

                        // Add the detail to the details array
                        $action['details'][] = $detail;
                    }
                }

                if ($action['button_name'] == 'Download') {
                    $action['url'] = $arr_container_datas['path_download'];
                }

                $action['expenses_image_id'] = $arr_container_datas['expenses_image_id'];
                $action['expenses_id'] = $arr_container_datas['expenses_id'];
            }

            // ***************************** //

            // Unset Data not Needed
            foreach ($this->fillable_attr_expenses_image->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_container_datas[$arrFieldsToUnsetTable]);
            }

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Get the formatted data then separate it on designated array
        $expenses_image = [];
        $expenses_file = [];
        foreach ($arr_all_data as $item) {
            $imageUrl = $item['file'];
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);

            if (in_array($extension, ['jpeg', 'png', 'jpg', 'JPEG', 'JPG'])) {
                $expenses_image[] = $item;
            } else {
                $expenses_file[] = $item;
            }
        }

        // Final response structure
        $response = [
            'expenses_image' => $expenses_image,
            'expenses_file' => $expenses_file,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_expenses_image->arrFieldsColumnHeaderTable()),
            // 'buttons' => $this->helper->formatApi(
            //     $relative_settings['prefix'],
            //     $relative_settings['payload'],
            //     $relative_settings['method'],
            //     $relative_settings['button_name'],
            //     $relative_settings['icon'],
            //     $relative_settings['container']
            // ),
        ];

        // // ***************************** //
        // // Add details on action crud
        // foreach ($response['buttons'] as &$buttons) {
        //     // Check if 'details' key doesn't exist, then add it
        //     if (!isset($buttons['details'])) {
        //         $buttons['details'] = [];
        //     }

        //     // Populate details for each attribute
        //     foreach ($this->fillable_attr_expenses_image->arrFormFieldsStore() as $key => $arrFormFieldsStore) {
        //         $buttons['details'][] = [
        //             'label' => $arrFormFieldsStore['label'],
        //             'type' => $arrFormFieldsStore['type'] ?? "input",
        //             'value' => $arrFormFieldsStore['value'] ?? null,
        //             'option' => $arrFormFieldsStore['option']  ?? null,
        //         ];
        //     }
        // }
        // // ***************************** //

        return response()->json(
            [
                'message' => "Successfully retrieve data",
                'data' => $response
            ],
            Response::HTTP_OK
        );
    }

    public function store(Request $request)
    {
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'amount' => 'required|numeric',
            'date_of_expense' => 'required|date',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Begin transaction
        DB::beginTransaction();
        try {
            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    0,
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
                $this->fillable_attr_expenses->arrToStores(),
                $result_merge_data,
                [],
                [],
                [],
                [],
            );

            // Create 
            $created = ExpensesModel::create($result_to_create);
            if (!$created) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store user information'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId(
                $created,
                $this->fillable_attr_expenses->idToUpdate(),
                Str::uuid()
            );

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
                    'user_action' => 'STORE_EXPENSES',
                ],
                $created->toArray(),
                1,
                null
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //

            DB::commit();
            return response()->json([
                'message' => 'Expenses store successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request)
    {
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'expenses_id' => 'required|string',
            'name' => 'required|string',
            'amount' => 'required|numeric',
            'date_of_expense' => 'required|date',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Decrypted id
            $decrypted_expenses_id = Crypt::decrypt($request->input('expenses_id'));

            // Check if voucher record exists
            $expenses = ExpensesModel::where('expenses_id', $decrypted_expenses_id)->first();
            if (!$expenses) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Handle image upload 
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    0,
                );
            }


            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $request->all(),
                $file_name,
                '',
            );

            // *********************************** //
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $expenses, // the model to update
                $this->fillable_attr_expenses->arrToUpdates(), // fields to update
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
                $expenses,
                $this->fillable_attr_expenses->arrToUpdates(),
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
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_EXPENSES',
                ],
                $result_update_logs_old_new,
                2,
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //

            DB::commit();

            return response()->json([
                'message' => 'Expenses update successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request)
    {
        $arr_log_details = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'expenses_id' => 'required|string',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        DB::beginTransaction();

        try {
            $decrypted_expenses_id = Crypt::decrypt($request->expenses_id);
            $expenses = ExpensesModel::where('expenses_id', $decrypted_expenses_id)->first();
            if (!$expenses) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $expenses->toArray();

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'DELETE_EXPENSES',
                ],
                $arr_log_details,
                3,
                env("PATH_FILE_INVENTORY")
            );

            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Delete 
            if (!$expenses->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete expenses'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }


            DB::commit();

            return response()->json([
                'message' => 'Successfully deleted data',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function uploadFile(Request $request)
    {
        $uploaded_files = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'image.*' => 'required|image|mimes:jpeg,png,jpg,JPEG,JPG,pdf,word|max:10240',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Handle image upload if it exists for the current item
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $file) {
                    if ($file->isValid()) {
                        $file_name = $this->helper->handleUploadFile(
                            [
                                'custom_folder' => 'expenses',
                                'file_image' => $file,
                                'image_actual_extension' => $file->getClientOriginalExtension(),
                                'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                            ],
                            0,
                        );

                        $uploaded_files[] = $file_name;
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Images store successfully',
                'images' => ''
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
