<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        $brandId = $this->route('brand') ? $this->route('brand')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brandId)
            ],
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên thương hiệu là bắt buộc.',
            'name.unique' => 'Tên thương hiệu đã tồn tại.',
            'name.max' => 'Tên thương hiệu không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
            'logo.image' => 'Logo phải là file hình ảnh.',
            'logo.mimes' => 'Logo phải có định dạng: jpeg, png, jpg, gif, svg.',
            'logo.max' => 'Kích thước logo không được vượt quá 2MB.',
            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên thương hiệu',
            'slug' => 'slug',
            'description' => 'mô tả',
            'logo' => 'logo',
            'is_active' => 'trạng thái hoạt động',
        ];
    }
}
