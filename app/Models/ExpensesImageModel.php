<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpensesImageModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'expenses_image_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'expenses_image_id',
        'expenses_id',
        'file',
        'user_id',
        'original_name',
        'size',
        'created_at',
        'updated_at',
    ];

    protected $dates = ['deleted_at'];

    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    public function idToUpdate(): array
    {
        return [
            'expenses_image_id' => 'expenses_image_id-',
        ];
    }

    public function arrToStores(): array
    {
        return [
            'expenses_id',
            'user_id',
            'file',
            'original_name',
            'size',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'user_id',
            'file',
            'original_name',
            'size',
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'expenses_image_id',
            'expenses_id',
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
        $prefix = 'expenses/expense-image/';

        $payload = [
            // 'update' => [
            //     'file',
            //     'eu_device'
            // ],
            'download' => [],
            'delete' => [
                'expenses_id',
                'expenses_image_id',
                'eu_device'
            ],
        ];
        $method = [
            // 'update' => 'POST',
            'download' => 'GET',
            'delete' => 'DELETE',
        ];
        $button_name = [
            // 'update' => 'Edit',
            'download' => 'Download',
            'delete' => 'Delete',
        ];
        $icon = [
            // 'update' => "radix-icons:pencil-2",
            'download' => "tabler:download",
            'delete' =>  "radix-icons:trash",
        ];
        $container = [
            // 'update' => 'modal',
            'download' => "page",
            'delete' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getApiRelativeSettings()
    {
        $prefix = 'expenses/';
        $payload = [
            'store' => $this->arrToStores(),
        ];

        $method = [
            'store' => 'POST',
        ];

        $button_name = [
            'store' => 'Create Image',
        ];

        $icon = [
            'store' => 'tabler:plus',
        ];

        $container = [
            'store' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function arrFormFieldsUpdate(): array
    {
        return [
            'file' => [
                'label' => "File",
                'type' => "file",
                'value' =>  null
            ],
        ];
    }

    public function arrFieldsToUnsetTable(): array
    {
        return [
            'expenses_image_id',
            'expenses_id',
            'user_id',
            'updated_at',
            'original_name',
            'path_download'
        ];
    }

    public function arrFieldsColumnHeaderTable(): array
    {
        return [
            'file',
            'owner',
            'size',
            'created_at',
            'actions',
        ];
    }

    public function arrFormFieldsStore(): array
    {
        return [
            'file' => [
                'label' => "File",
                'type' => "file",
                'value' =>  null
            ],
        ];
    }

    public function arrToDecryptEncryptedIds(): array
    {
        return [
            'expenses_id',
        ];
    }
}
