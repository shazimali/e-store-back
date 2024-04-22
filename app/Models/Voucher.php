<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'branch_id',
        'invoice_id',
        'amount',
        'date',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['type','total'];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function  getTypeAttribute(){
        return  'voucher';
    }

    public function  getTotalAttribute(){
        return  '';
    }
}
