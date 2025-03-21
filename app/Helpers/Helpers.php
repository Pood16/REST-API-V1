<?php
namespace App\Helpers;

use App\Models\CartItem;

class Helpers {

    public static function calculateSubTotal($cartItem){
        dd($cartItem);
        return $cartItem->product->price * $cartItem->quantity;
    }

}
