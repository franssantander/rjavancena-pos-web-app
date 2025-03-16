<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPersonalAccessTokenModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'users_personal_access_token_tbl';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'tokenable_type',
        'name',
        'token',
        'abilities',
        'status',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
    ];
}
