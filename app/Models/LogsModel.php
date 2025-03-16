<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogsModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'logs_tbl';
    protected $primaryKey = 'id';
    protected  $fillable = [
        'log_id',
        'user_id',
        'ip_address',
        'user_action',
        'details',
        'user_device',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
    protected $dates = ['deleted_at'];

    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    public function encryptedFields(): array
    {
        return [
            // USER ACC MODEL LOGS
            'email',
            'password',
            'old_password',
            'new_password',
            'old_email',
            'new_email',

            //USER INFO MODEL LOGS
            'image',
            'first_name',
            'middle_name',
            'last_name',
            // 'contact_number', 'contact_email',
            'address_1',
            'address_2',
            'region_code',
            'province_code',
            'city_or_municipality_code',
            'barangay_code',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay',
            'barangay_name',
            'description_location',

            //VOUCHE USED MODEL
            'purchase_group_id',
            ''
        ];
    }

    public function arrFieldsToDisplay(): array
    {
        return [
            'image',
            'first_name',
            'last_name',
        ];
    }

    public function notToDecrypt(): array
    {
        return [
            'user_id',
            'id',
            'deleted_at',
            'created_at',
            'updated_at'
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

    public function arrColumns(): array
    {
        return [
            'image',
            'name',
            'ip_address',
            'user_action',
            // 'details',
            // 'user_device',
            'created_at',
            'actions',
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = '';
        $payload = [
            'view-device' => [],
            'view-details' => [],
        ];
        $method = [
            'view-device' => '',
            'view-details' => '',
        ];
        $button_name = [
            'view-device' => 'View device',
            'view-details' => 'View details',
        ];
        $icon = [
            'view-device' => "radix-icons:laptop",
            'view-details' => "radix-icons:clipboard",
        ];
        $container = [
            'view-device' => 'modal',
            'view-details' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function arrFieldsToUnsetTable(): array
    {
        return [
            'log_id', 'details', 'user_device', 'deleted_at', 'updated_at'
        ];
    }
}
