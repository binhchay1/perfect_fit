<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingSettingsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Debug: Log the request data
        \Log::info('UpdateShippingSettingsRequest data:', $this->all());

        return [
            'shop_name' => 'required|string|max:255',
            'shipping_location' => 'required|array',
            'shipping_location.address' => 'required|string|max:255',
            'shipping_location.ward' => 'required|string|max:255',
            'shipping_location.district' => 'required|string|max:255',
            'shipping_location.city' => 'required|string|max:255',
            'shipping_location.province' => 'required|string|max:255',
            'shipping_location.country' => 'required|string|max:255',
            'shipping_location.coordinates.latitude' => 'nullable|numeric',
            'shipping_location.coordinates.longitude' => 'nullable|numeric',
            'phone' => 'required|string|max:20',
            'enable_domestic_shipping' => 'required|boolean',
            'enable_inter_province_shipping' => 'required|boolean',
            'carriers_id' => 'nullable|array',
            'carriers_id.*' => 'required|exists:shipping_carriers,id',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'shop_name.required' => 'Tên cửa hàng là bắt buộc.',
            'shop_name.max' => 'Tên cửa hàng không được vượt quá 255 ký tự.',
            'shipping_location.required' => 'Địa chỉ giao hàng là bắt buộc.',
            'shipping_location.array' => 'Địa chỉ giao hàng phải là một mảng.',
            'shipping_location.address.required' => 'Địa chỉ chi tiết là bắt buộc.',
            'shipping_location.ward.required' => 'Phường/Xã là bắt buộc.',
            'shipping_location.district.required' => 'Quận/Huyện là bắt buộc.',
            'shipping_location.city.required' => 'Thành phố/Tỉnh là bắt buộc.',
            'shipping_location.province.required' => 'Tỉnh/Thành phố là bắt buộc.',
            'shipping_location.country.required' => 'Quốc gia là bắt buộc.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'enable_domestic_shipping.required' => 'Trạng thái giao hàng nội thành là bắt buộc.',
            'enable_domestic_shipping.boolean' => 'Trạng thái giao hàng nội thành phải là true hoặc false.',
            'enable_inter_province_shipping.required' => 'Trạng thái giao hàng liên tỉnh là bắt buộc.',
            'enable_inter_province_shipping.boolean' => 'Trạng thái giao hàng liên tỉnh phải là true hoặc false.',
            'carriers_id.array' => 'Danh sách nhà vận chuyển phải là một mảng.',
            'carriers_id.*.required' => 'ID nhà vận chuyển là bắt buộc.',
            'carriers_id.*.exists' => 'Nhà vận chuyển không tồn tại.',
        ];
    }
}
