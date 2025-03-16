<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Models\InventoryProductRestockModel;
use Symfony\Component\HttpFoundation\Response;

class InventoryProductRestockController extends Controller
{
    protected $helper, $fillable_attr_product_restock;

    public function __construct(Helper $helper, InventoryProductRestockModel $fillable_attr_product_restock)
    {
        $this->helper = $helper;
        $this->fillable_attr_product_restock = $fillable_attr_product_restock;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $crud_settings = $this->fillable_attr_product_restock->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_product_restock->getApiRelativeSettings();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $inventory_product_restocks = InventoryProductRestockModel::orderBy('created_at', 'desc')->get();

        // Fetch paginated inventory items for the current page
        $inventory_product_restocks = InventoryProductRestockModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('remarks', 'LIKE', '%' . $search . '%');
                //  ->orWhere('item_code', 'LIKE', '%' . $search . '%') // Search in another column
                //  ->orWhere('yet_another_column', 'LIKE', '%' . $search . '%'); // Search in a third column
            })
            ->paginate($request->query('limit', 10), ['*'], 'page', $request->query('page', 1)); // Correct pagination

        // Get all data
        foreach ($inventory_product_restocks as $inventory_product_restock) {
            // Store on array the specific data
            foreach ($this->fillable_attr_product_restock->getFillableAttributes() as $getFillableAttribute) {
                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_product_restock->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($inventory_product_restock->$getFillableAttribute);
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_product_restock->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableTimeDate($inventory_product_restock->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $inventory_product_restock->$getFillableAttribute;
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
                    foreach ($this->fillable_attr_product_restock->arrFieldsUpdate() as $key => $arrDetails) {
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
                $action['inventory_product_id'] = $arr_container_datas['inventory_product_id'] ?? null;
            }

            // ***************************** //

            // Unset the fields not to use
            foreach ($this->fillable_attr_product_restock->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_container_datas[$arrFieldsToUnsetTable]);
            }

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Final response structure
        $response = [
            'inventory_product_restock' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_product_restock->arrFieldsColumnHeader()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $inventory_product_restocks->count(),
                'has_page' => $inventory_product_restocks->hasPages(),
                'has_more_pages' => $inventory_product_restocks->hasMorePages(),
                'current_page' => $inventory_product_restocks->currentPage(),
                'last_page' => $inventory_product_restocks->lastPage(),
                'per_page' => $inventory_product_restocks->perPage(),
                'next_page_url' => $inventory_product_restocks->nextPageUrl(),
                'previous_page_url' => $inventory_product_restocks->previousPageUrl(),
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
            foreach ($this->fillable_attr_product_restock->arrFieldsStore() as $key => $arrDetails) {
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

    /**
     * Store a newly created resource in storage.
     */
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
            'inventory_product_id' => 'required|string',
            'count' => 'required|numeric|min:1',
            'image' => 'required|mimes:jpeg,png,jpg,JPEG,JPG|max:10240', // Adjusted validation rule
            'remarks' => 'nullable',
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

        // Begin transaction
        DB::beginTransaction();

        try {
            // Decrypt inventory product ID
            $decrypted_inventory_product_id = Crypt::decrypt($request->input('inventory_product_id'));

            // Retrieve the inventory product
            $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)->first();
            if (!$inventory_product) {
                DB::rollBack();
                return response()->json(['message' => 'Inventory product ID not found.'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory-product-restock',
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
                $this->fillable_attr_product_restock->arrToStores(),
                $result_merge_data,
                $this->fillable_attr_product_restock->arrPayloadIdsToDecrypt(),
                [],
                [],
                [],
            );

            // Create 
            $created = InventoryProductRestockModel::create($result_to_create);
            if (!$created) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store product restock'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId(
                $created,
                $this->fillable_attr_product_restock->idToUpdate(),
                Str::uuid()
            );

            if ($update_unique_id) {
                DB::rollBack();
                return $update_unique_id;
            }
            // End Store
            // *********************************** //

            // *********************************** //
            // Perform the stock deduction
            $inventory_product = $inventory_product->update([
                'stocks' => $inventory_product->stocks + $request->input('count'),
            ]);

            if (!$inventory_product) {
                // Rollback the transaction if failed to update stock
                DB::rollBack();
                return response()->json(['message' => 'Failed to update new stocks'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
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
                    'user_action' => 'STORE_INVENTORY_PRODUCT_RESTOCK',
                ],
                $created->toArray(),
                1,
                env("PATH_FILE_INVENTORY_PRODUCT_RESTOCK")
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //

            DB::commit();
            return response()->json(['message' => 'Product restock stored successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'inventory_product_restock_id' => 'required|string',
            'inventory_product_id' => 'required|string',
            'count' => 'required|numeric|min:1',
            'image' => 'required|mimes:jpeg,png,jpg,JPEG,JPG|max:10240', // Adjusted validation rule
            'remarks' => 'nullable',
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
            $decrypted_inventory_product_restock_id = Crypt::decrypt($request->input('inventory_product_restock_id'));
            $decrypted_inventory_product_id = Crypt::decrypt($request->input('inventory_product_id'));

            // Check if voucher record exists
            $inventory_product_restock = InventoryProductRestockModel::where('inventory_product_restock_id', $decrypted_inventory_product_restock_id)
                ->where('inventory_product_id', $decrypted_inventory_product_id)
                ->first();
            if (!$inventory_product_restock) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            //  ******************************* //
            // Retrieve the inventory product
            $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)->first();
            if (!$inventory_product) {
                DB::rollBack();
                return response()->json(['message' => 'Inventory product ID not found.'], Response::HTTP_NOT_FOUND);
            }

            // Perform the stock addition
            $inventory_product = $inventory_product->update([
                'stocks' => $inventory_product->stocks - $inventory_product_restock->count,
            ]);
            //  ******************************* //

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Handle image upload 
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory-product-restock',
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
                $inventory_product_restock, // the model to update
                $this->fillable_attr_product_restock->arrToUpdates(), // fields to update
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
                $inventory_product_restock,
                $this->fillable_attr_product_restock->arrToUpdates(),
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

            //  ******************************* //
            // Retrieve the inventory product
            $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)->first();
            if (!$inventory_product) {
                DB::rollBack();
                return response()->json(['message' => 'Inventory product ID not found.'], Response::HTTP_NOT_FOUND);
            }

            // Perform the stock addition
            $inventory_product = $inventory_product->update([
                'stocks' => $inventory_product->stocks + $request->input('count'),
            ]);
            //  ******************************* //

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_INVENTORY_PRODUCT_RESTOCK',
                ],
                $result_update_logs_old_new,
                2,
                env("PATH_FILE_INVENTORY_PRODUCT_RESTOCK")
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
            'inventory_product_id' => 'required|string',
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
            $decrypted_inventory_product_restock_id = Crypt::decrypt($request->input('inventory_product_restock_id'));
            $decrypted_inventory_product_id = Crypt::decrypt($request->input('inventory_product_id'));

            // Check if voucher record exists
            $inventory_product_restock = InventoryProductRestockModel::where('inventory_product_restock_id', $decrypted_inventory_product_restock_id)
                ->where('inventory_product_id', $decrypted_inventory_product_id)
                ->first();
            if (!$inventory_product_restock) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $inventory_product_restock->toArray();

            //  ******************************* //
            // Retrieve the inventory product
            $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)->first();
            if (!$inventory_product) {
                DB::rollBack();
                return response()->json(['message' => 'Inventory product ID not found.'], Response::HTTP_NOT_FOUND);
            }

            // Perform the stock addition
            $inventory_product = $inventory_product->update([
                'stocks' => $inventory_product->stocks - $inventory_product_restock->count,
            ]);
            //  ******************************* //

            // Delete 
            if (!$inventory_product_restock->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete inventory product restock'], Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    'user_action' => 'DELETE_INVENTORY_PRODUCT_RESTOCK',
                ],
                $arr_log_details,
                3,
                env("PATH_FILE_INVENTORY_PRODUCT_RESTOCK")
            );

            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

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
