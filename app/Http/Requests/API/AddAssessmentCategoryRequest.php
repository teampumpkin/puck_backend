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
class AddAssessmentCategoryRequest extends FormRequest
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
//            "category_name"      => "required|unique:prc_advance_assessment_categories,category_name,".$this->get('category_name').",null,id,player_position_id,".$this->get('player_position_id'),
            "category_name"      => [
                'required',
                Rule::unique('prc_advance_assessment_categories')
                    ->where('category_name', $this->category_name)
                    ->where('player_position_id', $this->player_position_id)
            ],
            "player_position_id" => "required|integer"
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
