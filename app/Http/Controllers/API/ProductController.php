<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management operations"
 * )
 */

class ProductController extends Controller
{
    use ApiResponseTrait;

    private $productRepository;
    protected $utility;

    public function __construct(
        ProductRepository $productRepository,
        Utility $utility
    ) {
        $this->productRepository = $productRepository;
        $this->utility = $utility;
    }

    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Get all products",
     *     description="Retrieve a paginated list of all products",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                         @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                         @OA\Property(property="description", type="string", example="Comfortable running shoes"),
     *                         @OA\Property(property="price", type="number", format="float", example=150.00),
     *                         @OA\Property(property="gender", type="string", example="men"),
     *                         @OA\Property(property="product_type", type="string", example="sneakers"),
     *                         @OA\Property(property="status", type="integer", example=1),
     *                         @OA\Property(property="brand", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Nike"),
     *                             @OA\Property(property="slug", type="string", example="nike")
     *                         ),
     *                         @OA\Property(property="images", type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="image_path", type="string", example="images/products/nike-air-max.jpg"),
     *                                 @OA\Property(property="is_primary", type="boolean", example=true)
     *                             )
     *                         ),
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
            $products = $this->productRepository->index();
            $listProducts = $this->utility->paginate($products, 15);

            return $this->successResponse($listProducts, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/product/{product}",
     *     summary="Get product by slug",
     *     description="Retrieve a specific product by its slug",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product slug",
     *         @OA\Schema(type="string", example="nike-air-max")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                 @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                 @OA\Property(property="description", type="string", example="Comfortable running shoes"),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="gender", type="string", example="men"),
     *                 @OA\Property(property="product_type", type="string", example="sneakers"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="brand", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike")
     *                 ),
     *                 @OA\Property(property="images", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="image_path", type="string", example="images/products/nike-air-max.jpg"),
     *                         @OA\Property(property="is_primary", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="sizes", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="size", type="string", example="42"),
     *                         @OA\Property(property="stock_quantity", type="integer", example=10)
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $product = $this->productRepository->showBySlug($slug);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve product', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/products/search",
     *     summary="Search products",
     *     description="Search products by keyword",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         required=true,
     *         description="Search keyword",
     *         @OA\Schema(type="string", example="nike")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products search completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products search completed"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                     @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="brand", type="object",
     *                         @OA\Property(property="name", type="string", example="Nike")
     *                     )
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

            $products = $this->productRepository->search($keyword);

            return $this->successResponse($products, 'Products search completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to search products', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/product/brand/{brandId}",
     *     summary="Get products by brand",
     *     description="Retrieve products by brand ID",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="brandId",
     *         in="path",
     *         required=true,
     *         description="Brand ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products by brand retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products by brand retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                     @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getByBrand($brandId): JsonResponse
    {
        try {
            $products = $this->productRepository->getByBrand($brandId);

            return $this->successResponse($products, 'Products by brand retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products by brand', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/product/gender/{gender}",
     *     summary="Get products by gender",
     *     description="Retrieve products by gender",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="gender",
     *         in="path",
     *         required=true,
     *         description="Gender (men, women, unisex)",
     *         @OA\Schema(type="string", enum={"men", "women", "unisex"}, example="men")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products by gender retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products for men retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getByGender($gender): JsonResponse
    {
        try {
            $products = $this->productRepository->getByGender($gender);

            return $this->successResponse($products, "Products for {$gender} retrieved successfully");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products by gender', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/products/filters",
     *     summary="Get products with filters",
     *     description="Retrieve products with advanced filtering options",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="brand_id",
     *         in="query",
     *         description="Filter by brand ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="Filter by gender",
     *         required=false,
     *         @OA\Schema(type="string", enum={"men", "women", "unisex"}, example="men")
     *     ),
     *     @OA\Parameter(
     *         name="product_type",
     *         in="query",
     *         description="Filter by product type",
     *         required=false,
     *         @OA\Schema(type="string", example="sneakers")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=50.00)
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=200.00)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search keyword",
     *         required=false,
     *         @OA\Schema(type="string", example="nike")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Filtered products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Filtered products retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getWithFilters(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['brand_id', 'gender', 'product_type', 'min_price', 'max_price', 'search']);
            $products = $this->productRepository->getWithFilters($filters);

            return $this->successResponse($products, 'Filtered products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve filtered products', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/product/featured",
     *     summary="Get featured products",
     *     description="Retrieve featured products",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of featured products to return",
     *         required=false,
     *         @OA\Schema(type="integer", example=10, default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Featured products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Featured products retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                     @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="brand", type="object",
     *                         @OA\Property(property="name", type="string", example="Nike")
     *                     ),
     *                     @OA\Property(property="images", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="image_path", type="string", example="images/products/nike-air-max.jpg")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getFeatured(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $products = $this->productRepository->getFeatured($limit);

            return $this->successResponse($products, 'Featured products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve featured products', $e->getMessage());
        }
    }
}
