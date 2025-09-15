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

/**
 * @OA\Tag(
 *     name="Cart",
 *     description="Shopping cart management operations"
 * )
 */

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
     * @OA\Get(
     *     path="/cart",
     *     summary="Get user's cart items",
     *     description="Retrieve all items in the authenticated user's shopping cart",
     *     tags={"Cart"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart items retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart items retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="cart_items", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=150.00),
     *                         @OA\Property(property="size_name", type="string", example="42"),
     *                         @OA\Property(property="color_name", type="string", example="Black"),
     *                         @OA\Property(property="product", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                             @OA\Property(property="slug", type="string", example="nike-air-max")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="total_items", type="integer", example=3),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=450.00)
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
     * @OA\Post(
     *     path="/cart",
     *     summary="Add product to cart",
     *     description="Add a product with specific color and size to the user's shopping cart",
     *     tags={"Cart"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","product_color_id","product_size_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="product_color_id", type="integer", example=1),
     *             @OA\Property(property="product_size_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", minimum=1, maximum=10, example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product added to cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product added to cart successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="cart_id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="product_color_id", type="integer", example=1),
     *                 @OA\Property(property="product_size_id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="size_name", type="string", example="42"),
     *                 @OA\Property(property="color_name", type="string", example="Black")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or insufficient stock",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Insufficient stock. Available: 5")
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
     * @OA\Put(
     *     path="/cart/{id}",
     *     summary="Update cart item quantity",
     *     description="Update the quantity of a specific cart item",
     *     tags={"Cart"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Cart item ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", minimum=1, maximum=10, example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart item updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart item updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cart item not found")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/cart/{id}",
     *     summary="Remove item from cart",
     *     description="Remove a specific item from the user's shopping cart",
     *     tags={"Cart"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Cart item ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product removed from cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product removed from cart successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cart item not found")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/cart",
     *     summary="Clear cart",
     *     description="Remove all items from the user's shopping cart",
     *     tags={"Cart"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart cleared successfully")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/cart/summary",
     *     summary="Get cart summary",
     *     description="Get summary information about the user's cart (total items and amount)",
     *     tags={"Cart"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart summary retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_items", type="integer", example=3),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=450.00)
     *             )
     *         )
     *     )
     * )
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