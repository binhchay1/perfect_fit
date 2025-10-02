<?php

namespace App\Services;

use App\Enums\OTP as OTPEnum;
use App\Models\OtpVerification;
use App\Repositories\OtpRepository;
use Illuminate\Support\Facades\Log;

final class OtpService
{
    private string $smsApiUrl;
    private string $smsApiKey;

    public function __construct(
        private readonly OtpRepository $otpRepository
    ) {
        $this->smsApiUrl = config('services.sms.url', 'https://sms-api.com');
        $this->smsApiKey = config('services.sms.key', '');
    }

    public function sendOtp(string $phone, string $purpose): array
    {
        try {
            $otp = $this->otpRepository->createOtp($phone, $purpose);

            $sent = $this->sendSms($phone, $otp->otp_code, $purpose);

            if ($sent) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'expires_at' => $otp->expires_at,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send OTP',
            ];
        } catch (\Exception $e) {
            Log::error('OTP Send Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send OTP',
            ];
        }
    }

    public function verifyOtp(string $phone, string $otpCode, string $purpose): array
    {
        $otp = $this->otpRepository->findValidOtp($phone, $otpCode, $purpose);

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ];
        }

        $this->otpRepository->markAsUsed($otp);

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
            'otp_id' => $otp->id,
        ];
    }

    public function resendOtp(string $phone, string $purpose): array
    {
        return $this->sendOtp($phone, $purpose);
    }

    private function sendSms(string $phone, string $otpCode, string $purpose): bool
    {
        try {
            $message = $this->getOtpMessage($otpCode, $purpose);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->smsApiKey,
                'Accept' => 'application/json',
            ])->post("{$this->smsApiUrl}/send-sms", [
                'phone' => $phone,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SMS Send Error: ' . $e->getMessage());
            Log::info("OTP for {$phone}: {$otpCode}");
            return true;
        }
    }

    private function getOtpMessage(string $otpCode, string $purpose): string
    {
        $messages = [
            OTPEnum::PURPOSE_LOGIN => "Ma OTP dang nhap Perfect Fit cua ban la: {$otpCode}. Ma co hieu luc trong 5 phut.",
            OTPEnum::PURPOSE_REGISTER => "Ma xac thuc dang ky Perfect Fit: {$otpCode}. Ma co hieu luc trong 5 phut.",
            OTPEnum::PURPOSE_VERIFY_PHONE => "Ma xac thuc so dien thoai: {$otpCode}. Ma co hieu luc trong 5 phut.",
            OTPEnum::PURPOSE_ORDER_CONFIRM => "Ma xac nhan don hang Perfect Fit: {$otpCode}",
            OTPEnum::PURPOSE_PASSWORD_RESET => "Ma khoi phuc mat khau Perfect Fit: {$otpCode}. Ma co hieu luc trong 5 phut.",
        ];

        return $messages[$purpose] ?? "Ma OTP Perfect Fit: {$otpCode}";
    }
}

