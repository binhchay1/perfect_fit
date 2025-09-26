<?php

namespace App\Http\Controllers\API\Admin;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Admin Users",
 *     description="Admin user management operations"
 * )
 */
class UserController extends Controller
{
    use ApiResponseTrait;

    protected $userRepository;
    protected $utility;

    public function __construct(
        UserRepository $userRepository,
        Utility $utility
    ) {
        $this->userRepository = $userRepository;
        $this->utility = $utility;
    }

    /**
     * @OA\Get(
     *     path="/admin/users",
     *     summary="Get all users",
     *     description="Get a paginated list of all users with optional filtering",
     *     tags={"Admin Users"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of users per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for user name or email",
     *         required=false,
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by user role",
     *         required=false,
     *         @OA\Schema(type="string", enum={"admin", "user"}, example="user")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by user status",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1}, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com"),
     *                         @OA\Property(property="phone", type="string", example="1234567890"),
     *                         @OA\Property(property="role", type="string", example="user"),
     *                         @OA\Property(property="status", type="integer", example=1),
     *                         @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-12-26T10:00:00Z"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
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
     *     )
     * )
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $search = $request->get('search');
            $role = $request->get('role');
            $status = $request->get('status');

            if ($search || $role || $status !== null) {
                $users = $this->userRepository->getAllForAdmin($perPage, $search, $role, $status);
                return $this->successResponse($users, 'Users retrieved successfully');
            }

            $users = $this->userRepository->index();
            $paginated = $this->utility->paginate($users, $perPage);
            return $this->successResponse($paginated, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve users', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/user/{id}",
     *     summary="Get specific user",
     *     description="Get detailed information about a specific user by ID",
     *     tags={"Admin Users"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="1234567890"),
     *                 @OA\Property(property="country", type="string", example="Vietnam"),
     *                 @OA\Property(property="province", type="string", example="Ho Chi Minh"),
     *                 @OA\Property(property="district", type="string", example="District 1"),
     *                 @OA\Property(property="ward", type="string", example="Ward 1"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="postal_code", type="string", example="70000"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-12-26T10:00:00Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }
            return $this->successResponse($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve user', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/user/{id}",
     *     summary="Update user",
     *     description="Update user role and status (admin can only modify role and status, not personal info)",
     *     tags={"Admin Users"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", enum={0, 1}, example=1),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User role/status updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="status", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No valid fields to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No valid fields to update. Admin can only update role and status.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $validated = $request->validate([
                'status' => 'sometimes|integer|in:0,1',
                'role' => 'sometimes|string|in:admin,user',
            ]);

            // Admin can only update role and status, not personal info
            if (empty($validated)) {
                return $this->errorResponse('No valid fields to update. Admin can only update role and status.', 400);
            }

            DB::beginTransaction();

            $this->userRepository->update($validated, $id);

            DB::commit();

            $user->refresh();
            return $this->successResponse($user, 'User role/status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to update user', $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/user/{id}",
     *     summary="Delete user",
     *     description="Delete a specific user by ID",
     *     tags={"Admin Users"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            DB::beginTransaction();
            $this->userRepository->deleteUser($id);
            DB::commit();

            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete user', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/user/{id}/toggle-status",
     *     summary="Toggle user status",
     *     description="Toggle the active/inactive status of a specific user",
     *     tags={"Admin Users"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User status updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="status", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $user = $this->userRepository->toggleStatus($id);
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }
            return $this->successResponse($user, 'User status updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update user status', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/users/statistics",
     *     summary="Get user statistics",
     *     description="Get comprehensive user statistics for admin dashboard",
     *     tags={"Admin Users"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_users", type="integer", example=1200),
     *                 @OA\Property(property="active_users", type="integer", example=1150),
     *                 @OA\Property(property="inactive_users", type="integer", example=50),
     *                 @OA\Property(property="admin_users", type="integer", example=3),
     *                 @OA\Property(property="regular_users", type="integer", example=1197),
     *                 @OA\Property(property="verified_users", type="integer", example=1100),
     *                 @OA\Property(property="unverified_users", type="integer", example=100)
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->userRepository->getStatistics();
            return $this->successResponse($stats, 'User statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve user statistics', $e->getMessage());
        }
    }
}