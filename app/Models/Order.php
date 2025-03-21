<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
use App\Models\Payment;
=======
use Illuminate\Database\Eloquent\SoftDeletes;
>>>>>>> 47733ee5db6d0d72c238d0eb6c6add290c5e21a3

class Order extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "user_id",
        "total_price",
        "status",
        "session_id",
    ];

<<<<<<< HEAD
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasMany(Payment::class);
    }

    
=======
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function items(){
        return $this->hasMany(OrderItem::class);
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }
>>>>>>> 47733ee5db6d0d72c238d0eb6c6add290c5e21a3
}
