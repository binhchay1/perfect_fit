<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShippingCarrierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'shipping_type' => 'required|in:domestic,inter_province',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'information' => 'required|array',
            'cod' => 'required|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên nhà vận chuyển là bắt buộc.',
            'name.max' => 'Tên nhà vận chuyển không được vượt quá 255 ký tự.',
            'shipping_type.required' => 'Loại vận chuyển là bắt buộc.',
            'shipping_type.in' => 'Loại vận chuyển phải là nội thành hoặc liên tỉnh.',
            'information.required' => 'Thông tin cấu hình là bắt buộc.',
            'cod.required' => 'Cấu hình COD là bắt buộc.',
        ];
    }
}