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
use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
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
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy user',
                'type' => 'data_user_errors',
            ], 404);
        }

        $dataUser = $this->userRepository->getDataUser($user->id); //can be remove, because we already get user from auth

        return response()->json([
            'success' => true,
            'data' => $dataUser,
            'message' => 'Thông tin tài khoản user',
            'type' => 'data_user_success',
        ], 200);
    }
  

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
    
    public function changePassword(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Check if the current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Mật khẩu không trùng khớp',
                    'type' => 'password_user_incorrect',
                ],
                400
            );
        }

        // Update the password
        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Thay đổi mật khẩu thành công',
            'type' => 'password_change_success',
        ], 201);
    }
  
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
