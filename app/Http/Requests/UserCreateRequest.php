<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        'first_Name' => 'required|string|max:255',
        'last_Name' => 'required|string|max:255',

        // 'email' => 'required|string|email|indisposable|max:255|unique:users',
        'email' => 'required|string|email|max:255|unique:users',

        'password' => 'required|confirmed|string|min:6',
        'phone' => 'required|string',
        'image' => 'nullable',
        'address_line_1' => 'nullable',
        'address_line_2' => 'nullable',
        'country' => 'required|string',
        'city' => 'required|string',
        'postcode' => 'required|string',
        'lat' => 'required|string',
        'long' => 'required|string',
        'role' => 'required|string'
    ];

    }
}
