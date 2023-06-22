<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUser extends FormRequest
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
        return request()->isMethod('put') ? [
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->route('user'))
            ],
            'phone' => [
                'nullable',
                'max:255',
                Rule::unique('users')->ignore($this->route('user'))
            ],
            'roles' => 'array',
            'password' => 'nullable|min:6',
            'profile' => 'nullable|array',
            'profile..0.data.first_name' => 'required',
            'profile..0.data.last_name' => 'required',
            'projects' => 'array',
            'projects.*' => 'exists:projects,id',
            'location' => 'nullable|exists:locations,id'
        ] : [
            'photo' => 'sometimes|image|dimensions:max_width=200,max_height=200',
            'originalPhoto' => 'sometimes|image',
            'removePhoto' => 'sometimes',
            'images' => 'sometimes|array',
            'images.*' => 'image',
            'files' => 'sometimes|array',
            'files.*' => 'file',
        ];
    }
}
