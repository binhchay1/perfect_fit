<?php

namespace App\Repositories;

use App\Models\ShippingCarrier;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ShippingCarrierRepository extends BaseRepository
{
    use ApiResponseTrait;

    public function model()
    {
        return ShippingCarrier::class;
    }

    /**
     * Get carriers by shipping type.
     */
    public function getByShippingType(string $shippingType)
    {
        $carriers = $this->model->where('shipping_type', $shippingType)
            ->orderBy('is_default', 'desc')   // Default trước
            ->orderBy('name', 'asc')
            ->get();

        $activeCarriers = $carriers->where('is_active', true);
        $defaultCarrier = $carriers->where('is_default', true)->first();

        return [
            'all_carriers' => $carriers,
            'active_carriers' => $activeCarriers,
            'default_carrier' => $defaultCarrier,
            'shipping_type' => $shippingType
        ];
    }

    /**
     * Create a new carrier.
     */
    public function createCarrier(array $data)
    {
        // If this is set as default, unset other defaults of the same type
        if (isset($data['is_default']) && $data['is_default']) {
            $this->model->where('shipping_type', $data['shipping_type'])
                ->update(['is_default' => false]);
        }

        return $this->model->create($data);
    }

    /**
     * Update a carrier.
     */
    public function updateCarrier(int $id, array $data)
    {
        $carrier = $this->model->findOrFail($id);

        // If this is set as default, unset other defaults of the same type
        if (isset($data['is_default']) && $data['is_default']) {
            $this->model->where('shipping_type', $carrier->shipping_type)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $carrier->update($data);
        return $carrier;
    }

    /**
     * Set carrier as default.
     */
    public function setAsDefault(int $id)
    {
        $carrier = $this->model->findOrFail($id);

        // Unset other defaults of the same type
        $this->model->where('shipping_type', $carrier->shipping_type)
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);

        $carrier->update(['is_default' => true]);
        return $carrier;
    }
    
}
