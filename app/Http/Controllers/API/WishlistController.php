<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Repositories\WishlistRepository;
use App\Repositories\ProductRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Wishlist",
 *     description="Wishlist management operations"
 * )
 */
class WishlistController extends Controller
{
    use ApiResponseTrait;

    protected $wishlistRepository;
    protected $productRepository;
    protected $utility;

    public function __construct(
        WishlistRepository $wishlistRepository,
        Utility $utility,
        ProductRepository $productRepository
    ) {
        $this->wishlistRepository = $wishlistRepository;
        $this->utility = $utility;
        $this->productRepository = $productRepository;
    }

    /**
     * @OA\Get(
     *     path="/wishlist",
     *     summary="Get user's wishlist",
     *     description="Retrieve a paginated list of the authenticated user's wishlist items",
     *     tags={"Wishlist"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wishlist items retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Wishlist items retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="product", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                             @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                             @OA\Property(property="price", type="number", format="float", example=150.00),
     *                             @OA\Property(property="brand", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Nike")
     *                             ),
     *                             @OA\Property(property="images", type="array",
     *                                 @OA\Items(
     *                                     @OA\Property(property="image_path", type="string", example="images/products/nike-air-max.jpg"),
     *                                     @OA\Property(property="is_primary", type="boolean", example=true)
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string"),
     *                 @OA\Property(property="prev_page_url", type="string"),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
         
            $wishListItems = $this->wishlistRepository->getForUserWithRelations($user->id);
            $listWishListItems= $this->utility->paginate($wishListItems, 15);

            return $this->successResponse($listWishListItems, 'Wishlist items retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve wishlist items', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/wishlist",
     *     summary="Add product to wishlist",
     *     description="Add a product to the authenticated user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id"},
     *             @OA\Property(property="product_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product added to wishlist successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product added to wishlist successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="product", type="object",
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
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Product not available or already in wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product is not available")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $user = Auth::user();
            $productId = $request->product_id;

            // Check if product is active
            if (!$this->productRepository->isActive($productId)) {
                return $this->errorResponse('Product is not available', 400);
            }

            // Check if product is already in wishlist
            if ($this->wishlistRepository->isInWishlist($user->id, $productId)) {
                return $this->errorResponse('Product is already in your wishlist', 400);
            }

            $wishlistItem = $this->wishlistRepository->addToWishlist($user->id, $productId);
            $wishlistItem->load(['product.brand', 'product.colors.sizes']);

            return $this->successResponse($wishlistItem, 'Product added to wishlist successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add product to wishlist', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/wishlist/{id}",
     *     summary="Remove item from wishlist",
     *     description="Remove a specific item from the authenticated user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Wishlist item ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product removed from wishlist successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product removed from wishlist successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Wishlist item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Wishlist item not found")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $user = Auth::user();
            $deleted = $this->wishlistRepository->deleteForUser($id, $user->id);

            if (!$deleted) {
                return $this->errorResponse('Wishlist item not found', 404);
            }

            return $this->successResponse(null, 'Product removed from wishlist successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove product from wishlist', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/wishlist/remove-by-product",
     *     summary="Remove product from wishlist by product ID",
     *     description="Remove a product from wishlist using product ID instead of wishlist item ID",
     *     tags={"Wishlist"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id"},
     *             @OA\Property(property="product_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product removed from wishlist successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product removed from wishlist successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found in wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found in your wishlist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function removeByProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $user = Auth::user();
            $removed = $this->wishlistRepository->removeFromWishlist($user->id, $request->product_id);

            if (!$removed) {
                return $this->errorResponse('Product not found in your wishlist', 404);
            }

            return $this->successResponse(null, 'Product removed from wishlist successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove product from wishlist', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/wishlist",
     *     summary="Clear wishlist",
     *     description="Remove all items from the authenticated user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wishlist cleared successfully or already empty",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Wishlist cleared successfully")
     *         )
     *     )
     * )
     */
    public function clear()
    {
        try {
            $user = Auth::user();
            $cleared = $this->wishlistRepository->clearForUser($user->id);

            if (!$cleared) {
                return $this->successResponse(null, 'Wishlist is already empty');
            }

            return $this->successResponse(null, 'Wishlist cleared successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to clear wishlist', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/wishlist/count",
     *     summary="Get wishlist count",
     *     description="Get the total number of items in the authenticated user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wishlist count retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Wishlist count retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="count", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
    public function count()
    {
        try {
            $user = Auth::user();
            $count = $this->wishlistRepository->getCountForUser($user->id);

            return $this->successResponse(['count' => $count], 'Wishlist count retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve wishlist count', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/wishlist/check",
     *     summary="Check if product is in wishlist",
     *     description="Check whether a specific product is in the authenticated user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id"},
     *             @OA\Property(property="product_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wishlist status checked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Wishlist status checked successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_in_wishlist", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function check(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $user = Auth::user();
            $isInWishlist = $this->wishlistRepository->isInWishlist($user->id, $request->product_id);

            return $this->successResponse(['is_in_wishlist' => $isInWishlist], 'Wishlist status checked successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check wishlist status', 500);
        }
    }
}
