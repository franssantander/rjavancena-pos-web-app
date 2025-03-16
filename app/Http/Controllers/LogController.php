<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\LogsModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UserInfoModel;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class LogController extends Controller
{
    protected $helper, $fillable_attr_logs;

    public function __construct(Helper $helper, LogsModel $fillable_attr_logs)
    {
        $this->fillable_attr_logs = $fillable_attr_logs;
        $this->helper = $helper;
    }

    public function index(Request $request)
    {
        $crud_settings = $this->fillable_attr_logs->getApiCrudSettings();

        $arr_container_datas = [];
        $arr_all_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        $logs = LogsModel::orderBy('created_at', 'desc')->get();

        foreach ($logs as $log) {
            foreach ($this->fillable_attr_logs->getFillableAttributes() as $getFillableAttribute) {
                // Name | Image
                if ($getFillableAttribute == 'user_id') {
                    $user_info = UserInfoModel::where('user_id', $log->$getFillableAttribute)->first();
                    // Check and decrypt the first name if encrypted
                    $first_name = isset($user_info->first_name)
                        ? ($this->helper->isEncrypted($user_info->first_name)
                            ? Crypt::decrypt($user_info->first_name)
                            : $user_info->first_name)
                        : null;

                    // Check and decrypt the last name if encrypted
                    $last_name = isset($user_info->last_name)
                        ? ($this->helper->isEncrypted($user_info->last_name)
                            ? Crypt::decrypt($user_info->last_name)
                            : $user_info->last_name)
                        : null;

                    // Check and decrypt the image if encrypted
                    $image = isset($user_info->image)
                        ? ($this->helper->isEncrypted($user_info->image)
                            ?  env("PATH_FILE_USER_ACCOUNT") . Crypt::decrypt($user_info->image)
                            : $user_info->image)
                        : null;

                    $arr_container_datas['image'] = $image; // Assign image to array

                    // Combine first and last name, ensuring there's a space between them
                    $arr_container_datas['name'] = ($first_name && $last_name)
                        ? Str::ucfirst(Str::lower(trim("{$first_name} {$last_name}")))
                        : ($first_name ?: $last_name); // Handle case where either name might be missing
                }

                // Details
                else if ($getFillableAttribute == 'details') {
                    $details = [];
                    $details_json_decode = json_decode($log->details, true);

                    if (isset($details_json_decode['fields'])) {
                        foreach ($details_json_decode['fields'] as $fields) {
                            // Decrypt fields directly
                            foreach (['current', 'old', 'new'] as $key) {
                                if (isset($fields[$key])) {
                                    $fields[$key] = $this->helper->isEncrypted($fields[$key])
                                        ? Crypt::decrypt($fields[$key])
                                        : $fields[$key];
                                }
                            }
                            $details[] = $fields;
                        }
                    }

                    // Add the detail to the details array
                    $arr_container_datas['details'] = $details;
                }

                // user device
                else if ($getFillableAttribute == 'user_device') {
                    $user_device = json_decode($log->user_device, true);
                    $arr_container_datas[$getFillableAttribute] = $user_device;
                }
                // fields to convert date and time
                else if (in_array($getFillableAttribute, $this->fillable_attr_logs->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$getFillableAttribute] = $this->helper->convertReadableTimeDate($log->$getFillableAttribute);
                }
                // just declare
                else {
                    $arr_container_datas[$getFillableAttribute] = $log->$getFillableAttribute;
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

                // update the url for view
                if ($action['button_name'] == 'View device') {
                    $action['user_device'] = $arr_container_datas['user_device'];
                }

                if ($action['button_name'] == 'View details') {
                    $action['user_action_details'] = $arr_container_datas['details'];
                }
            }
            // ***************************** //

            // Unset the fields not to use
            foreach ($this->fillable_attr_logs->arrFieldsToUnsetTable() as $arrFieldsToUnsetTable) {
                unset($arr_container_datas[$arrFieldsToUnsetTable]);
            }

            $arr_all_data[] = $arr_container_datas;
        }

        // Final response structure
        $response = [
            'logs' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_logs->arrColumns()),
        ];

        return response()->json(
            [
                'message' => "Successfully retrieve data",
                'data' => $response
            ],
            Response::HTTP_OK
        );
    }

    // public function index(Request $request)
    // {
    //     $decrypted_logs = [];
    //     $result_json = [];
    //     $arr_with_parent_id = [];
    //     $crud_settings = $this->fillable_attr_logs->getApiCrudSettings();

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     $logs = LogsModel::orderBy('created_at', 'desc')->get();

    //     foreach ($logs as $log) {
    //         $details_json = json_decode($log['details'], true);

    //         // Decrypt the data
    //         $decrypted_data = $this->isDecryptedData($log->is_sensitive, ($details_json['fields'] ?? $details_json), $this->fillable_attr_logs->encryptedFields(), $this->fillable_attr_logs->notToDecrypt());

    //         // Decrypted data save on fields
    //         $arr_with_parent_id = [
    //             'fields' => $decrypted_data
    //         ];

    //         // Retrieve userInfo model
    //         $user_info = UserInfoModel::where('user_id', $log['user_id'])->first();
    //         if ($user_info) {
    //             $arr_user_info = [];

    //             foreach ($this->fillable_attr_logs->arrFieldsToDisplay() as $field) {
    //                 if (isset($user_info->{$field})) {
    //                     if (in_array($field, $this->fillable_attr_logs->encryptedFields())) {
    //                         if ($field == 'image') {
    //                             $arr_user_info[$field] = $user_info->{$field} ? env('PATH_FILE_USER_ACCOUNT') . Crypt::decrypt($user_info->{$field}) : null;
    //                         } else {
    //                             $arr_user_info[$field] = $this->helper->transformColumnName(Crypt::decrypt($user_info->{$field}));
    //                         }
    //                     } else {
    //                         $arr_user_info[$field] = $user_info->{$field};
    //                     }
    //                 }
    //             }
    //             $decrypted_logs['image'] = $arr_user_info['image'] ?? null;
    //             $decrypted_logs['name'] = ($arr_user_info['first_name'] ?? null) . " " . ($arr_user_info['last_name'] ?? null);
    //         } else {
    //             $decrypted_logs['name'] = null;
    //             $decrypted_logs['image'] = null;
    //         }

    //         foreach ($this->fillable_attr_logs->getFillableAttributes() as $fillableAttrLog) {

    //             if ($fillableAttrLog == 'details') {
    //                 $decrypted_logs['details'] = $arr_with_parent_id;
    //             } else if ($fillableAttrLog == 'user_device') {
    //                 $decrypted_logs['user_device'] = json_decode($log->$fillableAttrLog, true);
    //             } else if ($fillableAttrLog == 'user_action') {

    //                 $decrypted_logs[$fillableAttrLog] = $this->helper->transformColumnName($log->$fillableAttrLog);
    //             } else if (in_array($fillableAttrLog, $this->fillable_attr_logs->arrToConvertToReadableDateTime())) {
    //                 $decrypted_logs[$fillableAttrLog] = $this->helper->convertReadableTimeDate($log->$fillableAttrLog);
    //             } else {
    //                 $decrypted_logs[$fillableAttrLog] = $log->$fillableAttrLog;
    //             }
    //         }

    //         // ***************************** //
    //         // Format Api
    //         $crud_action = $this->helper->formatApi(
    //             $crud_settings['prefix'],
    //             $crud_settings['payload'],
    //             $crud_settings['method'],
    //             $crud_settings['button_name'],
    //             $crud_settings['icon'],
    //             $crud_settings['container']
    //         );


    //         // Add the format Api Crud
    //         $decrypted_logs['action'] = array_values($crud_action);
    //         // ***************************** //

    //         // ***************************** //
    //         // Add details on action crud
    //         foreach ($decrypted_logs['action'] as &$action) {
    //             // Check if 'details' key doesn't exist, then add it
    //             if (!isset($action['details'])) {
    //                 $action['details'] = [];
    //             }

    //             if ($action['button_name'] == 'View device') {
    //                 // Populate details for each attribute
    //                 $action['user_device'] = $decrypted_logs['user_device'];
    //             } else {
    //                 $action['user_action_details'][] = $decrypted_logs['details'];
    //             }
    //         }
    //         // ***************************** //

    //         // Remove the column not needed
    //         unset($decrypted_logs['details']);
    //         unset($decrypted_logs['user_device']);
    //         unset($decrypted_logs['user_info']);
    //         unset($decrypted_logs['is_sensitive']);
    //         unset($decrypted_logs['updated_at']);
    //         unset($decrypted_logs['deleted_at']);

    //         $result_json[] = $decrypted_logs;
    //     }

    //     // Final response structure
    //     $response = [
    //         'logs' => $result_json,
    //         'columns' => $this->helper->transformColumnName($this->fillable_attr_logs->arrColumns()),
    //     ];

    //     return response()->json(
    //         [
    //             'message' => "Successfully retrieve data",
    //             'data' => $response
    //         ],
    //         Response::HTTP_OK
    //     );
    // }

    // HELPER FUNCTION
    public function isDecryptedData($is_sensitive, $fields, $fields_to_decrypt, $not_to_decrypts)
    {
        $decrypted_data = [];

        // Iterate over each field in the log details
        foreach ($fields as $field_name => $field_value) {
            foreach ($not_to_decrypts as $not_to_decrypt) {
                if ($not_to_decrypt != $field_name) {
                    // Check if the field is sensitive and needs decryption
                    if ($is_sensitive == 1 && in_array($field_name, $fields_to_decrypt)) {
                        if (is_array($field_value)) {
                            $decOld = isset($field_value['oldEnc']) ? Crypt::decrypt($field_value['oldEnc']) : (isset($field_value['old']) ? Crypt::decrypt($field_value['old']) : null);
                            $decNew = isset($field_value['newEnc']) ? Crypt::decrypt($field_value['newEnc']) : (isset($field_value['new']) ? Crypt::decrypt($field_value['new']) : null);

                            $decrypted_data[$field_name]['old'] = $decOld;
                            $decrypted_data[$field_name]['new'] = $decNew;
                        } else {
                            $decrypted_data[$field_name] = $field_value !== null ? Crypt::decrypt($field_value) : null;
                        }
                    } else {
                        $decrypted_data[$field_name] = $field_value;
                    }
                } else {
                    // Field is not sensitive or does not need decryption
                    $decrypted_data[$field_name] = $field_value;
                }
            }
        }

        return $decrypted_data;
    }
}
