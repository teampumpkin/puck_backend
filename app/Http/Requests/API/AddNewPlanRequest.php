<?php

namespace App\Http\Requests\API;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Class AddCoachRequest
 * @package App\Http\Requests\API
 */
class AddNewPlanRequest extends FormRequest
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
            "plan_name"     => "required|min:3",
            "plan_code"     => "required|min:3",
            "plan_price"    => "required|numeric",
            "interval"      => "required|integer",
            "interval_unit" => "required|in:weeks,months,years"
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
