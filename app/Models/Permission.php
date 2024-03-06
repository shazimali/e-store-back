<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role', 'role_id', 'permission_id');
    }
    
}
