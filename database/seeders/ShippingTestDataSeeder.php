<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingSettings;
use App\Models\ShippingCarrier;

class ShippingTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo shipping settings
        ShippingSettings::create([
            'shop_name' => 'PerfectFit Store',
            'shipping_location' => [
                'address' => '123 Đường Nguyễn Huệ',
                'ward' => 'Phường Bến Nghé',
                'district' => 'Quận 1',
                'city' => 'Thành phố Hồ Chí Minh',
                'province' => 'Hồ Chí Minh',
                'postal_code' => '700000',
                'country' => 'Việt Nam',
                'coordinates' => [
                    'latitude' => 10.7769,
                    'longitude' => 106.7009
                ],
                'full_address' => '123 Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM, Việt Nam'
            ],
            'phone' => '0245354050',
            'enable_domestic_shipping' => true,
            'enable_inter_province_shipping' => true,
        ]);

        // 2. Tạo carriers nội thành (Domestic)
        ShippingCarrier::create([
            'name' => 'Ahamove',
            'logo' => 'https://example.com/logos/ahamove.png',
            'shipping_type' => 'domestic',
            'is_default' => true,
            'is_active' => true,
            'information' => [
                'first_km' => '2km đầu - 15.709₫',
                'second_km' => '1km tiếp - 19.636₫',
                'additional_km' => 'Km tiếp theo - 5.400₫/km',
                'description' => 'Giao hàng nhanh trong 30-60 phút, dưới 6km',
                'delivery_time' => '30-60 phút',
                'max_distance' => 6
            ],
            'cod' => [
                'free_threshold' => 'Miễn phí dưới 500.000₫',
                'rate_1' => 'Từ 500.000₫-5.000.000₫: 0.6%',
                'rate_2' => 'Từ 5.000.000₫-10.000.000₫: 0.88%'
            ]
        ]);

        ShippingCarrier::create([
            'name' => 'Grab Express',
            'logo' => 'https://example.com/logos/grab.png',
            'shipping_type' => 'domestic',
            'is_default' => false,
            'is_active' => true,
            'information' => [
                'first_km' => '2km đầu - 15.000₫',
                'second_km' => '1km tiếp - 18.000₫',
                'additional_km' => 'Km tiếp theo - 5.000₫/km',
                'description' => 'Giao hàng nhanh với Grab',
                'delivery_time' => '30-45 phút',
                'max_distance' => 10
            ],
            'cod' => [
                'free_threshold' => 'Miễn phí dưới 500.000₫',
                'rate_1' => 'Từ 500.000₫-5.000.000₫: 0.5%',
                'rate_2' => 'Từ 5.000.000₫-10.000.000₫: 0.8%'
            ]
        ]);

        ShippingCarrier::create([
            'name' => 'Giao hàng nhanh (GHN)',
            'logo' => 'https://example.com/logos/ghn.png',
            'shipping_type' => 'domestic',
            'is_default' => false,
            'is_active' => false,
            'information' => [
                'first_km' => '2km đầu - 16.000₫',
                'second_km' => '1km tiếp - 20.000₫',
                'additional_km' => 'Km tiếp theo - 6.000₫/km',
                'description' => 'Giao hàng nhanh với GHN',
                'delivery_time' => '45-60 phút',
                'max_distance' => 8
            ],
            'cod' => [
                'free_threshold' => 'Miễn phí dưới 500.000₫',
                'rate_1' => 'Từ 500.000₫-5.000.000₫: 0.6%',
                'rate_2' => 'Từ 5.000.000₫-10.000.000₫: 0.9%'
            ]
        ]);

        ShippingCarrier::create([
            'name' => 'Viettel Post',
            'logo' => 'https://example.com/logos/viettel.png',
            'shipping_type' => 'domestic',
            'is_default' => false,
            'is_active' => true,
            'information' => [
                'first_km' => '2km đầu - 14.000₫',
                'second_km' => '1km tiếp - 17.000₫',
                'additional_km' => 'Km tiếp theo - 4.500₫/km',
                'description' => 'Giao hàng với Viettel Post',
                'delivery_time' => '60-90 phút',
                'max_distance' => 15
            ],
            'cod' => [
                'free_threshold' => 'Miễn phí dưới 500.000₫',
                'rate_1' => 'Từ 500.000₫-5.000.000₫: 0.5%',
                'rate_2' => 'Từ 5.000.000₫-10.000.000₫: 0.7%'
            ]
        ]);

        // 3. Tạo carriers liên tỉnh (Inter-province)
        ShippingCarrier::create([
            'name' => 'Giao hàng tiết kiệm (GHTK)',
            'logo' => 'https://example.com/logos/ghtk.png',
            'shipping_type' => 'inter_province',
            'is_default' => true,
            'is_active' => true,
            'information' => [
                'first_km' => '1km đầu - 20.000₫',
                'second_km' => '1km tiếp - 25.000₫',
                'additional_km' => 'Km tiếp theo - 8.000₫/km',
                'description' => 'Giao hàng tiết kiệm liên tỉnh',
                'delivery_time' => '2-3 ngày',
                'max_distance' => 50
            ],
            'cod' => [
                'free_threshold' => 'Miễn phí dưới 1.000.000₫',
                'rate_1' => 'Từ 1.000.000₫-10.000.000₫: 0.8%',
                'rate_2' => 'Từ 10.000.000₫-20.000.000₫: 1.2%'
            ]
        ]);

        ShippingCarrier::create([
            'name' => 'J&T Express',
            'logo' => 'https://example.com/logos/jt.png',
            'shipping_type' => 'inter_province',
            'is_default' => false,
            'is_active' => true,
            'information' => [
                'first_km' => '1km đầu - 18.000₫',
                'second_km' => '1km tiếp - 22.000₫',
                'additional_km' => 'Km tiếp theo - 7.000₫/km',
                'description' => 'Giao hàng nhanh liên tỉnh với J&T',
                'delivery_time' => '1-2 ngày',
                'max_distance' => 30
            ],
            'cod' => [
                'free_threshold' => 'Miễn phí dưới 1.000.000₫',
                'rate_1' => 'Từ 1.000.000₫-10.000.000₫: 0.7%',
                'rate_2' => 'Từ 10.000.000₫-20.000.000₫: 1.0%'
            ]
        ]);

        ShippingCarrier::create([
            'name' => 'Ninja Van',
            'logo' => 'https://example.com/logos/ninja.png',
            'shipping_type' => 'inter_province',
            'is_default' => false,
            'is_active' => false,
            'information' => [
                'first_km' => '1km đầu - 22.000₫',
                'second_km' => '1km tiếp - 28.000₫',
                'additional_km' => 'Km tiếp theo - 9.000₫/km',
                'description' => 'Giao hàng cao cấp liên tỉnh',
                'delivery_time' => '1 ngày',
                'max_distance' => 25
            ],
            'cod' => [
                'free_threshold' => 'Miễn phí dưới 2.000.000₫',
                'rate_1' => 'Từ 2.000.000₫-15.000.000₫: 0.5%',
                'rate_2' => 'Từ 15.000.000₫-30.000.000₫: 0.8%'
            ]
        ]);
    }
}
