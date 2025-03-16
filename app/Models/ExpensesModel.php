<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpensesModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'expenses_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'expenses_id',
        'name',
        'amount',
        'date_of_expense',
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
            'name',
            'amount',
            'date_of_expense',
        ];
    }

    public function arrToUpdates(): array
    {
        return [
            'name',
            'amount',
            'date_of_expense',
        ];
    }

    public function idToUpdate(): array
    {
        return [
            'expenses_id' => 'expenses_id-',
        ];
    }

    public function arrToConvertIdsToEncrypted(): array
    {
        return  [
            'expenses_id',
        ];
    }

    public function arrToConvertToReadableDateTime(): array
    {
        return  [
            'date_of_expense',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    public function getApiCrudSettings()
    {
        $prefix = 'expenses/expense/';

        $payload = [
            'view' => '',
            'update' => $this->arrToUpdates(),
            'delete' => ['expenses_id', 'eu_device'],
        ];
        $method = [
            'view' => 'GET',
            'update' => 'POST',
            'delete' => 'DELETE',
        ];
        $button_name = [
            'view' => 'View',
            'update' => 'Edit',
            'delete' => 'Delete',
        ];
        $icon = [
            'view' => 'radix-icon:eye-open',
            'update' => "radix-icons:pencil-2",
            'delete' =>  "radix-icons:trash",
        ];
        $container = [
            'view' => 'modal',
            'update' => 'modal',
            'delete' => 'modal',
        ];

        return compact('prefix', 'payload', 'method', 'button_name', 'icon', 'container');
    }

    public function getApiRelativeSettings()
    {
        $prefix = 'expenses/expense/';
        $payload = [
            'store' => $this->arrToStores(),
        ];

        $method = [
            'store' => 'POST',
        ];

        $button_name = [
            'store' => 'Create Expenses',
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
            'name' => [
                'label' => "Name",
                'type' => "input",
                'value' =>  null
            ],
            'amount' => [
                'label' => "Amount",
                'type' => "number",
                'value' =>  null
            ],
            'date_of_expense' => [
                'label' => "Date Expenses",
                'type' => "date",
                'value' =>  null
            ],
        ];
    }

    public function arrFormFieldsStore(): array
    {
        return [
            'name' => [
                'label' => "Name",
                'type' => "input",
                'value' =>  null
            ],
            'amount' => [
                'label' => "Amount",
                'type' => "number",
                'value' =>  null
            ],
            'date_of_expense' => [
                'label' => "Date Expenses",
                'type' => "date",
                'value' =>  null
            ],
        ];
    }

    public function arrFieldsColumnHeader(): array
    {
        return [
            'name',
            'amount',
            'date_of_expense',
            'created_at',
            'updated_at',
            'actions',
        ];
    }
}
