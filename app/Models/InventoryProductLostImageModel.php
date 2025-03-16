<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryProductLostImageModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'inventory_product_lost_image_tbl';

    protected $primaryKey = 'id';
    protected $fillable = [
        'inventory_product_lost_image_id',
        'inventory_product_lost_id',

        'image',

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
            'inventory_product_lost_id',
            'image',
        ];
    }

    public function arrPayloadIdsToDecrypt(): array
    {
        return [
            'inventory_product_lost_id',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'inventory_product_lost_image_id' => 'inventory_product_lost_image_id-',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'image',
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'inventory_product_lost_image_id',
            'inventory_product_lost_id',
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'inventory/product/lost/image/';

        $payload = [
            'update' => ['inventory_product_lost_image_id', 'inventory_product_lost_id', 'image', 'eu_device'],
            'delete' => ['inventory_product_lost_image_id', 'inventory_product_lost_id', 'eu_device'],
        ];
        $method = [
            'update' => 'POST',
            'delete' => 'DELETE',
        ];
        $button_name = [
            'update' => 'Edit',
            'delete' => 'Delete',
        ];
        $icon = [
            'update' => "radix-icons:pencil-2",
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
        $prefix = 'inventory/product/lost/';
        $payload = [
            'store' => ['inventory_product_lost_id', 'image', 'eu_device']
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

    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    public function arrFieldsUpdate(): array
    {
        return [
            'image' => [
                'label' => "Image",
                'type' => "file",
                'value' =>  null
            ],
        ];
    }

    public function arrFieldsToUnsetTable(): array
    {
        return [
            'inventory_product_lost_image_id',
            'inventory_product_lost_id',
        ];
    }

    public function arrFieldsColumnHeaderTable(): array
    {
        return [
            'image',
            'created_at',
            'updated_at',
            'actions',
        ];
    }

    public function arrFieldsStore(): array
    {
        return [
            'image' => [
                'label' => "Image",
                'type' => "file",
                'value' =>  null
            ],
        ];
    }
}
