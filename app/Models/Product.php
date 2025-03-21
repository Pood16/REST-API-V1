<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'category_id',
    ];

    /**
     * @OA\Schema(
     *     schema="Product",
     *     title="Product",
     *     description="Product model",
     *     type="object",
     *     required={"id", "name", "slug", "price", "stock", "category_id"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="PlayStation 5"),
     *     @OA\Property(property="slug", type="string", example="playstation-5"),
     *     @OA\Property(property="description", type="string", example="Next-gen gaming console"),
     *     @OA\Property(property="price", type="number", format="float", example=499.99),
     *     @OA\Property(property="stock", type="integer", example=15),
     *     @OA\Property(property="category_id", type="integer", example=2),
     *     @OA\Property(
     *         property="images",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Image")
     *     )
     * )
     */

    public function images(){
        return $this->hasMany(Image::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function cartItems(){
        return $this->hasMany(CartItem::class, 'product_id');
    }
}
