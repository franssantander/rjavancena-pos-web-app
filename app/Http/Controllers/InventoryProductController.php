<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PurchaseModel;
use App\Models\InventoryModel;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use App\Models\InventoryProductLostModel;
use Illuminate\Support\Facades\Validator;
use App\Models\InventoryProductFoundModel;
use App\Models\InventoryProductRestockModel;
use App\Models\InventoryProductLostImageModel;
use Symfony\Component\HttpFoundation\Response;
use App\Models\InventoryProductFoundImageModel;
use App\Models\InventoryProductRestockImageModel;

class InventoryProductController extends Controller
{

    protected  $helper, $fillable_attr_inventory_children, $fillable_attr_product_lost, $fillable_attr_product_restock, $fillable_attr_product_found, $fillable_attr_product_lost_image;

    public function __construct(
        Helper $helper,
        InventoryProductModel $fillable_attr_inventory_children,
        InventoryProductLostModel $fillable_attr_product_lost,
        InventoryProductRestockModel $fillable_attr_product_restock,
        InventoryProductFoundModel $fillable_attr_product_found,
        InventoryProductLostImageModel $fillable_attr_product_lost_image

    ) {
        $this->helper = $helper;
        $this->fillable_attr_inventory_children = $fillable_attr_inventory_children;
        $this->fillable_attr_product_lost = $fillable_attr_product_lost;
        $this->fillable_attr_product_restock = $fillable_attr_product_restock;
        $this->fillable_attr_product_found = $fillable_attr_product_found;
        $this->fillable_attr_product_lost_image = $fillable_attr_product_lost_image;
    }

    public function index(Request $request)
    {
        $crud_settings = $this->fillable_attr_inventory_children->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_inventory_children->getApiRelativeSettings();
        $view_settings = $this->fillable_attr_inventory_children->getViewRowTable();
        $arr_inventory_item = [];
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

        $inventory_products = InventoryProductModel::orderBy('created_at', 'desc')->get();

        // Fetch paginated inventory items for the current page
        // $inventory_products = InventoryProductModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
        //     ->where(function ($query) use ($request) {
        //         $search = $request->query('search', '');
        //         $query->where('name', 'LIKE', '%' . $search . '%');
        //              ->orWhere('item_code', 'LIKE', '%' . $search . '%') // Search in another column
        //              ->orWhere('yet_another_column', 'LIKE', '%' . $search . '%'); // Search in a third column
        //     })
        //     ->paginate($request->query('limit', 10), ['*'], 'page', $request->query('page', 1)); // Correct pagination

        foreach ($inventory_products as $inventory_product) {
            foreach ($this->fillable_attr_inventory_children->getFillableAttributes() as $getFillableAttribute) {
                if ($getFillableAttribute == 'inventory_product_id') {
                    $arr_inventory_item[$getFillableAttribute] = Crypt::encrypt($inventory_product->$getFillableAttribute);
                } else if ($getFillableAttribute == 'inventory_id') {
                    $arr_inventory_item[$getFillableAttribute] = Crypt::encrypt($inventory_product->$getFillableAttribute);
                } else if ($getFillableAttribute == 'image') {
                    $arr_inventory_item[$getFillableAttribute] = $inventory_product->$getFillableAttribute ? env("PATH_FILE_INVENTORY_PRODUCT") . $inventory_product->$getFillableAttribute : null;
                } elseif (in_array($getFillableAttribute, $this->fillable_attr_inventory_children->arrToConvertToReadableDateTime())) {
                    $arr_inventory_item[$getFillableAttribute] = $this->helper->convertReadableTimeDate($inventory_product->$getFillableAttribute);
                } else {
                    $arr_inventory_item[$getFillableAttribute] = $inventory_product->$getFillableAttribute;
                }

                // Get all category and put in filter
                if ($getFillableAttribute == 'category') {
                    if (!in_array($inventory_product->$getFillableAttribute, $arr_filter)) {
                        $arr_filter[] = $inventory_product->$getFillableAttribute;
                    }
                }
            }

            // Count how many items have been sold
            $sold_count = PurchaseModel::where('inventory_id', $inventory_product->inventory_id)
                ->where('inventory_product_id', $inventory_product->inventory_product_id)
                ->where('status', 'PAID')
                ->count();

            // Assign the sold count to the inventory item array
            $arr_inventory_item['sold'] = $sold_count;


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
                foreach ($this->fillable_attr_inventory_children->arrDetailsProductShow() as $arrDetailsProductShow) {
                    $label = '';
                    $type = '';

                    // Determine label and type based on attribute
                    switch ($arrDetailsProductShow) {
                        case 'name':
                            $label = 'Product ' . $this->helper->transformColumnName($arrDetailsProductShow);
                            $type = 'input';
                            break;
                        case 'refundable':
                            $label = 'Can be refunded?';
                            $type = 'select';
                            break;
                        case 'image':
                            $label = 'Image'; // Assuming you want a label for image
                            $type = 'file';
                            break;
                        default:
                            $label = $this->helper->transformColumnName($arrDetailsProductShow);
                            $type = 'input';
                            break;
                    }

                    // Populate details for the attribute
                    $action['details'][] = [
                        'label' => $label,
                        'value' => $inventory_product->{$arrDetailsProductShow} ?? null,
                        'type' => $type,
                    ];
                }
            }
            // ***************************** //

            // Add view on row item
            $arr_inventory_item['view'] = [[
                'url' => $view_settings['url'] . $arr_inventory_item['inventory_product_id'],
                'method' => $view_settings['method']
            ]];

            // Collect each inventory item
            $all_inventory_items[] = $arr_inventory_item;
        }

        // Final response structure
        $response = [
            'inventory_product' => $all_inventory_items,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_inventory_children->getFillableAttributes()),
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
            //     'count' => $inventory_products->count(),
            //     'has_page' => $inventory_products->hasPages(),
            //     'has_more_pages' => $inventory_products->hasMorePages(),
            //     'current_page' => $inventory_products->currentPage(),
            //     'last_page' => $inventory_products->lastPage(),
            //     'per_page' => $inventory_products->perPage(),
            //     'next_page_url' => $inventory_products->nextPageUrl(),
            //     'previous_page_url' => $inventory_products->previousPageUrl(),
            // ],
        ];

        return response()->json(
            [
                'message' => "Successfully retrieve data",
                'data' => $response
            ],
            Response::HTTP_OK
        );
    }

    public function showLost(Request $request, string $id)
    {
        $crud_settings = $this->fillable_attr_product_lost->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_product_lost->getApiRelativeSettings();
        $view_settings = $this->fillable_attr_product_lost->getViewRowTable();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $inventory_product_losts = InventoryProductLostModel::orderBy('created_at', 'desc')
        //     ->where('inventory_product_id', Crypt::decrypt($id))
        //     ->get();

        // Fetch paginated inventory items for the current page
        $inventory_product_losts = InventoryModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->where('inventory_product_id', Crypt::decrypt($id))
            ->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('remarks', 'LIKE', '%' . $search . '%');
                // ->orWhere('another_column', 'LIKE', '%' . $search . '%') // Search in another column
                // ->orWhere('yet_another_column', 'LIKE', '%' . $search . '%'); // Search in a third column
            })
            ->paginate($request->query('limit', 10), ['*'], 'page', $request->query('page', 1)); // Correct pagination

        // Get all data
        foreach ($inventory_product_losts as $inventory_product_lost) {
            // Store on array the specific data
            foreach ($this->fillable_attr_product_lost->getFillableAttributes() as $getFillableAttribute) {
                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_product_lost->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($inventory_product_lost->$getFillableAttribute);
                }
                // Image
                else if ($getFillableAttribute == 'image') {
                    $arr_container_datas[$getFillableAttribute] = $inventory_product_lost->$getFillableAttribute ? env('PATH_FILE_INVENTORY_PRODUCT_LOST') . $inventory_product_lost->$getFillableAttribute : null;
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_product_lost->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableTimeDate($inventory_product_lost->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $inventory_product_lost->$getFillableAttribute;
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

                // ***************************** //
                // Custom added details for view
                $decrypted_inventory_product_id = Crypt::decrypt($arr_container_datas['inventory_product_id']);
                if ($action['button_name'] == 'View') {
                    $inventory_product_losts = InventoryProductLostModel::where('inventory_product_id', $decrypted_inventory_product_id)
                        ->get();

                    foreach ($inventory_product_losts as $inventory_product_lost) {
                        $inventory_product_lost_images = InventoryProductLostImageModel::where(
                            'inventory_product_lost_id',
                            $inventory_product_lost->inventory_product_lost_id
                        )->get();

                        foreach ($inventory_product_lost_images as $inventory_product_lost_image) {
                            // Add the detail to the action array
                            $action['details']['image'][] = [
                                'label' => 'Image',
                                'type' => 'file',
                                'value' => null,

                                'inventory_product_lost_image_id' => Crypt::encrypt($inventory_product_lost_image->inventory_product_lost_image_id),
                                'image' =>  env('PATH_FILE_INVENTORY_PRODUCT_LOST_IMAGE') . $inventory_product_lost_image->image,

                                'url_update' => 'inventory/product/lost/image/update',
                                'url_delete' => 'inventory/product/lost/image/delete',
                                'payload_update' => ['inventory_product_lost_image_id', 'inventory_product_lost_id'],
                                'payload_delete' => ['inventory_product_lost_image_id', 'inventory_product_lost_id'],
                            ];
                        }
                    }

                    // Add the detail to the action array
                    $action['details']['info'][] = [
                        'label' => 'Remarks',
                        'type' => 'textarea',
                        'value' => $arr_container_datas['remarks']
                    ];
                }
                // ***************************** //

                if ($action['button_name'] == 'Add image') {
                    // Populate details for each attribute
                    foreach ($this->fillable_attr_product_lost_image->arrFieldsStore() as $key => $arrDetails) {

                        // Initialize the detail array
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? 'input',
                            'value' => null,
                        ];

                        // Only add 'option' if it exists and is an array
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        // Add the detail to the action array
                        $action['details'][] = $detail;
                    }
                }

                if ($action['button_name'] == 'Edit') {
                    // Populate details for each remaining attribute
                    foreach ($this->fillable_attr_product_lost->arrFieldsUpdate() as $key => $arrFieldsUpdate) {
                        // Initialize the detail array
                        $detail = [
                            'label' => $arrFieldsUpdate['label'],
                            'type' => $arrFieldsUpdate['type'] ?? 'input',
                            'value' => $key === 'image' ? null : ($arrFieldsUpdate['value'] ?? ($arr_container_datas[$key] ?? null)),
                        ];

                        // Only add 'option' if it exists and is an array
                        if (isset($arrFieldsUpdate['option']) && is_array($arrFieldsUpdate['option'])) {
                            $detail['option'] = $arrFieldsUpdate['option'];
                        }

                        // Add the detail to the action array
                        $action['details'][] = $detail;
                    }
                }

                $action['inventory_product_lost_id'] = $arr_container_datas['inventory_product_lost_id'] ?? null;
                $action['inventory_product_id'] = $arr_container_datas['inventory_product_id'] ?? null;
            }

            // Add view on row item
            $arr_container_datas['view'] = [[
                'url' => $view_settings['url'] . $arr_container_datas['inventory_product_id'],
                'method' => $view_settings['method'],
            ]];

            // ***************************** //

            // Unset the fields not to use
            foreach ($this->fillable_attr_product_lost->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_container_datas[$arrFieldsToUnsetTable]);
            }

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Final response structure
        $response = [
            'inventory_product_lost' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_product_lost->arrFieldsColumnHeader()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $inventory_product_losts->count(),
                'has_page' => $inventory_product_losts->hasPages(),
                'has_more_pages' => $inventory_product_losts->hasMorePages(),
                'current_page' => $inventory_product_losts->currentPage(),
                'last_page' => $inventory_product_losts->lastPage(),
                'per_page' => $inventory_product_losts->perPage(),
                'next_page_url' => $inventory_product_losts->nextPageUrl(),
                'previous_page_url' => $inventory_product_losts->previousPageUrl(),
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
            foreach ($this->fillable_attr_product_lost->arrFieldsStore() as $key => $arrDetails) {
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

    public function showFound(Request $request, string $id)
    {
        $crud_settings = $this->fillable_attr_product_found->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_product_found->getApiRelativeSettings();
        $view_settings = $this->fillable_attr_product_restock->getViewRowTable();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $inventory_product_founds = InventoryProductFoundModel::orderBy('created_at', 'desc')
        // ->where('inventory_product_id', operator: Crypt::decrypt($id))
        // ->get();

        // Define the number of items per page
        $per_page = 10; // Set how many notifications you want per page

        // Fetch paginated inventory items foperator: or the current page
        $inventory_product_founds = InventoryModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->where('inventory_product_id', operator: Crypt::decrypt($id))
            ->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('remarks', 'LIKE', '%' . $search . '%');
                // ->orWhere('another_column', 'LIKE', '%' . $search . '%') // Search in another column
                // ->orWhere('yet_another_column', 'LIKE', '%' . $search . '%'); // Search in a third column
            })
            ->paginate($per_page, ['*'], 'page', $request->query('page', 1)); // Correct pagination

        // Get all data
        foreach ($inventory_product_founds as $inventory_product_founds) {
            // Store on array the specific data
            foreach ($this->fillable_attr_product_found->getFillableAttributes() as $getFillableAttribute) {
                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_product_found->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($inventory_product_founds->$getFillableAttribute);
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_product_found->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableTimeDate($inventory_product_founds->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $inventory_product_founds->$getFillableAttribute;
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

                // ***************************** //
                // Custom added details for view
                $decrypted_inventory_product_id = Crypt::decrypt($arr_container_datas['inventory_product_id']);
                if ($action['button_name'] == 'View') {
                    $inventory_product_founds = InventoryProductFoundModel::where('inventory_product_id', $decrypted_inventory_product_id)
                        ->get();

                    foreach ($inventory_product_founds as $inventory_product_found) {
                        $inventory_product_found_images = InventoryProductFoundImageModel::where(
                            'inventory_product_found_id',
                            $inventory_product_found->inventory_product_found_id
                        )->get();

                        foreach ($inventory_product_found_images as $inventory_product_found_image) {
                            // Add the detail to the action array
                            $action['details']['image'][] = [
                                'label' => 'Image',
                                'type' => 'file',
                                'value' => null,

                                'inventory_product_found_image_id' => Crypt::encrypt($inventory_product_found_image->inventory_product_found_image_id),
                                'image' =>  env('PATH_FILE_INVENTORY_PRODUCT_FOUND_IMAGE') . $inventory_product_found_image->image,

                                'url_update' => 'inventory/product/found/image/update',
                                'url_delete' => 'inventory/product/found/image/delete',
                                'payload_update' => ['inventory_product_found_image_id', 'inventory_product_found_id'],
                                'payload_delete' => ['inventory_product_found_image_id', 'inventory_product_found_id'],
                            ];
                        }
                    }

                    // Add the detail to the action array
                    $action['details']['info'][] = [
                        'label' => 'Remarks',
                        'type' => 'textarea',
                        'value' => $arr_container_datas['remarks']
                    ];
                }
                // ***************************** //

                if ($action['button_name'] == 'Add image') {
                    // Populate details for each attribute
                    foreach ($this->fillable_attr_product_lost_image->arrFieldsStore() as $key => $arrDetails) {

                        // Initialize the detail array
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? 'input',
                            'value' => null,
                        ];

                        // Only add 'option' if it exists and is an array
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        // Add the detail to the action array
                        $action['details'][] = $detail;
                    }
                }

                if ($action['button_name'] == 'Edit') {
                    // Populate details for each remaining attribute
                    foreach ($this->fillable_attr_product_found->arrFieldsUpdate() as $key => $arrFieldsUpdate) {
                        // Initialize the detail array
                        $detail = [
                            'label' => $arrFieldsUpdate['label'],
                            'type' => $arrFieldsUpdate['type'] ?? 'input',
                            'value' => $key === 'image' ? null : ($arrFieldsUpdate['value'] ?? ($arr_container_datas[$key] ?? null)),
                        ];

                        // Only add 'option' if it exists and is an array
                        if (isset($arrFieldsUpdate['option']) && is_array($arrFieldsUpdate['option'])) {
                            $detail['option'] = $arrFieldsUpdate['option'];
                        }

                        // Add the detail to the action array
                        $action['details'][] = $detail;
                    }
                }

                $action['inventory_product_found_id'] = $arr_container_datas['inventory_product_found_id'] ?? null;
                $action['inventory_product_id'] = $arr_container_datas['inventory_product_id'] ?? null;
            }

            // Add view on row item
            $arr_container_datas['view'] = [[
                'url' => $view_settings['url'] . $arr_container_datas['inventory_product_id'],
                'method' => $view_settings['method'],
            ]];

            // ***************************** //

            // Unset the fields not to use
            foreach ($this->fillable_attr_product_found->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_container_datas[$arrFieldsToUnsetTable]);
            }

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Final response structure
        $response = [
            'inventory_product_found' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_product_found->arrFieldsColumnHeader()),
            'buttons' => $this->helper->formatApi(
                $relative_settings['prefix'],
                $relative_settings['payload'],
                $relative_settings['method'],
                $relative_settings['button_name'],
                $relative_settings['icon'],
                $relative_settings['container']
            ),
            'pagination' => [
                'count' => $inventory_product_founds->count(),
                'has_page' => $inventory_product_founds->hasPages(),
                'has_more_pages' => $inventory_product_founds->hasMorePages(),
                'current_page' => $inventory_product_founds->currentPage(),
                'last_page' => $inventory_product_founds->lastPage(),
                'per_page' => $inventory_product_founds->perPage(),
                'next_page_url' => $inventory_product_founds->nextPageUrl(),
                'previous_page_url' => $inventory_product_founds->previousPageUrl(),
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
            foreach ($this->fillable_attr_product_found->arrFieldsStore() as $key => $arrDetails) {
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

    public function showRestock(Request $request, string $id)
    {
        $crud_settings = $this->fillable_attr_product_restock->getApiCrudSettings();
        $relative_settings = $this->fillable_attr_product_restock->getApiRelativeSettings();
        $view_settings = $this->fillable_attr_product_restock->getViewRowTable();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $inventory_product_restocks = InventoryProductRestockModel::orderBy('created_at', 'desc')
        //     ->where('inventory_product_id', Crypt::decrypt($id))
        //     ->get();

        // Define the number of items per page
        $per_page = 10; // Set how many notifications you want per page

        // Fetch paginated inventory items for the current page
        $inventory_product_restocks = InventoryModel::orderBy('created_at', 'desc') // Ensure results are ordered for consistent pagination
            ->where('inventory_product_id', Crypt::decrypt($id))
            ->where(function ($query) use ($request) {
                $search = $request->query('search', '');
                $query->where('remarks', 'LIKE', '%' . $search . '%');
                // ->orWhere('another_column', 'LIKE', '%' . $search . '%') // Search in another column
                // ->orWhere('yet_another_column', 'LIKE', '%' . $search . '%'); // Search in a third column
            })
            ->paginate($per_page, ['*'], 'page', $request->query('page', 1)); // Correct pagination


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

                // ***************************** //
                // Custom added details for view
                $decrypted_inventory_product_id = Crypt::decrypt($arr_container_datas['inventory_product_id']);
                if ($action['button_name'] == 'View') {
                    $inventory_product_restocks = InventoryProductRestockModel::where('inventory_product_id', $decrypted_inventory_product_id)
                        ->get();

                    foreach ($inventory_product_restocks as $inventory_product_restock) {
                        $inventory_product_restock_images = InventoryProductRestockImageModel::where(
                            'inventory_product_restock_id',
                            $inventory_product_restock->inventory_product_restock_id
                        )->get();

                        foreach ($inventory_product_restock_images as $inventory_product_restock_image) {
                            // Add the detail to the action array
                            $action['details']['image'][] = [
                                'label' => 'Image',
                                'type' => 'file',
                                'value' => null,

                                'inventory_product_restock_image' => Crypt::encrypt($inventory_product_restock_image->inventory_product_restock_image_id),
                                'image' =>  env('PATH_FILE_INVENTORY_PRODUCT_RESTOCK_IMAGE') . $inventory_product_restock_image->image,

                                'url_update' => 'inventory/product/restock/image/update',
                                'url_delete' => 'inventory/product/restock/image/delete',
                                'payload_update' => ['inventory_product_restock_image', 'inventory_product_restock_id'],
                                'payload_delete' => ['inventory_product_restock_image', 'inventory_product_restock_id'],
                            ];
                        }
                    }

                    // Add the detail to the action array
                    $action['details']['info'][] = [
                        'label' => 'Remarks',
                        'type' => 'textarea',
                        'value' => $arr_container_datas['remarks']
                    ];
                }
                // ***************************** //


                if ($action['button_name'] == 'Add image') {
                    // Populate details for each attribute
                    foreach ($this->fillable_attr_product_lost_image->arrFieldsStore() as $key => $arrDetails) {

                        // Initialize the detail array
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? 'input',
                            'value' => null,
                        ];

                        // Only add 'option' if it exists and is an array
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        // Add the detail to the action array
                        $action['details'][] = $detail;
                    }
                }


                if ($action['button_name'] == 'Edit') {
                    // Populate details for each remaining attribute
                    foreach ($this->fillable_attr_product_restock->arrFieldsUpdate() as $key => $arrFieldsUpdate) {
                        // Initialize the detail array
                        $detail = [
                            'label' => $arrFieldsUpdate['label'],
                            'type' => $arrFieldsUpdate['type'] ?? 'input',
                            'value' => $key === 'image' ? null : ($arrFieldsUpdate['value'] ?? ($arr_container_datas[$key] ?? null)),
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

            // Add view on row item
            $arr_container_datas['view'] = [[
                'url' => $view_settings['url'] . $arr_container_datas['inventory_product_id'],
                'method' => $view_settings['method'],
            ]];

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

    public function show(Request $request, string $id)
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

        $inventory_product = InventoryProductModel::where('inventory_product_id', Crypt::decrypt($id))->first();
        if (!$inventory_product) {
            return response()->json(
                [
                    'message' => 'Data not found',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        foreach ($this->fillable_attr_inventory_children->getFillableAttributes() as $getFillableAttribute) {
            if ($getFillableAttribute == 'inventory_product_id') {
                $arr_inventory_item[$getFillableAttribute] = Crypt::encrypt($inventory_product->$getFillableAttribute);
            } else if ($getFillableAttribute == 'inventory_id') {
                $arr_inventory_item[$getFillableAttribute] = Crypt::encrypt($inventory_product->$getFillableAttribute);
            } elseif (in_array($getFillableAttribute, $this->fillable_attr_inventory_children->arrToConvertToReadableDateTime())) {
                $arr_inventory_item[$getFillableAttribute] = $this->helper->convertReadableTimeDate($inventory_product->$getFillableAttribute);
            } else {
                $arr_inventory_item[$getFillableAttribute] = $inventory_product->$getFillableAttribute;
            }
        }

        $total_sales = PurchaseModel::where('inventory_id', $inventory_product->inventory_id)
            ->where('inventory_product_id', $inventory_product->inventory_product_id)
            ->where('status', 'PAID')
            ->count();
        $arr_inventory_item['sells'] = $total_sales;


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

            // Populate details for each attribute
            foreach ($this->fillable_attr_inventory_children->arrDetailsProductShow() as $arrDetailsProductShow) {
                $action['details'][] = [
                    'label' => $arrDetailsProductShow  == 'name' ? 'Product' . " " . $this->helper->transformColumnName($arrDetailsProductShow) : $this->helper->transformColumnName($arrDetailsProductShow),
                    'value' => $inventory_product->$arrDetailsProductShow ?? null,
                    'type' => 'input',
                ];
            }
        }
        // ***************************** //

        // Add view on row item
        $arr_inventory_item['view'] = [[
            'url' => $view_settings['url'] . $arr_inventory_item['inventory_product_id'],
            'method' => $view_settings['method']
        ]];

        // Collect each inventory item
        $all_inventory_items[] = $arr_inventory_item;

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
            // 'filter' => $filter
        ];

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
        $array_merge_insert = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'inventory_id' => 'required|string',
            'item_code' => 'required|string|max:500',
            'image' =>  'nullable|image|mimes:jpeg,png,jpg,JPG|max:10240',
            'description' => 'nullable',
            'refundable' => 'required|string|max:3',
            'name' => 'required|string|max:500',
            'retail_price' => 'required|numeric',
            'discounted_price' => 'numeric',
            'stocks' => 'required|numeric',
            'supplier_name' => 'nullable',
            'design' => 'nullable|string|max:500',
            'size' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:500',
            'unit_supplier_price' => 'nullable|numeric',
            'eu_device' => 'required|string',

            'low_stocks' => 'required|numeric',
            'moderate_stocks' => 'required|numeric',

            'item_expiration_at' => 'nullable|string|max:255',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            DB::rollBack();
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        $inventoryItemCode = InventoryProductModel::where('item_code', $request->input('item_code'))->first();
        if ($inventoryItemCode) {
            return response()->json(['message' => "This item code is already used by another product."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $decrypted_inventory_id = Crypt::decrypt($request->input('inventory_id'));

            // Retrieve the inventory record
            $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();
            // Check if inventory record exists
            if (!$inventory) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Parent inventory ID not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Handle image upload 
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory-product',
                        'file_image' => $request->file('image'),
                        'image_actual_extension' => $request->file('image')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    0,
                );
            }

            // Append column and value to store
            $array_merge_insert = $request->all();
            $array_merge_insert['category'] = $inventory->category;

            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $array_merge_insert,
                $file_name,
                'image',
            );

            // *********************************** //
            // Start Store
            // Format the content
            $result_to_create = $this->helper->arrStoreMultipleData(
                $this->fillable_attr_inventory_children->arrToStores(),
                $result_merge_data,
                $this->fillable_attr_inventory_children->arrPayloadIdsToDecrypt(),
                [],
                [],
                [],
            );

            // Create 
            $created = InventoryProductModel::create($result_to_create);
            if (!$created) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store user information'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_inventory_children->idToUpdate(), Str::uuid());
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
                    'user_action' => 'STORE_INVENTORY_ITEM_CHILD',
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
                'message' => 'Inventory records child stored successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request)
    {
        $changes_for_log = [];
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'inventory_product_id' => 'required|string',
            'item_code' => 'required|string|max:255',
            'image' =>  'nullable|image|mimes:jpeg,png,jpg,JPG|max:10240',
            'description' => 'nullable',
            'refundable' => 'required|string|max:3',
            'name' => 'required|string|max:500',
            'retail_price' => 'required|numeric',
            'discounted_price' => 'numeric',
            'stocks' => 'required|numeric',
            'supplier_name' => 'nullable',
            'design' => 'nullable|string|max:500',
            'size' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:500',
            'unit_supplier_price' => 'nullable|numeric',
            'eu_device' => 'required|string',

            'low_stocks' => 'required|numeric',
            'moderate_stocks' => 'required|numeric',

            'item_expiration_at' => 'nullable|string|max:255',
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

        // Decrypted id
        $decrypted_inventory_product_id = Crypt::decrypt($request->input('inventory_product_id'));

        $inventory_item_code = InventoryProductModel::where('item_code', $request->input('item_code'))
            ->where('inventory_product_id', '!=', $decrypted_inventory_product_id)
            ->exists();
        if ($inventory_item_code) {
            return response()->json(['message' => "This item code is already used by another product."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        DB::beginTransaction();
        try {
            // Check if inventory record exists
            $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)
                ->first();
            if (!$inventory_product) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'inventory-product',
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
                $inventory_product, // the model to update
                $this->fillable_attr_inventory_children->arrToUpdates(), // fields to update
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
                $inventory_product,
                $this->fillable_attr_inventory_children->arrToUpdates(),
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
                    'user_action' => 'UPDATE_INVENTORY_ITEM_CHILD',
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
                'message' => 'Successfully update inventory child',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

        // Check if 'inventory_product_id' and 'eu_device' are provided
        $validator = Validator::make($request->all(), [
            'inventory_product_id' => 'required|string',
            'inventory_id' => 'required|string',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate 'eu_device'
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        DB::beginTransaction();

        try {
            $decrypted_inventory_product_id = Crypt::decrypt($request->inventory_product_id);
            $decrypted_inventory_id = Crypt::decrypt($request->inventory_id);

            $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();
            if (!$inventory) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $inventory_product = InventoryProductModel::where('inventory_id', $decrypted_inventory_id)
                ->where('inventory_product_id', $decrypted_inventory_product_id)->first();
            if (!$inventory_product) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $inventory->toArray();

            // Checking Id on other tbl if exist unset the the api
            $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($inventory_product->inventory_product_id, $this->fillable_attr_inventory_children->arrModelWithId());

            // Check if 'is_exist' is 'yes' in the first element and then unset it
            if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                return response()->json(['message' => 'Can\'t delete because this id exist on other table'], Response::HTTP_NOT_FOUND);
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
                    'user_action' => 'DELETE_INVENTORY_ITEM_CHILD',
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
            if (!$inventory_product->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete inventory'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::commit();

            return response()->json([
                'message' => 'Successfully deleted inventory record',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeMultiple(Request $request)
    {
        $file_name = '';
        // Initialize an array to store all created items
        $created_items = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if 'items' key exists in the request
        if (!$request->has('items')) {
            return response()->json(
                ['message' => 'Missing items in the request'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $arr_items_error_fields = $this->fillable_attr_inventory_children->arrToStores();

        $validator = Validator::make($request->all(), [
            'items.*.inventory_id' => 'required|string',
            'items.*.item_code' => 'required|string|max:255',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,JPG|max:10240',
            'items.*.description' => 'nullable',
            'items.*.refundable' => 'required|string|max:3',
            'items.*.name' => 'required|string|max:500',
            'items.*.category' => 'required|string|max:500',
            'items.*.retail_price' => 'required|numeric',
            'items.*.discounted_price' => 'nullable|numeric',
            'items.*.stocks' => 'required|numeric',
            'items.*.supplier_name' => 'nullable',
            'items.*.design' => 'nullable|string|max:500',
            'items.*.size' => 'nullable|string|max:500',
            'items.*.color' => 'nullable|string|max:500',
            'items.*.unit_supplier_price' => 'nullable|numeric',
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


        DB::beginTransaction();
        try {
            foreach ($request['items'] as $user_input) {
                $inventoryItemCode = InventoryProductModel::where('item_code', $user_input['eu_device'])->first();
                if ($inventoryItemCode) {
                    return response()->json(['message' => "This item code is already used by another product."], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $decrypted_inventory_id = Crypt::decrypt($user_input['inventory_id']);

                // Validate eu_device
                $result_validate_eu_device = $this->helper->validateEuDevice($user_input['eu_device']);
                if ($result_validate_eu_device) {
                    return $result_validate_eu_device;
                }

                // Retrieve the inventory record
                $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();
                // Check if inventory record exists
                if (!$inventory) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Parent inventory ID not found',
                    ], Response::HTTP_NOT_FOUND);
                }

                // Handle image upload if it exists for the current item
                if (isset($user_input['image']) && $user_input['image']->isValid()) {
                    // Handle image upload 
                    $file_name = $this->helper->handleUploadFile(
                        [
                            'custom_folder' => 'inventory-product',
                            'file_image' => $user_input['image'],
                            'image_actual_extension' => $user_input['image']->getClientOriginalExtension(),
                            'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                        ],
                        0,
                    );
                }

                // Create the InventoryProductModel instance with the selected attributes
                $result_to_create = $this->helper->arrStoreMultipleData(
                    $this->fillable_attr_inventory_children->arrToStores(),
                    $user_input,
                    $file_name != '' ? $file_name : ''
                );

                // Added column and value to store
                $result_append_data = $this->helper->addColumnAndValue($result_to_create, $this->fillable_attr_inventory_children->getArrFieldsToAppend(), $inventory);

                $created = InventoryProductModel::create($result_append_data);
                if (!$created) {
                    DB::rollBack();
                    return response()->json(
                        ['message' => 'Failed to store Inventory Child'],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // Update the unique ID
                $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_inventory_children->idToUpdate(), Str::uuid());
                if ($update_unique_id) {
                    DB::rollBack();
                    return $update_unique_id;
                }

                $created_items[] = $created;
                $eu_device = $user_input['eu_device'];
            }


            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'STORE_MULTIPLE_INVENTORY_CHILD_ITEMS',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();

            return response()->json([
                'message' => 'Inventory records child stored successfully',
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateMultiple(Request $request)
    {
        $changes_for_log = [];
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

        $arr_items_error_fields = $this->fillable_attr_inventory_children->arrToUpdates();

        $validator = Validator::make($request->all(), [
            'items.*.inventory_id' => 'required|string',
            'items.*.item_code' => 'required|string|max:255',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,JPG|max:10240',
            'items.*.description' => 'nullable',
            'items.*.refundable' => 'required|string|max:3',
            'items.*.name' => 'required|string|max:500|unique:inventory_product_tbl,name',
            'items.*.retail_price' => 'required|numeric',
            'items.*.discounted_price' => 'nullable|numeric',
            'items.*.stocks' => 'required|numeric',
            'items.*.supplier_name' => 'nullable',
            'items.*.design' => 'nullable|string|max:500',
            'items.*.size' => 'nullable|string|max:500',
            'items.*.color' => 'nullable|string|max:500',
            'items.*.unit_supplier_price' => 'nullable|numeric',
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

        DB::beginTransaction();

        try {
            foreach ($request['items'] as $user_input) {
                $inventoryItemCode = InventoryProductModel::where('item_code', $user_input['eu_device'])->first();
                if ($inventoryItemCode) {
                    return response()->json(['message' => "This item code is already used by another product."], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                // Decrypted id
                $decrypted_inventory_product_id = Crypt::decrypt($user_input['inventory_product_id']);

                // Validate eu_device
                $result_validate_eu_device = $this->helper->validateEuDevice($user_input['eu_device']);
                if ($result_validate_eu_device) {
                    return $result_validate_eu_device;
                }

                $inventory = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)
                    ->first();
                if (!$inventory) {
                    DB::rollBack();
                    return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
                }

                // Handle image upload if it exists for the current item
                if (isset($user_input['image']) && $user_input['image']->isValid()) {
                    // Handle image upload 
                    $file_name = $this->helper->handleUploadFile(
                        [
                            'custom_folder' => 'inventory-product',
                            'file_image' => $user_input['image'],
                            'image_actual_extension' => $user_input['image']->getClientOriginalExtension(),
                            'image_actual_name_without_extension' => pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME),
                        ],
                        0,
                    );
                }

                // Get the changes of the fields
                $result_changes_item_for_logs = $this->helper->updateLogsOldNew($inventory, $this->fillable_attr_inventory_children->arrToUpdates(), $user_input, $file_name != '' ? $file_name : '');
                $changes_for_log[] = [
                    'inventory_product_id' => $user_input['inventory_product_id'],
                    'fields' => $result_changes_item_for_logs,
                ];

                // Update Multiple Data
                $result_update_multi_data = $this->helper->arrUpdateMultipleData($inventory, $this->fillable_attr_inventory_children->arrToUpdates(), $user_input, $file_name != '' ? $file_name : '');
                if ($result_update_multi_data) {
                    DB::rollBack();
                    return $result_update_multi_data;
                }

                $eu_device = $user_input['eu_device'];
            }

            // Check if there are changes for logs
            $result_changes_logs = $this->helper->checkIfTheresChangesLogs($changes_for_log);
            if ($result_changes_logs) {
                DB::rollBack();
                return $result_changes_logs;
            }

            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE_MULTIPLE_INVENTORY_CHILD_ITEMS',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();

            return response()->json([
                'message' => 'Successfully update inventory child',
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            'items.*.inventory_product_id' => 'required|string',
            'items.*.inventory_id' => 'required|string',
            'items.*.eu_device' => 'required|string',
        ]);

        $arr_items_error_fields = $this->fillable_attr_inventory_children->arrToDeletes();

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
                $decrypted_inventory_product_id = Crypt::decrypt($user_input['inventory_product_id']);
                $decrypted_inventory_id = Crypt::decrypt($user_input['inventory_id']);

                $inventory = InventoryModel::where('inventory_id', $decrypted_inventory_id)->first();
                if (!$inventory) {
                    return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
                }

                $inventory_product = InventoryProductModel::where('inventory_id', $decrypted_inventory_id)
                    ->where('inventory_product_id', $decrypted_inventory_product_id)->first();
                if (!$inventory_product) {
                    return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
                }


                // Validate eu_device
                $result_validate_eu_device = $this->helper->validateEuDevice($user_input['eu_device']);
                if ($result_validate_eu_device) {
                    return $result_validate_eu_device;
                }

                // Checking Id on other tbl if exist unset the api
                $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($inventory_product->inventory_product_id, $this->fillable_attr_inventory_children->arrModelWithId());

                // Check if 'is_exist' is 'yes' in the first element and then unset it
                if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
                    return response()->json(['message' => 'Can\'t delete because this id exist on other table'], Response::HTTP_NOT_FOUND);
                }

                // Get details to log
                $log_details = [];
                foreach ($this->fillable_attr_inventory_children->getFillableAttributes() as $getFillableAttributes) {
                    $log_details[$getFillableAttributes] = $inventory_product->$getFillableAttributes;
                }
                $arr_log_details[] = $log_details;


                // Delete the inventory
                if (!$inventory_product->delete()) {
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
                    'user_action' => 'DELETE_MULTIPLE_INVENTORY_CHILD_ITEMS',
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
