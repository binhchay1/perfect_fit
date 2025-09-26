<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductTag;
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
        // First, ensure we have brands
        $this->call(BrandSeeder::class);

        // Create test products directly with the existing structure
        $this->createTestProducts();
    }

    private function createTestProducts(): void
    {
        $brands = Brand::all();

        if ($brands->isEmpty()) {
            throw new \Exception('No brands found. Please run BrandSeeder first.');
        }

        // Check if products already exist to avoid duplicates
        $existingCount = Product::count();
        if ($existingCount > 0) {
            echo "Products already exist. Skipping product creation.\n";
            return;
        }

        // Define basic colors and sizes that we'll create
        $colorNames = ['Black', 'White', 'Blue', 'Red', 'Green'];
        $sizeNames = ['S', 'M', 'L', 'XL'];

        $products = [
            [
                'name' => 'Classic Cotton T-Shirt',
                'slug' => 'classic-cotton-t-shirt',
                'description' => 'Comfortable cotton t-shirt perfect for everyday wear. Made from 100% organic cotton.',
                'price' => 250000,
                'material' => 'Cotton',
                'images' => null,
                'product_type' => 'genuine',
                'product_link' => null,
                'gender' => 'male',
                'is_active' => true,
                'brand_id' => $brands->random()->id,
                'tag_names' => ['Casual', 'Cotton', 'Basic'],
                'color_names' => $colorNames,
                'size_names' => $sizeNames,
            ],
            [
                'name' => 'Premium Denim Jeans',
                'slug' => 'premium-denim-jeans',
                'description' => 'High-quality denim jeans with perfect fit. Durable and stylish for any occasion.',
                'price' => 850000,
                'material' => 'Denim',
                'images' => null,
                'product_type' => 'genuine',
                'product_link' => null,
                'gender' => 'male',
                'is_active' => true,
                'brand_id' => $brands->random()->id,
                'tag_names' => ['Denim', 'Premium'],
                'color_names' => ['Blue', 'Black', 'Navy'],
                'size_names' => ['28', '29', '30', '31', '32', '33', '34'],
            ],
            [
                'name' => 'Running Sneakers',
                'slug' => 'running-sneakers',
                'description' => 'Lightweight running shoes with advanced cushioning technology. Perfect for jogging and daily wear.',
                'price' => 1200000,
                'material' => 'Synthetic',
                'images' => null,
                'product_type' => 'self_produced',
                'product_link' => null,
                'gender' => 'male',
                'is_active' => true,
                'brand_id' => $brands->random()->id,
                'tag_names' => ['Sport', 'Running', 'Athletic'],
                'color_names' => ['Black', 'White', 'Blue', 'Red', 'Gray'],
                'size_names' => ['37', '38', '39', '40', '41', '42'],
            ],
        ];

        foreach ($products as $productData) {
            $tagNames = $productData['tag_names'];
            $colorNames = $productData['color_names'];
            $sizeNames = $productData['size_names'];

            unset($productData['tag_names'], $productData['color_names'], $productData['size_names']);

            $product = Product::create($productData);

            // Create tags for this product
            foreach ($tagNames as $tagName) {
                $product->tags()->create([
                    'tag' => $tagName,
                ]);
            }

            // Create product colors for this product
            foreach ($colorNames as $colorName) {
                $productColor = ProductColor::create([
                    'product_id' => $product->id,
                    'color_name' => $colorName,
                    'images' => [],
                ]);

                // Create product sizes for this color
                foreach ($sizeNames as $sizeName) {
                    ProductSize::create([
                        'product_color_id' => $productColor->id,
                        'size_name' => $sizeName,
                        'quantity' => rand(10, 50), // Random stock quantity
                        'sku' => $product->slug . '-' . strtolower($colorName) . '-' . $sizeName,
                    ]);
                }
            }
        }
    }
}
