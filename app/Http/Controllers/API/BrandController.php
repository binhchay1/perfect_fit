<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Repositories\BrandRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use ApiResponseTrait;

    private $brandRepository;
    private $utility;

    public function __construct(
        BrandRepository $brandRepository,
        Utility $utility
    ) {
        $this->brandRepository = $brandRepository;
        $this->utility = $utility;
    }

    /**
     * Display a listing of brands
     */
    public function index(): JsonResponse
    {
        try {
            $brands = $this->brandRepository->index();
            $listBrands = $this->utility->paginate($brands, 15);
            return $this->successResponse($listBrands, 'Brands retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve brands', $e->getMessage());
        }
    }

    /**
     * Get brand by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $brand = $this->brandRepository->showBySlug($slug);

            if (!$brand) {
                return $this->notFoundResponse('Brand not found');
            }

            return $this->successResponse($brand, 'Brand retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve brand', $e->getMessage());
        }
    }
    /**
     * Search brands
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword', '');

            if (empty($keyword)) {
                return $this->errorResponse('Keyword is required', 400);
            }

            $brands = $this->brandRepository->search($keyword);

            return $this->successResponse($brands, 'Brands search completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to search brands', $e->getMessage());
        }
    }
    /**
     * Get brands with products
     */
    public function getWithProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $brands = $this->brandRepository->getWithProducts($limit);

            return $this->successResponse($brands, 'Brands with products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve brands with products', $e->getMessage());
        }
    }

}
