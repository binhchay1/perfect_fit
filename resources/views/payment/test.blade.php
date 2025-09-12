<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VNPay Payment Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .loading {
            text-align: center;
            color: #666;
        }

        .test-info {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .test-info h3 {
            margin-top: 0;
        }

        .test-info ul {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🧪 VNPay Payment Test</h1>

        <div class="test-info">
            <h3>📋 Test Information:</h3>
            <ul>
                <li><strong>Test Card:</strong> 9704198526191432198</li>
                <li><strong>CVV:</strong> 123</li>
                <li><strong>Expire Date:</strong> 12/25</li>
                <li><strong>OTP:</strong> 123456</li>
            </ul>
        </div>

        <form id="paymentForm">
            <div class="form-group">
                <label for="token">Bearer Token:</label>
                <input type="text" id="token" name="token" value="{{ $token ?? '' }}"
                    placeholder="Enter your Bearer token">
            </div>

            <div class="form-group">
                <label for="orderId">Order ID:</label>
                <input type="number" id="orderId" name="orderId" value="1" placeholder="Enter order ID">
            </div>

            <button type="submit" id="submitBtn">🚀 Test VNPay Payment</button>
        </form>

        <div id="result" class="result"></div>
    </div>

    <script>
        document.getElementById('paymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const token = document.getElementById('token').value;
            const orderId = document.getElementById('orderId').value;
            const submitBtn = document.getElementById('submitBtn');
            const resultDiv = document.getElementById('result');

            if (!token) {
                showResult('error', 'Please enter your Bearer token');
                return;
            }

            if (!orderId) {
                showResult('error', 'Please enter Order ID');
                return;
            }

            // Show loading
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Creating Payment...';
            showResult('info', 'Creating payment request...');

            try {
                const response = await fetch('/api/payment/vnpay/create', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: parseInt(orderId)
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showResult('success', `
                        <h3>✅ Payment Request Created Successfully!</h3>
                        <p><strong>Payment ID:</strong> ${data.data.payment_id}</p>
                        <p><strong>Order ID:</strong> ${data.data.order_id}</p>
                        <p><strong>Order Number:</strong> ${data.data.order_number}</p>
                        <p><strong>Amount:</strong> ${data.data.amount.toLocaleString()} VND</p>
                        <p><strong>Payment URL:</strong> <a href="${data.data.payment_url}" target="_blank">Click here to pay</a></p>
                        <p><em>Click the link above to test payment with VNPay sandbox</em></p>
                    `);
                } else {
                    showResult('error', `
                        <h3>❌ Payment Request Failed</h3>
                        <p><strong>Error:</strong> ${data.message}</p>
                        ${data.errors ? `<p><strong>Details:</strong> ${JSON.stringify(data.errors)}</p>` : ''}
                    `);
                }
            } catch (error) {
                showResult('error', `
                    <h3>❌ Network Error</h3>
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p>Please check your internet connection and try again.</p>
                `);
            } finally {
                // Reset button
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Test VNPay Payment';
            }
        });

        function showResult(type, message) {
            const resultDiv = document.getElementById('result');
            resultDiv.className = `result ${type}`;
            resultDiv.innerHTML = message;
            resultDiv.style.display = 'block';
        }
    </script>
</body>

</html>
