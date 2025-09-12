<?php

namespace App\Http\Controllers\API\Admin;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Get all users for admin with optional filters
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
     * Show a specific user
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
     * Update user role and status only (Admin can only change role and status)
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
     * Delete a specific user
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
     * Toggle active status for a user
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
     * Get user statistics for admin dashboard
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