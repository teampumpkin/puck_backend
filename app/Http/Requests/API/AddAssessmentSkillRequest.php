<?php

namespace App\Http\Requests\API;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Class AddCoachRequest
 * @package App\Http\Requests\API
 */
class AddAssessmentSkillRequest extends FormRequest
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
            "skill_name"  => [
                'required',
                Rule::unique('prc_advance_assessment_skills')
                    ->where('skill_name', $this->skill_name)
                    ->where('category_id', $this->category_id)
            ],
            "category_id" => "required|integer"
        ];
    }

    /**
     * @param Validator $validator
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data'    => [],
        ]);
        throw new ValidationException($validator, $response);
    }
}
