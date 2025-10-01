<?php

namespace App\Repositories;

use App\Models\ShippingSettings;

class ShippingSettingsRepository extends BaseRepository
{

    public function model()
    {
        return ShippingSettings::class;
    }

    /**
     * Get shipping settings.
     */
    public function getSettings()
    {
        $settings = $this->model->first();

        if (!$settings) {
            $settings = $this->model->create([
                'shop_name' => 'PerfectFit',
                'shipping_location' => [
                    'address' => '123 Đường Nguyễn Huệ',
                    'ward' => 'Phường Bến Nghé',
                    'district' => 'Quận 1',
                    'city' => 'Thành phố Hồ Chí Minh',
                    'province' => 'Hồ Chí Minh',
                    'country' => 'Việt Nam'
                ],
                'phone' => '0245354050',
                'enable_domestic_shipping' => true,
                'enable_inter_province_shipping' => false,
                'carriers_id' => []
            ]);
        }

        return $settings;
    }

    /**
     * Update shipping settings.
     */
    public function updateSettings(array $data)
    {
        $settings = $this->model->first();

        if (!$settings) {
            $settings = $this->model->create($data);
        } else {
            $settings->update($data);
        }

        return $settings;
    }
}
