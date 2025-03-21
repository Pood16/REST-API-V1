<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = "products_images";
    protected $fillable = [
        'product_id',
        'image_url',
        'is_primary'
    ];

    /**
     * @OA\Schema(
     *     schema="Image",
     *     title="Product Image",
     *     description="Image model for a product",
     *     type="object",
     *     required={"id", "product_id", "image_url", "is_primary"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="product_id", type="integer", example=1),
     *     @OA\Property(property="image_url", type="string", example="products/image1.jpg"),
     *     @OA\Property(property="is_primary", type="boolean", example=true)
     * )
     */


    public function product(){
        return $this->belongsTo(Product::class);
    }


}
