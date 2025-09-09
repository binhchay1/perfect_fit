<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Just Do It - Thương hiệu thể thao hàng đầu thế giới',
                'is_active' => true,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'Impossible is Nothing - Thương hiệu thể thao Đức',
                'is_active' => true,
            ],
            [
                'name' => 'Uniqlo',
                'slug' => 'uniqlo',
                'description' => 'LifeWear - Thời trang Nhật Bản với chất lượng cao',
                'is_active' => true,
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'description' => 'Fast Fashion - Thời trang Tây Ban Nha',
                'is_active' => true,
            ],
            [
                'name' => 'H&M',
                'slug' => 'hm',
                'description' => 'Fashion and Quality at the Best Price - Thời trang Thụy Điển',
                'is_active' => true,
            ],
            [
                'name' => 'Gucci',
                'slug' => 'gucci',
                'description' => 'Luxury Fashion House - Thời trang cao cấp Ý',
                'is_active' => true,
            ],
           
        ];

        foreach ($brands as $brandData) {
            Brand::create($brandData);
        }
    }
}