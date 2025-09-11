<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User must be authenticated (handled by middleware)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'shipping_address.address' => 'required|string|max:500',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.district' => 'required|string|max:100',
            'shipping_address.ward' => 'required|string|max:100',
            'shipping_address.postal_code' => 'nullable|string|max:10',

            'billing_address' => 'nullable|array',
            'billing_address.name' => 'required_with:billing_address|string|max:255',
            'billing_address.phone' => 'required_with:billing_address|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'billing_address.address' => 'required_with:billing_address|string|max:500',
            'billing_address.city' => 'required_with:billing_address|string|max:100',
            'billing_address.district' => 'required_with:billing_address|string|max:100',
            'billing_address.ward' => 'required_with:billing_address|string|max:100',
            'billing_address.postal_code' => 'nullable|string|max:10',

            'payment_method' => 'nullable|in:cod,bank_transfer,credit_card,e_wallet',
            'discount_amount' => 'nullable|numeric|min:0|max:999999.99',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shipping_address.required' => 'Địa chỉ giao hàng là bắt buộc.',
            'shipping_address.name.required' => 'Tên người nhận là bắt buộc.',
            'shipping_address.name.max' => 'Tên người nhận không được vượt quá 255 ký tự.',
            'shipping_address.phone.required' => 'Số điện thoại là bắt buộc.',
            'shipping_address.phone.regex' => 'Số điện thoại không hợp lệ.',
            'shipping_address.address.required' => 'Địa chỉ chi tiết là bắt buộc.',
            'shipping_address.address.max' => 'Địa chỉ chi tiết không được vượt quá 500 ký tự.',
            'shipping_address.city.required' => 'Tỉnh/Thành phố là bắt buộc.',
            'shipping_address.district.required' => 'Quận/Huyện là bắt buộc.',
            'shipping_address.ward.required' => 'Phường/Xã là bắt buộc.',

            'billing_address.name.required_with' => 'Tên người thanh toán là bắt buộc khi có địa chỉ thanh toán.',
            'billing_address.phone.required_with' => 'Số điện thoại thanh toán là bắt buộc khi có địa chỉ thanh toán.',
            'billing_address.phone.regex' => 'Số điện thoại thanh toán không hợp lệ.',
            'billing_address.address.required_with' => 'Địa chỉ thanh toán là bắt buộc khi có địa chỉ thanh toán.',

            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'discount_amount.numeric' => 'Số tiền giảm giá phải là số.',
            'discount_amount.min' => 'Số tiền giảm giá không được âm.',
            'discount_amount.max' => 'Số tiền giảm giá quá lớn.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'shipping_address.name' => 'tên người nhận',
            'shipping_address.phone' => 'số điện thoại',
            'shipping_address.address' => 'địa chỉ chi tiết',
            'shipping_address.city' => 'tỉnh/thành phố',
            'shipping_address.district' => 'quận/huyện',
            'shipping_address.ward' => 'phường/xã',
            'shipping_address.postal_code' => 'mã bưu điện',

            'billing_address.name' => 'tên người thanh toán',
            'billing_address.phone' => 'số điện thoại thanh toán',
            'billing_address.address' => 'địa chỉ thanh toán',
            'billing_address.city' => 'tỉnh/thành phố thanh toán',
            'billing_address.district' => 'quận/huyện thanh toán',
            'billing_address.ward' => 'phường/xã thanh toán',
            'billing_address.postal_code' => 'mã bưu điện thanh toán',

            'payment_method' => 'phương thức thanh toán',
            'discount_amount' => 'số tiền giảm giá',
            'notes' => 'ghi chú',
        ];
    }
}
