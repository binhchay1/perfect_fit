<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCarrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'shipping_type',
        'is_default',
        'is_active',
        'information',
        'cod',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'information' => 'array',
        'cod' => 'array',
    ];

    /**
     * Scope for active carriers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for domestic shipping.
     */
    public function scopeDomestic($query)
    {
        return $query->where('shipping_type', 'domestic');
    }

    /**
     * Scope for inter-province shipping.
     */
    public function scopeInterProvince($query)
    {
        return $query->where('shipping_type', 'inter_province');
    }

    /**
     * Calculate shipping cost based on distance.
     */
    public function calculateShippingCost($distance)
    {
        $totalCost = 0;
        $info = $this->information;

        // First km pricing
        if ($distance > 0 && isset($info['first_km'])) {
            $firstKmDistance = min($distance, $info['first_km']['distance']);
            $totalCost += $firstKmDistance * $info['first_km']['price'];
            $distance -= $firstKmDistance;
        }

        // Second km pricing
        if ($distance > 0 && isset($info['second_km'])) {
            $secondKmDistance = min($distance, $info['second_km']['distance']);
            $totalCost += $secondKmDistance * $info['second_km']['price'];
            $distance -= $secondKmDistance;
        }

        // Additional km pricing
        if ($distance > 0 && isset($info['additional_km'])) {
            $totalCost += $distance * $info['additional_km']['price'];
        }

        return $totalCost;
    }

    /**
     * Calculate COD fee based on order amount.
     */
    public function calculateCodFee($orderAmount)
    {
        $cod = $this->cod;

        if ($orderAmount <= $cod['free_threshold']) {
            return 0;
        }

        if ($orderAmount <= $cod['rate_1']['threshold']) {
            return $orderAmount * ($cod['rate_1']['percentage'] / 100);
        }

        if ($orderAmount <= $cod['rate_2']['threshold']) {
            return $orderAmount * ($cod['rate_2']['percentage'] / 100);
        }

        return $orderAmount * ($cod['rate_2']['percentage'] / 100);
    }

    /**
     * Get pricing summary for display.
     */
    public function getPricingSummary()
    {
        $info = $this->information;
        $summary = [];

        if (isset($info['first_km'])) {
            $summary[] = "{$info['first_km']['distance']}km đầu: " . number_format($info['first_km']['price']) . "₫";
        }

        if (isset($info['second_km'])) {
            $summary[] = "{$info['second_km']['distance']}km tiếp: " . number_format($info['second_km']['price']) . "₫";
        }

        if (isset($info['additional_km'])) {
            $summary[] = "Km tiếp theo: " . number_format($info['additional_km']['price']) . "₫/km";
        }

        return implode(', ', $summary);
    }

    /**
     * Get COD summary for display.
     */
    public function getCodSummary()
    {
        $cod = $this->cod;
        $summary = [];

        $summary[] = "Miễn phí dưới " . number_format($cod['free_threshold']) . "₫";
        $summary[] = "Từ " . number_format($cod['free_threshold']) . "₫-" . number_format($cod['rate_1']['threshold']) . "₫: {$cod['rate_1']['percentage']}%";
        $summary[] = "Từ " . number_format($cod['rate_1']['threshold']) . "₫-" . number_format($cod['rate_2']['threshold']) . "₫: {$cod['rate_2']['percentage']}%";

        return implode(', ', $summary);
    }
}
