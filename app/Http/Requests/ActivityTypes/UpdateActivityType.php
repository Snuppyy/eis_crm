<?php

namespace App\Http\Requests\ActivityTypes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityType extends FormRequest
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
            'name' => 'required|max:255',
            'assigning_roles' => 'array',
            'verifying_roles' => 'array'
        ];
    }
}
