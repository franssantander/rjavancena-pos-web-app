<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryProductFoundModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'inventory_product_found_tbl';

    protected $primaryKey = 'id';
    protected $fillable = [
        'inventory_product_found_id',
        'inventory_product_id',

        'count',
        'image',
        'remarks',

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
            'inventory_product_id',
            'count',
            'image',
            'remarks',
        ];
    }


    public function arrToUpdates(): array
    {
        return [
            'count',
            'image',
            'remarks',
        ];
    }

    public function arrPayloadIdsToDecrypt(): array
    {
        return [
            'inventory_product_id',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'inventory_product_found_id' => 'inventory_product_found_id-',
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'inventory_product_found_id',
            'inventory_product_id',
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

    public function getApiCrudSettings()
    {
        $prefix = 'inventory/product/found/';

        $payload = [
            // 'view' => ['inventory_product_found_id'],
            // 'store' => ['inventory_product_id', 'image', 'eu_device'],
            'update' => ['inventory_product_found_id', 'inventory_product_id',  'count', 'remarks', 'eu_device'],
            'delete' => ['inventory_product_found_id', 'inventory_product_id', 'eu_device'],
        ];
        $method = [
            // 'view' => 'GET',
            // 'store' => 'POST',
            'update' => 'POST',
            'delete' => 'DELETE',
        ];
        $button_name = [
            // 'view' => 'View',
            // 'store' => 'Store',
            'update' => 'Edit',
            'delete' => 'Delete',
        ];
        $icon = [
            // 'view' => "radix-icons:pencil-2",
            // 'store' => 'tabler:plus',
            'update' => "radix-icons:pencil-2",
            'delete' =>  "radix-icons:trash",
        ];
        $container = [
            // 'view' => "modal",
            // 'store' => 'modal',
            'update' => 'modal',
            'delete' => 'modal',
        ];


        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getApiRelativeSettings()
    {
        $prefix = 'inventory/product/found/';
        $payload = [
            'store' => ['inventory_product_id', 'count', 'remarks', 'eu_device']
        ];

        $method = [
            'store' => 'POST',
        ];

        $button_name = [
            'store' => 'Create',
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
        // $prefix = 'inventory/product/';
        // $url = $prefix . 'show/';
        $url = '';
        $method = 'GET';
        return compact('url',  'method');
    }

    public function arrFieldsColumnHeader(): array
    {
        return [
            'count',
            'remarks',
            'created_at',
            'updated_at',
            'actions',
        ];
    }

    public function arrFieldsStore(): array
    {
        return [
            'count' => [
                'label' => "Count",
                'type' => "number",
                'value' =>  null
            ],
            'image' => [
                'label' => "Image",
                'type' => "file",
                'value' =>  null
            ],
            'remarks' => [
                'label' => "Remarks",
                'type' => "textarea",
                'value' =>  null
            ],
        ];
    }

    public function arrFieldsUpdate(): array
    {
        return [
            'count' => [
                'label' => "Count",
                'type' => "number",
                'value' =>  null
            ],
            'image' => [
                'label' => "Image",
                'type' => "file",
                'value' =>  null
            ],
            'remarks' => [
                'label' => "Remarks",
                'type' => "textarea",
                'value' =>  null
            ],
        ];
    }

    public function arrFieldsToUnsetTable(): array
    {
        return [
            'inventory_product_found_id',
            'inventory_product_id',
        ];
    }
}
