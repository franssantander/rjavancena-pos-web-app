<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use App\Models\VoucherModel;
use Illuminate\Http\Request;
use App\Models\VoucherItemModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class VoucherItemController extends Controller
{
    protected $helper, $fillable_attr_voucher, $fillable_attr_voucher_items;

    public function __construct(Helper $helper, VoucherModel $fillable_attr_voucher, VoucherItemModel $fillable_attr_voucher_items)
    {
        $this->helper = $helper;
        $this->fillable_attr_voucher = $fillable_attr_voucher;
        $this->fillable_attr_voucher_items = $fillable_attr_voucher_items;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Initialize an array to store all created items
        $created_items = [];
        $eu_device = '';
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'voucher_id' => 'required|string',
            'voucher_code' => 'required|string|max:255',
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
            $decrypted_voucher_id = Crypt::decrypt($request->voucher_id);

            $voucher = VoucherModel::where('voucher_id', $decrypted_voucher_id)
                ->first();
            if (!$voucher) {
                DB::rollBack();
                return response()->json(['message' => 'Invalid voucher ID'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Check if the voucher code is exist
            if (VoucherItemModel::where('voucher_code', $request->voucher_code)->exists()) {
                return response()->json(['message' => 'Voucher code already exists'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

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
                $this->fillable_attr_voucher_items->arrToStores(),
                $result_merge_data,
                $this->fillable_attr_voucher_items->arrPayloadIdsToDecrypt(),
                [],
                [],
                [],
            );

            // Create 
            $created = VoucherItemModel::create($result_to_create);
            if (!$created) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'Failed to store user information'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the unique I.D
            $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_voucher_items->idToUpdate(), Str::uuid());
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
                    'user_action' => 'STORE_VOUCHER_ITEM_CHILD',
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
            return response()->json(['message' => 'Voucher item child stored successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // TODO: Check why dont have status on voucher child item
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // Initialize an array to store all created items
        $file_name = '';

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'voucher_items_id' => 'required|string',
            'voucher_id' => 'required|string',
            'voucher_code' => 'required|string|max:255',
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
            // Decrypted id
            $decrypted_voucher_id = Crypt::decrypt($request->input('voucher_id'));
            $decrypted_voucher_items_id = Crypt::decrypt($request->input('voucher_items_id'));

            // Check if voucher record exists
            $voucher_item = VoucherItemModel::where('voucher_id', $decrypted_voucher_id)
                ->where('voucher_items_id', $decrypted_voucher_items_id)
                ->first();

            if (!$voucher_item) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            if (VoucherItemModel::where('voucher_code', $request->voucher_code)
                ->exists()
            ) {
                return response()->json(['message' => 'Voucher already exist'], Response::HTTP_UNPROCESSABLE_ENTITY);
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
                '',
            );

            // *********************************** //
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $voucher_item, // the model to update
                $this->fillable_attr_voucher_items->arrToUpdates(), // fields to update
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
                $voucher_item,
                $this->fillable_attr_voucher_items->arrToUpdates(),
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
                    'user_action' => 'UPDATE_VOUCHER_ITEM_CHILD',
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
                'message' => 'Voucher child code item update successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // TODO: Check the other table to delete
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
            'voucher_id' => 'required|string',
            'voucher_items_id' => 'required|string',
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
            $decrypted_voucher_items_id = Crypt::decrypt($request->voucher_items_id);

            $voucher_item = VoucherItemModel::where('voucher_id', $decrypted_voucher_id)
                ->where('voucher_items_id', $decrypted_voucher_items_id)
                ->first();
            if (!$voucher_item) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $arr_log_details = $voucher_item->toArray();

            // dd($arr_log_details);

            // // Checking Id on other tbl if exist unset the the api
            // $is_exist_id_other_tbl = $this->helper->isExistIdOtherTbl($voucher_item->voucher_id, $this->fillable_attr_voucher_items->arrModelWithId());
            // // Check if 'is_exist' is 'yes' in the first element then cant delete
            // if (!empty($is_exist_id_other_tbl) && $is_exist_id_other_tbl[0]['is_exist'] == 'yes') {
            //     return response()->json([
            //         'message' => 'Can\'t delete because this id exist on other table',
            //         'voucher_id' => $request->voucher_id,

            //     ], Response::HTTP_NOT_FOUND);
            // }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'DELETE_VOUCHER_ITEM_CHILD',
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


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
}
