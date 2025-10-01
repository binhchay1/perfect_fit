<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ShippingSettingsRepository;
use App\Models\ShippingSettings;
use App\Http\Requests\UpdateShippingSettingsRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShippingSettingsController extends Controller
{
    protected $shippingSettingsRepository;

    public function __construct(ShippingSettingsRepository $shippingSettingsRepository)
    {
        $this->shippingSettingsRepository = $shippingSettingsRepository;
    }

    /**
     * Get shipping settings.
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = ShippingSettings::first();

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipping settings not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => 'Shipping settings retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve shipping settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update shipping settings.
     */
    public function updateSettings(UpdateShippingSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Xử lý carriers_id nếu có
            if (isset($data['carriers_id'])) {
                $settings = $this->shippingSettingsRepository->getSettings();
                $settings->setCarriersFromIds($data['carriers_id']);
                unset($data['carriers_id']); // Xóa khỏi data để không update trực tiếp
            }

            $settings = $this->shippingSettingsRepository->updateSettings($data);

            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => 'Shipping settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shipping settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
