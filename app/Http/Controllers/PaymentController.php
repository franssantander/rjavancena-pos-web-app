<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\PaymentModel;
use Illuminate\Http\Request;
use App\Models\PurchaseModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{


    protected $helper, $fillable_attr_purchase, $fillable_attr_inventory_product, $fillable_attr_payment;

    public function __construct(Helper $helper, PurchaseModel $fillable_attr_purchase, InventoryProductModel $fillable_attr_inventory_product, PaymentModel $fillable_attr_payment)
    {
        $this->helper = $helper;
        $this->fillable_attr_purchase = $fillable_attr_purchase;
        $this->fillable_attr_inventory_product = $fillable_attr_inventory_product;
        $this->fillable_attr_payment = $fillable_attr_payment;
    }

    // TODO: Logs payment
    public function payment(Request $request)
    {
        $status = 'PAID';
        $arr_inventory_product_id = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|string',
            'purchase_group_id' => 'required|string',
            'user_id' => 'required|string',
            'money' => 'required|numeric|min:1',
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

        // Start a transaction
        DB::beginTransaction();
        try {
            $decrypted_payment_id = Crypt::decrypt($request->payment_id);
            $decrypted_purchase_group_id = Crypt::decrypt($request->purchase_group_id);
            $decrypted_user_id_customer = Crypt::decrypt($request->user_id);

            $payment = PaymentModel::where('payment_id', $decrypted_payment_id)
                ->where('user_id', $decrypted_user_id_customer)
                ->where('purchase_group_id', $decrypted_purchase_group_id)
                ->where('status', 'NOT PAID')
                ->first();

            if (!$payment) {
                DB::rollBack();
                return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
            }

            $final_total = $payment->final_total_amount ?? $payment->total_amount;
            if ($final_total > $request->money) {
                DB::rollBack();
                return response()->json(['message' => 'Please input an amount greater than your purchase total amount.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $change = $request->money - $final_total;
            $paying = $payment->update([
                'money' => $request->money,
                'change' => $change,
                'status' => $status,
                'paid_at' => Carbon::now()
            ]);

            if (!$paying) {
                // Rollback the transaction and return the error response
                DB::rollBack();
                return response()->json(['message' => 'Failed to pay purchase.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $purchase_update = PurchaseModel::where('user_id_customer', $decrypted_user_id_customer)
                ->where('purchase_group_id',  $decrypted_purchase_group_id)
                ->update([
                    'status' => $status,
                ]);

            if (!$purchase_update) {
                // Rollback the transaction and return the error response
                DB::rollBack();
                return response()->json(['message' => 'Failed to pay purchase.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Store logs for create Purchase
            $user_logs['payment'] = $payment->toArray();

            // Get the items has been update status to PAID
            $ctr = 0;
            $purchase_items = PurchaseModel::where('user_id_customer', $decrypted_user_id_customer)
                ->where('purchase_group_id',  $decrypted_purchase_group_id)->where('status',  'PAID')->get();
            foreach ($purchase_items as $purchase_item) {
                $user_logs['items_purchase-' . $ctr] = $purchase_item->toArray();
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
                    'user_action' => 'PAYMENT',
                ],
                $user_logs,
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

            $purchases = PurchaseModel::where('user_id_customer', $decrypted_user_id_customer)
                ->where('purchase_group_id',  $decrypted_purchase_group_id)
                ->get();

            foreach ($purchases as $purchase) {
                if (!in_array($purchase->inventory_product_id, $arr_inventory_product_id)) {
                    $arr_inventory_product_id[] = $purchase->inventory_product_id;
                }
            }

            // Send the Ids of $arr_inventory_product_id to make a notification
            app(NotificationController::class)->storeStock($arr_inventory_product_id);

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Purchase successfully paid.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an exception
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while processing your payment.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function payment(Request $request)
    // {
    //     $status = 'PAID';
    //     $arr_inventory_product_id = [];

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     // Validation rules for each item in the array
    //     $validator = Validator::make($request->all(), [
    //         'payment_id' => 'required|string',
    //         'purchase_group_id' => 'required|string',
    //         'user_id' => 'required|string',
    //         'money' => 'required|numeric|min:1',
    //         'eu_device' => 'required|string',
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

    //     // Validate Eu Device
    //     $result_validate_eu_device = $this->helper->validateEuDevice($request->eu_device);
    //     if ($result_validate_eu_device) {
    //         return $result_validate_eu_device;
    //     }

    //     // Start a transaction
    //     DB::beginTransaction();
    //     try {
    //         $decrypted_payment_id = Crypt::decrypt($request->payment_id);
    //         $decrypted_purchase_group_id = Crypt::decrypt($request->purchase_group_id);
    //         $decrypted_user_id_customer = Crypt::decrypt($request->user_id);

    //         $payment = PaymentModel::where('payment_id', $decrypted_payment_id)
    //             ->where('user_id', $decrypted_user_id_customer)
    //             ->where('purchase_group_id', $decrypted_purchase_group_id)
    //             ->where('status', 'NOT PAID')
    //             ->first();

    //         if (!$payment) {
    //             // Rollback the transaction and return the error response
    //             DB::rollBack();
    //             return response()->json(['message' => 'Data not found'], Response::HTTP_NOT_FOUND);
    //         }

    //         if ($payment->total_amount > $request->money) {
    //             // Rollback the transaction and return the error response
    //             DB::rollBack();
    //             return response()->json(['message' => 'Please input an amount greater than your purchase total amount.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    //         }

    //         $paying = $payment->update([
    //             'money' => $request->money,
    //             'change' => $request->money - $payment->total_amount,
    //             'status' => $status,
    //             'paid_at' => Carbon::now()
    //         ]);

    //         if (!$paying) {
    //             // Rollback the transaction and return the error response
    //             DB::rollBack();
    //             return response()->json(['message' => 'Failed to pay purchase.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }

    //         $purchase_update = PurchaseModel::where('user_id_customer', $decrypted_user_id_customer)
    //             ->where('purchase_group_id',  $decrypted_purchase_group_id)
    //             ->update([
    //                 'status' => $status,
    //             ]);

    //         if (!$purchase_update) {
    //             // Rollback the transaction and return the error response
    //             DB::rollBack();
    //             return response()->json(['message' => 'Failed to pay purchase.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //         }

    //         // Get the items has been update status to PAID
    //         $purchase_items = PurchaseModel::where('user_id_customer', $decrypted_user_id_customer)
    //             ->where('purchase_group_id',  $decrypted_purchase_group_id)->where('status',  'PAID')->get();
    //         foreach ($purchase_items as $purchase_item) {
    //             $items_paid[] = $purchase_item;
    //         }

    //         $user_logs['payment'] = [$payment];
    //         $user_logs['items'] = $items_paid;
    //         $eu_device = $request->input('eu_device');

    //         // Logs
    //         $log_result = $this->helper->log(
    //             $request,
    //             [
    //                 'user_device' => $eu_device,
    //                 'user_id' => $user->user_id,
    //                 'is_history' => 0,
    //                 'user_action' => 'PAID',
    //             ]
    //         );
    //         if ($log_result->getStatusCode() !== Response::HTTP_OK) {
    //             DB::rollBack();
    //             return $log_result;
    //         }

    //         $purchases = PurchaseModel::where('user_id_customer', $decrypted_user_id_customer)
    //             ->where('purchase_group_id',  $decrypted_purchase_group_id)
    //             ->get();

    //         foreach ($purchases as $purchase) {
    //             if (!in_array($purchase->inventory_product_id, $arr_inventory_product_id)) {
    //                 $arr_inventory_product_id[] = $purchase->inventory_product_id;
    //             }
    //         }

    //         // Send the Ids of $arr_inventory_product_id to make a notification
    //         app(NotificationController::class)->storeStock($arr_inventory_product_id);

    //         // Commit the transaction
    //         DB::commit();

    //         return response()->json(['message' => 'Purchase successfully paid.'], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         // Rollback the transaction in case of an exception
    //         DB::rollBack();
    //         return response()->json(['message' => 'An error occurred while processing your payment.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function receipt(Request $request)
    {
        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'purchase_group_id' => 'required|string',
            'user_id' => 'required|string',
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

        $purchases = PurchaseModel::where('purchase_group_id', $request->purchase_group_id)
            ->where('user_id_customer', $request->user_id)
            ->where('status', 'PAID')
            ->get();

        $fields = $this->fillable_attr_payment->arrFieldsPaymentReceipt();
        $arr_purchases = [];
        $final_total_price = 0; // Initialize final_total_price

        foreach ($purchases as $purchase) {
            $key = "{$purchase->name}-{$purchase->retail_price}-{$purchase->discounted_price}";

            if (!isset($arr_purchases[$key])) {
                $arr_purchases[$key] = [
                    'name' => $purchase->name,
                    'retail_price' => $purchase->retail_price,
                    'discounted_price' => $purchase->discounted_price,
                    'qty' => 0,
                    'total_price' => 0,
                ];
            }

            $arr_purchases[$key]['qty']++;

            if ($purchase->discounted_price == 0.00) {
                $arr_purchases[$key]['total_price'] += $purchase->retail_price;
            } else {
                $arr_purchases[$key]['total_price'] += $purchase->discounted_price;
            }

            // Accumulate total_price into final_total_price
            $final_total_price += $arr_purchases[$key]['total_price'];
        }

        // Reindex the array
        $arr_purchases = array_values($arr_purchases);

        $final_total_price;

        $pdf = PDF::loadView('pdf.receipt.receipt', compact('arr_purchases', 'final_total_price'))
            ->setPaper([0, 0, 300, 600])
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);;

        // Save or download the PDF
        return $pdf->download('receipt' . Carbon::now()->format('YmdHis') . '.pdf');
    }
}
