<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        "order_id",
        "payment_type",
        "status",
        "transaction_id",
    ];

<<<<<<< HEAD
    public function order()
    {
=======
    public function order(){
>>>>>>> 47733ee5db6d0d72c238d0eb6c6add290c5e21a3
        return $this->belongsTo(Order::class);
    }
}
