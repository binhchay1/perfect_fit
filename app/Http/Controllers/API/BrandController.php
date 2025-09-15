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

/**
 * @OA\Tag(
 *     name="Brands",
 *     description="Brand management operations"
 * )
 */

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
     * @OA\Get(
     *     path="/brands",
     *     summary="Get all brands",
     *     description="Retrieve a paginated list of all brands",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Brands retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Brands retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Nike"),
     *                         @OA\Property(property="slug", type="string", example="nike"),
     *                         @OA\Property(property="description", type="string", example="Nike brand description"),
     *                         @OA\Property(property="logo_path", type="string", example="images/brands/nike-logo.png"),
     *                         @OA\Property(property="status", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string"),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="prev_page_url", type="string"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/brand/{brand}",
     *     summary="Get brand by slug",
     *     description="Retrieve a specific brand by its slug",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="brand",
     *         in="path",
     *         required=true,
     *         description="Brand slug",
     *         @OA\Schema(type="string", example="nike")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Brand retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Brand retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nike"),
     *                 @OA\Property(property="slug", type="string", example="nike"),
     *                 @OA\Property(property="description", type="string", example="Nike brand description"),
     *                 @OA\Property(property="logo_path", type="string", example="images/brands/nike-logo.png"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Brand not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Brand not found")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/brands/search",
     *     summary="Search brands",
     *     description="Search brands by keyword",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         required=true,
     *         description="Search keyword",
     *         @OA\Schema(type="string", example="nike")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Brands search completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Brands search completed"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike"),
     *                     @OA\Property(property="slug", type="string", example="nike"),
     *                     @OA\Property(property="description", type="string", example="Nike brand description"),
     *                     @OA\Property(property="logo_path", type="string", example="images/brands/nike-logo.png"),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Keyword is required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Keyword is required")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/brand/with-products",
     *     summary="Get brands with products",
     *     description="Retrieve brands that have associated products",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of brands to return",
     *         required=false,
     *         @OA\Schema(type="integer", example=10, default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Brands with products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Brands with products retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike"),
     *                     @OA\Property(property="slug", type="string", example="nike"),
     *                     @OA\Property(property="description", type="string", example="Nike brand description"),
     *                     @OA\Property(property="logo_path", type="string", example="images/brands/nike-logo.png"),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="products_count", type="integer", example=25),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
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
