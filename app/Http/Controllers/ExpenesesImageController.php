<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ExpensesModel;
use App\Models\ExpensesImageModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ExpenesesImageController extends Controller
{

    protected $helper, $fillable_attr_expenses, $fillable_attr_expenses_image;

    public function __construct(Helper $helper, ExpensesModel $fillable_attr_expenses, ExpensesImageModel $fillable_attr_expenses_image)
    {
        $this->helper = $helper;
        $this->fillable_attr_expenses = $fillable_attr_expenses;
        $this->fillable_attr_expenses_image = $fillable_attr_expenses_image;
    }

    public function store(Request $request)
    {
        $file_name = '';
        $validated_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'expenses_id' => 'required|string',
            'file' => 'required|file|mimes:jpeg,png,jpg,JPEG,JPG,pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240',
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

        $expenses = ExpensesModel::where('expenses_id', Crypt::decrypt($request->expenses_id));
        if (!$expenses) {
            return response()->json(
                [
                    'message' => 'Data not found'
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Begin transaction
        DB::beginTransaction();
        try {
            // Handle image upload if it exists
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'expenses-image',
                        'file_image' => $request->file('file'),
                        'image_actual_extension' => $request->file('file')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    1,
                );
            }

            // Make some condition on file handling
            foreach ($this->fillable_attr_expenses_image->arrToStores() as $arrToStores) {
                if ($arrToStores == 'user_id') {
                    $validated_data[$arrToStores] = $user->user_id;
                } else if ($arrToStores == 'original_name') {
                    $validated_data[$arrToStores] = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
                } else if ($arrToStores == 'size') {
                    $validated_data[$arrToStores] = $this->helper->fileFormatSize($request->file('file')->getSize());
                } else {
                    $validated_data[$arrToStores] = $request->input($arrToStores);
                }
            }

            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $validated_data,
                $file_name,
                'file',
            );

            // *********************************** //
            // Start Store
            // Format the content
            $result_to_create = $this->helper->arrStoreMultipleData(
                $this->fillable_attr_expenses_image->arrToStores(),
                $result_merge_data,
                $this->fillable_attr_expenses_image->arrToDecryptEncryptedIds(),
                [],
                [],
                [],
            );

            // Create 
            $created = ExpensesImageModel::create($result_to_create);
            if (!$created) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store Expenses'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId(
                $created,
                $this->fillable_attr_expenses_image->idToUpdate(),
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
                    'user_action' => 'STORE_EXPENSES_IMAGE',
                ],
                $created->toArray(),
                1,
                env('PATH_FILE_EXPENSES_CHILD')
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
                'message' => 'Expenses image store successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // TODO: Add Logs
    public function update(Request $request)
    {
        $file_name = '';
        $validated_data = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'expenses_id' => 'required|string',
            'expenses_image_id' => 'required|string',
            'file' => 'required|file|mimes:jpeg,png,jpg,JPEG,JPG,pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240',
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
            $decrypted_expenses_image_id = Crypt::decrypt($request->input('expenses_image_id'));

            // Check if voucher record exists
            $expenses_image = ExpensesImageModel::where('expenses_image_id', $decrypted_expenses_image_id)
                ->where('expenses_id', $decrypted_expenses_id)
                ->first();
            if (!$expenses_image) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            // Handle image upload if it exists
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file_name = $this->helper->handleUploadFile(
                    [
                        'custom_folder' => 'expenses-image',
                        'file_image' => $request->file('file'),
                        'image_actual_extension' => $request->file('file')->getClientOriginalExtension(),
                        'image_actual_name_without_extension' => pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    1,
                );
            }

            // Make some condition on file handling update
            foreach ($this->fillable_attr_expenses_image->arrToUpdates() as $arrToUpdates) {
                if ($arrToUpdates == 'user_id') {
                    $validated_data[$arrToUpdates] = $user->user_id;
                } else if ($arrToUpdates == 'original_name') {
                    $validated_data[$arrToUpdates] = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
                } else if ($arrToUpdates == 'size') {
                    $validated_data[$arrToUpdates] = $this->helper->fileFormatSize($request->file('file')->getSize());
                } else {
                    $validated_data[$arrToUpdates] = $request->input($arrToUpdates);
                }
            }

            // Get the changes of the fields
            $result_changes_item_for_logs = $this->helper->updateLogsOldNew(
                $expenses_image,
                $this->fillable_attr_expenses_image->arrToUpdates(),
                $validated_data,
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
                $expenses_image,
                $this->fillable_attr_expenses_image->arrToUpdates(),
                $validated_data,
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
                    'user_action' => 'UPDATE_EXPENSES',
                ]
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }

            DB::commit();
            return response()->json([
                'message' => 'Expenses image update successfully',
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
            'expenses_image_id' => 'required|string',
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
            $decrypted_expenses_id = Crypt::decrypt($request->input('expenses_id'));
            $decrypted_expenses_image_id = Crypt::decrypt($request->input('expenses_image_id'));

            // Check if voucher record exists
            $expenses_image = ExpensesImageModel::where('expenses_image_id', $decrypted_expenses_image_id)
                ->where('expenses_id', $decrypted_expenses_id)
                ->first();
            if (!$expenses_image) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $expenses_image->toArray();

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'DELETE_EXPENSES_IMAGE',
                ],
                $arr_log_details,
                3,
                env("PATH_FILE_EXPENSES_CHILD")
            );

            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Rollback the transaction
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //

            // Delete the user
            if (!$expenses_image->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete expenses image'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::commit();

            return response()->json([
                'message' => 'Expenses image delete successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
