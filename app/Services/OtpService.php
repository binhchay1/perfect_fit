<?php


namespace App\Services;

use App\Enums\OTP as OTPEnum;
use App\Models\OtpVerification;
use App\Repositories\OtpRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OtpService
{
    private string $smsProvider;
    private string $twilioSid;
    private string $twilioToken;
    private string $twilioFrom;

    public function __construct(
        private readonly OtpRepository $otpRepository
    ) {
        $this->smsProvider = config('services.sms.provider', 'log');
        $this->smsApiUrl = config('services.sms.url', 'https://sms-api.com');
        $this->smsApiKey = config('services.sms.key', '');
        
        // Twilio Configuration
        $this->twilioSid = config('services.twilio.sid', '');
        $this->twilioToken = config('services.twilio.token', '');
        $this->twilioFrom = config('services.twilio.from', '');
    }

    public function sendOtp(string $phone, string $purpose): array
    {
        try {
            $otp = $this->otpRepository->createOtp($phone, $purpose);

            $sent = $this->sendSms($phone, $otp->otp_code, $purpose);

            if ($sent) {
                return [
                    'success' => true,
                    'message' => 'OTP đã được gửi thành công',
                    'expires_at' => $otp->expires_at,
                ];
            }

            return [
                'success' => false,
                'message' => 'Không thể gửi OTP',
            ];
        } catch (\Exception $e) {
            Log::error('OTP Send Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Không thể gửi OTP',
            ];
        }
    }

    public function verifyOtp(string $phone, string $otpCode, string $purpose): array
    {
        $otp = $this->otpRepository->findValidOtp($phone, $otpCode, $purpose);

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'OTP không hợp lệ hoặc đã hết hạn',
            ];
        }

        $this->otpRepository->markAsUsed($otp);

        return [
            'success' => true,
            'message' => 'Xác thực OTP thành công',
            'otp_id' => $otp->id,
        ];
    }

    public function resendOtp(string $phone, string $purpose): array
    {
        return $this->sendOtp($phone, $purpose);
    }

    private function sendSms(string $phone, string $otpCode, string $purpose): bool
    {
        $message = $this->getOtpMessage($otpCode, $purpose);
        $formattedPhone = $this->formatPhoneNumber($phone);

        return match ($this->smsProvider) {
            'twilio' => $this->sendViaTwilio($formattedPhone, $message),
            'firebase' => $this->sendViaFirebase($formattedPhone, $otpCode),
            'esms' => $this->sendViaEsms($formattedPhone, $message),
            'speedsms' => $this->sendViaSpeedSMS($formattedPhone, $message),
            default => $this->sendViaLog($formattedPhone, $otpCode, $purpose),
        };
    }

    /**
     * Send SMS via Twilio (Free trial with credits)
     */
    private function sendViaTwilio(string $phone, string $message): bool
    {
        try {
            if (empty($this->twilioSid) || empty($this->twilioToken)) {
                Log::warning('Twilio credentials not configured');
                return $this->sendViaLog($phone, '', '');
            }

            $response = Http::withBasicAuth($this->twilioSid, $this->twilioToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json", [
                    'From' => $this->twilioFrom,
                    'To' => $phone,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("Twilio SMS sent to {$phone}");
                return true;
            }

            Log::error('Twilio SMS failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Twilio Error: ' . $e->getMessage());
            return $this->sendViaLog($phone, '', '');
        }
    }

    /**
     * Send SMS via Firebase Phone Authentication (Free)
     * Note: This is client-side, so we just log for development
     */
    private function sendViaFirebase(string $phone, string $otpCode): bool
    {
        Log::info("Firebase OTP for {$phone}: {$otpCode}");
        return true;
    }

    /**
     * Send SMS via eSMS Vietnam (Paid service for Vietnam)
     */
    private function sendViaEsms(string $phone, string $message): bool
    {
        try {
            $apiKey = config('services.esms.api_key', '');
            $secretKey = config('services.esms.secret_key', '');
            
            if (empty($apiKey) || empty($secretKey)) {
                return $this->sendViaLog($phone, '', '');
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/', [
                'ApiKey' => $apiKey,
                'SecretKey' => $secretKey,
                'Phone' => $phone,
                'Content' => $message,
                'SmsType' => '2', // Brand name SMS
                'Brandname' => config('services.esms.brandname', 'PerfectFit'),
            ]);

            return $response->successful() && $response->json('CodeResult') === '100';
        } catch (\Exception $e) {
            Log::error('eSMS Error: ' . $e->getMessage());
            return $this->sendViaLog($phone, '', '');
        }
    }

    /**
     * Send SMS via SpeedSMS Vietnam (Paid service)
     */
    private function sendViaSpeedSMS(string $phone, string $message): bool
    {
        try {
            $accessToken = config('services.speedsms.access_token', '');
            
            if (empty($accessToken)) {
                return $this->sendViaLog($phone, '', '');
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api.speedsms.vn/index.php/sms/send', [
                'to' => [$phone],
                'content' => $message,
                'sms_type' => 2,
                'sender' => config('services.speedsms.sender', 'PerfectFit'),
            ], [
                'access-token' => $accessToken,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SpeedSMS Error: ' . $e->getMessage());
            return $this->sendViaLog($phone, '', '');
        }
    }

    /**
     * Log OTP to console/file (Development mode)
     */
    private function sendViaLog(string $phone, string $otpCode, string $purpose): bool
    {
        Log::info("=== OTP Code ===");
        Log::info("Phone: {$phone}");
        Log::info("OTP: {$otpCode}");
        Log::info("Purpose: {$purpose}");
        Log::info("================");
        return true;
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove leading zero and add country code for international format
        if (str_starts_with($phone, '0')) {
            return '+84' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '+')) {
            return '+84' . $phone;
        }

        return $phone;
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

