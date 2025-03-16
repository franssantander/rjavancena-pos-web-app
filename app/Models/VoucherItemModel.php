<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherItemModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'vouchers_items_tbl';

    protected $primaryKey = 'id';
    protected $fillable = [
        'voucher_items_id',
        'voucher_id',
        'voucher_code',
        'status',
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
            'voucher_id',
            'voucher_code',
            'status',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'voucher_code',
            'status',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'voucher_items_id' => 'voucher_item_id-',
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'voucher_items_id',
            'voucher_id',
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

    public function arrModelWithId(): array
    {
        return [
            'VoucherItemModel'  => ['voucher_id']
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'voucher/item/';

        $payload = [
            'update' => $this->arrToUpdates(),
            'delete' => ['voucher_items_id', 'eu_device']
        ];
        $method = [
            'update' => 'POST',
            'delete' => 'DELETE',
        ];
        $button_name = [
            'update' => 'edit',
            'delete' => 'delete',
        ];
        $icon = [
            'update' => "radix-icons:pencil-1",
            'delete' =>  "radix-icons:trash",
        ];
        $container = [
            'update' => 'modal',
            'delete' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getApiRelativeSettings()
    {
        $prefix = 'voucher/item/';
        $payload = [
            'store' => $this->arrToStores()
        ];

        $method = [
            'store' => 'POST',
        ];

        $button_name = [
            'store' => 'Create voucher',
        ];

        $icon = [
            'store' => null,
        ];

        $container = [
            'store' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function arrFields(): array
    {
        return [
            'voucher_code' => [
                'label' => "Voucher Code",
                'type' => "input",
                'value' =>  null,
            ],
        ];
    }

    public function getViewRowTable()
    {
        // $prefix = 'inventory/parent/';
        // $url = $prefix . 'product/show/';
        $url = '';
        $method = 'GET';
        return compact('url',  'method');
    }

    public function unsetActions(): array
    {
        return [
            'delete',
        ];
    }

    public function arrFieldsValueToUpperCase(): array
    {
        return [
            'voucher_code',
            'status',
        ];
    }

    public function arrFieldsColumnHeader(): array
    {
        return [
            'voucher_code',
            'status',
            'created_at',
            'updated_at',
            'actions',
        ];
    }

    public function arrPayloadIdsToDecrypt(): array
    {
        return [
            'voucher_id',
        ];
    }
}
