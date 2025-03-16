<?php

namespace App\Http\Controllers;

use App\Jobs\LogJob;
use App\Helper\Helper;
use Illuminate\Support\Str;
use App\Models\PaymentModel;
use App\Models\VoucherModel;
use Illuminate\Http\Request;
use App\Models\PurchaseModel;
use Illuminate\Support\Carbon;
use App\Models\VoucherItemModel;
use App\Models\VoucherUsedModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{

    protected $helper, $fillable_attr_purchase, $fillable_attr_inventory_product, $fillable_attr_payment, $fillable_attr_voucher_used;

    public function __construct(
        Helper $helper,
        PurchaseModel $fillable_attr_purchase,
        InventoryProductModel $fillable_attr_inventory_product,
        PaymentModel $fillable_attr_payment,
        VoucherUsedModel $fillable_attr_voucher_used
    ) {
        $this->helper = $helper;
        $this->fillable_attr_purchase = $fillable_attr_purchase;
        $this->fillable_attr_inventory_product = $fillable_attr_inventory_product;
        $this->fillable_attr_payment = $fillable_attr_payment;
        $this->fillable_attr_voucher_used = $fillable_attr_voucher_used;
    }

    public function store(Request $request)
    {
        $status = 'NOT PAID';
        $ctr = 0;
        $arr_store_fresh_create = [];
        $arr_all_purchase = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'inventory_product_id' => 'required|string',
            'purchase_group_id' => 'nullable',
            'user_id_customer' => 'nullable',
            'quantity' => 'required|numeric|min:1',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        // Start the transaction
        DB::beginTransaction();

        try {
            // Decrypted Variables
            $decrypted_inventory_product_id = $request->inventory_product_id != "" && $request->inventory_product_id != null ? Crypt::decrypt($request->inventory_product_id) : null;
            $decrypted_purchase_group_id = isset($request->purchase_group_id) &&  $request->purchase_group_id != "" && $request->purchase_group_id != null ? Crypt::decrypt($request->purchase_group_id) : null;
            $decrypted_purchase_user_id_customer = isset($request->user_id_customer) &&  $request->user_id_customer != "" && $request->user_id_customer != null ? Crypt::decrypt($request->user_id_customer) : null;
            $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)->first();
            if (!$inventory_product) {
                return response()->json(['message' => 'Inventory Product ID not found'], Response::HTTP_NOT_FOUND);
            }

            if ($inventory_product->stocks < $request->quantity) {
                return response()->json(['message' => 'Sorry, can\'t add due to insufficient stock', 'stocks' => $inventory_product->stocks], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Add New Item on purchase_group_id
            if (
                $decrypted_inventory_product_id != '' &&
                $decrypted_purchase_group_id != '' && $decrypted_purchase_group_id != null &&  $decrypted_purchase_group_id != false &&
                $decrypted_purchase_user_id_customer != '' && $decrypted_purchase_user_id_customer != null &&  $decrypted_purchase_user_id_customer != false
            ) {

                do {
                    foreach ($this->fillable_attr_purchase->arrToStores() as $arrToStores) {
                        if ($arrToStores == 'purchase_group_id') {
                            $arr_store_fresh_create[$arrToStores] = $decrypted_purchase_group_id;
                        } else if ($arrToStores == 'user_id_customer') {
                            $arr_store_fresh_create[$arrToStores] = $decrypted_purchase_user_id_customer;
                        } else if ($arrToStores == 'inventory_product_id') {
                            $arr_store_fresh_create[$arrToStores] =  $decrypted_inventory_product_id;
                        } else if ($arrToStores == 'user_id_menu') {
                            $arr_store_fresh_create[$arrToStores] = $user->user_id;
                        } else if ($arrToStores == 'status') {
                            $arr_store_fresh_create[$arrToStores] = $status;
                        } else if ($arrToStores == 'customer_name') {
                            $purchase_name = PurchaseModel::where('purchase_group_id', $decrypted_purchase_group_id)
                                ->where('user_id_customer', $decrypted_purchase_user_id_customer)
                                ->first();
                            if ($purchase_name) {
                                Log::info("QUERY");
                                Log::info($arr_store_fresh_create);
                                $arr_store_fresh_create['customer_name'] = $purchase_name->customer_name;
                            }
                            Log::info("GET THE CUSTOMER NAME");
                            Log::info($arr_store_fresh_create);
                        } else {
                            $arr_store_fresh_create[$arrToStores] = $inventory_product->$arrToStores;
                        }
                    }

                    Log::info("TO CREATE!");
                    Log::info($arr_store_fresh_create);

                    // Create a new purchase record
                    $created_purchase = PurchaseModel::create($arr_store_fresh_create);
                    if (!$created_purchase) {
                        DB::rollBack();
                        return response()->json(
                            ['message' => 'Failed to store purchase'],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    // Update the unique I.D
                    $update_unique_id = $this->helper->updateUniqueId($created_purchase, $this->fillable_attr_purchase->idToUpdatePurchase(), Str::uuid());
                    if ($update_unique_id) {
                        DB::rollBack();
                        return $update_unique_id;
                    }

                    // Minus Stock
                    $minus_stock = $this->minusStock($decrypted_inventory_product_id);
                    $total_amount_payment = $this->totalAmountPayment($decrypted_purchase_group_id, $created_purchase->user_id_customer);

                    // Update the payment record
                    $payment = PaymentModel::where('purchase_group_id', $created_purchase->purchase_group_id)->first();

                    if (!$payment) {
                        DB::rollBack();
                        return response()->json(
                            ['message' => 'Payment record not found'],
                            Response::HTTP_NOT_FOUND
                        );
                    }

                    $payment->update([
                        'total_amount' => $total_amount_payment['total_amount'],
                        'total_discounted_amount' => $total_amount_payment['total_discounted_amount'],
                    ]);

                    // Re-fetch the updated payment record
                    $updated_payment = PaymentModel::where('id', $payment->id)->first();


                    // Store logs for create Purchase
                    $arr_all_purchase['purchase' . "-" . $ctr][] = $created_purchase->toArray();
                    // Store logs for update Payment
                    $arr_all_purchase["payment" . "-" . $ctr][] = $updated_payment->toArray();

                    $ctr++;
                } while ($ctr < $request->quantity);

                // *********************************** //
                // Start log
                // Logs
                $log_result = $this->helper->log(
                    $request,
                    [
                        'user_device' => $request->eu_device,
                        'user_id' => $user->user_id,
                        'is_history' => 0,
                        'user_action' => 'ADDED_NEW_PURCHASE_ITEM',
                    ],
                    $arr_all_purchase,
                    4
                );
                if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                    DB::rollBack();
                    return $log_result;
                }
                // End log
                // *********************************** //

                // Commit the transaction
                DB::commit();

                return response()->json(
                    [
                        'message' => 'Purchase and Payment records stored successfully',
                    ],
                    Response::HTTP_OK
                );
            }
            // Fresh Create
            else {
                Log::info(message: "DITO NA PUNTA BAGO 1");
                $ctr = 0;
                $group_purchase_id = $this->generateGroupPurchaseId();
                $new_customer_id = $this->generateCustomerId();

                if ($group_purchase_id == '') {
                    return response()->json(
                        ['message' => 'Failed generate purchase I.D'],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                if ($new_customer_id == '') {
                    return response()->json(
                        ['message' => 'Failed generate costumer I.D'],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                $check_purchase_and_customer_id = PurchaseModel::whereLike('purchase_group_id', $group_purchase_id)
                    ->whereLike('user_id_customer', $new_customer_id);
                while ($check_purchase_and_customer_id) {
                    $group_purchase_id = $this->generateGroupPurchaseId();
                    $new_customer_id = $this->generateCustomerId();

                    $check_purchase_and_customer_id = PurchaseModel::where('purchase_group_id', 'LIKE', $group_purchase_id)
                        ->where('user_id_customer', 'LIKE', $new_customer_id)
                        ->exists();
                }

                do {
                    // STEP 1 Fresh Create start 0
                    if ($ctr == 0) {
                        foreach ($this->fillable_attr_purchase->arrToStores() as $arrToStores) {
                            if ($arrToStores == 'user_id_customer') {
                                $arr_store_fresh_create[$arrToStores] = $new_customer_id;
                            } else if ($arrToStores == 'purchase_group_id') {
                                $arr_store_fresh_create[$arrToStores] = $group_purchase_id;
                            } else if ($arrToStores == 'user_id_menu') {
                                $arr_store_fresh_create[$arrToStores] = $user->user_id;
                            } else if ($arrToStores == 'status') {
                                $arr_store_fresh_create[$arrToStores] = $status;
                            } else {
                                $arr_store_fresh_create[$arrToStores] = $inventory_product->$arrToStores;
                            }
                        }

                        // Initialize the customer array and default value
                        $arr_customers = [];
                        $customer_prefix = "customer-";

                        // Fetch all purchases
                        $all_purchases = PurchaseModel::get();
                        // Check if there are no purchases
                        if ($all_purchases->isEmpty()) {
                            // Set the initial customer name if no purchases exist
                            $arr_store_fresh_create['customer_name'] = $customer_prefix . '1';
                        } else {
                            // Iterate through all purchases
                            foreach ($all_purchases as $all_purchase) {
                                // Extract the numeric part after the dash using explode on the correct field, e.g. purchase_id
                                $customer_number = explode('-', string: $all_purchase->customer_name)[1];

                                // Store in array if not already present
                                if (!in_array($customer_number, haystack: $arr_customers)) {
                                    $arr_customers[] = $customer_number;
                                }
                            }

                            // Get the highest number from the array and increment by 1
                            $max_customer_number = max($arr_customers);
                            $arr_store_fresh_create['customer_name'] = $customer_prefix . ((int) $max_customer_number + 1);
                        }


                        // Create a new purchase record
                        $created_purchase = PurchaseModel::create($arr_store_fresh_create);
                        if (!$created_purchase) {
                            DB::rollBack();
                            return response()->json(
                                ['message' => 'Failed to store purchase'],
                                Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                        }

                        // Update the unique I.D Purchase
                        $update_unique_id = $this->helper->updateUniqueId($created_purchase, $this->fillable_attr_purchase->idToUpdatePurchase(), Str::uuid());
                        if ($update_unique_id) {
                            DB::rollBack();
                            return $update_unique_id;
                        }

                        // Minus Stock
                        $minus_stock = $this->minusStock($decrypted_inventory_product_id);
                        $total_amount_payment = $this->totalAmountPayment($created_purchase->purchase_group_id, $created_purchase->user_id_customer);

                        // Create a new payment record
                        $created_payment = PaymentModel::create([
                            'user_id' => $created_purchase->user_id_customer,
                            'purchase_group_id' => $created_purchase->purchase_group_id,
                            'user_id_menu' => $user->user_id,
                            'payment_method' => 'CASH',
                            'total_amount' => $total_amount_payment['total_amount'],
                            'total_discounted_amount' => $total_amount_payment['total_discounted_amount'],
                            'status' => $status,
                        ]);
                        if (!$created_payment) {
                            DB::rollBack();
                            return response()->json(
                                ['message' => 'Failed to store payment'],
                                Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                        }

                        // Update the unique I.D Payment
                        $update_unique_id = $this->helper->updateUniqueId($created_payment, $this->fillable_attr_purchase->idToUpdatePayment(), Str::uuid());
                        if ($update_unique_id) {
                            DB::rollBack();
                            return $update_unique_id;
                        }

                        // Store logs for create Purchase
                        $arr_all_purchase['purchase' . "-" . $ctr][] = $created_purchase->toArray();
                        // Store logs for update Payment
                        $arr_all_purchase['payment' . "-" . $ctr][] = $created_payment->toArray();
                    }
                    // STEP 2 Fresh Create but greater 0 quantity 
                    else {
                        foreach ($this->fillable_attr_purchase->arrToStores() as $arrToStores) {
                            if ($arrToStores == 'user_id_customer') {
                                $arr_store_fresh_create[$arrToStores] = $new_customer_id;
                            } else if ($arrToStores == 'purchase_group_id') {
                                $arr_store_fresh_create[$arrToStores] = $group_purchase_id;
                            } else if ($arrToStores == 'user_id_menu') {
                                $arr_store_fresh_create[$arrToStores] = $user->user_id;
                            } else if ($arrToStores == 'status') {
                                $arr_store_fresh_create[$arrToStores] = $status;
                            } else {
                                $arr_store_fresh_create[$arrToStores] = $inventory_product->$arrToStores;
                            }
                        }

                        // Create a new purchase record
                        $created_purchase = PurchaseModel::create($arr_store_fresh_create);
                        if (!$created_purchase) {
                            DB::rollBack();
                            return response()->json(
                                ['message' => 'Failed to store purchase'],
                                Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                        }

                        // Update the unique I.D
                        $update_unique_id = $this->helper->updateUniqueId($created_purchase, $this->fillable_attr_purchase->idToUpdatePurchase(), Str::uuid());
                        if ($update_unique_id) {
                            DB::rollBack();
                            // Retun only if theres an error
                            return $update_unique_id;
                        }

                        // Minus Stock
                        $minus_stock = $this->minusStock($decrypted_inventory_product_id);
                        $total_amount_payment = $this->totalAmountPayment($created_purchase->purchase_group_id, $created_purchase->user_id_customer);

                        // Update the payment record
                        $payment = PaymentModel::where('purchase_group_id', $created_purchase->purchase_group_id)->first();

                        if (!$payment) {
                            DB::rollBack();
                            return response()->json(
                                ['message' => 'Payment record not found'],
                                Response::HTTP_NOT_FOUND
                            );
                        }

                        $payment->update([
                            'total_amount' => $total_amount_payment['total_amount'],
                            'total_discounted_amount' => $total_amount_payment['total_discounted_amount'],
                        ]);

                        // Re-fetch the updated payment record
                        $updated_payment = PaymentModel::where('id', $payment->id)->first();

                        // Store logs for create Purchase
                        $arr_all_purchase['purchase' . "-" . $ctr][] = $created_purchase->toArray();
                        // Store logs for update Payment
                        $arr_all_purchase["payment" . "-" . $ctr][] = $updated_payment->toArray();
                    }

                    $ctr++;
                } while ($ctr < $request->quantity);

                // dd($arr_all_purchase);

                // *********************************** //
                // Start log
                // Logs
                $log_result = $this->helper->log(
                    $request,
                    [
                        'user_device' => $request->eu_device,
                        'user_id' => $user->user_id,
                        'is_history' => 0,
                        'user_action' => 'STORE_PURCHASE_ITEM',
                    ],
                    $arr_all_purchase,
                    4
                );
                if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                    DB::rollBack();
                    return $log_result;
                }
                // End log
                // *********************************** //

                // Commit the transaction
                DB::commit();

                return response()->json(
                    [
                        'message' => 'Purchase and Payment records stored successfully',
                    ],
                    Response::HTTP_OK
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateQty(Request $request)
    {
        $arr_add_purchase = [];
        $arr_minus_purchase = [];
        $ctr = 0;
        $qty = 0;

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'purchase_id' => 'required|string',
            'purchase_group_id' => 'required|string',
            'inventory_id' => 'required|string',
            'inventory_product_id' => 'required|string',
            'user_id_customer' => 'required|string',
            'quantity' => 'required|numeric|min:1',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }


        // Start the transaction
        DB::beginTransaction();

        try {
            $decrypted_purchase_id = Crypt::decrypt($request->purchase_id);
            $decrypted_purchase_group_id = Crypt::decrypt($request->purchase_group_id);
            $decrypted_inventory_id = Crypt::decrypt($request->inventory_id);
            $decrypted_inventory_product_id = Crypt::decrypt($request->inventory_product_id);
            $decrypted_user_id_customer = Crypt::decrypt($request->user_id_customer);

            $purchase_count = PurchaseModel::where('purchase_group_id', $decrypted_purchase_group_id)
                ->where('user_id_customer', $decrypted_user_id_customer)
                ->where('user_id_menu', $user->user_id)
                ->where('inventory_id', $decrypted_inventory_id)
                ->where('inventory_product_id', $decrypted_inventory_product_id)
                ->count();

            // Add qty
            if ($request->quantity > $purchase_count) {
                $qty = $request->quantity - $purchase_count;

                $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)
                    ->where('inventory_id', $decrypted_inventory_id)
                    ->first();

                if (!$inventory_product) {
                    return response()->json(['message' => 'Inventory Product ID not found'], Response::HTTP_NOT_FOUND);
                }

                if ($inventory_product->stocks < $qty) {
                    return response()->json([
                        'message' => 'Failed to increment out of stocks',
                        'parameter' => $inventory_product->item_code,
                        'item' => 'Out of stock. Only ' . $inventory_product->stocks . " available",
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                while ($ctr < $qty) {
                    $update_stock = $inventory_product->update([
                        'stocks' => $inventory_product->stocks - 1,
                    ]);

                    if (!$update_stock) {
                        DB::rollBack();
                        return response()->json(
                            [
                                'message' => 'Failed to update stock. Please try again later.',
                            ],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    $purchase = PurchaseModel::where('purchase_id', $decrypted_purchase_id)
                        ->where('purchase_group_id', $decrypted_purchase_group_id)
                        ->where('inventory_id', $decrypted_inventory_id)
                        ->where('inventory_product_id', $decrypted_inventory_product_id)
                        ->where('user_id_customer', $decrypted_user_id_customer)
                        ->where('user_id_menu', $user->user_id)
                        ->first();

                    if (!$purchase) {
                        DB::rollBack();
                        return response()->json(
                            [
                                'message' => 'No data found',
                            ],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    $arr_store = [];
                    foreach ($this->fillable_attr_purchase->arrAddQtyPurchases() as $arrAddQtyPurchases) {
                        $arr_store[$arrAddQtyPurchases] = $purchase->$arrAddQtyPurchases;
                    }

                    // Create a new purchase using the attributes of $purchase
                    $created = PurchaseModel::create($arr_store);
                    if (!$created) {
                        DB::rollBack();
                        return response()->json(
                            [
                                'message' => 'Failed to store purchase',
                            ],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    // Update the purchase_id with the correct format
                    $update_purchase_id = $created->update([
                        'purchase_id' => 'purchase_id-' . Str::uuid(),
                    ]);
                    if (!$update_purchase_id) {
                        DB::rollBack();
                        return response()->json(
                            ['message' => 'Failed to update purchase ID'],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    $total_amount_payment = $this->totalAmountPayment($decrypted_purchase_group_id, $decrypted_user_id_customer);
                    $update_payment = PaymentModel::where('user_id', $decrypted_user_id_customer)
                        ->where('purchase_group_id', $decrypted_purchase_group_id)
                        ->first()
                        ->update([
                            'total_amount' => $total_amount_payment['total_amount'],
                            'total_discounted_amount' => $total_amount_payment['total_discounted_amount'],
                        ]);

                    // Check if payment record exists
                    if (!$update_payment) {
                        DB::rollBack();
                        return response()->json(
                            ['message' => 'Failed to update total amount'],
                            Response::HTTP_NOT_FOUND
                        );
                    }

                    // Holder logs
                    $arr_add_purchase['add_qty_purchase' . "-" . $ctr][] = $purchase->toArray();
                    $ctr++;
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
                        'user_action' => 'ADD_QUANTITY_PURCHASE_ITEM',
                    ],
                    $arr_add_purchase,
                    4
                );
                if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                    DB::rollBack();
                    return $log_result;
                }
                // End log
                // *********************************** //

                // Commit the transaction
                DB::commit();

                return response()->json(
                    [
                        'message' => 'Success add on item',
                    ],
                    Response::HTTP_OK
                );
            }

            // Minus qty
            else if ($request->quantity < $purchase_count) {
                $qty = $purchase_count - $request->quantity;

                try {
                    $purchases = PurchaseModel::where('purchase_group_id', $decrypted_purchase_group_id)
                        ->where('user_id_customer', $decrypted_user_id_customer)
                        ->where('user_id_menu', $user->user_id)
                        ->where('inventory_id', $decrypted_inventory_id)
                        ->where('inventory_product_id', $decrypted_inventory_product_id)
                        ->get();

                    $inventory_product = InventoryProductModel::where('inventory_product_id', $decrypted_inventory_product_id)
                        ->where('inventory_id', $decrypted_inventory_id)
                        ->first();
                    if (!$inventory_product) {
                        return response()->json(['message' => 'Inventory Product ID not found'], Response::HTTP_NOT_FOUND);
                    }

                    $update_stock = $inventory_product->update([
                        'stocks' => $inventory_product->stocks + $qty,
                    ]);

                    if (!$update_stock) {
                        DB::rollBack();
                        return response()->json(
                            [
                                'message' => 'Failed to update stock. Please try again later.',
                            ],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    $ctr = 0; // Initialize the counter

                    foreach ($purchases as $purchase) {
                        if ($ctr >= $qty) {
                            break; // Exit the foreach loop if the required quantity is reached
                        }

                        if ($purchase->delete()) {
                            // Holder logs
                            $arr_minus_purchase['minus_qty_purchase' . "-" . $ctr][] = $purchase->toArray();
                            $ctr++; // Increment the counter
                        } else {
                            DB::rollBack();
                            return response()->json(['message' => 'Failed to delete item.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }

                    if ($ctr < $qty) {
                        DB::rollBack();
                        return response()->json(['message' => 'Not enough purchases to delete'], Response::HTTP_BAD_REQUEST);
                    }

                    $total_amount_payment = $this->totalAmountPayment($decrypted_purchase_group_id, $decrypted_user_id_customer);
                    $update_payment = PaymentModel::where('user_id', $decrypted_user_id_customer)
                        ->where('purchase_group_id', $decrypted_purchase_group_id)
                        ->first()
                        ->update([
                            'total_amount' => $total_amount_payment['total_amount'],
                            'total_discounted_amount' => $total_amount_payment['total_discounted_amount'],
                        ]);

                    // Check if payment record exists
                    if (!$update_payment) {
                        DB::rollBack();
                        return response()->json(
                            ['message' => 'Failed to update total amount'],
                            Response::HTTP_NOT_FOUND
                        );
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
                            'user_action' => 'MINUS_QUANTITY_PURCHASE_ITEM',
                        ],
                        $arr_minus_purchase,
                        4
                    );
                    if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                        DB::rollBack();
                        return $log_result;
                    }
                    // End log
                    // *********************************** //

                    // Commit the transaction
                    DB::commit();

                    return response()->json(
                        [
                            'message' => 'Success minus on item',
                        ],
                        Response::HTTP_OK
                    );
                } catch (\Exception $e) {
                    // Rollback the transaction in case of any error
                    DB::rollBack();
                    return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteQtyAll(Request $request)
    {
        $ctr = 0;
        $arr_delete_purchase = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            DB::rollBack();
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'purchase_id' => 'required|array',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Validate Eu Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        // Start the transaction
        DB::beginTransaction();
        try {
            foreach ($request->purchase_id as $purchase_id) {
                $decrypted_purchase_id = Crypt::decrypt($purchase_id);

                $purchase = PurchaseModel::where('purchase_id', $decrypted_purchase_id)->first();
                if (!$purchase) {
                    DB::rollBack();
                    return response()->json(['message' => 'Purchase not found'], Response::HTTP_NOT_FOUND);
                }

                // Store the successfully deleted purchase ID
                // Holder logs
                $arr_delete_purchase['delete_purchase' . "-" . $ctr][] = $purchase->toArray();

                // Check if 1 purchase only then go to payment table and get the 
                // voucher_used_group_id then get the code and delete it and update the same code to the vouchers_items_tbl and change the status to AVAILABLE
                $purchase_check_if_1 = PurchaseModel::where('purchase_group_id', $purchase->purchase_group_id)
                    ->where('user_id_menu', $purchase->user_id_menu)->count();
                if ($purchase_check_if_1 == 1) {
                    $payment_delete_voucher_used = PaymentModel::where('purchase_group_id', $purchase->purchase_group_id)
                        ->where('user_id_menu', $purchase->user_id_menu)
                        ->first();
                    if ($payment_delete_voucher_used) {
                        $voucher_used_to_deletes = VoucherUsedModel::where('voucher_used_group_id', $payment_delete_voucher_used->voucher_used_group_id)
                            ->where('payment_id', operator: $payment_delete_voucher_used->payment_id)
                            ->get();
                        foreach ($voucher_used_to_deletes as $voucher_used_to_delete) {
                            if ($voucher_used_to_delete) {
                                $voucher_item_to_update_available = VoucherItemModel::where('voucher_code', $voucher_used_to_delete->voucher_code)
                                    ->first();
                                if ($voucher_item_to_update_available) {
                                    $voucher_item_to_update_available->update([
                                        'status' => 'AVAILABLE'
                                    ]);

                                    // Delete the voucher on voucher used
                                    $voucher_used_to_delete->delete();
                                }
                            }
                        }
                    }
                }

                if (!$purchase->delete()) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to delete purchase'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Update stock after deleting all purchases
                $inventory_product = InventoryProductModel::where('inventory_product_id', $purchase->inventory_product_id)
                    ->where('inventory_id', $purchase->inventory_id)
                    ->first();
                if (!$inventory_product) {
                    DB::rollBack();
                    return response()->json(['message' => 'Inventory Product ID not found'], Response::HTTP_NOT_FOUND);
                }

                $inventory_product->update([
                    'stocks' => $inventory_product->stocks + 1,
                ]);

                $total_amount_payment = $this->totalAmountPaymentDeleteAll($purchase->purchase_group_id, $purchase->user_id_customer);
                $update_payment = PaymentModel::where('user_id', $purchase->user_id_customer)
                    ->where('purchase_group_id', $purchase->purchase_group_id)
                    ->first();

                if (!$update_payment) {
                    DB::rollBack();
                    return response()->json(['message' => 'Payment record not found'], Response::HTTP_NOT_FOUND);
                }

                $update_payment->update([
                    'total_amount' => $total_amount_payment['total_amount'],
                    'total_discounted_amount' => $total_amount_payment['total_discounted_amount'],
                ]);

                // Check if total amount is zero and then delete the payment record
                if ($total_amount_payment['total_amount'] == 0.00) {
                    if (!$update_payment->delete()) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to delete payment'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
                $ctr++;
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
                    'user_action' => 'DELETE_PURCHASE_ITEM',
                ],
                $arr_delete_purchase,
                4
            );
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack();
                return $log_result;
            }
            // End log
            // *********************************** //


            // Commit the transaction
            DB::commit();

            return response()->json(
                [
                    'message' => 'Purchase and Payment records deleted successfully',
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserIdMenuCustomer(Request $request)
    {
        // Initialize array to store purchase information
        $grouped_purchases = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Fetch purchases
        $purchases = PurchaseModel::where('user_id_menu', $user->user_id)
            ->where('status', 'NOT PAID')
            ->orderBy('created_at', 'desc') // Add this line to sort by 'created_at' in ascending order
            ->get();

        // Loop through purchases 
        foreach ($purchases as $purchase) {
            // Generate a key based on the user_id_customer
            $key = $purchase->user_id_customer;

            // Check if the key already exists in the grouped purchases array
            if (isset($grouped_purchases[$key])) {
                // If the key exists, check if the same purchase details already exist
                $found = false;
                foreach ($grouped_purchases[$key] as &$grouped_purchase) {
                    if (
                        $grouped_purchase['purchase_group_id'] === $purchase->purchase_group_id &&
                        $grouped_purchase['inventory_id'] === $purchase->inventory_id &&
                        $grouped_purchase['inventory_product_id'] === $purchase->inventory_product_id &&
                        $grouped_purchase['customer_name'] === $purchase->customer_name &&
                        $grouped_purchase['item_code'] === $purchase->item_code &&
                        $grouped_purchase['image'] === $purchase->image &&
                        $grouped_purchase['name'] === $purchase->name &&
                        $grouped_purchase['category'] === $purchase->category &&
                        $grouped_purchase['retail_price'] === $purchase->retail_price &&
                        $grouped_purchase['discounted_price'] === $purchase->discounted_price
                    ) {
                        // Initialize arr_purchase_id if it's not already set
                        if (!isset($grouped_purchase['arr_purchase_id'])) {
                            $grouped_purchase['arr_purchase_id'] = [];
                        }
                        $grouped_purchase['arr_purchase_id'][] = $purchase->purchase_id;

                        // If the same purchase details exist, increment the count and update total_price
                        $grouped_purchase['count']++;
                        if ($purchase->discounted_price != 0) {
                            $grouped_purchase['total_price'] = $purchase->discounted_price * $grouped_purchase['count'];
                        } else {
                            $grouped_purchase['total_price'] = $purchase->retail_price * $grouped_purchase['count'];
                        }

                        $found = true;
                        break;
                    }
                }
                // If the same purchase details not found, add the new purchase details
                if (!$found) {
                    $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
                    $grouped_purchases[$key][] = [
                        'purchase_id' => $purchase->purchase_id,
                        'purchase_group_id' => $purchase->purchase_group_id,
                        'user_id_customer' => $purchase->user_id_customer,
                        'inventory_id' => $purchase->inventory_id,
                        'inventory_product_id' => $purchase->inventory_product_id,
                        'customer_name' => $purchase->customer_name,
                        'item_code' => $purchase->item_code,
                        'image' => $purchase->image,
                        'name' => $purchase->name,
                        'category' => $purchase->category,
                        // 'design' => $purchase->design,
                        // 'size' => $purchase->size,
                        // 'color' => $purchase->color,
                        'retail_price' => $purchase->retail_price,
                        'discounted_price' => $purchase->discounted_price,
                        'count' => 1,
                        'total_price' => $total_price,
                        'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
                    ];
                }
            } else {
                // If the key doesn't exist, initialize a new customer's purchases array    
                $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
                $grouped_purchases[$key][] = [
                    'purchase_id' => $purchase->purchase_id,
                    'purchase_group_id' => $purchase->purchase_group_id,
                    'user_id_customer' => $purchase->user_id_customer,
                    'inventory_id' => $purchase->inventory_id,
                    'inventory_product_id' => $purchase->inventory_product_id,
                    'customer_name' => $purchase->customer_name,
                    'item_code' => $purchase->item_code,
                    'image' => $purchase->image,
                    'name' => $purchase->name,
                    'category' => $purchase->category,
                    'retail_price' => $purchase->retail_price,
                    'discounted_price' => $purchase->discounted_price,
                    'count' => 1,
                    'total_price' => $total_price,
                    'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
                ];
            }
        }

        // Prepare an array to hold each customer's data as objects
        $formatted_data = [];

        // Add payment information and format as objects
        foreach ($grouped_purchases as $user_id_customer => $items) {
            // *********************************** //
            // Start independent key and values
            $customer_data = new \stdClass(); // Create a new stdClass object for each customer | Container for saving value
            $customer_data->customer_id = $user_id_customer;
            $customer_data->customer_name = $items[0]['customer_name'];
            $customer_data->purchase_group_id = Crypt::encrypt($items[0]['purchase_group_id']); // Add purchase_group_id
            $customer_data->user_id_customer = Crypt::encrypt($user_id_customer); // Add user_id_customer
            $customer_data->total_orders = count($items); // Calculate total_orders as the number of unique items
            // End independent key and values
            // *********************************** //


            // *********************************** //
            // Start payment key group
            $customer_data->payment = PaymentModel::where('purchase_group_id', $items[0]['purchase_group_id'])
                ->where('user_id', $user_id_customer)
                ->get()
                ->toArray();

            // Encrypt payment information | All payment information here at &$payment_info
            foreach ($customer_data->payment as &$payment_info) {
                $customer_data->payment_id = Crypt::encrypt($payment_info['payment_id']); // Add payment_id
                $customer_data->voucher_used_group_id =  Crypt::encrypt($payment_info['voucher_used_group_id']); // Add voucher_used_group_id

                $payment_info['payment_id'] = Crypt::encrypt($payment_info['payment_id']);
                $payment_info['user_id'] = Crypt::encrypt($payment_info['user_id']);
                $payment_info['purchase_group_id'] = Crypt::encrypt($payment_info['purchase_group_id']);
                $payment_info['voucher_used_group_id'] = Crypt::encrypt($payment_info['voucher_used_group_id']);

                // External put in payment only
                $payment_info['user_id_customer'] = Crypt::encrypt($user_id_customer);

                unset($payment_info['id']);
                unset($payment_info['created_at']);
                unset($payment_info['updated_at']);
                unset($payment_info['deleted_at']);
            }

            // End payment key group
            // *********************************** //

            // *********************************** //
            // Start voucher key group
            $decrypted_voucher_used_group_id = Crypt::decrypt($customer_data->payment[0]['voucher_used_group_id']);
            $decrypted_payment_id = Crypt::decrypt($customer_data->payment[0]['payment_id']);
            if ($decrypted_voucher_used_group_id != null) {
                $voucher_used = VoucherUsedModel::where('voucher_used_group_id', $decrypted_voucher_used_group_id)
                    ->where('payment_id', $decrypted_payment_id)
                    ->get();

                $customer_data->voucher = $this->getCrudOfVoucherUsed($voucher_used);
            }
            // End voucher key group
            // *********************************** //

            // *********************************** //
            // Start item key group
            $customer_data->items = [];
            // Add items and format each as an object
            foreach ($items as $item) {
                $formatted_item = new \stdClass();
                $formatted_item->purchase_id = Crypt::encrypt($item['purchase_id']);
                $formatted_item->purchase_group_id = Crypt::encrypt($item['purchase_group_id']);
                $formatted_item->user_id_customer = Crypt::encrypt($item['user_id_customer']);
                $formatted_item->inventory_id = Crypt::encrypt($item['inventory_id']);
                $formatted_item->inventory_product_id = Crypt::encrypt($item['inventory_product_id']);
                $formatted_item->item_code = $item['item_code'];
                $formatted_item->name = $item['name'];
                $formatted_item->category = $item['category'];
                $formatted_item->image = $item['image'] != null ? env("PATH_FILE_INVENTORY_PRODUCT") . $item['image'] : $item['image'];
                $formatted_item->retail_price = $item['retail_price'];
                $formatted_item->discounted_price = $item['discounted_price'];
                $formatted_item->count = $item['count'];
                $formatted_item->total_price = $item['total_price'];
                $formatted_item->stocks = InventoryProductModel::where('inventory_product_id', $item['inventory_product_id'])
                    ->first()
                    ->stocks;

                // Encrypt arr_purchase_id
                $encrypted_purchase_ids = [];
                foreach ($item['arr_purchase_id'] as $purchase_id) {
                    $encrypted_purchase_ids[] = Crypt::encrypt($purchase_id);
                }
                $formatted_item->arr_purchase_id = $encrypted_purchase_ids;

                // items key
                $customer_data->items[] = $formatted_item;
            }
            // End item key group
            // *********************************** //

            $formatted_data[] = $customer_data;
        }

        // Prepare response
        $response_data = [
            'message' => 'Data retrieved successfully',
            'data' => $formatted_data,
        ];

        return response()->json($response_data, Response::HTTP_OK);
    }

    public function updateCustomerName(Request $request)
    {
        $merged_array = [];
        $result_update_logs_old_new = [];
        $arr_log_details = [];
        $file_name = '';
        $ctr = 0;

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            DB::rollBack();
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'purchase_group_id' => 'required|string',
            'user_id_customer' => 'required|string',
            'customer_name' => 'required|string',
            'eu_device' => 'required|string',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Start the transaction
        DB::beginTransaction();

        try {
            $decrypted_purchase_group_id = Crypt::decrypt($request->purchase_group_id);
            $decrypted_user_id_customer = Crypt::decrypt($request->user_id_customer);

            $purchases = PurchaseModel::where('purchase_group_id', $decrypted_purchase_group_id)
                ->where('user_id_customer', $decrypted_user_id_customer)
                ->get();

            if (!$purchases) {
                return response()->json(['message' => 'Purchase not found'], Response::HTTP_NOT_FOUND);
            }

            foreach ($purchases as $purchase) {
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
                    $purchase, // the model to update
                    $this->fillable_attr_purchase->arrUpdateCustomerName(), // fields to update
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
                    $purchase,
                    $this->fillable_attr_purchase->arrUpdateCustomerName(),
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

                $arr_purchase_id = [
                    'purchase_id' => [
                        'old' => $purchase->purchase_id,
                        'new' => $purchase->purchase_id,
                    ]
                ];

                $merged_array = array_merge($result_update_logs_old_new, $arr_purchase_id);

                // Holder Log
                $arr_log_details['update_customer_name-' . $ctr] = $merged_array;
                $ctr++;
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
                    'user_action' =>  'UPDATE_CUSTOMER_NAME',
                ],
                $arr_log_details,
                5,
                null
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Success update customer name',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteCustomer(Request $request)
    {
        $ctr = 0;
        $arr_log_details = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|string',
            'user_id' => 'required|string',
            'purchase_group_id' => 'required|string',
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
            $decrypted_payment_id = Crypt::decrypt($request->payment_id);
            $decrypted_user_id = Crypt::decrypt($request->user_id);
            $decrypted_purchase_group_id = Crypt::decrypt($request->purchase_group_id);

            $payment = PaymentModel::where('payment_id', $decrypted_payment_id)
                ->where('payment_id', $decrypted_payment_id)
                ->where('user_id', $decrypted_user_id)
                ->where('purchase_group_id', $decrypted_purchase_group_id)
                ->first();

            $arr_log_details['delete_customer_payment'][] = $payment->toArray();

            if (!$payment) {
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }


            // Start delete the vouchers_items_tbl and update on vouchers_items_tbl to AVAILABLE
            if ($payment->voucher_used_group_id != null) {
                $voucher_useds = VoucherUsedModel::where('voucher_used_group_id', $payment->voucher_used_group_id)->get();
                foreach ($voucher_useds as $voucher_used) {
                    $voucher_item = VoucherItemModel::where('voucher_code',  $voucher_used->voucher_code)->first();
                    if ($voucher_item) {
                        $voucher_item->update([
                            'status' => 'AVAILABLE'
                        ]);
                    }
                    $arr_log_details['delete_customer_voucher_used'][] = $voucher_used->toArray();
                    if (!$voucher_used->delete()) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to delete voucher on purchase'], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }
            }

            $purchases = PurchaseModel::where('user_id_customer', $decrypted_user_id)
                ->where('purchase_group_id', $decrypted_purchase_group_id)
                ->get();

            foreach ($purchases as $purchase) {
                $inventory_product = InventoryProductModel::where('inventory_id', $purchase->inventory_id)
                    ->where('inventory_product_id', $purchase->inventory_product_id)
                    ->first();

                $update_stock = $inventory_product->update([
                    'stocks' => $inventory_product->stocks + 1,
                ]);

                if (!$update_stock) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to update stock'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                // Holder Log
                $arr_log_details['delete_customer_purchase-' . $ctr][] = $purchase->toArray();

                // Delete the user
                if (!$purchase->delete()) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to delete purchase'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $ctr++;
            }

            // Delete the payment
            if (!$payment->delete()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete payment'], Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    'user_action' => 'DELETE_CUSTOMER',
                ],
                $arr_log_details,
                4,
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
                'message' => 'Successfully deleted data',
                // 'log_message' => $log_result
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getVoucherUses(Request $request, $id)
    {
        $crud_settings = $this->fillable_attr_voucher_used->getApiCrudSettings();
        $arr_all_data = [];

        // Validation rules for the request
        $validator = Validator::make($request->all(), [
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate EU Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        $voucher_uses = VoucherUsedModel::orderBy('created_at', 'desc')
            ->where('payment_id', Crypt::decrypt($id))
            ->get();

        foreach ($voucher_uses as $voucher_use) {
            $arr_container_datas = []; // Reset the container data for each voucher use

            // Store specific data in the array
            foreach ($this->fillable_attr_voucher_used->arrFieldsColumnHeader() as $arrFieldsColumnHeader) {
                // Fields to encrypt
                if (in_array($arrFieldsColumnHeader, $this->fillable_attr_voucher_used->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$arrFieldsColumnHeader] = Crypt::encrypt($voucher_use->$arrFieldsColumnHeader);
                }
                // Fields to convert date and time
                else if (in_array($arrFieldsColumnHeader, $this->fillable_attr_voucher_used->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$arrFieldsColumnHeader] = $this->helper->convertReadableDate($voucher_use->$arrFieldsColumnHeader);
                }
                // Just declare
                else {
                    $arr_container_datas[$arrFieldsColumnHeader] = $voucher_use->$arrFieldsColumnHeader;
                }
            }

            // Format API for CRUD actions
            $crud_action = $this->helper->formatApi(
                $crud_settings['prefix'],
                $crud_settings['payload'],
                $crud_settings['method'],
                $crud_settings['button_name'],
                $crud_settings['icon'],
                $crud_settings['container']
            );

            // Add the formatted API CRUD actions
            $arr_container_datas['actions'] = array_values($crud_action);

            // Add details on action CRUD
            foreach ($arr_container_datas['actions'] as &$action) {
                if (!isset($action['details'])) {
                    $action['details'] = [];
                }

                if ($action['button_name'] == 'Edit') {
                    // Populate details for each remaining attribute
                    foreach ($this->fillable_attr_voucher_used->arrFields() as $key => $arrDetails) {
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? "input",
                            'value' => $arr_container_datas[$key] ?? $arrDetails['value'],
                        ];

                        // Only add 'option' if it exists in $arrDetails and is an array (for select type)
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        $action['details'][] = $detail;
                    }
                }

                $action['voucher_used_id'] =  $arr_container_datas['voucher_used_id'];
            }

            unset($arr_container_datas['voucher_used_id']);

            // Add the container data to the overall data array
            $arr_all_data[] = $arr_container_datas;
        }

        // Add 'voucher_code' with the value from 'voucher_items_id'
        $arr_all_data = array_map(function ($item) {
            // Prepare a new item with the 'voucher_code' at the top
            $new_item = [];

            if (isset($item['voucher_items_id'])) {
                $new_item['voucher_code'] = $item['voucher_items_id'];
            }

            // Add the rest of the original keys
            foreach ($item as $key => $value) {
                if ($key !== 'voucher_items_id') {
                    $new_item[$key] = $value;
                }
            }

            return $new_item;
        }, $arr_all_data);

        // Check if the key exists and change it
        // Find the index of "voucher_items_id" and replace it with "voucher_code"
        $fields_to_change = $this->fillable_attr_voucher_used->arrFieldsColumnHeader();
        $index = array_search("voucher_items_id", $fields_to_change);
        if ($index !== false) {
            $fields_to_change[$index] = "voucher_code";
        }

        // Unset "voucher_used_id" | COLUMN
        $key = array_search("voucher_used_id", $fields_to_change);
        if ($key !== false) {
            unset($fields_to_change[$key]);
        }

        // Final response structure
        $response = [
            'voucher' => $arr_all_data,
            'columns' => $this->helper->transformColumnName($fields_to_change),
        ];

        return response()->json(
            [
                'message' => "Successfully retrieved data",
                'data' => $response
            ],
            Response::HTTP_OK
        );
    }

    public function addVoucher(Request $request)
    {
        $file_name = '';
        $arr_voucher_code = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for the request
        $validator = Validator::make($request->all(), [
            'purchase_group_id' => 'required|string',
            'user_id_customer' => 'required|string',
            'voucher_code' => '',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Custom check for voucher_code
        if (empty($request->input('voucher_code'))) {
            return response()->json(['message' => "The voucher code field is required."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate EU Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        $voucher_item = VoucherItemModel::where('voucher_code', $request->voucher_code)->first();
        if (!$voucher_item) {
            return response()->json(['message' => "Voucher code does not exist on voucher item."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if the voucher exists
        $voucher = VoucherModel::where('voucher_id', $voucher_item->voucher_id)->first();
        if ($voucher) {
            // Check if the current time is outside the valid expiration period
            $now = Carbon::now();
            $expiration_start = Carbon::parse($voucher->expiration_start_at);
            $expiration_end = Carbon::parse($voucher->expiration_end_at);

            if ($now->lt($expiration_start)) {
                // Voucher is not valid yet
                return response()->json(['message' => 'Voucher code is not valid yet. Available at ' . $this->helper->convertReadableDate($expiration_start)], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($now->gt($expiration_end)) {
                // Voucher is expired
                if ($voucher_item->status == 'AVAILABLE') {
                    // Update the voucher status to 'EXPIRED'
                    $voucher_item->update([
                        'status' => 'EXPIRED'
                    ]);
                }

                return response()->json(['message' => 'Voucher code is expired.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Check if voucher is expired
        if ($voucher_item->status == 'USED') {
            return response()->json(['message' => "Voucher code is already in use."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $voucher = VoucherModel::where('voucher_id', $voucher_item->voucher_id)->first();
        if (!$voucher) {
            return response()->json(['message' => "Voucher code does not exist voucher."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check on parent voucher is inactive
        if ($voucher->status == 'INACTIVE') {
            return response()->json(['message' => "Voucher code inactive."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Start the transaction
        DB::beginTransaction();

        try {
            $decrypted_purchase_group_id = Crypt::decrypt($request->purchase_group_id);
            $decrypted_user_id_customer = Crypt::decrypt($request->user_id_customer);

            // Validate if the cart exists for the voucher application
            $payment = PaymentModel::where([
                ['purchase_group_id', $decrypted_purchase_group_id],
                ['user_id_menu', $user->user_id],
                ['user_id', $decrypted_user_id_customer],
                ['status', 'NOT PAID']
            ])->first();

            if (!$payment) {
                return response()->json(['message' => 'Invalid IDs'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($payment->status == 'PAID') {
                return response()->json(['message' => 'Invalid status'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // If null then generate the voucher_used_group_id and use that on payment_tbl
            if ($payment->voucher_used_group_id == null) {
                $generate_voucher_id = $this->generateVoucherUsedGroupId();

                $validated_data = [
                    'voucher_used_group_id' => $generate_voucher_id,
                    'payment_id' => $payment->payment_id,
                    'voucher_code' =>  $voucher_item->voucher_code,
                    'discount_amount' => $voucher->discount_amount,
                ];

                // Merge the content and file or image
                $result_merge_data = $this->helper->arrMergeContentAndFile(
                    $request,
                    $validated_data,
                    $file_name,
                    '',
                );

                // Create instance with the selected attributes
                $result_to_create = $this->helper->arrStoreMultipleData(
                    $this->fillable_attr_voucher_used->arrToStores(),
                    $result_merge_data,
                    [],
                    [],
                    [],
                    [],
                );


                // Store data
                $created = VoucherUsedModel::create($result_to_create);
                if (!$created) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to store voucher used'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Update the unique I.D
                $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_voucher_used->idToUpdate(), Str::uuid());
                if ($update_unique_id) {
                    DB::rollBack();
                    return $update_unique_id;
                }

                // ************************** //
                // Update also on payment_tbl the voucher_used_group_id
                if (!$payment->update([
                    'voucher_used_group_id' => $created->voucher_used_group_id
                ])) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to update voucher used group id.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                /**  get all the voucher_code on voucher used model and check the value of it and 
                 *   update the voucher_total_discounted_amount and final_total_amount for computation
                 */
                $voucher_useds = VoucherUsedModel::where('payment_id', $payment->payment_id)
                    ->where('voucher_used_group_id', $payment->voucher_used_group_id)
                    ->get();

                foreach ($voucher_useds as $voucher_used) {
                    $arr_voucher_code[] = $voucher_used->voucher_code;
                }

                // Update also on payment_tbl the voucher_total_discounted_amount and final_total_amount
                $return_total_discount = $this->calculateTotalDiscount($arr_voucher_code);
                if (!$payment->update([
                    'voucher_total_discounted_amount' => $return_total_discount,
                    'final_total_amount' => $payment->total_amount - $return_total_discount,
                ])) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to update voucher total discounted amount and final total amount'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                // ************************** //

                // ************************** //
                // Update the voucher item
                if (!$voucher_item->update([
                    'status' => 'USED',
                ])) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to update status on voucher item.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                // ************************** //
            }


            // Store voucher used no generating id
            else {
                $validated_data = [
                    'payment_id' => $payment->payment_id,
                    'voucher_code' =>  $voucher_item->voucher_code,
                    'voucher_used_group_id' => $payment->voucher_used_group_id,
                    'discount_amount' => $voucher->discount_amount,
                ];

                // Merge the content and file or image
                $result_merge_data = $this->helper->arrMergeContentAndFile(
                    $request,
                    $validated_data,
                    $file_name,
                    '',
                );

                // *********************************** //
                // Start Store
                // Create instance with the selected attributes
                $result_to_create = $this->helper->arrStoreMultipleData(
                    $this->fillable_attr_voucher_used->arrToStores(),
                    $result_merge_data,
                    [],
                    [],
                    []
                );

                // Store data
                $created = VoucherUsedModel::create($result_to_create);
                if (!$created) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to store voucher used'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Update the unique I.D
                $update_unique_id = $this->helper->updateUniqueId(
                    $created,
                    $this->fillable_attr_voucher_used->idToUpdate(),
                    Str::uuid()
                );
                if ($update_unique_id) {
                    DB::rollBack();
                    return $update_unique_id;
                }
                // End Store
                // *********************************** //

                /**  get all the voucher_code on voucher used model and check the value of it and 
                 *   update the voucher_total_discounted_amount and final_total_amount for computation
                 */

                $voucher_useds = VoucherUsedModel::where('payment_id', $payment->payment_id)
                    ->where('voucher_used_group_id', $payment->voucher_used_group_id)
                    ->get();
                foreach ($voucher_useds as $voucher_used) {
                    $arr_voucher_code[] = $voucher_used->voucher_code;
                }

                // Update also on payment_tbl the voucher_total_discounted_amount
                $return_total_discount = $this->calculateTotalDiscount($arr_voucher_code);
                if (!$payment->update([
                    'voucher_total_discounted_amount' => $return_total_discount,
                    'final_total_amount' => $payment->total_amount - $return_total_discount,
                ])) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to update voucher total discounted amount and final total amount'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Update the voucher item
                if (!$voucher_item->update([
                    'status' => 'USED',
                ])) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to update status on voucher item.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
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
                    'user_action' => 'ADD_VOUCHER_ON_MENU',
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

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Voucher added successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateVoucher(Request $request)
    {
        $file_name = '';
        $arr_voucher_code = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for the request
        $validator = Validator::make($request->all(), [
            'voucher_used_id' => 'required|string',
            'voucher_code' => '',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Custom check for voucher_code
        if (empty($request->input('voucher_code'))) {
            return response()->json(['message' => "The voucher code field is required."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate EU Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }


        // Start the transaction
        DB::beginTransaction();
        try {
            $decrypted_voucher_used_id = Crypt::decrypt($request->voucher_used_id);
            $voucher_used = VoucherUsedModel::where('voucher_used_id', $decrypted_voucher_used_id)->first();
            if (!$voucher_used) {
                return response()->json(['message' => "Voucher used id not found."], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $arr_voucher_used_old = $voucher_used->toArray();

            $voucher_item = VoucherItemModel::where('voucher_code', $request->voucher_code)->first();
            if (!$voucher_item) {
                return response()->json(['message' => "Voucher code does not exist on voucher item."], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check if voucher is already used
            if ($voucher_item->status == 'USED') {
                return response()->json(['message' => "Voucher code is already in use."], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $voucher = VoucherModel::where('voucher_id', $voucher_item->voucher_id)->first();
            if (!$voucher) {
                return response()->json(['message' => "Voucher code does not exist voucher."], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            // Check on parent voucher is inactive
            if ($voucher->status == 'INACTIVE') {
                return response()->json(['message' => "Voucher code inactive."], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Validate if the cart exists for the voucher application
            $payment = PaymentModel::where('payment_id', $voucher_used->payment_id)->first();

            if (!$payment) {
                return response()->json(['message' => 'Invalid payment id'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if ($payment->status == 'PAID') {
                return response()->json(['message' => 'Invalid status'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validated_data = [
                'discount_amount' => $voucher->discount_amount,
                'voucher_code' => $request->voucher_code,
            ];

            // Merge the content and file or image
            $result_merge_data = $this->helper->arrMergeContentAndFile(
                $request,
                $validated_data,
                $file_name,
                '',
            );

            // *********************************** //
            // Start checking changes
            // Get the changes of the fields
            $result_update_logs_old_new = $this->helper->updateLogsOldNew(
                $voucher_used, // the model to update
                $this->fillable_attr_voucher_used->arrToUpdates(), // fields to update
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
                $voucher_used,
                $this->fillable_attr_voucher_used->arrToUpdates(),
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
                    'user_action' => 'UPDATE_VOUCHER_ON_MENU',
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

            // Update the status of voucher_code on voucher_item_child
            if (!VoucherItemModel::where('voucher_code', $arr_voucher_used_old['voucher_code'])
                ->update([
                    'status' => 'AVAILABLE'
                ])) {
                return response()->json(['message' => 'Failed to update status voucher item'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            /**  get all the voucher_code on voucher used model and check the value of it and 
             *   update the voucher_total_discounted_amount and final_total_amount for computation
             */
            $voucher_useds = VoucherUsedModel::where('payment_id', $payment->payment_id)
                ->where('voucher_used_group_id', $payment->voucher_used_group_id)
                ->get();
            foreach ($voucher_useds as $voucher_used) {
                $arr_voucher_code[] = $voucher_used->voucher_code;
            }

            // Update also on payment_tbl the voucher_total_discounted_amount
            $return_total_discount = $this->calculateTotalDiscount($arr_voucher_code);
            if (!$payment->update([
                'voucher_total_discounted_amount' => $return_total_discount,
                'final_total_amount' => $payment->total_amount - $return_total_discount,
            ])) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to update voucher total discounted amount and final total amount'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update the voucher item
            if (!$voucher_item->update([
                'status' => 'USED',
            ])) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to update status on voucher item.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Voucher update successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyVoucher(Request $request)
    {
        $arr_holder_voucher_used = [];
        $arr_voucher_code = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for the request
        $validator = Validator::make($request->all(), [
            'voucher_used_id' => 'required|string',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate EU Device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        $decrypted_voucher_used_id = Crypt::decrypt($request->voucher_used_id);
        $voucher_used = VoucherUsedModel::where('voucher_used_id', $decrypted_voucher_used_id)->first();
        if (!$voucher_used) {
            return response()->json(['message' => "Voucher used id not found."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Start the transaction
        DB::beginTransaction();
        try {
            $arr_holder_voucher_used = $voucher_used->toArray();

            // Delete the voucher
            if ($voucher_used->delete()) {
                $payment = PaymentModel::where('payment_id', $arr_holder_voucher_used["payment_id"])->first();

                /**  
                 *   get all the voucher_code on voucher used model and check the value of it and 
                 *   update the voucher_total_discounted_amount and final_total_amount for computation
                 */
                $get_voucher_useds = VoucherUsedModel::where('payment_id', $payment->payment_id)
                    ->where('voucher_used_group_id', $payment->voucher_used_group_id)
                    ->get();
                foreach ($get_voucher_useds as $get_voucher_used) {
                    $arr_voucher_code[] = $get_voucher_used->voucher_code;
                }

                $return_total_discount = $this->calculateTotalDiscount($arr_voucher_code);

                if ($return_total_discount != 0) {
                    if (!$payment->update([
                        'voucher_total_discounted_amount' => $return_total_discount,
                        'final_total_amount' => $payment->total_amount - $return_total_discount,
                    ])) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to update voucher total discounted amount and final total amount'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // Update the voucher item status
                    VoucherItemModel::where('voucher_code', $arr_holder_voucher_used["voucher_code"])->update([
                        'status' => 'AVAILABLE',
                    ]);
                } else {
                    if (!$payment->update([
                        'voucher_used_group_id' => null,
                        'voucher_total_discounted_amount' => 0.00,
                        'final_total_amount' => null,
                    ])) {
                        DB::rollBack();
                        return response()->json(['message' => 'Failed to update voucher total discounted amount, final total amount and voucher used group id'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // Update the voucher item status
                    VoucherItemModel::where('voucher_code', $arr_holder_voucher_used["voucher_code"])->update([
                        'status' => 'AVAILABLE',
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
                        'user_action' => 'DELETE_VOUCHER_ON_MENU',
                    ],
                    $arr_holder_voucher_used,
                    3,
                    null
                );

                // Failed to create log
                if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                    DB::rollBack(); // Rollback transaction
                    return $log_result;
                }
                // End log
                // *********************************** //
            } else {
                DB::rollBack();
                return response()->json(['message' => 'Failed to delete voucher used'], Response::HTTP_UNPROCESSABLE_ENTITY);
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

    // Child Add voucher | Update Voucher
    private function calculateTotalDiscount(array $voucher_items)
    {

        $total_discount = 0;
        foreach ($voucher_items as $voucher_code) {
            $voucher_item = VoucherUsedModel::where('voucher_code', $voucher_code)->first();

            $total_discount += $voucher_item->discount_amount;
        }
        return $total_discount;
    }

    // CHILD store
    private function generateGroupPurchaseId()
    {
        return 'purchase_group_id-' . Str::uuid();
    }

    // CHILD store
    private function generateCustomerId()
    {
        return "customer-" . Str::uuid();
    }

    // CHILD addVoucher
    private function generateVoucherUsedGroupId()
    {
        return "voucher_used_group_id-" . Str::uuid();
    }

    // CHILD store
    private function totalAmountPayment($purchase_group_id, $customer_id)
    {
        $total_amount = 0.00;
        $total_discounted_amount = 0.00;
        $arr_to_data = [];

        // Retrieve all purchases with the given purchase group ID  
        $purchases = PurchaseModel::where('purchase_group_id', $purchase_group_id)
            ->where('user_id_customer', $customer_id)
            ->get();

        foreach ($purchases as $purchase) {
            $inventory_product = InventoryProductModel::where('inventory_product_id', $purchase->inventory_product_id)
                ->where('inventory_id', $purchase->inventory_id)
                ->first();

            if (!$inventory_product) {
                // Inventory product not found for the current purchase
                return response()->json(['message' => 'Inventory product not found for purchase ID ' . $purchase->id], Response::HTTP_NOT_FOUND);
            }

            // Add the price of the inventory product to the total amount
            $total_amount += $purchase->discounted_price != 0.00 ? $purchase->discounted_price : $purchase->retail_price;
            $total_discounted_amount += $purchase->discounted_price;
        }

        // This is the amount of user going to pay
        $arr_to_data['total_amount'] = $total_amount;
        // Just computation save of user from discount
        $arr_to_data['total_discounted_amount'] = $total_discounted_amount;

        // Return the total amount
        return $arr_to_data;
    }

    // CHILD store
    private function minusStock($inventory_product_id)
    {
        // Start the transaction
        DB::beginTransaction();

        try {
            $inventory_product = InventoryProductModel::where('inventory_product_id', $inventory_product_id)
                ->first();
            if (!$inventory_product) {
                // Rollback the transaction if inventory product not found
                DB::rollBack();
                return response()->json(['message' => 'Inventory Product ID not found'], Response::HTTP_NOT_FOUND);
            }

            // Perform the stock deduction
            $updated = $inventory_product->update([
                'stocks' => $inventory_product->stocks - 1,
            ]);

            if (!$updated) {
                // Rollback the transaction if failed to update stock
                DB::rollBack();
                return response()->json(['message' => 'Failed to update new stocks'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Stocks deducted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // CHILD deleteALl
    private function totalAmountPaymentDeleteAll($purchase_group_id, $customer_id)
    {
        $total_amount = 0.00;
        $total_discounted_amount = 0.00;
        $arr_to_data = [];

        // Start transaction
        DB::beginTransaction();

        // Retrieve all purchases with the given purchase group ID
        $purchases = PurchaseModel::where('purchase_group_id', $purchase_group_id)
            ->where('user_id_customer', $customer_id)
            ->get();

        if ($purchases->isEmpty()) {
            // Rollback the transaction if no purchases found
            DB::rollBack();
            $arr_to_data['total_amount'] = 0.00;
            $arr_to_data['total_discounted_amount'] = 0.00;

            return $arr_to_data;
        }

        foreach ($purchases as $purchase) {
            $inventory_product = InventoryProductModel::where('inventory_product_id', $purchase->inventory_product_id)
                ->first();

            if (!$inventory_product) {
                // Rollback the transaction if inventory product not found for any purchase
                DB::rollBack();
                return response()->json(['message' => 'Inventory product not found for purchase ID ' . $purchase->id], Response::HTTP_NOT_FOUND);
            }

            // Add the price of the inventory product to the total amount
            $total_amount += $purchase->discounted_price != 0.00 ? $purchase->discounted_price : $purchase->retail_price;
            $total_discounted_amount += $purchase->discounted_price;
        }

        // Commit the transaction if all purchases are processed successfully
        DB::commit();


        $arr_to_data['total_amount'] = $total_amount;
        $arr_to_data['total_discounted_amount'] = $total_discounted_amount;

        // Return the total amount
        return $arr_to_data;
    }

    // Child of getUserIdMenuCustomer
    private function getCrudOfVoucherUsed($voucher_uses)
    {
        $crud_settings = $this->fillable_attr_voucher_used->getApiCrudSettings();

        foreach ($voucher_uses as $voucher_use) {
            $arr_container_datas = []; // Reset the container data for each voucher use

            // Store specific data in the array
            foreach ($this->fillable_attr_voucher_used->arrFieldsColumnHeader() as $arrFieldsColumnHeader) {
                // Fields to encrypt
                if (in_array($arrFieldsColumnHeader, $this->fillable_attr_voucher_used->arrToConvertIdsToEncrypted())) {
                    $arr_container_datas[$arrFieldsColumnHeader] = Crypt::encrypt(value: $voucher_use->$arrFieldsColumnHeader);

                    // $arr_container_datas[$arrFieldsColumnHeader] =  $voucher_use->$arrFieldsColumnHeader;
                }
                // Fields to convert date and time
                else if (in_array($arrFieldsColumnHeader, $this->fillable_attr_voucher_used->arrToConvertToReadableDateTime())) {
                    $arr_container_datas[$arrFieldsColumnHeader] = $this->helper->convertReadableDate($voucher_use->$arrFieldsColumnHeader);
                }
                // Just declare
                else {
                    $arr_container_datas[$arrFieldsColumnHeader] = $voucher_use->$arrFieldsColumnHeader;
                }
            }

            // Format API for CRUD actions
            $crud_action = $this->helper->formatApi(
                $crud_settings['prefix'],
                $crud_settings['payload'],
                $crud_settings['method'],
                $crud_settings['button_name'],
                $crud_settings['icon'],
                $crud_settings['container']
            );

            // Add the formatted API CRUD actions
            $arr_container_datas['actions'] = array_values($crud_action);

            // Add details on action CRUD
            foreach ($arr_container_datas['actions'] as &$action) {
                if (!isset($action['details'])) {
                    $action['details'] = [];
                }

                if ($action['button_name'] == 'Edit') {
                    // Populate details for each remaining attribute
                    foreach ($this->fillable_attr_voucher_used->arrFields() as $key => $arrDetails) {
                        $detail = [
                            'label' => $arrDetails['label'],
                            'type' => $arrDetails['type'] ?? "input",
                            'value' => $arr_container_datas[$key] ?? $arrDetails['value'],
                        ];

                        // Only add 'option' if it exists in $arrDetails and is an array (for select type)
                        if (isset($arrDetails['option']) && is_array($arrDetails['option'])) {
                            $detail['option'] = $arrDetails['option'];
                        }

                        $action['details'][] = $detail;
                    }
                }

                $action['voucher_used_id'] =  $arr_container_datas['voucher_used_id'];
            }

            // Add the container data to the overall data array
            $arr_all_data[] = $arr_container_datas;
        }

        // Add 'voucher_code' with the value from 'voucher_items_id'
        $arr_all_data = array_map(function ($item) {
            // Prepare a new item with the 'voucher_code' at the top
            $new_item = [];

            if (isset($item['voucher_items_id'])) {
                $new_item['voucher_code'] = $item['voucher_items_id'];
            }

            // Add the rest of the original keys
            foreach ($item as $key => $value) {
                if ($key !== 'voucher_items_id') {
                    $new_item[$key] = $value;
                }
            }

            return $new_item;
        }, $arr_all_data);

        // Check if the key exists and change it
        // Find the index of "voucher_items_id" and replace it with "voucher_code"
        $fields_to_change = $this->fillable_attr_voucher_used->arrFieldsColumnHeader();
        $index = array_search("voucher_items_id", $fields_to_change);
        if ($index !== false) {
            $fields_to_change[$index] = "voucher_code";
        }

        // Unset "voucher_used_id" | COLUMN
        $key = array_search("voucher_used_id", $fields_to_change);
        if ($key !== false) {
            unset($fields_to_change[$key]);
        }

        // Final response structure
        return $arr_all_data;
    }
}
