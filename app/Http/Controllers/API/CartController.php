<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\CartRepository;
use App\Repositories\CartItemRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductColorRepository;
use App\Repositories\ProductSizeRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    use ApiResponseTrait;

    protected $cartRepository;
    protected $cartItemRepository;
    protected $productRepository;
    protected $productColorRepository;
    protected $productSizeRepository;

    public function __construct(
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository,
        ProductRepository $productRepository,
        ProductColorRepository $productColorRepository,
        ProductSizeRepository $productSizeRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartItemRepository = $cartItemRepository;
        $this->productRepository = $productRepository;
        $this->productColorRepository = $productColorRepository;
        $this->productSizeRepository = $productSizeRepository;
    }

    /**
     * Display a listing of the user's cart items.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $cart = $this->cartRepository->getForUserWithItems($user->id);

            if (!$cart) {
                return $this->successResponse([
                    'cart_items' => [],
                    'total_items' => 0,
                    'total_amount' => 0,
                ], 'Cart is empty');
            }

            return $this->successResponse([
                'cart_items' => $cart->cartItems,
                'total_items' => $cart->total_items,
                'total_amount' => $cart->total_amount,
            ], 'Cart items retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cart items', 500);
        }
    }

    /**
     * Add a product to the cart.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'product_color_id' => 'required|exists:product_colors,id',
                'product_size_id' => 'required|exists:product_sizes,id',
                'quantity' => 'required|integer|min:1|max:10',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $user = Auth::user();

            // Validate product, color, and size using repositories
            $validation = $this->productRepository->validateForCart(
                $request->product_id,
                $request->product_color_id,
                $request->product_size_id
            );

            if (!$validation['valid']) {
                return $this->errorResponse($validation['error'], 400);
            }

            $product = $validation['product'];
            $productColor = $this->productColorRepository->getByIdWithRelations($request->product_color_id);
            $productSize = $this->productSizeRepository->getByIdWithRelations($request->product_size_id);

            // Check stock availability
            if (!$this->productSizeRepository->hasStock($request->product_size_id, $request->quantity)) {
                $availableStock = $this->productSizeRepository->getAvailableStock($request->product_size_id);
                return $this->errorResponse('Insufficient stock. Available: ' . $availableStock, 400);
            }

            // Get or create user's cart
            $cart = $this->cartRepository->getOrCreateForUser($user->id);

            // Check if the same item already exists in cart
            $existingCartItem = $this->cartItemRepository->findExistingItem(
                $cart->id,
                $request->product_id,
                $request->product_color_id,
                $request->product_size_id
            );

            if ($existingCartItem) {
                // Update quantity if item already exists
                $newQuantity = $existingCartItem->quantity + $request->quantity;

                if ($this->cartItemRepository->exceedsMaxQuantity($existingCartItem->quantity, $request->quantity)) {
                    return $this->errorResponse('Maximum quantity per item is 10', 400);
                }

                if (!$this->productSizeRepository->hasStock($request->product_size_id, $newQuantity)) {
                    $availableStock = $this->productSizeRepository->getAvailableStock($request->product_size_id);
                    return $this->errorResponse('Insufficient stock. Available: ' . $availableStock, 400);
                }

                $this->cartItemRepository->updateQuantity($existingCartItem->id, $newQuantity);
                $cartItem = $this->cartItemRepository->getByIdWithRelations($existingCartItem->id);
            } else {
                // Create new cart item
                $cartItem = $this->cartItemRepository->createItem(
                    $cart->id,
                    $request->product_id,
                    $request->product_color_id,
                    $request->product_size_id,
                    $request->quantity,
                    $product->price,
                    $productSize->size_name,
                    $productColor->color_name
                );

                $cartItem = $this->cartItemRepository->getByIdWithRelations($cartItem->id);
            }

            return $this->successResponse($cartItem, 'Product added to cart successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add product to cart', 500);
        }
    }

    /**
     * Update the quantity of a cart item.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1|max:10',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $user = Auth::user();
            $cartItem = $this->cartItemRepository->getForUser($id, $user->id);

            if (!$cartItem) {
                return $this->errorResponse('Cart item not found', 404);
            }

            $this->cartItemRepository->updateQuantity($cartItem->id, $request->quantity);
            $updatedCartItem = $this->cartItemRepository->getByIdWithRelations($cartItem->id);

            return $this->successResponse($updatedCartItem, 'Cart item updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update cart item', 500);
        }
    }

    /**
     * Remove a product from the cart.
     */
    public function destroy(string $id)
    {
        try {
            $user = Auth::user();
            $deleted = $this->cartItemRepository->deleteForUser($id, $user->id);

            if (!$deleted) {
                return $this->errorResponse('Cart item not found', 404);
            }

            return $this->successResponse(null, 'Product removed from cart successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove product from cart', 500);
        }
    }

    /**
     * Clear all items from the user's cart.
     */
    public function clear()
    {
        try {
            $user = Auth::user();
            $cleared = $this->cartRepository->clearForUser($user->id);

            if (!$cleared) {
                return $this->successResponse(null, 'Cart is already empty');
            }

            return $this->successResponse(null, 'Cart cleared successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to clear cart', 500);
        }
    }

    /**
     * Get cart summary (total items and price).
     */
    public function summary()
    {
        try {
            $user = Auth::user();
            $summary = $this->cartRepository->getSummaryForUser($user->id);

            return $this->successResponse($summary, 'Cart summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cart summary', 500);
        }
    }
}
