<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Tài Khoản - Perfect Fit</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: #ffffff;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
        }

        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #555555;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .button-container {
            text-align: center;
            margin: 35px 0;
        }

        .verify-button {
            display: inline-block;
            padding: 16px 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }

        .info-box {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }

        .info-box p {
            color: #555555;
            font-size: 14px;
            margin: 8px 0;
        }

        .info-box strong {
            color: #667eea;
        }

        .security-notice {
            background: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .security-notice p {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }

        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .url-box {
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            word-break: break-all;
            font-size: 12px;
            color: #495057;
            margin-bottom: 20px;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .copyright {
            font-size: 12px;
            color: #999999;
            margin-top: 20px;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }

            .header h1 {
                font-size: 24px;
            }

            .content {
                padding: 30px 20px;
            }

            .verify-button {
                padding: 14px 36px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">PF</div>
            <h1>Perfect Fit</h1>
            <p>Thời Trang Hoàn Hảo Cho Bạn</p>
        </div>

        <div class="content">
            <div class="greeting">Xin chào {{ $name }}! 👋</div>

            <div class="message">
                Cảm ơn bạn đã đăng ký tài khoản tại <strong>Perfect Fit</strong>. Chúng tôi rất vui mừng được chào đón bạn đến với cộng đồng thời trang của chúng tôi!
            </div>

            <div class="message">
                Để hoàn tất quá trình đăng ký và kích hoạt tài khoản của bạn, vui lòng xác thực địa chỉ email bằng cách nhấn vào nút bên dưới:
            </div>

            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verify-button">Xác Thực Email</a>
            </div>

            <div class="info-box">
                <p><strong>📧 Email:</strong> {{ $email }}</p>
                <p><strong>⏰ Thời gian hiệu lực:</strong> 24 giờ</p>
                <p><strong>🔒 Bảo mật:</strong> Link chỉ sử dụng được 1 lần</p>
            </div>

            <div class="security-notice">
                <p>⚠️ <strong>Lưu ý bảo mật:</strong> Nếu bạn không thực hiện đăng ký này, vui lòng bỏ qua email này hoặc liên hệ với chúng tôi để được hỗ trợ.</p>
            </div>

            <div class="message" style="margin-top: 30px; font-size: 14px; color: #666;">
                Sau khi xác thực thành công, bạn có thể:
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Mua sắm và trải nghiệm các sản phẩm thời trang</li>
                    <li>Nhận các ưu đãi và khuyến mãi độc quyền</li>
                    <li>Theo dõi đơn hàng và lịch sử mua sắm</li>
                    <li>Sử dụng AI tư vấn thời trang cá nhân hóa</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <div class="footer-title">Không thể nhấn vào nút? Sao chép link bên dưới:</div>
            <div class="url-box">{{ $verificationUrl }}</div>

            <div class="social-links">
                <a href="#">Facebook</a> •
                <a href="#">Instagram</a> •
                <a href="#">TikTok</a>
            </div>

            <div class="copyright">
                © {{ date('Y') }} Perfect Fit. All rights reserved.<br>
                Email này được gửi tự động, vui lòng không trả lời.
            </div>
        </div>
    </div>
</body>

</html>
