<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ShippingCarrierRepository;
use App\Models\ShippingCarrier;
use App\Http\Requests\CreateShippingCarrierRequest;
use App\Http\Requests\UpdateShippingCarrierRequest;
use App\Http\Requests\ToggleCarrierRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShippingCarrierController extends Controller
{
    protected $shippingCarrierRepository;

    public function __construct(ShippingCarrierRepository $shippingCarrierRepository)
    {
        $this->shippingCarrierRepository = $shippingCarrierRepository;
    }

    /**
     * Get domestic carriers only.
     */
    public function getDomesticCarriers(): JsonResponse
    {
        try {
            $data = $this->shippingCarrierRepository->getByShippingType('domestic');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Domestic carriers retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve domestic carriers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inter-province carriers only.
     */
    public function getInterProvinceCarriers(): JsonResponse
    {
        try {
            $data = $this->shippingCarrierRepository->getByShippingType('inter_province');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Inter-province carriers retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inter-province carriers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new carrier.
     */
    public function createCarrier(CreateShippingCarrierRequest $request): JsonResponse
    {
        try {
            $carrier = $this->shippingCarrierRepository->createCarrier($request->validated());

            return response()->json([
                'success' => true,
                'data' => $carrier,
                'message' => 'Carrier created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create carrier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a carrier.
     */
    public function updateCarrier(UpdateShippingCarrierRequest $request, int $id): JsonResponse
    {
        try {
            $carrier = $this->shippingCarrierRepository->updateCarrier($id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $carrier,
                'message' => 'Carrier updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update carrier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set carrier as default.
     */
    public function setAsDefault(int $id): JsonResponse
    {
        try {
            $carrier = $this->shippingCarrierRepository->setAsDefault($id);

            return response()->json([
                'success' => true,
                'data' => $carrier,
                'message' => 'Carrier set as default successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set carrier as default: ' . $e->getMessage()
            ], 500);
        }
    }

}
