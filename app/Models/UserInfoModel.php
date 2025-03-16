<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

class UserInfoModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'users_info_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_info_id',
        'user_id',
        'image',
        'first_name',
        'middle_name',
        'last_name',
        // 'contact_number',
        // 'contact_email',
        'address_1',
        'address_2',
        'region_code',
        'province_code',
        'city_or_municipality_code',
        'barangay_code',
        'region_name',
        'province_name',
        'city_or_municipality_name',
        'barangay_name',
        'description_location',
        'deleted_at',
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
            'image',
            'first_name',
            'middle_name',
            'last_name',
            // 'contact_number',
            // 'contact_email',
            'address_1',
            'address_2',
            'region_code',
            'province_code',
            'city_or_municipality_code',
            'barangay_code',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'image',
            'first_name',
            'middle_name',
            'last_name',
            // 'contact_number',
            // 'contact_email',
            'address_1',
            'address_2',
            'region_code',
            'province_code',
            'city_or_municipality_code',
            'barangay_code',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }

    public function arrToUpdatesAdmin(): array
    {
        return [
            'image',
            'first_name',
            'middle_name',
            'last_name',
            // 'contact_number',
            // 'contact_email',
            'address_1',
            'address_2',
            'region_code',
            'province_code',
            'city_or_municipality_code',
            'barangay_code',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }


    public function arrToUpdatesImage(): array
    {
        return [
            'image',
        ];
    }


    public function arrToDecrypt(): array
    {
        return [
            'image',
            'first_name',
            'middle_name',
            'last_name',
            // 'contact_number',
            // 'contact_email',
            'address_1',
            'address_2',
            'region_code',
            'province_code',
            'city_or_municipality_code',
            'barangay_code',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }

    public function arrDetails(): array
    {
        return [
            'image',
            'first_name',
            'middle_name',
            'last_name',
            // 'contact_number',
            // 'contact_email',
            'address_1',
            'address_2',
            // 'region_code',
            // 'province_code',
            // 'city_or_municipality_code',
            // 'barangay_code',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }

    public function arrDetailsGetUserInfo(): array
    {
        return [
            // 'image',
            'first_name',
            'middle_name',
            'last_name',
            // 'contact_number',
            // 'contact_email',
            'address_1',
            'address_2',
            // 'region_code',
            // 'province_code',
            // 'city_or_municipality_code',
            // 'barangay_code',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'user_info_id' => 'user_info_id-',
        ];
    }

    public function arrFieldsToFormatUcFirst(): array
    {
        return [
            'first_name',
            'middle_name',
            'last_name',
            'address_1',
            'address_2',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }

    public function getApiCrudSettings()
    {
        $AuthModel = app()->make(AuthModel::class);

        $prefix = 'user-info/';
        $payload = [
            'update' => $this->arrDetailsGetUserInfo(),
            'update-image' => $this->arrToUpdatesImage(),
            'update-password' => $AuthModel->arrUpdatePassword(),
            'update-email' => $AuthModel->arrUpdateEmail(),
            'resend-code-email' => null,
            'resend-code-password' =>  null,
        ];
        $method = [
            'update' => 'POST',
            'update-image' => 'POST',
            'update-password' => 'POST',
            'update-email' => 'POST',
            'resend-code-email' => 'POST',
            'resend-code-password' => 'POST',
        ];
        $button_name = [
            'update' => 'edit account',
            'update-image' => 'edit image',
            'update-password' => 'edit password',
            'update-email' => 'edit email',
            'resend-code-email' => 'resend code',
            'resend-code-password' => 'resend code',
        ];
        $icon = [
            'update' => "radix-icons:pencil-1",
            'update-image' => "radix-icons:pencil-1",
            'update-password' => "radix-icons:pencil-1",
            'update-email' => "radix-icons:pencil-1",
            'resend-code-email' => null,
            'resend-code-password' => null,
        ];
        $container = [
            'update' => 'modal',
            'update-image' => 'null',
            'update-password' => 'modal',
            'update-email' => 'modal',
            'resend-code-email' => null,
            'resend-code-password' => null,
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function arrModelWithId(): array
    {
        return [
            'HistoryModel'  => ['tbl_id'],
            'LogsModel'  => ['user_id'],
            'PaymentModel'  => ['user_id'],
            'PurchaseModel'  => ['user_id_menu'],
            'UserInfoModel'  => ['user_id'],
        ];
    }

    public function arrFieldsToUppercase(): array
    {
        return [
            'first_name',
            'middle_name',
            'last_name',
            'address_1',
            'address_2',
            'region_name',
            'province_name',
            'city_or_municipality_name',
            'barangay_name',
            'description_location',
        ];
    }
}
