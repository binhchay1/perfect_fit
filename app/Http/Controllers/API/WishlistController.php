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
     * Display a listing of the user's wishlist items.
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
     * Add a product to the wishlist.
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
     * Remove a product from the wishlist.
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
     * Remove product from wishlist by product ID.
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
     * Clear all items from the user's wishlist.
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
     * Get wishlist count for user.
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
     * Check if product is in wishlist.
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
