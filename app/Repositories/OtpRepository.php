<?php

namespace App\Repositories;

use App\Enums\OTP as OTPEnum;
use App\Models\OtpVerification;

class OtpRepository extends BaseRepository
{
    public function model()
    {
        return OtpVerification::class;
    }

    public function findValidOtp(string $phone, string $otpCode, string $purpose)
    {
        return $this->model
            ->where('phone', $phone)
            ->where('otp_code', $otpCode)
            ->where('purpose', $purpose)
            ->where('is_used', OTPEnum::STATUS_UNUSED)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', OTPEnum::MAX_ATTEMPTS)
            ->first();
    }

    public function createOtp(string $phone, string $purpose)
    {
        $otpCode = $this->generateOtpCode();

        return $this->model->create([
            'phone' => $phone,
            'otp_code' => $otpCode,
            'purpose' => $purpose,
            'is_used' => OTPEnum::STATUS_UNUSED,
            'expires_at' => now()->addMinutes(OTPEnum::EXPIRY_MINUTES),
            'attempts' => 0,
        ]);
    }

    public function markAsUsed(OtpVerification $otp)
    {
        return $otp->update([
            'is_used' => OTPEnum::STATUS_USED,
            'verified_at' => now(),
        ]);
    }

    public function incrementAttempts(OtpVerification $otp)
    {
        return $otp->increment('attempts');
    }

    public function deleteExpiredOtps()
    {
        return $this->model
            ->where('expires_at', '<', now())
            ->delete();
    }

    private function generateOtpCode(): string
    {
        return str_pad((string)random_int(0, 999999), OTPEnum::OTP_LENGTH, '0', STR_PAD_LEFT);
    }
}

