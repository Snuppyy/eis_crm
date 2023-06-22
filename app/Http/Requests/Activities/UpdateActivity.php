<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivity extends FormRequest
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
            'activity_type_id' => 'required|exists:activity_types,id',
            // 'client_id' => 'required|exists:users,id',
            // 'assigner_id' => 'required|exists:users,id',
            // 'verifier_id' => 'nullable|exists:users,id',
            'assigner_comment' => 'nullable',
            'verifier_comment' => 'nullable',
            'performed_on' => 'nullable|date',
            'verified' => 'boolean'
        ];
    }
}
