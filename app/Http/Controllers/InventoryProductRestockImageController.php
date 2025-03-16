<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Models\InventoryProductRestockModel;
use Symfony\Component\HttpFoundation\Response;
use App\Models\InventoryProductRestockImageModel;

class InventoryProductRestockImageController extends Controller
{
    protected $helper, $fillable_attr_product_restock_image;

    public function __construct(Helper $helper, InventoryProductRestockImageModel $fillable_attr_product_restock_image)
    {
        $this->helper = $helper;
        $this->fillable_attr_product_restock_image = $fillable_attr_product_restock_image;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $crud_settings = $this->fillable_attr_product_restock_image->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_product_restock_image->getApiRelativeSettings();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $inventory_product_restocks = InventoryProductRestockImageModel::orderBy('created_at', 'desc')->get();

        // Define the number of items per page
        $per_page = 10; // Set how many notifications you want per page

        // Fetch paginated inventory items for the current page
        $inventory_product_restock_images = InventoryProductRestockImageModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->paginate($per_page, ['*'], 'page', $request->query('page', 1)); // Correct pagination


        // Get all data
        foreach ($inventory_product_restock_images as $inventory_product_restock_image) {
            // Store on array the specific data
            foreach ($this->fillable_attr_product_restock_image->getFillableAttributes() as $getFillableAttribute) {
                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_product_restock_image->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($inventory_product_restock_image->$getFillableAttribute);
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_product_restock_image->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableTimeDate($inventory_product_restock_image->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $inventory_product_restock_image->$getFillableAttribute;
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
                    // Populate details for each remaining attribute
                    foreach ($this->fillable_attr_product_restock_image->arrFieldsUpdate() as $key => $arrDetails) {
                        // Initialize the detail array
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? 'input',
                            'value' => $arrDetails['value'] ?? ($arr_container_datas[$key] ?? null),
                        ];

                        // Only add 'option' if it exists and is an array
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        // Add the detail to the action array
                        $action['details'][] = $detail;
                    }
                }

                $action['inventory_product_restock_id'] = $arr_container_datas['inventory_product_restock_id'] ?? null;
                $action['inventory_product_restock_image_id'] = $arr_container_datas['inventory_product_restock_image_id'] ?? null;
            }

            // ***************************** //

            // Unset the fields not to use
            foreach ($this->fillable_attr_product_restock_image->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_container_datas[$arrFieldsToUnsetTable]);
            }

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Final response structure
        $response = [
            'inventory_product_restock' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_product_restock_image->arrFieldsColumnHeaderTable()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $inventory_product_restock_images->count(),
                'has_page' => $inventory_product_restock_images->hasPages(),
                'has_more_pages' => $inventory_product_restock_images->hasMorePages(),
                'current_page' => $inventory_product_restock_images->currentPage(),
                'last_page' => $inventory_product_restock_images->lastPage(),
                'per_page' => $inventory_product_restock_images->perPage(),
                'next_page_url' => $inventory_product_restock_images->nextPageUrl(),
                'previous_page_url' => $inventory_product_restock_images->previousPageUrl(),
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
            foreach ($this->fillable_attr_product_restock_image->arrFieldsStore() as $key => $arrDetails) {
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

    // TODO: Logs
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $created_items = [];
        $file_name = '';

        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'inventory_product_restock_id' => 'required|string',
            'image' => 'required|mimes:jpeg,png,jpg,JPEG,JPG|max:10240', // Adjusted validation rule
            'eu_device' => 'required|string',
        ]);


        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        DB::beginTransaction();

        try {
            $decrypted_inventory_product_restock_id = Crypt::decrypt($request->input('inventory_product_restock_id'));

            $inventory_product_restock_id = InventoryProductRestockModel::where('inventory_product_restock_id', $decrypted_inventory_product_restock_id)->first();
            if (!$inventory_product_restock_id) {
                DB::rollBack();
                return response()->json(['message' => 'Inventory product restock ID not found.'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Handle image upload 
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory-product-restock-image',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    0,
                );
            }


            $result_to_create = $this->helper->arrStoreMultipleData(
                $this->fillable_attr_product_restock_image->arrToStores(),
                $request->all(),
                $file_name,
                $this->fillable_attr_product_restock_image->arrPayloadIdsToDecrypt(),
                [],
                []
            );


            $created = InventoryProductRestockImageModel::create($result_to_create);
            if (!$created) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to store product lost'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_product_restock_image->idToUpdate(), Str::uuid());
            if ($update_unique_id) {
                DB::rollBack();
                return $update_unique_id;
            }

            $created_items[] = $created;

            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->input('eu_device'),
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'STORE_INVENTORY_PRODUCT_RESTOCK_IMAGE',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['message' => 'Product restock images stored successfully'], Response::HTTP_OK);
    }


    // TODO: Logs
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
            'inventory_product_restock_id' => 'required|string',
            'image' => 'required|mimes:jpeg,png,jpg,JPEG,JPG|max:10240',
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
            $decrypted_inventory_product_restock_image_id = Crypt::decrypt($request->input('inventory_product_restock_image_id'));
            $decrypted_inventory_product_restock_id = Crypt::decrypt($request->input('inventory_product_restock_id'));

            // Check if voucher record exists
            $inventory_product_restock_image = InventoryProductRestockImageModel::where('inventory_product_restock_image_id', $decrypted_inventory_product_restock_image_id)
                ->where('inventory_product_restock_id', $decrypted_inventory_product_restock_id)
                ->first();
            if (!$inventory_product_restock_image) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory-product-restock-image',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    0,
                );
            }

            // Get the changes of the fields
            $result_changes_item_for_logs = $this->helper->updateLogsOldNew(
                $inventory_product_restock_image,
                $this->fillable_attr_product_restock_image->arrToUpdates(),
                $request->all(),
                $file_name
            );
            $changes_for_logs[] = [
                'fields' => $result_changes_item_for_logs,
            ];

            // Check if there's Changes Logs
            $result_changes_logs = $this->helper->checkIfTheresChangesLogs($changes_for_logs);
            if ($result_changes_logs) {
                DB::rollBack();
                return $result_changes_logs;
            }

            // Update Multiple Data
            $result_update_multi_data = $this->helper->arrUpdateMultipleData(
                $inventory_product_restock_image,
                $this->fillable_attr_product_restock_image->arrToUpdates(),
                $request->all(),
                $file_name != '' ? $file_name : ''
            );
            if ($result_update_multi_data) {
                DB::rollBack();
                return $result_update_multi_data;
            }

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->input('eu_device'),
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_INVENTORY_PRODUCT_RESTOCK_IMAGE',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();
            return response()->json([
                'message' => 'Inventory product restock update successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // TODO: Logs
    /**
     * Remove the specified resource from storage.
     */
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
            'inventory_product_restock_id' => 'required|string',
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
            // Decrypted id
            $decrypted_inventory_product_restock_image_id = Crypt::decrypt($request->input('inventory_product_restock_image_id'));
            $decrypted_inventory_product_restock_id = Crypt::decrypt($request->input('inventory_product_restock_id'));

            // Check if voucher record exists
            $inventory_product_restock_image = InventoryProductRestockImageModel::where('inventory_product_restock_image_id', $decrypted_inventory_product_restock_image_id)
                ->where('inventory_product_restock_id', $decrypted_inventory_product_restock_id)
                ->first();
            if (!$inventory_product_restock_image) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            foreach ($this->fillable_attr_product_restock_image->getFillableAttributes() as $getFillableAttributes) {
                $arr_log_details['fields'][$getFillableAttributes] = $inventory_product_restock_image->$getFillableAttributes;
            }

            // Delete the user
            if (!$inventory_product_restock_image->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete inventory product lost image'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->input('eu_device'),
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'DELETE_INVENTORY_PRODUCT_RESTOCK_IMAGE',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();

            return response()->json([
                'message' => 'Successfully deleted data',
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
