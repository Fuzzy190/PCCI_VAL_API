<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    protected $fillable = ['payment_method', 'account_name', 'account_no', 'is_active'];
}