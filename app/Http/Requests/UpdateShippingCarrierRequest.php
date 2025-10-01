<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingCarrierRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'logo' => 'nullable|string|max:255',
            'shipping_type' => 'sometimes|in:domestic,inter_province',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'information' => 'sometimes|array',
            'information.first_km' => 'sometimes|string|max:255',
            'information.second_km' => 'sometimes|string|max:255',
            'information.additional_km' => 'sometimes|string|max:255',
            'information.description' => 'nullable|string|max:500',
            'information.delivery_time' => 'nullable|string|max:100',
            'information.max_distance' => 'nullable|integer|min:1',
            'information.working_hours' => 'nullable|string|max:100',
            'information.weight_limit' => 'nullable|integer|min:1',
            'cod' => 'sometimes|array',
            'cod.free_threshold' => 'sometimes|string|max:255',
            'cod.rate_1' => 'sometimes|string|max:255',
            'cod.rate_2' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Tên nhà vận chuyển không được vượt quá 255 ký tự.',
            'shipping_type.in' => 'Loại vận chuyển phải là nội thành hoặc liên tỉnh.',
            'information.first_km.max' => 'Thông tin km đầu tiên không được vượt quá 255 ký tự.',
            'information.second_km.max' => 'Thông tin km thứ 2 không được vượt quá 255 ký tự.',
            'information.additional_km.max' => 'Thông tin km bổ sung không được vượt quá 255 ký tự.',
            'information.description.max' => 'Mô tả không được vượt quá 500 ký tự.',
            'information.delivery_time.max' => 'Thời gian giao hàng không được vượt quá 100 ký tự.',
            'information.max_distance.integer' => 'Khoảng cách tối đa phải là số nguyên.',
            'information.max_distance.min' => 'Khoảng cách tối đa phải lớn hơn 0.',
            'information.working_hours.max' => 'Giờ làm việc không được vượt quá 100 ký tự.',
            'information.weight_limit.integer' => 'Giới hạn trọng lượng phải là số nguyên.',
            'information.weight_limit.min' => 'Giới hạn trọng lượng phải lớn hơn 0.',
            'cod.free_threshold.max' => 'Thông tin miễn phí COD không được vượt quá 255 ký tự.',
            'cod.rate_1.max' => 'Thông tin tỷ lệ COD cấp 1 không được vượt quá 255 ký tự.',
            'cod.rate_2.max' => 'Thông tin tỷ lệ COD cấp 2 không được vượt quá 255 ký tự.',
        ];
    }
}
