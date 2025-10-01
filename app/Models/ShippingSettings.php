<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_name',
        'shipping_location',
        'phone',
        'enable_domestic_shipping',
        'enable_inter_province_shipping',
        'carriers_id',
    ];

    protected $casts = [
        'shipping_location' => 'array',
        'enable_domestic_shipping' => 'boolean',
        'enable_inter_province_shipping' => 'boolean',
        'carriers_id' => 'array',
    ];


    /**
     * Add carrier to selected carriers.
     */
    public function addSelectedCarrier(int $carrierId, string $shippingType, bool $isDefault = false): void
    {
        $carrier = ShippingCarrier::findOrFail($carrierId);

        if ($carrier->shipping_type !== $shippingType) {
            throw new \InvalidArgumentException('Carrier does not match shipping type');
        }

        $carriersId = $this->carriers_id ?? [];

        // Nếu set làm default, bỏ default của carrier khác cùng loại
        if ($isDefault) {
            foreach ($carriersId as &$carrierData) {
                if ($carrierData['shipping_type'] === $shippingType) {
                    $carrierData['is_default'] = false;
                }
            }
        }

        // Thêm carrier mới
        $carriersId[] = [
            'carrier_id' => $carrierId,
            'shipping_type' => $shippingType,
            'is_default' => $isDefault,
            'is_active' => true,
            'priority' => count($carriersId) + 1
        ];

        $this->update(['carriers_id' => $carriersId]);
    }

    /**
     * Set carriers from simple ID array.
     */
    public function setCarriersFromIds(array $carrierIds): void
    {
        $carriers = ShippingCarrier::whereIn('id', $carrierIds)->get();
        $carriersData = [];

        foreach ($carriers as $carrier) {
            $carriersData[] = [
                'carrier_id' => $carrier->id,
                'shipping_type' => $carrier->shipping_type,
                'is_default' => $carrier->is_default,
                'is_active' => $carrier->is_active,
                'priority' => count($carriersData) + 1
            ];
        }

        $this->update(['carriers_id' => $carriersData]);
    }


    /**
     * Get selected carriers with full carrier details.
     */
    public function getSelectedCarriersWithDetails(string $shippingType)
    {
        $carriersId = $this->carriers_id ?? [];
        $filteredCarriers = array_filter($carriersId, function ($carrier) use ($shippingType) {
            return $carrier['shipping_type'] === $shippingType;
        });

        $carrierIds = array_column($filteredCarriers, 'carrier_id');
        $carriers = ShippingCarrier::whereIn('id', $carrierIds)->get()->keyBy('id');

        return collect($filteredCarriers)->map(function ($selected) use ($carriers) {
            $carrier = $carriers->get($selected['carrier_id']);
            return [
                'carrier' => $carrier,
                'is_default' => $selected['is_default'],
                'is_active' => $selected['is_active'],
                'priority' => $selected['priority']
            ];
        })->sortBy('priority');
    }
}
