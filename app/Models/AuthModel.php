<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        // 'phone_number',
        'email',
        'password',
        'role',
        'status',
        'verification_number',
        'verification_key',
        'phone_verified_at',
        'email_verified_at',
        'update_password_at',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
    protected $dates = ['deleted_at'];

    public function getFillableAttributes(): array
    {
        return $this->fillable;
    }

    public function unsetForRetrieves(): array
    {
        return  [
            'id',
            'password',
            'verification_key',
            'session_token',
            'verify_email_token',
            'verify_phone_token',
            'reset_password_token',
        ];
    }

    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'phone_verified_at',
            'email_verified_at',
            'update_password_at',
            'deleted_at',
            'created_at',
            'updated_at'
        ];
    }

    public function arrEnvRoles(): array
    {
        return [
            'ROLE_SUPER_ADMIN' => 'SUPER ADMIN',
            'ROLE_ADMIN' => 'ADMIN',
            'ROLE_CLIENT' => 'CLIENT',
            'ROLE_DELIVERY' => 'DELIVERY',
            'ROLE_CASHIER' => 'CASHIER',
        ];
    }

    public function arrToStores(): array
    {
        return [
            'user_id',
            'phone_number',
            'email',
            'password',
            'role',
            'status',
            'verification_number',
            'phone_verified_at',
            'email_verified_at',
        ];
    }

    public function arrSeedToStores(): array
    {
        return [
            'user_id',
            'email',
            'password',
            'role',
            'status',
            'verification_number',
            'verify_email_token',
            'verify_email_token_expire_at',
            'email_verified_at',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'phone_number',
            'email',
            'password',
            'role',
            'status',
        ];
    }

    public function arrEnvAccountStatus(): array
    {
        return [
            'ACCOUNT_ACTIVATE' => env('ACCOUNT_ACTIVATE'),
            'ACCOUNT_PENDING' => env('ACCOUNT_PENDING'),
            'ACCOUNT_BANNED' => env('ACCOUNT_BANNED'),
            'ACCOUNT_RESTRICTED' => env('ACCOUNT_RESTRICTED'),
        ];
    }

    public function arrEnvAccountRole(): array
    {
        return [
            'ROLE_SUPER_ADMIN' => env('ROLE_SUPER_ADMIN'),
            'ROLE_ADMIN' => env('ROLE_ADMIN'),
            'ROLE_CLIENT' => env('ROLE_CLIENT'),
            'ROLE_CASHIER' => env('ROLE_CASHIER'),
            // 'ROLE_DELIVERY' => env('ROLE_DELIVERY'),
        ];
    }

    public function arrEnvAccountRestrictRoleNotification(): array
    {
        return [
            'ROLE_SUPER_ADMIN' => env('ROLE_SUPER_ADMIN'),
            'ROLE_ADMIN' => env('ROLE_ADMIN'),
            'ROLE_CASHIER' => env('ROLE_CASHIER'),
        ];
    }

    public function getApiCrudSettings()
    {
        $UserInfoModel = app()->make(UserInfoModel::class);

        $prefix = 'accounts/admin/';
        $payload = [
            'update' => [
                'user_id',
                'phone_number',
                'email',
                'password',
                'password_confirmation',
                'role',
                'status',
                'eu_device'
            ],
            'update-user-info' => $UserInfoModel->arrToUpdates(),
            'destroy' => ['user_id', 'eu_device']
        ];
        $method = [
            'update' => 'POST',
            'update-user-info' => 'POST',
            'destroy' => 'DELETE',
        ];
        $button_name = [
            'update' => 'edit account',
            'update-user-info' => 'edit user information',
            'destroy' => 'delete',
        ];
        $icon = [
            'update' => "radix-icons:pencil-1",
            'update-user-info' => "radix-icons:person",
            'destroy' =>  "radix-icons:trash",
        ];
        $container = [
            'update' => 'modal',
            'update-user-info' => 'modal',
            'destroy' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getApiRelativeSettings()
    {
        $AuthModel = app()->make(UserInfoModel::class);

        $prefix = 'accounts/admin/';
        $payload = [
            'store-auth-user-info' => [
                'phone_number',
                'email',
                'password',
                'password_confirmation',
                'role',
                'status',

                'image',
                'first_name',
                'middle_name',
                'last_name',
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

                'eu_device'
            ],
        ];

        $method = [
            'store-auth-user-info' => 'POST',
        ];

        $button_name = [
            'store-auth-user-info' => 'Add User',
        ];

        $icon = [
            'store-auth-user-info' => null,
        ];

        $container = [
            'store-auth-user-info' => 'modal',
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

    public function unsetActions(): array
    {
        return [
            'delete',
        ];
    }

    public function arrDetails(): array
    {
        return [
            'phone_number',
            'email',
            'password',
            'role',
            'status',
        ];
    }

    public function arrDetailsAccountWithUserInfo(): array
    {
        return [
            // Auth Fields
            'phone_number',
            'email',
            'password',
            'role',
            'status',

            // User info Fields
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
            'description_location'
        ];
    }

    public function getViewRowTable()
    {
        $prefix = 'admin/accounts/';

        $url = $prefix . 'show/';
        $method = 'GET';
        return compact('url',  'method');
    }

    public function getNavLinksRoleSuperAdmin(): array
    {
        return [
            [
                'title' => 'Menu',
                'path' => '/menu',
                'icon' => 'heroicons-outline:view-grid',
                'path_key' => 'inventory/product/index'
            ],
            [
                'title' => 'Dashboard',
                'path' => '/dashboard',
                'icon' => 'heroicons-outline:chart-pie',
                'path_key' => 'dashboard'
            ],
            [
                'title' => 'Inventory',
                'path' => '/inventory',
                'icon' => 'heroicons-outline:cube',
                'path_key' => 'inventory/parent/index'
            ],
            [
                'title' => 'Users',
                'path' => '/users',
                'icon' => 'heroicons-outline:user-group',
                'path_key' => 'accounts/admin/index'
            ],
            [
                'title' => 'Customer',
                'path' => '/customer',
                "icon" =>  "heroicons-outline:emoji-happy",
                "path_key" =>  "dashboard/get-recent-transaction-cashier"
            ],
            [
                'title' => 'Voucher',
                'path' => '/voucher',
                "icon" =>  "heroicons-outline:ticket",
                "path_key" =>  "voucher/parent/index"
            ],
            [
                'title' => 'Expenses',
                'path' => '/expenses',
                "icon" =>  "tabler:report-money",
                "path_key" =>  "expenses/expense/index"
            ],
            [
                'title' => 'Logs',
                'path' => '/logs',
                "icon" =>  "heroicons-outline:clock",
                "path_key" =>  "log/index"
            ]
        ];
    }

    public function getNavLinksRoleAdmin(): array
    {
        return [
            [
                'title' => 'Menu',
                'path' => '/menu',
                'icon' => 'heroicons-outline:view-grid',
                'path_key' => 'inventory/product/index'
            ],
            [
                'title' => 'Dashboard',
                'path' => '/dashboard',
                'icon' => 'heroicons-outline:chart-pie',
                'path_key' => 'dashboard'
            ],
            [
                'title' => 'Inventory',
                'path' => '/inventory',
                'icon' => 'heroicons-outline:cube',
                'path_key' => 'inventory/parent/index'
            ],
            [
                'title' => 'Users',
                'path' => '/users',
                'icon' => 'heroicons-outline:user-group',
                'path_key' => 'accounts/admin/index'
            ],
            [
                'title' => 'Customer',
                'path' => '/customer',
                "icon" =>  "heroicons-outline:emoji-happy",
                "path_key" =>  "dashboard/get-recent-transaction-cashier"
            ],
            [
                'title' => 'Voucher',
                'path' => '/voucher',
                "icon" =>  "heroicons-outline:ticket",
                "path_key" =>  "voucher/parent/index"
            ],
            [
                'title' => 'Expenses',
                'path' => '/expenses',
                "icon" =>  "tabler:report-money",
                "path_key" =>  "expenses/expense/index"
            ],
            [
                'title' => 'Logs',
                'path' => '/logs',
                "icon" =>  "heroicons-outline:clock",
                "path_key" =>  "log/index"
            ]
        ];
    }

    public function getNavLinksRoleCashier(): array
    {
        return [
            [
                'title' => 'Menu',
                'path' => '/menu',
                'icon' => 'heroicons-outline:view-grid',
                'path_key' => 'inventory/product/index'
            ],
            [
                'title' => 'Customer',
                'path' => '/customer',
                "icon" =>  "heroicons-outline:emoji-happy",
                "path_key" =>  "dashboard/get-recent-transaction-cashier"
            ]
        ];
    }

    public function columnHeader(): array
    {
        return [
            'user_id',
            'image',
            'name',
            'email',
            // 'password',
            'role',
            'status',
            'created_at',
            'actions',
        ];
    }

    public function statusColor(): array
    {
        return [
            env('ACCOUNT_ACTIVATE') => 'success',
            env('ACCOUNT_PENDING') => 'warning',
            env('ACCOUNT_RESTRICTED')  => 'danger',
            env('ACCOUNT_BANNED') => 'danger'
        ];
    }

    public function arrUpdatePassword(): array
    {
        return [
            "current_password",
            "password",
            "password_confirmation",
            "verification_number",
        ];
    }

    public function arrUpdateEmail(): array
    {
        return [
            "new_email",
            "current_password",
            "verification_number",
        ];
    }
}
