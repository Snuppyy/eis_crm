<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class StoreUser extends FormRequest
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
            'email' => 'nullable|email|max:255|unique:users',
            'phone' => 'nullable|max:255|unique:users',
            'roles' => 'array',
            'password' => 'nullable|min:6',
            'profile' => 'nullable|array',
            'profile..0.data.first_name' => 'required',
            'profile..0.data.last_name' => 'required',
            'projects' => 'array',
            'projects.*' => 'exists:projects,id',
            'location' => 'nullable|exists:locations,id'
        ];
    }
}
