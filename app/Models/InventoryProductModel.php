<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryProductModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'inventory_product_tbl';

    protected $primaryKey = 'id';
    protected $fillable = [
        'inventory_product_id',

        'inventory_id',

        'item_code',
        'image',
        'name',
        'category',
        'supplier_name',

        'retail_price',
        'discounted_price',
        'unit_supplier_price',

        'refundable',

        'stocks',

        'low_stocks',
        'moderate_stocks',

        'item_expiration_at',

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
            'inventory_id',
            'item_code',
            'image',
            'name',
            // 'description',
            'refundable',
            'supplier_name',
            // 'design',
            // 'size',
            // 'color',
            'retail_price',
            'discounted_price',
            'unit_supplier_price',
            'stocks',

            'low_stocks',
            'moderate_stocks',

            'item_expiration_at',

            // Append column to insert
            'category'
        ];
    }
    public function arrToUpdates(): array
    {
        return [
            'item_code',
            'image',
            'name',
            // 'description',
            'refundable',
            'supplier_name',
            // 'design',
            // 'size',
            // 'color',
            'retail_price',
            'discounted_price',
            'unit_supplier_price',
            'stocks',

            'low_stocks',
            'moderate_stocks',

            'item_expiration_at',
        ];
    }

    public function arrToDeletes(): array
    {
        return [
            'inventory_product_id',
            'inventory_id',
            'eu_device',
        ];
    }

    public function unsetActions(): array
    {
        return [
            'delete',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'inventory_product_id' => 'inventory_product_id-',
        ];
    }

    public function arrModelWithId(): array
    {
        return [
            'PurchaseModel'  => ['inventory_product_id',]
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'inventory/product/';
        $payload = [
            'lost-found-restock-index' => [
                'inventory_product_id',
                'eu_device',
            ],
            'update' => [
                'inventory_product_id',
                'inventory_id',
                'item_code',
                'image',
                'name',
                'description',
                'supplier_name',
                'refundable',
                'design',
                'size',
                'color',
                'retail_price',
                'discounted_price',
                'unit_supplier_price',
                'stocks',
                'low_stocks',
                'moderate_stocks',
                'item_expiration_at',
                'eu_device',
            ],
            'delete' => ['inventory_product_id', 'eu_device']
        ];
        $method = [
            'lost-found-restock-index' => 'GET',
            'update' => 'POST',
            'delete' => 'DELETE',
        ];
        $button_name = [
            'lost-found-restock-index' => 'View',
            'update' => 'edit',
            'delete' => 'delete',
        ];
        $icon = [
            'lost-found-restock-index' => 'radix-icon:eye-open',
            'update' => "radix-icons:pencil-1",
            'delete' =>  "radix-icons:trash",
        ];
        $container = [
            'lost-found-restock-index' => 'page',
            'update' => 'modal',
            'delete' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getApiRelativeSettings()
    {
        $prefix = 'inventory/product/';
        $payload = [
            'store' => [
                'inventory_id',
                'item_code',
                'image',
                'name',
                'description',
                'refundable',
                'supplier_name',
                'design',
                'size',
                'color',
                'retail_price',
                'discounted_price',
                'unit_supplier_price',
                'stocks',
                'eu_device',

                'low_stocks',
                'moderate_stocks',

                'item_expiration_at',
            ]
        ];

        $method = [
            'store' => 'POST',
        ];

        $button_name = [
            'store' => 'Add Product',
        ];

        $icon = [
            'store' => null,
        ];

        $container = [
            'store' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'item_expiration_at',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    public function arrDetails(): array
    {
        return [
            'item_code',
            'image',
            'name',
            'category',
            'description',
            'refundable',
            'supplier_name',
            'design',
            'size',
            'color',
            'retail_price',
            'discounted_price',
            'unit_supplier_price',
            'stocks',

            'low_stocks',
            'moderate_stocks',

            'item_expiration_at',
        ];
    }

    public function arrDetailsProductShow(): array
    {
        return [
            'item_code',
            'image',
            'name',
            // 'category',
            // 'description',
            'supplier_name',
            // 'design',
            // 'size',
            // 'color',
            'retail_price',
            'discounted_price',
            'refundable',
            'unit_supplier_price',
            'stocks',

            'low_stocks',
            'moderate_stocks',

            'item_expiration_at',
        ];
    }

    public function getViewRowTable()
    {
        // $prefix = 'inventory/product/';
        // $url = $prefix . 'show/';
        $url = '';
        $method = 'GET';
        return compact('url',  'method');
    }

    public function getArrFieldsToAppend(): array
    {
        return [
            'category',
        ];
    }

    public function arrColumns(): array
    {
        return [
            'item_code',
            'image',
            'name',
            'retail_price',
            'discounted_price',
            'refundable',
            'sells',
            'stocks',
            'status', // empty | low | moderate | in stock
            'item_expiration_at',
            'actions',
        ];
    }

    public function arrPayloadIdsToDecrypt(): array
    {
        return [
            'inventory_id',
        ];
    }

    public function arrFieldsToUppercase(): array
    {
        return [
            'name',
            'category',
            'refundable',
            'supplier_name',
        ];
    }

    public function arrFieldsToUnsetTable(): array
    {
        return [
            'inventory_product_id',
            'inventory_id',
            'category',
            'supplier_name',
            'unit_supplier_price',
            'low_stocks',
            'moderate_stocks',
            'created_at',
            'updated_at',
        ];
    }

    public function arrFieldsUpdate(): array
    {
        return [
            'item_code' => [
                'label' => "Item code",
                'type' => "number",
                'value' =>  null
            ],
            'image' => [
                'label' => "Image",
                'type' => "file",
                'value' =>  null
            ],
            'name' => [
                'label' => "Product name",
                'type' => "input",
                'value' =>  null
            ],
            'supplier_name' => [
                'label' => "Supplier name",
                'type' => "input",
                'value' =>  null
            ],
            'retail_price' => [
                'label' => "Retail price",
                'type' => "number",
                'value' =>  null
            ],
            'discounted_price' => [
                'label' => "Discounted price",
                'type' => "number",
                'value' =>  null
            ],
            'refundable' => [
                'label' => "Refundable",
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
            'unit_supplier_price' => [
                'label' => "Unit supplier price",
                'type' => "number",
                'value' =>  null
            ],
            'stocks' => [
                'label' => "Stocks",
                'type' => "number",
                'value' =>  null
            ],
            'low_stocks' => [
                'label' => "Low stocks",
                'type' => "number",
                'value' =>  null
            ],
            'moderate_stocks' => [
                'label' => "Moderate stocks",
                'type' => "number",
                'value' =>  null
            ],
            'item_expiration_at' => [
                'label' => "Item expiration at",
                'type' => "date",
                'value' =>  null
            ],
        ];
    }

    public function arrFieldsStore(): array
    {
        return [
            'item_code' => [
                'label' => "Item code",
                'type' => "number",
                'value' =>  null
            ],
            'image' => [
                'label' => "Image",
                'type' => "file",
                'value' =>  null
            ],
            'name' => [
                'label' => "Product name",
                'type' => "input",
                'value' =>  null
            ],
            'supplier_name' => [
                'label' => "Supplier name",
                'type' => "input",
                'value' =>  null
            ],
            'retail_price' => [
                'label' => "Retail price",
                'type' => "number",
                'value' =>  null
            ],
            'discounted_price' => [
                'label' => "Discounted price",
                'type' => "number",
                'value' =>  null
            ],
            'refundable' => [
                'label' => "Refundable",
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
            'unit_supplier_price' => [
                'label' => "Unit supplier price",
                'type' => "number",
                'value' =>  null
            ],
            'stocks' => [
                'label' => "Stocks",
                'type' => "number",
                'value' =>  null
            ],
            'low_stocks' => [
                'label' => "Low stocks",
                'type' => "number",
                'value' =>  null
            ],
            'moderate_stocks' => [
                'label' => "Moderate stocks",
                'type' => "number",
                'value' =>  null
            ],
            'item_expiration_at' => [
                'label' => "Item expiration at",
                'type' => "date",
                'value' =>  null
            ],
        ];
    }

    public function arrFieldsToDisplayOnLostFoundRestock(): array
    {
        return [
            'item_code' => null,
            'image'  => env("PATH_FILE_INVENTORY_PRODUCT"),
            'name' => null,
            'category' => null,
            'supplier_name' => null,

            'retail_price' => null,
            'discounted_price' => null,
            'unit_supplier_price' => null,

            'refundable' => null,

            'stocks' => null,

            'low_stocks' => null,
            'moderate_stocks' => null,

        ];
    }
}
