<?php

namespace App\Http\Controllers\API\Admin;

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
     * Get all brands (including inactive) for admin
     */
    public function getAll(): JsonResponse
    {
        try {
            $brands = $this->brandRepository->getAll();
            $listBrands = $this->utility->paginate($brands, 15);
            return $this->successResponse($listBrands, 'All brands retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve all brands', $e->getMessage());
        }
    }

    /**
     * Store a newly created brand
     */
    public function store(BrandRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle logo upload
            if (isset($data['logo'])) {
                $img = $this->utility->saveLogoBrand($data);
                if ($img) {
                    $path = '/images/brand/logo' . $data['logo']->getClientOriginalName();
                    $data['logo'] = $path;
                }
            }

            $brand = $this->brandRepository->store($data);

            DB::commit();

            $brand->loadCount(['products', 'activeProducts']);

            return $this->successResponse($brand, 'Brand created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to create brand', $e->getMessage());
        }
    }

    /**
     * Display the specified brand
     */
    public function show(string $id): JsonResponse
    {
        try {
            $brand = $this->brandRepository->show($id);

            if (!$brand) {
                return $this->notFoundResponse('Brand not found');
            }

            return $this->successResponse($brand, 'Brand retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve brand', $e->getMessage());
        }
    }

    /**
     * Update the specified brand
     */
    public function update(BrandRequest $request, string $id): JsonResponse
    {
        try {
            $brand = $this->brandRepository->show($id);

            if (!$brand) {
                return $this->notFoundResponse('Brand not found');
            }

            DB::beginTransaction();

            $data = $request->validated();

            // Generate slug if name changed and slug not provided
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle logo upload
            if (isset($data['logo'])) {
                $img = $this->utility->saveLogoBrand($data);
                if ($img) {
                    $path = '/images/brand/logo' . $data['logo']->getClientOriginalName();
                    $data['logo'] = $path;
                }
            }

            $this->brandRepository->update($data, $id);

            DB::commit();

            $brand->refresh();
            $brand->loadCount(['products', 'activeProducts']);

            return $this->successResponse($brand, 'Brand updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to update brand', $e->getMessage());
        }
    }

    /**
     * Remove the specified brand
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $brand = $this->brandRepository->show($id);

            if (!$brand) {
                return $this->notFoundResponse('Brand not found');
            }

            DB::beginTransaction();

            // Delete the brand
            $this->brandRepository->deleteBrand($id);

            DB::commit();

            return $this->successResponse(null, 'Brand deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete brand', $e->getMessage());
        }
    }

    /**
     * Toggle brand active status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $brand = $this->brandRepository->toggleStatus($id);

            if (!$brand) {
                return $this->notFoundResponse('Brand not found');
            }

            return $this->successResponse($brand, 'Brand status updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update brand status', $e->getMessage());
        }
    }
}
