<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PurchaseModel;
use App\Models\InventoryModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class InventoryController extends Controller
{

    protected $helper, $fillable_attr_inventorys, $fillable_attr_inventory_children;

    public function __construct(Helper $helper, InventoryModel $fillable_attr_inventorys, InventoryProductModel $fillable_attr_inventory_children)
    {
        $this->helper = $helper;
        $this->fillable_attr_inventorys = $fillable_attr_inventorys;
        $this->fillable_attr_inventory_children = $fillable_attr_inventory_children;
    }

    public function index(Request $request)
    {
        $crud_settings = $this->fillable_attr_inventorys->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_inventorys->getApiRelativeSettings();
        $view_settings = $this->fillable_attr_inventorys->getViewRowTable();
        $arr_inventory_item = [];
        $arr_parent_inventory_data = [];
        $all_inventory_items = [];
        $arr_filter = [];

        // // Validation rules for query parameters 'limit' and 'page'
        // $validator = Validator::make($request->query(), [
        //     'page' => 'required|integer|min:1',
        // ]);

        // // Check if validation fails
        // if ($validator->fails()) {
        //     return response()->json(
        //         [
        //             'message' => $validator->errors(),
        //         ],
        //         Response::HTTP_UNPROCESSABLE_ENTITY
        //     );
        // }

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Fetch paginated inventory items for the current page
        $inventory_parents = InventoryModel::orderBy('created_at', 'desc')->get(); // Ensure results are ordered for consistent pagination

        // Fetch paginated inventory items for the current page
        // $inventory_parents = InventoryModel::orderBy('created_at', 'desc'); // Ensure results are ordered for consistent pagination

        // // Apply search filter if provided
        // if ($request->query('search') != '') {
        //     $inventory_parents = $inventory_parents->where(function ($query) use ($request) {
        //         $search = $request->query('search', '');
        //         $query->where('name', 'LIKE', '%' . $search . '%');
        //     });
        // }

        // if ($request->query('category') != '') {
        //     // Apply category filter
        //     $inventory_parents = $inventory_parents->where('category', $request->query('category'));
        // }

        // // Apply pagination
        // $inventory_parents = $inventory_parents->paginate(
        //     $request->query('limit', 10), // Items per page
        //     ['*'], // Select all columns
        //     'page', // Pagination parameter name
        //     $request->query('page', 1) // Current page
        // );

        // Get all data 
        foreach ($inventory_parents as $inventory_parent) {

            // Store on array the specific data
            foreach ($this->fillable_attr_inventorys->getFillableAttributes() as $getFillableAttribute) {
                if ($getFillableAttribute == 'inventory_id') {
                    $arr_parent_inventory_data[$getFillableAttribute] = Crypt::encrypt($inventory_parent->$getFillableAttribute);
                } else if ($getFillableAttribute == 'image') {
                    $arr_parent_inventory_data[$getFillableAttribute] = $inventory_parent->$getFillableAttribute ? env("PATH_FILE_INVENTORY") . $inventory_parent->$getFillableAttribute : null;
                } else if (in_array($getFillableAttribute, $this->fillable_attr_inventorys->arrToConvertToReadableDateTime())) {
                    $arr_parent_inventory_data[$getFillableAttribute] = $this->helper->convertReadableTimeDate($inventory_parent->$getFillableAttribute);
                } else {
                    $arr_parent_inventory_data[$getFillableAttribute] = $inventory_parent->$getFillableAttribute;
                }

                // Get all category and put in filter
                if ($getFillableAttribute == 'category') {
                    if (!in_array($inventory_parent->$getFillableAttribute, $arr_filter)) {
                        $arr_filter[] = $inventory_parent->$getFillableAttribute;
                    }
                }
            }

            $arr_inventory_item = $arr_parent_inventory_data;
            $inventory_children = InventoryProductModel::where('inventory_id', $inventory_parent->inventory_id)->get();
            $arr_inventory_item['variant'] =  $inventory_children->count();

            $arr_inventory_item['stocks'] = $inventory_children->sum('stocks');

            // TODO : check if correct total sales
            // Calculate total sales for all inventory items including both discounted and retail prices
            $total_sales = 0;
            foreach ($inventory_children as $child) {
                if ($child->status == "PAID") {
                    $purchases = PurchaseModel::where('inventory_id', $child->inventory_id)
                        ->where('inventory_product_id', $child->inventory_product_id)
                        ->get();
                    foreach ($purchases as $purchase) {
                        if ($purchase->discounted_price != null) {
                            $total_sales += $purchase->discounted_price;
                        } else {
                            $total_sales += $purchase->retail_price;
                        }
                    }
                }
            }
            $arr_inventory_item['total_sales'] = $total_sales;

            // TODO : fix total discounted
            $ctr_total_discounted = 0;
            foreach ($inventory_children as $child) {
                if ($child->status == "PAID") {
                    if ($child->discounted_price != null) {
                        $ctr_total_discounted++;
                    }
                }
            }
            $arr_inventory_item['total_discounted'] = $ctr_total_discounted;

            // TODO : fix the total return once e-commerce PAID
            $arr_inventory_item['total_return'] = 0;

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
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($inventory_parent->inventory_id, $this->fillable_attr_inventorys->arrModelWithId());
            // Unset actions based on conditions
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                foreach ($this->fillable_attr_inventorys->unsetActions() as $unsetAction) {
                    $crud_action = array_filter($crud_action, function ($action) use ($unsetAction) {
                        return $action['button_name'] !== ucfirst($unsetAction);
                    });
                }
            }

            // Add the format Api Crud
            $arr_inventory_item['action'] = array_values($crud_action);
            // ***************************** //

            // ***************************** //
            // Add details on action crud
            foreach ($arr_inventory_item['action'] as &$action) {
                // Check if 'details' key doesn't exist, then add it
                if (!isset($action['details'])) {
                    $action['details'] = [];
                }

                // Populate details for each attribute
                foreach ($this->fillable_attr_inventorys->arrDetails() as $arrDetails) {
                    if ($arrDetails == 'image') {
                        $action['details'][] = [
                            'label' => "Product " . ucfirst($arrDetails),
                            'type' => 'file',
                            'value' =>  null
                        ];
                    } else {
                        $action['details'][] = [
                            'label' => "Product " . ucfirst($arrDetails),
                            'type' => 'input',
                            'value' => $arr_inventory_item[$arrDetails] ?? null
                        ];
                    }
                }
            }
            // ***************************** //

            // ***************************** //
            // Add details on action crud
            foreach ($arr_inventory_item['action'] as &$action) {
                $action['inventory_id'] =  $arr_parent_inventory_data['inventory_id'];
            }
            // ***************************** //

            // Add view on row item
            $arr_inventory_item['view'] = [[
                'url' => $view_settings['url'] . $arr_parent_inventory_data['inventory_id'],
                'method' => $view_settings['method'],
                'name' => $inventory_parent->name,
            ]];

            // Data
            $all_inventory_items[] = $arr_inventory_item;
        }

        // Final response structure
        $response = [
            'inventory' => $all_inventory_items,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_inventorys->getFillableAttributes()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'filter_category' => $arr_filter,
            // 'pagination' => [
            //     'count' => $inventory_parents->count(),
            //     'has_page' => $inventory_parents->hasPages(),
            //     'has_more_pages' => $inventory_parents->hasMorePages(),
            //     'current_page' => $inventory_parents->currentPage(),
            //     'last_page' => $inventory_parents->lastPage(),
            //     'per_page' => $inventory_parents->perPage(),
            //     'next_page_url' => $inventory_parents->nextPageUrl(),
            //     'previous_page_url' => $inventory_parents->previousPageUrl(),
            // ],
        ];

        // ***************************** //
        // Add details on action crud
        foreach ($response['buttons'] as &$buttons) {
            // Check if 'details' key doesn't exist, then add it
            if (!isset($buttons['details'])) {
                $buttons['details'] = [];
            }

            // Populate details for each attribute
            foreach ($this->fillable_attr_inventorys->arrDetails() as $arrDetails) {
                if ($arrDetails == 'image') {
                    $buttons['details'][] = [
                        'label' => "Product " . ucfirst($arrDetails),
                        'type' => 'file',
                        'value' => null,
                    ];
                } else {
                    $buttons['details'][] = [
                        'label' => "Product " . ucfirst($arrDetails),
                        'type' => 'input',
                        'value' => null,
                    ];
                }
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

    public function showProduct(Request $request, string $id)
    {
        $crud_settings = $this->fillable_attr_inventory_children->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_inventory_children->getApiRelativeSettings();
        $view_settings = $this->fillable_attr_inventory_children->getViewRowTable();
        $arr_inventory_item = [];
        $all_inventory_items = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $inventory_products = InventoryProductModel::orderBy('created_at', 'desc')
        //     ->where('inventory_id', Crypt::decrypt($id))
        //     ->get();

        // Fetch paginated inventory items for the current page
        $inventory_products = InventoryProductModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->where('inventory_id', Crypt::decrypt($id));

        // Apply search filter if provided
        if ($request->query('search') != '') {
            $inventory_products = $inventory_products->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('item_code', 'LIKE', '%' . $search . '%'); // Search in another column
            });
        }

        // Apply pagination
        $inventory_products = $inventory_products->paginate(
            $request->query('limit', 10), // Items per page
            ['*'], // Select all columns
            'page', // Pagination parameter name
            $request->query('page', 1) // Current page
        );

        if (!$inventory_products) {
            return response()->json(
                [
                    'message' => 'Data not found',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        foreach ($inventory_products as $inventory_product) {
            foreach ($this->fillable_attr_inventory_children->getFillableAttributes() as $getFillableAttribute) {
                if ($getFillableAttribute == 'inventory_product_id') {
                    $arr_inventory_item[$getFillableAttribute] = Crypt::encrypt($inventory_product->$getFillableAttribute);
                } else if ($getFillableAttribute == 'inventory_id') {
                    $arr_inventory_item[$getFillableAttribute] = Crypt::encrypt($inventory_product->$getFillableAttribute);
                } else if ($getFillableAttribute == 'image') {
                    $arr_inventory_item[$getFillableAttribute] = $inventory_product->$getFillableAttribute ? env("PATH_FILE_INVENTORY_PRODUCT") . $inventory_product->$getFillableAttribute : null;
                } elseif (in_array($getFillableAttribute, $this->fillable_attr_inventory_children->arrToConvertToReadableDateTime())) {
                    $arr_inventory_item[$getFillableAttribute] = $inventory_product->$getFillableAttribute != null ? $this->helper->convertReadableDate($inventory_product->$getFillableAttribute) : null;
                } else {
                    $arr_inventory_item[$getFillableAttribute] = $inventory_product->$getFillableAttribute;
                }
            }

            // ***************************** //
            $arr_inventory_item['status'] = '';
            // Status
            if ($arr_inventory_item['stocks'] == 0) {
                $arr_inventory_item['status'] =  'Empty';
            } elseif ($arr_inventory_item['stocks'] <= $arr_inventory_item['low_stocks']) {
                $arr_inventory_item['status'] = 'Low';
            } elseif ($arr_inventory_item['stocks'] <= $arr_inventory_item['moderate_stocks']) {
                // Only set to Moderate if Low has not been set
                if ($arr_inventory_item['status'] == '') {
                    $arr_inventory_item['status'] = 'Moderate';
                }
            } else {
                // If none of the above conditions are met, set to High
                $arr_inventory_item['status'] = 'High';
            }

            $total_sales = PurchaseModel::where('inventory_id', $inventory_product->inventory_id)
                ->where('inventory_product_id', $inventory_product->inventory_product_id)
                ->where('status', 'PAID')
                ->count();
            $arr_inventory_item['sells'] = $total_sales;
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
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($inventory_product->inventory_id, $this->fillable_attr_inventory_children->arrModelWithId());
            // Unset actions based on conditions
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                foreach ($this->fillable_attr_inventory_children->unsetActions() as $unsetAction) {
                    $crud_action = array_filter($crud_action, function ($action) use ($unsetAction) {
                        return $action['button_name'] !== ucfirst($unsetAction);
                    });
                }
            }

            // Add the format Api Crud
            $arr_inventory_item['action'] = array_values($crud_action);
            // ***************************** //

            // ***************************** //
            // Add details on action crud
            foreach ($arr_inventory_item['action'] as &$action) {
                // Check if 'details' key doesn't exist, then add it
                if (!isset($action['details'])) {
                    $action['details'] = [];
                }

                // Iterate through each attribute to populate details
                if ($action['button_name'] == 'View') {
                    $action['url_lost_show'] = "inventory/product/lost/show/" . $arr_inventory_item['inventory_product_id'];
                    $action['url_restock_show'] = "inventory/product/restock/show/" . $arr_inventory_item['inventory_product_id'];
                    $action['url_found_show'] = "inventory/product/found/show/" . $arr_inventory_item['inventory_product_id'];

                    // Loop and display it
                    foreach ($this->fillable_attr_inventory_children->arrFieldsToDisplayOnLostFoundRestock() as $key => $arrFieldsToDisplayOnLostFoundRestock) {

                        if ($key == 'image') {
                            $action[$key] = $arr_inventory_item[$key] != null ? $arrFieldsToDisplayOnLostFoundRestock . $arr_inventory_item[$key] : null;
                        } else {
                            $action[$key] = $arr_inventory_item[$key];
                        }
                    }
                }

                // Iterate through each attribute to populate details
                if ($action['button_name'] == 'Edit') {
                    $detail = [];

                    // Populate details for each remaining attribute
                    foreach ($this->fillable_attr_inventory_children->arrFieldsUpdate() as $key => $arrDetails) {
                        // Initialize the detail array
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? 'input',
                            'value' => $key == 'image' || $key == 'item_expiration_at' ? null : $arrDetails['value'] ?? ($arr_inventory_item[$key] ?? null),
                        ];

                        // Only add 'option' if it exists and is an array
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        // Add the detail to the action array
                        $action['details'][] = $detail;
                    }
                }

                $action['inventory_product_id'] =  $arr_inventory_item['inventory_product_id'];
                $action['inventory_id'] =  $arr_inventory_item['inventory_id'];
            }
            // ***************************** //

            // Add view on row item
            $arr_inventory_item['view'] = [[
                'url' => $view_settings['url'] . $arr_inventory_item['inventory_id'],
                'method' => $view_settings['method'],
                'name' => $arr_inventory_item['name'],
            ]];

            // Unset Data not Needed
            foreach ($this->fillable_attr_inventory_children->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_inventory_item[$arrFieldsToUnsetTable]);
            }

            // Collect each inventory item
            $all_inventory_items[] = $arr_inventory_item;
        }


        // Final response structure
        $response = [
            'inventory_product' => $all_inventory_items,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_inventory_children->arrColumns()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $inventory_products->count(),
                'has_page' => $inventory_products->hasPages(),
                'has_more_pages' => $inventory_products->hasMorePages(),
                'current_page' => $inventory_products->currentPage(),
                'last_page' => $inventory_products->lastPage(),
                'per_page' => $inventory_products->perPage(),
                'next_page_url' => $inventory_products->nextPageUrl(),
                'previous_page_url' => $inventory_products->previousPageUrl(),
            ],
            // 'filter' => $filter
        ];

        // ***************************** //
        // Add details on action crud
        foreach ($response['buttons'] as &$buttons) {
            // Check if 'details' key doesn't exist, then add it
            if (!isset($buttons['details'])) {
                $buttons['details'] = [];
            }

            // Populate details for each attribute
            foreach ($this->fillable_attr_inventory_children->arrFieldsStore() as $key => $arrDetails) {
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

    public function store(Request $request)
    {
        // Initialize an array to store all created items
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'image' =>  'nullable|image|mimes:jpeg,png,jpg,JPG|max:10240',
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

        $exists = InventoryModel::where('name', $request->input('name'))
            ->where('category', $request->input('category'))
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'The product name is already exist.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
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
                $this->fillable_attr_inventorys->arrToStores(),
                $result_merge_data,
                [],
                [],
                [],
                [],
            );

            // Create 
            $created = InventoryModel::create($result_to_create);
            if (!$created) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store user information'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId(
                $created,
                $this->fillable_attr_inventorys->idToUpdate(),
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
                    'user_action' => 'STORE_INVENTORY_PARENT',
                ],
                $created->toArray(),
                1,
                env("PATH_FILE_INVENTORY")
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
                'message' => 'Inventory records parent store successfully',
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
            'inventory_id' => 'required|string',
            'name' => 'required|string|max:255',
            'image' =>  'nullable|image|mimes:jpeg,png,jpg,JPG|max:10240',
            'category' => 'required|string|max:255',
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

        // Declare variable to unset
        $request_all = $request->all();
        $arr_to_update = $this->fillable_attr_inventorys->arrToUpdates();

        // Checking if the same name and category then unset it don't use it for update changes
        $exists = InventoryModel::where('name', $request->input('name'))
            ->where('category', $request->input('category'))
            ->exists();
        if ($exists) {
            $arr_unset_fields = ['name', 'category'];

            foreach ($arr_unset_fields as $arr_unset_field) {
                // Unset from $request_all
                if (isset($request_all[$arr_unset_field])) {
                    unset($request_all[$arr_unset_field]);
                }

                // Unset from $arr_to_update (indexed array)
                if (($key = array_search($arr_unset_field, $arr_to_update)) !== false) {
                    unset($arr_to_update[$key]);
                }
            }

            // Re-index the array to maintain numerical keys
            $arr_to_update = array_values($arr_to_update);
        }

        DB::beginTransaction();
        try {
            // Decrypted id
            $decrypted_inventory_id = Crypt::decrypt($request->input('inventory_id'));

            // Check if inventory record exists
            $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();
            if (!$inventory) {
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
                'image',
            );


            // *********************************** //
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $inventory, // the model to update
                $this->fillable_attr_inventorys->arrToUpdates(), // fields to update
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
                $inventory,
                $this->fillable_attr_inventorys->arrToUpdates(),
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

            // Update Category Child
            $inventory_product = InventoryProductModel::where('inventory_id', $inventory->inventory_id);
            if ($inventory_product) {
                $inventory_product->update([
                    'category' => $request->category,
                ]);
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
                    'user_action' => 'UPDATE_INVENTORY_PARENT',
                ],
                $result_update_logs_old_new,
                2,
                env("PATH_FILE_INVENTORY")
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
                'message' => 'Inventory records parent update successfully',
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
            'inventory_id' => 'required|string',
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
            $decrypted_inventory_id = Crypt::decrypt($request->inventory_id);
            $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();
            if (!$inventory) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $inventory->toArray();

            // Checking Id on other tbl if exist unset the the api
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($inventory->inventory_id, $this->fillable_attr_inventorys->arrModelWithId());
            // Check if 'is_exist' is 'yes' in the first element then cant delete
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                return response()->json([
                    'message' => 'Can\'t delete because this id exist on other table',
                    // 'inventory_id' => $request->inventory_id,

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
                    'user_action' => 'DELETE_INVENTORY_PARENT',
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

            // Delete the user
            if (!$inventory->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete inventory'], Response::HTTP_UNPROCESSABLE_ENTITY);
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

    public function show(Request $request, string $id)
    {
        $arr_inventory = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        $inventory = InventoryModel::where('inventory_id', Crypt::decrypt($id))->first();
        if (!$inventory) {
            return response()->json(
                [
                    'message' => 'Data not found',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        foreach ($this->fillable_attr_inventorys->getFillableAttributes() as $getFillableAttribute) {
            if ($getFillableAttribute == 'inventory_id') {
                $arr_inventory[$getFillableAttribute] = Crypt::encrypt($inventory->$getFillableAttribute);
            } else if (in_array($getFillableAttribute, $this->fillable_attr_inventory_children->arrToConvertToReadableDateTime())) {
                $carbon_date = Carbon::parse($inventory->$getFillableAttribute);
                $value = $carbon_date->format('F j, Y g:i a');
                $arr_inventory[$getFillableAttribute] = $value;
            } else {
                $arr_inventory[$getFillableAttribute] = $inventory->$getFillableAttribute;
            }
        }


        return response()->json(
            [
                'message' => "Successfully retrieve data",
                'data' => $arr_inventory,
            ],
            Response::HTTP_OK
        );
    }

    public function storeMultiple(Request $request)
    {
        // Initialize an array to store all created items
        $created_items = [];
        $eu_device = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if 'items' key exists in the request
        if (!$request->has('items') || empty($request['items'])) {
            return response()->json(['message' => 'Missing or empty items in the request'], Response::HTTP_BAD_REQUEST);
        }

        $arr_items_error_fields = $this->fillable_attr_inventorys->arrToStores();

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'items.*.name' => 'required|string|max:255',
            'items.*.category' => 'required|string|max:255',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,JPG|max:10240',
            'items.*.eu_device' => 'required|string',
        ]);

        // Add custom validation rule for unique combination of name and category
        $validator->after(function ($validator) use ($request, $arr_items_error_fields) {
            foreach ($request['items'] as $index => $user_input) {
                $exists = InventoryModel::where('name', $user_input['name'])
                    ->where('category', $user_input['category'])
                    ->exists();

                if ($exists) {
                    foreach ($arr_items_error_fields as $field) {
                        $validator->errors()->add("items.$index.$field", 'Already exists.');
                    }
                }
            }
        });

        // Check if validation fails
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $formattedErrors = [];

            foreach ($request['items'] as $index => $item) {
                $itemErrors = [];

                foreach ($arr_items_error_fields as $field) {
                    if (isset($errors["items.$index.$field"])) {
                        $itemErrors[$field] = array_map(function ($msg) use ($index) {
                            return preg_replace("/items\.$index\./", '', $msg);
                        }, $errors["items.$index.$field"]);
                    }
                }

                if (!empty($itemErrors)) {
                    $formattedErrors[$index] = $itemErrors;
                }
            }

            return response()->json(['message' => array_values($formattedErrors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            foreach ($request['items'] as $user_input) {
                // Validate eu_device
                $result_validate_eu_device = $this->helper->validateEuDevice($user_input['eu_device']);
                if ($result_validate_eu_device) {
                    DB::rollBack();
                    return $result_validate_eu_device;
                }

                // Handle image upload if it exists for the current item
                if (isset($user_input['image']) && $user_input['image']->isValid()) {

                    $file_name = $this->helper->handleUploadFile(
                        [
                            'custom_folder' => 'inventory',
                            'file_image' => $user_input['image'],
                            'image_actual_extension' => $user_input['image']->getClientOriginalExtension(),
                            'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                        ],
                        0,
                    );
                }

                // Create the InventoryModel instance with the selected attributes
                $result_to_create = $this->helper->arrStoreMultipleData(
                    $this->fillable_attr_inventorys->arrToStores(),
                    $user_input,
                    $file_name
                );
                $created = InventoryModel::create($result_to_create);
                if (!$created) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'message' => 'Failed to store Inventory Parent'
                        ],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // Update the unique I.D
                $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_inventorys->idToUpdate(), Str::uuid());
                if ($update_unique_id) {
                    DB::rollBack();
                    return $update_unique_id;
                }

                $created_items[] = $created;
                $eu_device = $user_input['eu_device'];
            }

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'STORE_MULTIPLE_INVENTORY_ITEMS_PARENT',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();
            return response()->json([
                'message' => 'Inventory records parent store successfully',
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateMultiple(Request $request)
    {
        $arr_existing_data = [];
        $changes_for_logs = [];
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if 'items' key exists in the request
        if (!$request->has('items') || empty($request['items'])) {
            return response()->json(['message' => 'Missing or empty items in the request'], Response::HTTP_BAD_REQUEST);
        }

        $arr_items_error_fields = $this->fillable_attr_inventorys->arrToUpdates();

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'items.*.inventory_id' => 'required|string',
            'items.*.name' => 'required|string|max:255',
            'items.*.category' => 'required|string|max:255',
            'items.*.eu_device' => 'required|string',
        ]);

        // Add custom validation rule for unique combination of name and category
        $validator->after(function ($validator) use ($request, $arr_items_error_fields) {
            foreach ($request['items'] as $index => $user_input) {
                $exists = InventoryModel::where('name', $user_input['name'])
                    ->where('category', $user_input['category'])
                    ->exists();

                if ($exists) {
                    foreach ($arr_items_error_fields as $field) {
                        $validator->errors()->add("items.$index.$field", 'Already exists.');
                    }
                }
            }
        });

        // Check if validation fails
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $formattedErrors = [];

            foreach ($request['items'] as $index => $item) {
                $itemErrors = [];

                foreach ($arr_items_error_fields as $field) {
                    if (isset($errors["items.$index.$field"])) {
                        $itemErrors[$field] = array_map(function ($msg) use ($index) {
                            return preg_replace("/items\.$index\./", '', $msg);
                        }, $errors["items.$index.$field"]);
                    }
                }

                if (!empty($itemErrors)) {
                    $formattedErrors[$index] = $itemErrors;
                }
            }

            return response()->json(['message' => array_values($formattedErrors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        DB::beginTransaction();
        try {
            // Input User
            foreach ($request['items'] as $user_input) {
                // Decrypted id
                $decrypted_inventory_id = Crypt::decrypt($user_input['inventory_id']);

                // Validate eu_device
                $result_validate_eu_device = $this->helper->validateEuDevice($user_input['eu_device']);
                if ($result_validate_eu_device) {
                    DB::rollBack();
                    return $result_validate_eu_device;
                }

                // Check if inventory record exists
                $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();

                if (!$inventory) {
                    DB::rollBack();
                    return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
                }

                // Get the changes of the fields
                $result_changes_item_for_logs = $this->helper->updateLogsOldNew($inventory, $this->fillable_attr_inventorys->arrToUpdates(), $user_input, $file_name != '' ? $file_name : '');
                $changes_for_logs[] = [
                    'inventory_id' => Crypt::decrypt($user_input['inventory_id']),
                    'fields' => $result_changes_item_for_logs,
                ];

                // Update Multiple Data
                $result_update_multi_data = $this->helper->arrUpdateMultipleData($inventory, $this->fillable_attr_inventorys->arrToUpdates(), $user_input, $file_name != '' ? $file_name : '');
                if ($result_update_multi_data) {
                    DB::rollBack();
                    return $result_update_multi_data;
                }

                // Update Category Child
                $inventory_product = InventoryProductModel::where('inventory_id', $inventory->inventory_id)->update([
                    'category' => $inventory->category,
                ]);

                if (!$inventory_product) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to update inventory child category'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $eu_device = $user_input['eu_device'];
            }

            // Check if theres Changes Logs
            $changesCheckResponse = $this->helper->checkIfTheresChangesLogs($changes_for_logs);
            if ($changesCheckResponse) {
                DB::rollBack();
                return $changesCheckResponse;
            }

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_MULTIPLE_INVENTORY_ITEMS_PARENT',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();
            return response()->json([
                'message' => 'Inventory records parent update successfully',
                // 'log_message' => $log_result,
                'exist_data' => $arr_existing_data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyMultiple(Request $request)
    {
        $arr_log_details = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if 'items' key exists in the request
        if (!$request->has('items') || empty($request['items'])) {
            return response()->json(['message' => 'Missing or empty items in the request'], Response::HTTP_BAD_REQUEST);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'items.*.inventory_id' => 'required|string',
            'items.*.eu_device' => 'required|string',
        ]);

        $arr_items_error_fields = $this->fillable_attr_inventorys->arrToDeletes();

        // Check if validation fails
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $formattedErrors = [];

            foreach ($request['items'] as $index => $item) {
                $itemErrors = [];

                foreach ($arr_items_error_fields as $field) {
                    if (isset($errors["items.$index.$field"])) {
                        $itemErrors[$field] = array_map(function ($msg) use ($index) {
                            return preg_replace("/items\.$index\./", '', $msg);
                        }, $errors["items.$index.$field"]);
                    }
                }

                if (!empty($itemErrors)) {
                    $formattedErrors[$index] = $itemErrors;
                }
            }

            return response()->json(['message' => array_values($formattedErrors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            foreach ($request['items'] as $user_input) {
                // Decrypted id
                $decrypted_inventory_id = Crypt::decrypt($user_input['inventory_id']);

                // Validate eu_device
                $result_validate_eu_device = $this->helper->validateEuDevice($user_input['eu_device']);
                if ($result_validate_eu_device) {
                    return $result_validate_eu_device;
                }

                // Check if inventory record exists
                $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();
                if (!$inventory) {
                    return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
                }

                // Checking Id on other tbl if exist unset the api
                $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($inventory->inventory_id, $this->fillable_attr_inventorys->arrModelWithId());

                // Check if 'is_exist' is 'yes' in the first element and then unset it
                if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                    return response()->json([
                        'message' => 'Can\'t delete because this id exist on other table',
                        'inventory_id' => $user_input['inventory_id'],
                    ], Response::HTTP_NOT_FOUND);
                }

                // Get details to log
                $log_details = [];
                foreach ($this->fillable_attr_inventorys->getFillableAttributes() as $fillable_attr_inventorys) {
                    $log_details[$fillable_attr_inventorys] = $inventory->$fillable_attr_inventorys;
                }
                $arr_log_details[] = $log_details;

                // Delete the inventory
                if (!$inventory->delete()) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to delete inventory'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $eu_device = $user_input['eu_device'];
            }

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'DELETE_MULTIPLE_INVENTORY_ITEMS_PARENT',
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
