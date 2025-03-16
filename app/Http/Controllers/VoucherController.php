<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use App\Models\VoucherModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\VoucherItemModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class VoucherController extends Controller
{

    protected $helper, $fillable_attr_voucher, $fillable_attr_voucher_items;

    public function __construct(Helper $helper, VoucherModel $fillable_attr_voucher, VoucherItemModel $fillable_attr_voucher_items)
    {
        $this->helper = $helper;
        $this->fillable_attr_voucher = $fillable_attr_voucher;
        $this->fillable_attr_voucher_items = $fillable_attr_voucher_items;
    }

    public function index(Request $request)
    {
        $crud_settings = $this->fillable_attr_voucher->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_voucher->getApiRelativeSettings();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $voucher_parents = VoucherModel::orderBy('created_at', 'desc')->get();

        // Fetch paginated
        $voucher_parents = VoucherModel::orderBy('created_at', 'desc');

        // Apply search filter if provided
        if ($request->query('search') != '') {
            $voucher_parents = $voucher_parents->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('name', 'LIKE', '%' . $search . '%');
                // ->orWhere('item_code', 'LIKE', '%' . $search . '%'); // Search in another column
            });
        }

        // Apply date range filters if provided
        if ($request->query('expiration_start_at') != '' || $request->query('expiration_end_at') != '') {
            if ($request->query('expiration_start_at') != '') {
                $voucher_parents = $voucher_parents->where('expiration_start_at', '>=', $request->query('expiration_start_at'));
            }
            if ($request->query('expiration_end_at') != '') {
                $voucher_parents = $voucher_parents->where('expiration_end_at', '<=', $request->query('expiration_end_at'));
            }
        }

        // Apply date range filters for created_at if provided
        if ($request->query('created_start_at') != '' || $request->query('created_end_at') != '') {
            if ($request->query('created_start_at') != '') {
                $voucher_parents = $voucher_parents->where('created_at', '>=', $request->query('created_start_at'));
            }
            if ($request->query('created_end_at') != '') {
                $voucher_parents = $voucher_parents->where('created_at', '<=', $request->query('created_end_at'));
            }
        }

        // Apply date range filters for updated_at if provided
        if ($request->query('updated_start_at') != '' || $request->query('updated_end_at') != '') {
            if ($request->query('updated_start_at') != '') {
                $voucher_parents = $voucher_parents->where('updated_at', '>=', $request->query('updated_start_at'));
            }
            if ($request->query('updated_end_at') != '') {
                $voucher_parents = $voucher_parents->where('updated_at', '<=', $request->query('updated_end_at'));
            }
        }

        // Apply date range filters for deleted_at if provided
        if ($request->query('deleted_start_at') != '' || $request->query('deleted_end_at') != '') {
            if ($request->query('deleted_start_at') != '') {
                $voucher_parents = $voucher_parents->where('deleted_at', '>=', $request->query('deleted_start_at'));
            }
            if ($request->query('deleted_end_at') != '') {
                $voucher_parents = $voucher_parents->where('deleted_at', '<=', $request->query('deleted_end_at'));
            }
        }

        // status
        if ($request->query('status') != '' && in_array($request->query('status'), ['ACTIVE', 'INACTIVE', 'EXPIRED'])) {
            if ($request->query('status') != '') {
                $voucher_parents = $voucher_parents->where('status', Str::upper($request->query('status')));
            }
        }

        // Apply pagination
        $voucher_parents = $voucher_parents->paginate(
            $request->query('limit', 10), // Items per page
            ['*'], // Select all columns
            'page', // Pagination parameter name
            $request->query('page', 1) // Current page
        );

        // Get all data
        foreach ($voucher_parents as $voucher_parent) {
            // Store on array the specific data
            foreach ($this->fillable_attr_voucher->getFillableAttributes() as $getFillableAttribute) {
                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_voucher->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($voucher_parent->$getFillableAttribute);
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_voucher->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableDate($voucher_parent->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $voucher_parent->$getFillableAttribute;
                }
            }

            // ***************************** //
            // Added fields
            $arr_container_datas['used'] = VoucherItemModel::where('voucher_id', $voucher_parent->voucher_id)
                ->where('status', 'USED')
                ->count();

            // Added fields AVAILABLE
            $arr_container_datas['available'] = VoucherItemModel::where('voucher_id', $voucher_parent->voucher_id)
                ->where('status', 'AVAILABLE')
                ->count();

            // Total number of vouchers already created for the given voucher_id
            $total_vouchers = VoucherItemModel::where('voucher_id', $voucher_parent->voucher_id)->count();
            $arr_container_datas['total_vouchers'] = $total_vouchers;
            // ***************************** //


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
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($voucher_parent->voucher_id, $this->fillable_attr_voucher->arrModelWithId());
            // Unset actions based on conditions
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                foreach ($this->fillable_attr_voucher->unsetActions() as $unsetAction) {
                    $crud_action = array_filter($crud_action, function ($action) use ($unsetAction) {
                        return $action['button_name'] !== ucfirst($unsetAction);
                    });
                }
            }

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

                // update the url for view
                if ($action['button_name'] == 'View details') {
                    $action['url'] = $arr_container_datas['voucher_id'];
                }

                if ($action['button_name'] == 'Edit') {
                    // Get the array of fields
                    $fields = $this->fillable_attr_voucher->arrFields();

                    // Unset specific fields
                    unset($fields['generate_vouchers'], $fields['counter_vouchers']);

                    // Populate details for each remaining attribute
                    foreach ($fields as $key => $arrDetails) {
                        // Check if the key is 'expiration_start_at' or 'expiration_end_at' and format the value if it exists
                        if (in_array($key, ['expiration_start_at', 'expiration_end_at']) && isset($arr_container_datas[$key])) {
                            $formattedValue = Carbon::parse($arr_container_datas[$key])->format('Y-m-d');
                        } else {
                            $formattedValue = $arr_container_datas[$key] ?? null;
                        }

                        // Initialize the detail array
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? "input",
                            'value' => $arrDetails['value'] ?? $formattedValue,
                        ];

                        // Only add 'option' if it exists in $arrDetails and is an array (for select type)
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        $action['details'][] = $detail;
                    }
                }

                $action['voucher_id'] =  $arr_container_datas['voucher_id'];
                $action['name'] = $arr_container_datas['name'];
            }
            // ***************************** //

            // Unset fields
            unset($arr_container_datas['voucher_id']);
            unset($arr_container_datas['created_at']);
            unset($arr_container_datas['updated_at']);

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Final response structure
        $response = [
            'voucher' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_voucher->arrFieldsColumnHeader()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $voucher_parents->count(),
                'has_page' => $voucher_parents->hasPages(),
                'has_more_pages' => $voucher_parents->hasMorePages(),
                'current_page' => $voucher_parents->currentPage(),
                'last_page' => $voucher_parents->lastPage(),
                'per_page' => $voucher_parents->perPage(),
                'next_page_url' => $voucher_parents->nextPageUrl(),
                'previous_page_url' => $voucher_parents->previousPageUrl(),
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
            foreach ($this->fillable_attr_voucher->arrFields() as $key => $arrDetails) {
                $label = str_replace('_', ' ', ucfirst($key)); // Convert key to label

                $buttons['details'][] = [
                    'label' => $label,
                    'type' => $arrDetails['type'] ?? "input",
                    'value' => $arrDetails['value'] ?? null,
                    'option' => $arrDetails['option']  ?? null,
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

    public function showVoucherItemChild(Request $request, string $id)
    {
        $crud_settings = $this->fillable_attr_voucher_items->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_voucher_items->getApiRelativeSettings();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $voucher_child_items = VoucherItemModel::orderBy('created_at', 'desc')
        //     ->where('voucher_id', Crypt::decrypt($id))
        //     ->get();

        // Fetch paginated inventory items for the current page
        $voucher_child_items = VoucherItemModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->where('voucher_id', Crypt::decrypt($id));

        // Apply search filter if provided
        if ($request->query('search') != '') {
            $voucher_child_items = $voucher_child_items->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('item_code', 'LIKE', '%' . $search . '%');
                // ->orWhere('item_code', 'LIKE', '%' . $search . '%'); // Search in another column
            });
        }

        // Apply pagination
        $voucher_child_items = $voucher_child_items->paginate(
            $request->query('limit', 10), // Items per page
            ['*'], // Select all columns
            'page', // Pagination parameter name
            $request->query('page', 1) // Current page
        );


        // Get all data
        foreach ($voucher_child_items as $voucher_child_item) {
            // Store on array the specific data
            foreach ($this->fillable_attr_voucher_items->getFillableAttributes() as $getFillableAttribute) {

                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_voucher_items->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($voucher_child_item->$getFillableAttribute);
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_voucher_items->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableTimeDate($voucher_child_item->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $getFillableAttribute == 'status'
                        ? ucfirst(strtolower($voucher_child_item->$getFillableAttribute))
                        : $voucher_child_item->$getFillableAttribute;
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
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($voucher_child_item->voucher_code, $this->fillable_attr_voucher_items->arrModelWithId());
            // Unset actions based on conditions
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                foreach ($this->fillable_attr_voucher_items->unsetActions() as $unsetAction) {
                    $crud_action = array_filter($crud_action, function ($action) use ($unsetAction) {
                        return $action['button_name'] !== ucfirst($unsetAction);
                    });
                }
            }

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
                    // Populate details for each attribute
                    foreach ($this->fillable_attr_voucher_items->arrFields() as $key => $arrDetails) {
                        $action['details'][] = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? "input",
                            'value' => $arrDetails['value'] ?? $arr_container_datas[$key],
                            'option' => $arrDetails['option'] ?? null,
                        ];
                    }
                }

                $action['voucher_items_id'] =  $arr_container_datas['voucher_items_id'];
                $action['voucher_id'] =  $arr_container_datas['voucher_id'];
            }
            // ***************************** //

            // Data
            $arr_all_data[] = $arr_container_datas;
        }


        // ***************************** //
        // Unset the key not needed to display
        $arr_unset_details = [
            'voucher_items_id',
            'voucher_id',
        ];

        foreach ($arr_all_data as &$data) {
            foreach ($arr_unset_details as $unset_detail) {
                unset($data[$unset_detail]);
            }
        }

        // ***************************** //

        // Final response structure
        $response = [
            'voucher_items' => $arr_all_data,  // Use $arr_all_data instead of $data
            'columns' => $this->helper->transformColumnName($this->fillable_attr_voucher_items->arrFieldsColumnHeader()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $voucher_child_items->count(),
                'has_page' => $voucher_child_items->hasPages(),
                'has_more_pages' => $voucher_child_items->hasMorePages(),
                'current_page' => $voucher_child_items->currentPage(),
                'last_page' => $voucher_child_items->lastPage(),
                'per_page' => $voucher_child_items->perPage(),
                'next_page_url' => $voucher_child_items->nextPageUrl(),
                'previous_page_url' => $voucher_child_items->previousPageUrl(),
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
            foreach ($this->fillable_attr_voucher_items->arrFields() as $key => $arrDetails) {
                $label = str_replace('_', ' ', ucfirst($key)); // Convert key to label

                $buttons['details'][] = [
                    'label' => $label,
                    'type' => $arrDetails['type'] ?? "input",
                    'value' => $arrDetails['value'] ?? null,
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

    public function store(Request $request)
    {
        // Initialize an array to store all created items
        $created_items = [];
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'discount_amount' => 'required|numeric',
            'expiration_start_at' => 'required|string|max:255',
            'expiration_end_at' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'eu_device' => 'required|string',
            'generate_vouchers' => 'nullable|string|max:255',
            'counter_vouchers' => $request->input('generate_vouchers') === 'YES'
                ? 'required|numeric|min:1'
                : 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Handle image upload if it exists
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile([
                    'custom_folder' => 'voucher',
                    'file_image' => $request->file('image'),
                    'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                ]);
            }

            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $request->all(),
                $file_name,
                '',
            );

            // *********************************** //
            // Start Store
            // Format the content
            $result_to_create = $this->helper->arrStoreMultipleData(
                $this->fillable_attr_voucher->arrToStores(),
                $result_merge_data,
                [],
                [],
                [],
                [],
            );

            // Create 
            $created = VoucherModel::create($result_to_create);
            if (!$created) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store voucher parent'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_voucher->idToUpdate(), Str::uuid());
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
                    'user_action' => 'STORE_VOUCHER_PARENT',

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

            // Generate voucher children if needed
            if ($request->input('generate_vouchers') === 'YES' && $request->input('counter_vouchers') > 0) {
                $return_generate_voucher = $this->generateVoucherChild($request, $user->user_id, $created->voucher_id, $request->input('counter_vouchers'));

                if ($return_generate_voucher->getStatusCode() !== Response::HTTP_OK) {
                    DB::rollBack();
                    return $return_generate_voucher;
                }
            }

            DB::commit();
            return response()->json(['message' => 'Voucher parent stored successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function generateVoucherChild($request, $user_id, $voucher_id, $counter_vouchers)
    {
        $file_name = '';
        $ctr = 1;
        $arr_log_details = [];  // Initialize the array inside the method

        while ($ctr <= $counter_vouchers) {  // Fix loop condition
            do {
                $generated_voucher = $this->helper->generateRandomString();
            } while (VoucherItemModel::where('voucher_code', $generated_voucher)->exists());

            $data_to_store = [
                'voucher_id' => $voucher_id,
                'voucher_code' => $generated_voucher,
                'status' => 'AVAILABLE',
            ];

            DB::beginTransaction();

            try {
                // Merge the content and file or image
                $result_merge_data = $this->helper->arrMergeContentAndFile(
                    $request,
                    $data_to_store,
                    $file_name,
                    'image',
                );

                // Format the content
                $result_to_create = $this->helper->arrStoreMultipleData(
                    $this->fillable_attr_voucher_items->arrToStores(),
                    $result_merge_data,
                    [],
                    [],
                    [],
                    [],
                );

                $created = VoucherItemModel::create($result_to_create);
                if (!$created) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to store Voucher Child'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Update the unique I.D
                $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_voucher_items->idToUpdate(), Str::uuid());
                if ($update_unique_id) {
                    DB::rollBack();
                    return $update_unique_id;
                }

                $arr_log_details['generate_voucher_child-' . $ctr][] = $created;

                DB::commit();
                $ctr++;  // Increment the counter only after a successful creation

            } catch (\Exception $e) {
                DB::rollBack();
                Log::info($e->getMessage());
                return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // *********************************** //
        // Start log
        // Logs
        $log_result = $this->helper->log(
            $request,
            [
                'user_device' => $request->eu_device,
                'user_id' => $user_id,
                'is_history' => 0,
                'user_action' => 'STORE_VOUCHER_CHILD',
            ],
            $arr_log_details,
            4,
            null
        );

        // Failed to create log
        if ($log_result->getStatusCode() !== Response::HTTP_OK) {
            DB::rollBack(); // Rollback transaction
            return $log_result;
        }
        // End log
        // *********************************** //


        return response()->json(['message' => 'Generate voucher child successfully'], Response::HTTP_OK);
    }

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
            'voucher_id' => 'required|string',
            'name' => 'required|string',
            'discount_amount' => 'required|numeric',
            'expiration_start_at' => 'required|string|max:255',
            'expiration_end_at' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        DB::beginTransaction();
        try {
            // Decrypted id
            $decrypted_voucher_id = Crypt::decrypt($request->input('voucher_id'));

            // Check if voucher record exists
            $voucher = VoucherModel::where('voucher_id', $decrypted_voucher_id)->first();
            if (!$voucher) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Handle image upload 
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'voucher',
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
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $voucher, // the model to update
                $this->fillable_attr_voucher->arrToUpdates(), // fields to update
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
                $voucher,
                $this->fillable_attr_voucher->arrToUpdates(),
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
                    'user_action' => 'UPDATE_VOUCHER_PARENT',
                ],
                $result_update_logs_old_new,
                2,
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
                'message' => 'Voucher parent item update successfully',
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
            'voucher_id' => 'required|string',
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
            $decrypted_voucher_id = Crypt::decrypt($request->voucher_id);
            $voucher = VoucherModel::where('voucher_id', $decrypted_voucher_id)->first();
            if (!$voucher) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $voucher->toArray();

            // Checking Id on other tbl if exist unset the the api
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($voucher->voucher_id, $this->fillable_attr_voucher->arrModelWithId());
            // Check if 'is_exist' is 'yes' in the first element then cant delete
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                return response()->json([
                    'message' => 'Can\'t delete because this id exist on other table',
                    'voucher_id' => $request->voucher_id,

                ], Response::HTTP_NOT_FOUND);
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
                    'user_action' => 'DELETE_VOUCHER_PARENT',
                ],
                $arr_log_details,
                3,
                null
            );

            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Delete 
            if (!$voucher->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete voucher'], Response::HTTP_UNPROCESSABLE_ENTITY);
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
}
