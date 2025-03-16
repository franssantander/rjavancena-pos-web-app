<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'vouchers_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'voucher_id',
        'name',
        'discount_amount',
        'expiration_start_at',
        'expiration_end_at',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $dates = ['deleted_at'];

    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    public function arrFieldsColumnHeader(): array
    {
        return [
            'name',
            'discount_amount',
            'expiration_start_at',
            'expiration_end_at',
            'status',
            'used',
            'available',
            'total_vouchers',
            'actions',
        ];
    }

    public function arrToStores(): array
    {
        return [
            'name',
            'discount_amount',
            'expiration_start_at',
            'expiration_end_at',
            'status',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'name',
            'discount_amount',
            'expiration_start_at',
            'expiration_end_at',
            'status',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'voucher_id' => 'voucher_id-',
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'voucher/parent/';

        // Get the current update payload
        $updatePayload = $this->arrToUpdates();
        // Insert 'voucher_id' into the update payload
        array_unshift($updatePayload, 'voucher_id');

        $payload = [
            'view' => '',
            'update' => $updatePayload,
            'delete' => ['voucher_id', 'eu_device'],
        ];
        $method = [
            'view' => 'GET',
            'update' => 'POST',
            'delete' => 'DELETE',
        ];
        $button_name = [
            'view' => 'View details',
            'update' => 'edit',
            'delete' => 'delete',
        ];
        $icon = [
            'view' =>  "",
            'update' => "radix-icons:pencil-2",
            'delete' =>  "radix-icons:trash",
        ];
        $container = [
            'view' => 'page',
            'update' => 'modal',
            'delete' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getApiRelativeSettings()
    {
        $prefix = 'voucher/parent/';
        $payload = [
            'store' => ['name', 'discount_amount', 'expiration_start_at', 'expiration_end_at', 'status', 'eu_device']
        ];

        $method = [
            'store' => 'POST',
        ];

        $button_name = [
            'store' => 'New voucher',
        ];

        $icon = [
            'store' => 'tabler:plus',
        ];

        $container = [
            'store' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getViewRowTable()
    {
        // $prefix = 'inventory/parent/';
        // $url = $prefix . 'product/show/';
        $url = '';
        $method = 'GET';
        return compact('url',  'method');
    }

    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'expiration_start_at',
            'expiration_end_at',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'voucher_id',
        ];
    }


    public function arrModelWithId(): array
    {
        return [
            'VoucherItemModel'  => ['voucher_id']
        ];
    }

    public function arrFields(): array
    {
        return [
            'name' => [
                'label' => "Name",
                'type' => "input",
                'value' =>  null
            ],
            'discount_amount' => [
                'label' => "Discount amount",
                'type' => "number",
                'value' =>  null
            ],
            'expiration_start_at' => [
                'label' => "Expiration start at",
                'type' => "date",
                'value' =>  null
            ],
            'expiration_end_at' => [
                'label' => "Expiration end at",
                'type' => "date",
                'value' =>  null
            ],
            'generate_vouchers' => [
                'label' => "Generate voucher",
                'type' => "select",
                'value' =>  null,
                'option' => [
                    [
                        'label' => 'Yes',
                        'value' => 'YES'
                    ],
                    [
                        'label' => 'No',
                        'value' => 'NO'
                    ],
                ],
            ],
            'counter_vouchers' => [
                'label' => "Counter voucher",
                'type' => "number",
                'value' =>  null
            ],
            'status' => [
                'label' => "Status",
                'type' => "select",
                'value' =>  null,
                'option' => [
                    [
                        'label' => 'Active',
                        'value' => 'ACTIVE'
                    ],
                    [
                        'label' => 'In active',
                        'value' => 'INACTIVE'
                    ],
                ],
            ],
        ];
    }

    public function arrFieldsValueToUpperCase(): array
    {
        return [
            'voucher_code',
            'status',
        ];
    }

    public function unsetActions(): array
    {
        return [
            'delete',
        ];
    }

    public function columnToDisplayVoucherItemChild(): array
    {
        return [
            'status',
            'created_at',
            'updated_at',
            'actions',
        ];
    }
}
