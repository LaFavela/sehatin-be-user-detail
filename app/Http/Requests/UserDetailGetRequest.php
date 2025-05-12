<?php

namespace app\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use function Laravel\Prompts\error;

class UserDetailGetRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'string|nullable',
            'user_id' => 'string|nullable',
            'per_page' => 'integer|nullable',
            'sort_by' => ['string', 'nullable', Rule::in([
                'name',
                'weight',
                'goal',
                'height',
                'bmi',
                'bmr',
                'activity',
                'birthday',
                'gender',
                'created_at',
                'updated_at'
            ])],
            'sort_direction' => ['string', 'nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    // Failed validation method
    public $validator = null;

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $this->validator = $validator;
    }
}
