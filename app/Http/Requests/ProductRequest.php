<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
        $productId = $this->route('product') ? $this->route('product')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->ignore($productId)
            ],

            'description' => 'nullable|string|max:2000',
            'brand_id' => 'required|exists:brands,id',
            'material' => 'required|nullable|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'price' => 'required|nullable|numeric|min:0',
            'product_type' => 'required|in:genuine,self_produced',
            'product_link' => 'nullable|url|max:255',
            'gender' => 'required|in:male,female',
            'is_active' => 'boolean',
            'tags' => 'required|nullable|array',
            'tags.*' => 'string|max:50',
            'colors' => 'nullable|array',
            'colors.*.color_name' => 'required|string|max:100',
            'colors.*.images' => 'nullable|array',
            'colors.*.images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'colors.*.sizes' => 'nullable|array',
            'colors.*.sizes.*.size_name' => 'required|string|max:10',
            'colors.*.sizes.*.quantity' => 'required|nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.unique' => 'Tên sản phẩm đã tồn tại.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
            'slug.regex' => 'Slug chỉ được chứa chữ cái thường, số và dấu gạch ngang.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'brand_id.required' => 'Thương hiệu là bắt buộc.',
            'brand_id.exists' => 'Thương hiệu không tồn tại.',
            'material.required' => 'Chất liệu là bắt buộc.',
            'material.max' => 'Chất liệu không được vượt quá 255 ký tự.',
            'images.array' => 'Hình ảnh phải là mảng.',
            'images.*.required' => 'Hình ảnh sản phẩm là bắt buộc.',
            'images.*.image' => 'File phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif, svg.',
            'images.*.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
            'price.required' => 'Giá sản phẩm là bắt buộc.',
            'price.numeric' => 'Giá phải là số.',
            'price.min' => 'Giá không được nhỏ hơn 0.',
            'product_type.required' => 'Loại sản phẩm là bắt buộc.',
            'product_type.in' => 'Loại sản phẩm phải là genuine hoặc self_produced.',
            'product_link.url' => 'Link sản phẩm phải là URL hợp lệ.',
            'gender.required' => 'Giới tính là bắt buộc.',
            'gender.in' => 'Giới tính phải là male hoặc female.',
            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
            'tags.required' => 'Tags là bắt buộc.',
            'tags.array' => 'Tags phải là mảng.',
            'tags.*.string' => 'Mỗi tag phải là chuỗi.',
            'tags.*.max' => 'Mỗi tag không được vượt quá 50 ký tự.',
            'colors.array' => 'Màu sắc phải là mảng.',
            'colors.*.color_name.required' => 'Tên màu là bắt buộc.',
            'colors.*.color_name.string' => 'Tên màu phải là chuỗi.',
            'colors.*.color_name.max' => 'Tên màu không được vượt quá 100 ký tự.',
            'colors.*.images.array' => 'Ảnh màu phải là mảng.',
            'colors.*.images.*.required' => 'Ảnh màu là bắt buộc.',
            'colors.*.images.*.image' => 'File ảnh màu phải là hình ảnh.',
            'colors.*.images.*.mimes' => 'Ảnh màu phải có định dạng: jpeg, png, jpg, gif, svg.',
            'colors.*.images.*.max' => 'Kích thước ảnh màu không được vượt quá 2MB.',
            'colors.*.sizes.array' => 'Sizes phải là mảng.',
            'colors.*.sizes.*.size_name.required' => 'Tên size là bắt buộc.',
            'colors.*.sizes.*.size_name.string' => 'Tên size phải là chuỗi.',
            'colors.*.sizes.*.size_name.max' => 'Tên size không được vượt quá 10 ký tự.',
            'colors.*.sizes.*.quantity.required' => 'Số lượng là bắt buộc.',
            'colors.*.sizes.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'colors.*.sizes.*.quantity.min' => 'Số lượng không được nhỏ hơn 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên sản phẩm',
            'slug' => 'slug',
            'description' => 'mô tả',
            'brand_id' => 'thương hiệu',
            'material' => 'chất liệu',
            'images' => 'hình ảnh',
            'price' => 'giá',
            'product_type' => 'loại sản phẩm',
            'product_link' => 'link sản phẩm',
            'gender' => 'giới tính',
            'is_active' => 'trạng thái hoạt động',
            'tags' => 'tags',
            'colors' => 'màu sắc',
        ];
    }
}