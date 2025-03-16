<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'payment_tbl';
    protected $primaryKey = 'id';
    protected  $fillable = [
        'payment_id',

        'user_id',
        'purchase_group_id',
        'user_id_menu',

        'payment_method',

        'voucher_used_group_id',
        'voucher_total_discounted_amount',

        'total_discounted_amount',
        'total_amount',
        'final_total_amount',

        'money',
        'change',
        'status',

        'paid_at',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $dates = ['deleted_at'];

    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    public function arrToUpdates(): array
    {
        return [
            'money',
            'change',
            'status',
            'paid_at',
        ];
    }

    public function getTodaysTranction(): array
    {
        return [
            'user_id',
            'paid_at',
            'total_amount',
            'status',
        ];
    }

    public function getRecentTransaction(): array
    {
        return [
            'user_id',
            'paid_at',
            'total_amount',
            'status',
        ];
    }

    public function arrRecentTransactionPayload(): array
    {
        return [
            'payment_id',
            'user_id',
            'purchase_group_id',
            'status',
        ];
    }


    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'created_at',
            'updated_at',
            'deleted_at',
            'paid_at'
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'dashboard/';
        $payload = [
            'update-status-void' => ['payment_id', 'user_id', 'purchase_group_id', 'status', 'eu_device'],
            'view' => ['eu_device'],
        ];
        $method = [
            'update-status-void' => 'POST',
            'view' => 'POST',
        ];
        $button_name = [
            'update-status-void' => 'void',
            'view' => 'view',
        ];
        $icon = [
            'update-status-void' => "radix-icons:pencil-1",
            'view' =>  "radix-icons:eye-open",
        ];
        $container = [
            'update-status-void' => 'none',
            'view' =>  'none'
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function columnHeader(): array
    {
        return [
            'user_id',
            'customer_name',
            // 'cashier_image',
            // 'cashier_name',
            'paid_at',
            'total_amount',
            'status',
            'actions',
        ];
    }

    public function statusColorRecentTransaction(): array
    {
        return [
            'PAID' => 'success',
            'NOT PAID' => 'warning',
        ];
    }

    public function arrFieldsPaymentReceipt(): array
    {
        return [
            'name',
            'retail_price',
            'discounted_price',
        ];
    }
}
