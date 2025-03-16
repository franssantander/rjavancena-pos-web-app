<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'notification_tbl';
    protected $primaryKey = 'id';
    protected  $fillable = [
        'id',
        'notification_id',
        'user_id',
        'tbl_reference_id',
        'name',
        'details',
        'is_read',
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
            'notification_id' => 'notification_id-',
        ];
    }

    public function arrToStores(): array
    {
        return [
            'user_id',
            'tbl_reference_id',
            'name',
            'details',
            'is_read',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'is_read',
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'notification_id',
        ];
    }

    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'created_at',
            'updated_at',
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'notification/';

        $payload = [
            'update-view' => ['notification_id', 'is_read', 'eu_device'],
        ];
        $method = [
            'update-view' => 'POST',
        ];
        $button_name = [
            'update-view' => 'Edit',
        ];
        $icon = [
            'update-view' => "radix-icons:pencil-2",
        ];
        $container = [
            'update-view' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }


    public function arrFields(): array
    {
        return [
            'is_read' => [
                'label' => "Update",
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
        ];
    }

    public function arrFieldsToUnsetTable(): array
    {
        return [
            'user_id',
            'tbl_reference_id',
            'updated_at',
        ];
    }
}
