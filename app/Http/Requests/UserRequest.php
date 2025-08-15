<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:191|unique:users,email,' . $this->user()->id,
            'phone' => 'required|numeric|digits_between:10,11',
            'address' => 'required|max:255',
            'profile_photo_path' => 'image|mimes:jpeg,png,jpg|max:2048',
            'country' => 'required|max:255',
            'province' => 'required|max:255',
            'district' => 'required|max:255',
            'ward' => 'required|max:255',
            'postal_code' => 'required|max:255',
        ];
    }
    
    public function messages()
    {
        return [
            'name.required' => __('validation.required'),
            'name.string' => __('validation.string'),
            'name.max' => __('validation.max'),
            'email.required' => __('validation.required'),
            'email.email' => __('validation.email'),
            'email.unique' => __('validation.unique'),
            'email.max' => __('validation.max'),
            'phone.required' => __('validation.required'),
            'phone.digits_between' => __('validation.digits_between'),
            'phone.numeric' => __('validation.numeric'),
            'phone.max' => __('validation.max'),
            'address.required' => __('validation.required'),
            'address.max' => __('validation.max'),
            'profile_photo_path.image' => __('validation.image'),
            'profile_photo_path.mimes' => __('validation.mimes'),
            'profile_photo_path.max' => __('validation.max'),
            'country.required' => __('validation.required'),
            'country.max' => __('validation.max'),
            'province.required' => __('validation.required'),
            'province.max' => __('validation.max'),
            'district.required' => __('validation.required'),
            'district.max' => __('validation.max'),
            'ward.required' => __('validation.required'),
            'ward.max' => __('validation.max'),
            'postal_code.required' => __('validation.required'),
            'postal_code.max' => __('validation.max'),
        ];
    }
}
