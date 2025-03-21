<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all category IDs
        $categoryIds = Category::pluck('id')->toArray();

        if (empty($categoryIds)) {
            // If no categories exist, create one to avoid foreign key constraint issues
            $categoryId = Category::create([
                'name' => 'Action',
                'slug' => 'action'
            ])->id;
            $categoryIds = [$categoryId];
        }

        // Sample game products with game-related names and descriptions
        $gameProducts = [
            [
                'name' => 'Elite Warrior',
                'description' => 'An action-packed adventure where you fight as an elite warrior against hordes of enemies.',
                'price' => 59.99,
                'stock' => 25
            ],
            [
                'name' => 'Galaxy Explorer',
                'description' => 'Explore the vast universe in this open-world space simulation game.',
                'price' => 49.99,
                'stock' => 30
            ],
            [
                'name' => 'Ancient Kingdoms',
                'description' => 'Build your empire and conquer territories in this strategic RPG set in medieval times.',
                'price' => 39.99,
                'stock' => 15
            ],
            [
                'name' => 'Speed Racers',
                'description' => 'Experience adrenaline-pumping races on challenging tracks from around the world.',
                'price' => 44.99,
                'stock' => 20
            ],
            [
                'name' => 'Puzzle Master',
                'description' => 'Test your brain with increasingly difficult puzzles in this award-winning game.',
                'price' => 29.99,
                'stock' => 35
            ]
        ];

        foreach ($gameProducts as $product) {
            Product::create([
                'name' => $product['name'],
                'description' => $product['description'],
                'slug' => Str::slug($product['name']),
                'price' => $product['price'],
                'category_id' => $categoryIds[array_rand($categoryIds)], // Random category from existing ones
                'stock' => $product['stock']
            ]);
        }

        // Add some additional random products
        for ($i = 0; $i < 10; $i++) {
            $name = 'Game ' . ($i + 1);
            Product::create([
                'name' => $name,
                'description' => 'This is a description for ' . $name,
                'slug' => Str::slug($name),
                'price' => rand(1999, 5999) / 100,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'stock' => rand(5, 50)
            ]);
        }
    }
}
