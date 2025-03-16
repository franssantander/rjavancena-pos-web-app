<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherUsedModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'vouchers_used_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'voucher_used_id',
        'voucher_used_group_id',
        'payment_id',
        'voucher_code',
        'discount_amount',
        'created_at',
        'updated_at',
    ];
    protected $dates = ['deleted_at'];

    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    public function arrToStores(): array
    {
        return [
            'voucher_used_group_id',
            'payment_id',
            'voucher_code',
            'discount_amount',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'discount_amount',
            'voucher_code',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'voucher_used_id' => 'voucher_used_id-',
        ];
    }

    public function arrExemptedToDecrypt(): array
    {
        return [
            'payment_id'
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'voucher_used_id',
        ];
    }

    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    public function arrFieldsColumnHeader(): array
    {
        return [
            'voucher_used_id',
            'voucher_code',
            'discount_amount',
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'purchase/';

        $payload = [
            // 'update-voucher' => ['voucher_used_id', 'voucher_code', 'eu_device'],
            'delete-voucher' => ['voucher_used_id', 'eu_device'],
        ];
        $method = [
            // 'update-voucher' => 'POST',
            'delete-voucher' => 'DELETE',
        ];
        $button_name = [
            // 'update-voucher' => 'edit',
            'delete-voucher' => 'delete',
        ];
        $icon = [
            // 'update-voucher' => "radix-icons:pencil-2",
            'delete-voucher' =>  "radix-icons:cross-circled",
        ];
        $container = [
            // 'update-voucher' => 'modal',
            'delete-voucher' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function arrFields(): array
    {
        return [
            'voucher_code' => [
                'label' => "Voucher Code",
                'type' => "input",
                'value' =>  null
            ],
        ];
    }
}
