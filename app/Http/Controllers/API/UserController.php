<?php

namespace App\Http\Controllers\API;

use App\Enums\Users;
use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Jobs\SendEmailResetPassword;
use App\Repositories\PasswordRestRepository;
use App\Repositories\PublisherResourceRepository;
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="User",
 *     description="User management operations"
 * )
 */
class UserController extends Controller
{
    use ApiResponseTrait;
    protected $userRepository;
    protected $utility;
    protected $passwordResetRepository;

    public function __construct(
        UserRepository $userRepository,
        PasswordRestRepository $passwordResetRepository,
        Utility $utility
    ) {
        $this->userRepository = $userRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->utility = $utility;
    }

    public function me()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $dataUser = $this->userRepository->getDataUser($user->id);

            return $this->successResponse($dataUser, 'User information retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve user information', $e->getMessage());
        }
    }


    /**
     * @OA\Post(
     *     path="/update-info",
     *     summary="Update current user information",
     *     description="Update the current authenticated user's profile information",
     *     tags={"User"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="John Updated"),
     *                 @OA\Property(property="phone", type="string", example="1234567890"),
     *                 @OA\Property(property="country", type="string", example="Vietnam"),
     *                 @OA\Property(property="province", type="string", example="Ho Chi Minh"),
     *                 @OA\Property(property="district", type="string", example="District 1"),
     *                 @OA\Property(property="ward", type="string", example="Ward 1"),
     *                 @OA\Property(property="address", type="string", example="123 Updated St"),
     *                 @OA\Property(property="postal_code", type="string", example="70000"),
     *                 @OA\Property(property="profile_photo_path", type="string", format="binary", description="Profile photo file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User information updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Updated"),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="message", type="string", example="Cập nhập thông tin thành công")
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
    public function updateCurrentUser(UserRequest $request)
    {
        $user = Auth::user();
        $input = $request->except(['_token']);
        if ($request->has('email') && $input['email'] !== $user->email) {
            unset($input['email']);
        }

        if (isset($input['profile_photo_path'])) {
            $img = $this->utility->saveImageUser($input);
            if ($img) {
                $path = 'images/upload/user/' . $input['profile_photo_path']->getClientOriginalName();
                $input['profile_photo_path'] = $path;
            }
        }
        $user = $this->userRepository->update($input, $user->id);
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Cập nhập thông tin thành công',
            'type' => 'update_user_success',
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/change-password",
     *     summary="Change user password",
     *     description="Change the current authenticated user's password",
     *     tags={"User"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword123"),
     *             @OA\Property(property="new_password", type="string", format="password", minLength=8, example="newpassword123"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Current password is incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Current password is incorrect")
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
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', 400);
            }

            $input = ['password' => bcrypt($request->new_password)];
            $this->userRepository->updatePassword($user->email, $input);

            return $this->successResponse(null, 'Password changed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to change password', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/forget-password",
     *     summary="Forgot password",
     *     description="Send password reset email to user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Password reset email sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token được tạo thành công, vui lòng kiểm tra email để thay đổi mật khẩu"),
     *             @OA\Property(property="type", type="string", example="send_password_success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email already sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email đã được gửi thông tin, hãy kiểm tra mail của bạn"),
     *             @OA\Property(property="type", type="string", example="data_email_sent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tài khoản email không tồn tại"),
     *             @OA\Property(property="type", type="string", example="email_user_not_exist")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        //check mail
        $dataEmail = $request->input('email');
        $user = $this->userRepository->getInfo($dataEmail);
        if (!isset($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản email không tồn tại',
                'type' => 'email_user_not_exist',
            ], 404);
        }

        $token = Str::random(64);

        $checkMail = $this->passwordResetRepository->getInfo($dataEmail);
        if ($checkMail) {
            return response()->json([
                'success' => false,
                'message' => 'Email đã được gửi thông tin, hãy kiểm tra mail của bạn',
                'type' => 'data_email_sent',
            ], 200);
        }

        //send mail
        $dataReset = [
            'email' => $dataEmail,
            'token' => $token,
            'created_at' => Carbon::now()
        ];

        $this->passwordResetRepository->createPassReset($dataReset);

        $dataMail = [
            'email' => $user->email,
            'token' => $token,
        ];
        $userMail = $user->email;

        SendEmailResetPassword::dispatch($userMail, $dataMail);

        return response()->json([
            'success' => true,
            'message' => 'Token được tạo thành công, vui lòng kiểm tra email để thay đổi mật khẩu',
            'type' => 'send_password_success',
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/reset-password/{token}",
     *     summary="Reset password",
     *     description="Reset user password using token from email",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Password reset token",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password","password_confirmation"},
     *             @OA\Property(property="password", type="string", format="password", minLength=6, example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bạn đã thay đổi mật khẩu thành công!"),
     *             @OA\Property(property="type", type="string", example="change_password_success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Hết thời gian lấy lại mật khẩu! Vui lòng xác thực lại"),
     *             @OA\Property(property="type", type="string", example="change_password_error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request, $token)
    {
        //update password
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $checkToken = $this->passwordResetRepository->checkToken($token);
        if (!isset($checkToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Hết thời gian lấy lại mật khẩu! Vui lòng xác thực lại',
                'type' => 'change_password_error',
            ], 400);
        }

        //reset password
        $currentTimestamp = Carbon::now('Asia/Ho_Chi_Minh');
        $tokenCreatedAt = Carbon::parse($checkToken->created_at);
        $expiresTokenTime = $tokenCreatedAt->addMinutes(15);

        if ($currentTimestamp->gt($expiresTokenTime)) {
            $this->passwordResetRepository->destroy($token);
            return response()->json([
                'success' => false,
                'message' => 'Hết thời gian lấy lại mật khẩu! Vui lòng xác thực lại',
                'type' => 'change_password_error',
            ], 400);
        }

        $input['password'] = bcrypt($request->password);
        $this->userRepository->updatePassword($checkToken->email, $input);
        $this->passwordResetRepository->destroy($token);

        return response()->json([
            'success' => true,
            'message' => 'Bạn đã thay đổi mật khẩu thành công! ',
            'type' => 'change_password_success',
        ], 201);
    }
}
