<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Helper\Helper;
use App\Models\AuthModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\InventoryModel;
use App\Models\NotificationModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    protected $helper, $fillable_attr_notification, $fillable_attr_auth;

    public function __construct(Helper $helper, NotificationModel $fillable_attr_notification, AuthModel $fillable_attr_auth,)
    {
        $this->helper = $helper;
        $this->fillable_attr_notification = $fillable_attr_notification;
        $this->fillable_attr_auth = $fillable_attr_auth;
    }

    public function getNotificationByUser(Request $request)
    {
        $arr_container_datas = [];
        $arr_container_action = [];
        $arr_all_data = [];
        $crud_settings = $this->fillable_attr_notification->getApiCrudSettings();

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
        $arr_container_action['actions'] = array_values($crud_action);

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Define the number of items per page
        $per_page = 5; // Set how many notifications you want per page

        // Fetch paginated notifications for the current page
        $notifications = NotificationModel::orderBy('id', 'desc') // Ensure results are ordered for consistent pagination
            ->paginate($per_page, ['*'], 'page', $request->query('page', default: 1)); // Pass the current page number explicitly

        foreach ($notifications as $notification) {
            // Store on array the specific data
            foreach ($this->fillable_attr_notification->getFillableAttributes() as $getFillableAttribute) {
                // fields to encrypt
                if (in_array($getFillableAttribute, $this->fillable_attr_notification->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($notification->$getFillableAttribute);
                } else if ($getFillableAttribute == 'name') {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->strToLowerCaseFirstUpperCase($notification->$getFillableAttribute);
                } else if ($getFillableAttribute == 'details') {
                    $details = json_decode($notification->$getFillableAttribute, true);

                    $arr_container_datas['name'] = $details['name'] . " " . "(" . $this->helper->strToLowerCaseFirstUpperCase(
                        str_replace(
                            '_',
                            ' ',
                            preg_replace('/_id-\d+$/', '', $arr_container_datas['name'])
                        )
                    ) . ")";
                    $arr_container_datas['page'] = $this->helper->strToLowerCaseFirstUpperCase(str_replace('_', ' ', preg_replace('/_id-\d+$/', '', $arr_container_datas['tbl_reference_id'])));

                    $arr_container_datas['url_update'] = $arr_container_action['actions'][0]['url'];
                    $arr_container_datas['url_view'] = Crypt::encrypt($details['inventory_id']);
                }
                // fields to convert date and time
                else if ($getFillableAttribute == 'created_at') {
                    $arr_container_datas['time'] = $this->helper->timeAgo($notification->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $notification->$getFillableAttribute;
                }
            }

            // Unset the fields not to use
            foreach ($this->fillable_attr_notification->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_container_datas[$arrFieldsToUnsetTable]);
            }

            // Data
            $arr_all_data[] = $arr_container_datas;
        }

        // Final response
        return response()->json(
            $arr_all_data,
            Response::HTTP_OK
        );
    }


    // This code display only the page and limit
    // public function getNotificationByUser(Request $request)
    // {
    //     $arr_container_datas = [];
    //     $arr_container_action = [];
    //     $arr_all_data = [];
    //     $crud_settings = $this->fillable_attr_notification->getApiCrudSettings();

    //     // Validation rules for query parameters 'limit' and 'page'
    //     $validator = Validator::make($request->query(), [
    //         'limit' => 'required|integer|min:1',
    //         'page' => 'required|integer|min:1',
    //     ]);

    //     // Check if validation fails
    //     if ($validator->fails()) {
    //         return response()->json(
    //             [
    //                 'message' => $validator->errors(),
    //             ],
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }

    //     // ***************************** //
    //     // Format Api
    //     $crud_action = $this->helper->formatApi(
    //         $crud_settings['prefix'],
    //         $crud_settings['payload'],
    //         $crud_settings['method'],
    //         $crud_settings['button_name'],
    //         $crud_settings['icon'],
    //         $crud_settings['container']
    //     );

    //     // Add the format Api Crud
    //     $arr_container_action['actions'] = array_values($crud_action);
    //     // ***************************** //

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     // Fetch paginated notifications using paginate
    //     $limit = (int) $request->query('limit', 10); // Default to 10 items per page if not provided
    //     $page = (int) $request->query('page', 1); // Default to page 1 if not provided

    //     $notifications = NotificationModel::where('user_id', $user->user_id)
    //         ->orderBy('id', 'desc') // Ensure results are ordered for consistent pagination
    //         ->paginate($limit, ['*'], 'page', $page);

    //     foreach ($notifications as $notification) {
    //         // Store on array the specific data
    //         foreach ($this->fillable_attr_notification->getFillableAttributes() as $getFillableAttribute) {
    //             // fields to encrypt
    //             if (in_array($getFillableAttribute, $this->fillable_attr_notification->arrToConvertIdsToEncrypted())) {
    //                 $arr_container_datas[$getFillableAttribute] = Crypt::encrypt($notification->$getFillableAttribute);
    //             } else if ($getFillableAttribute == 'name') {
    //                 $arr_container_datas[$getFillableAttribute] = $this->helper->strToLowerCaseFirstUpperCase($notification->$getFillableAttribute);
    //             } else if ($getFillableAttribute == 'details') {
    //                 $details = json_decode($notification->$getFillableAttribute, true);

    //                 $arr_container_datas['name'] = $details['name'] . " " . "(" . $this->helper->strToLowerCaseFirstUpperCase(
    //                     str_replace(
    //                         '_',
    //                         ' ',
    //                         preg_replace('/_id-\d+$/', '', $arr_container_datas['name'])
    //                     )
    //                 ) . ")";
    //                 $arr_container_datas['page'] = $this->helper->strToLowerCaseFirstUpperCase(str_replace('_', ' ', preg_replace('/_id-\d+$/', '', $arr_container_datas['tbl_reference_id'])));


    //                 $arr_container_datas['url_update'] = $arr_container_action['actions'][0]['url'];
    //                 $arr_container_datas['url_view'] = Crypt::encrypt($details['inventory_id']);
    //             }
    //             // fields to convert date and time
    //             else if ($getFillableAttribute == 'created_at') {
    //                 $arr_container_datas['time'] = $this->helper->timeAgo($notification->$getFillableAttribute);
    //             }
    //             // just declare
    //             else {
    //                 $arr_container_datas[$getFillableAttribute] = $notification->$getFillableAttribute;
    //             }
    //         }

    //         // Unset the fields not to use
    //         foreach ($this->fillable_attr_notification->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
    //             unset($arr_container_datas[$arrFieldsToUnsetTable]);
    //         }

    //         // Data
    //         $arr_all_data[] = $arr_container_datas;
    //     }

    //     $total = NotificationModel::where('user_id', $user->user_id)->count();

    //     // Final response
    //     $response = [
    //         'pagination' => [
    //             'count' => $notifications->count(),
    //             // 'cursor' => $notifications->cursor(), // Only applicable for cursor pagination
    //             'get_options' => $notifications->getOptions(), // Only applicable for cursor pagination
    //             'has_page' => $notifications->hasPages(),
    //             'has_more_pages' => $notifications->hasMorePages(),
    //             'current_page' => $notifications->currentPage(),
    //             'last_page' => $notifications->lastPage(),
    //             'per_page' => $notifications->perPage(),
    //             'total' => $notifications->total(),
    //             'next_page_url' => $notifications->nextPageUrl(),
    //             'previous_page_url' => $notifications->previousPageUrl(),
    //         ],
    //         'notification' => $arr_all_data,
    //     ];

    //     return response()->json(
    //         [
    //             'message' => "Successfully retrieve data",
    //             'data' => $response
    //         ],
    //         Response::HTTP_OK
    //     );
    // }



    public function storeStock($ids)
    {
        $message = '';

        foreach ($ids as $id) {
            $inventory_product = InventoryProductModel::where('inventory_product_id', $id)->first();

            if (!$inventory_product) {
                return response()->json([
                    'message' => "Product with ID $id not found",
                ], Response::HTTP_NOT_FOUND);
            }

            // Determine stock levels
            $is_low_stock = $inventory_product->stocks <= $inventory_product->low_stocks;
            $is_moderate_stock = !$is_low_stock && $inventory_product->stocks <= $inventory_product->moderate_stocks;

            DB::beginTransaction();
            try {
                $notifications_created = false;

                // Create notification for low stock
                if ($is_low_stock) {
                    $this->createNotification($inventory_product, 'LOW_STOCK');
                    $notifications_created = true;
                }

                // Create notification for moderate stock
                if ($is_moderate_stock) {
                    $this->createNotification($inventory_product, 'MODERATE_STOCK');
                    $notifications_created = true;
                }

                DB::commit();

                if ($notifications_created) {
                    if ($is_low_stock && $is_moderate_stock) {
                        $message = 'Low stock and moderate stock notifications stored successfully';
                    } elseif ($is_low_stock) {
                        $message = 'Low stock notification stored successfully';
                    } elseif ($is_moderate_stock) {
                        $message = 'Moderate stock notification stored successfully';
                    }
                } else {
                    $message = 'No relevant stock notifications were created';
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return response()->json(['message' => $message], Response::HTTP_OK);
    }


    // TODO: Logs
    public function updateView(Request $request)
    {
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|string',
            'is_read' => 'required|boolean',
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

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        DB::beginTransaction();
        try {
            $notification = NotificationModel::where('notification_id', Crypt::decrypt($request->input('notification_id')))->first();
            if (!$notification) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists for the current item
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Handle image upload 
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'notification',
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
                $notification, // the model to update
                $this->fillable_attr_notification->arrToUpdates(), // fields to update
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
                $notification,
                $this->fillable_attr_notification->arrToUpdates(),
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
                    'user_action' => 'UPDATE_STATUS_NOTIFICATION',
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

            return response()->json(
                [
                    'message' => 'Notification update successfully'
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function updateView(Request $request)
    // {
    //     $file_name = '';

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'notification_id' => 'required|string',
    //         'is_read' => 'required|boolean',
    //         'eu_device' => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
    //     }

    //     // Check if validation fails
    //     if ($validator->fails()) {
    //         return response()->json(
    //             [
    //                 'message' => $validator->errors(),
    //             ],
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }

    //     // Validate eu_device
    //     $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
    //     if ($result_validate_eu_device) {
    //         return $result_validate_eu_device;
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $notification = NotificationModel::where('notification_id', Crypt::decrypt($request->input('notification_id')))->first();
    //         if (!$notification) {
    //             return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
    //         }

    //         // Get the changes of the fields
    //         $result_changes_item_for_logs = $this->helper->updateLogsOldNew(
    //             $notification,
    //             $this->fillable_attr_notification->arrToUpdates(),
    //             $request->all(),
    //             $file_name,
    //         );

    //         $changes_for_logs[] = [
    //             'fields' => $result_changes_item_for_logs,
    //         ];

    //         // Check if there's Changes Logs
    //         $result_changes_logs = $this->helper->checkIfTheresChangesLogs($changes_for_logs);
    //         if ($result_changes_logs) {
    //             DB::rollBack();
    //             return $result_changes_logs;
    //         }

    //         // Update Multiple Data
    //         $result_update_multi_data = $this->helper->arrUpdateMultipleData(
    //             $notification,
    //             $this->fillable_attr_notification->arrToUpdates(),
    //             $request->all(),
    //             $file_name != '' ? $file_name : ''
    //         );

    //         if ($result_update_multi_data) {
    //             DB::rollBack();
    //             return $result_update_multi_data;
    //         }

    //         // Logs
    //         $log_result = $this->helper->log(
    //             $request,
    //             [
    //                 'user_device' => $request->input('eu_device'),
    //                 'user_id' => $user->user_id,
    //                 'is_history' => 0,
    //                 'user_action' => 'UPDATE STATUS NOTIFICATION',
    //             ]
    //         );

    //         if ($log_result->getStatusCode() !== Response::HTTP_OK) {
    //             DB::rollBack();
    //             return $log_result;
    //         }

    //         DB::commit();

    //         return response()->json(
    //             [
    //                 'message' => 'Notification update successfully'
    //             ],
    //             Response::HTTP_OK
    //         );
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }


    // CHILDE STORE
    private function createNotification($inventory_product, $type)
    {
        $auths = AuthModel::get();
        foreach ($auths as $auth) {
            if ($auth && in_array($auth->role, $this->fillable_attr_auth->arrEnvAccountRestrictRoleNotification())) {
                $json_data = json_encode($inventory_product->toArray(), JSON_PRETTY_PRINT);

                $created = NotificationModel::create([
                    'user_id' => $auth->user_id,
                    'tbl_reference_id' => $inventory_product->inventory_product_id,
                    'name' => $type,
                    'details' => $json_data
                ]);

                if (!$created) { // If creation failed
                    throw new \Exception("Failed to create notification for $type");
                }

                // Update the unique ID
                $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_notification->idToUpdate(), Str::uuid());
                if ($update_unique_id) {
                    throw new \Exception("Failed to update unique ID for $type notification");
                }
            }
        }
    }
}
