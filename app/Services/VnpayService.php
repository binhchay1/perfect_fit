<?php

namespace App\Services;

use Illuminate\Http\Request;

class VnpayService
{
    /**
     * Create VNPay payment URL
     */
    public function createPaymentUrl(array $params): string
    {
        // Required parameters
        $params['vnp_Version'] = config('vnpay.version');
        $params['vnp_Command'] = config('vnpay.command');
        $params['vnp_TmnCode'] = config('vnpay.tmn_code');
        $params['vnp_CreateDate'] = date('YmdHis');
        $params['vnp_CurrCode'] = config('vnpay.currency');
        $params['vnp_IpAddr'] = request()->ip();
        $params['vnp_Locale'] = config('vnpay.locale');
        $params['vnp_OrderType'] = config('vnpay.order_type');

        // Sort parameters alphabetically
        ksort($params);

        // Create query string
        $queryString = http_build_query($params);

        // Create signature
        $signature = hash_hmac('sha512', $queryString, config('vnpay.hash_secret'));

        // Return payment URL
        return config('vnpay.url') . '?' . $queryString . '&vnp_SecureHash=' . $signature;
    }

    /**
     * Verify VNPay signature
     */
    public function verifySignature(array $params): bool
    {
        $signature = $params['vnp_SecureHash'] ?? '';
        unset($params['vnp_SecureHash']);

        // Sort parameters alphabetically
        ksort($params);

        // Create query string
        $queryString = http_build_query($params);

        // Create expected signature
        $expectedSignature = hash_hmac('sha512', $queryString, config('vnpay.hash_secret'));

        return $signature === $expectedSignature;
    }

    /**
     * Get payment status from VNPay response code
     */
    public function getPaymentStatus(string $responseCode): string
    {
        return match ($responseCode) {
            '00' => 'paid',
            '07' => 'failed', // Trừ tiền thành công, giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)
            '09' => 'failed', // Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking
            '10' => 'failed', // Xác thực thông tin thẻ/tài khoản không đúng quá 3 lần
            '11' => 'failed', // Đã hết hạn chờ thanh toán. Xin vui lòng thực hiện lại giao dịch
            '12' => 'failed', // Thẻ/Tài khoản bị khóa
            '24' => 'failed', // Khách hàng hủy giao dịch
            '51' => 'failed', // Tài khoản không đủ số dư để thực hiện giao dịch
            '65' => 'failed', // Tài khoản đã vượt quá hạn mức giao dịch trong ngày
            '75' => 'failed', // Ngân hàng thanh toán đang bảo trì
            '79' => 'failed', // Nhập sai mật khẩu thanh toán quá số lần quy định
            default => 'failed',
        };
    }

    /**
     * Get payment message from VNPay response code
     */
    public function getPaymentMessage(string $responseCode): string
    {
        return match ($responseCode) {
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công, giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
            '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking',
            '10' => 'Xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Đã hết hạn chờ thanh toán. Xin vui lòng thực hiện lại giao dịch',
            '12' => 'Thẻ/Tài khoản bị khóa',
            '24' => 'Khách hàng hủy giao dịch',
            '51' => 'Tài khoản không đủ số dư để thực hiện giao dịch',
            '65' => 'Tài khoản đã vượt quá hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng thanh toán đang bảo trì',
            '79' => 'Nhập sai mật khẩu thanh toán quá số lần quy định',
            default => 'Giao dịch thất bại',
        };
    }

    /**
     * Format amount for VNPay (multiply by 100)
     */
    public function formatAmount(float $amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Parse amount from VNPay (divide by 100)
     */
    public function parseAmount(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Generate order info for VNPay
     */
    public function generateOrderInfo(string $orderNumber, string $description = ''): string
    {
        $info = "Thanh toan don hang #{$orderNumber}";
        if ($description) {
            $info .= " - {$description}";
        }
        return $info;
    }
}