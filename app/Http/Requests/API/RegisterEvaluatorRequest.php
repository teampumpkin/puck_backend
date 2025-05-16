<?php

namespace App\Http\Requests\API;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Class RegisterRequest
 * @package App\Http\Requests\API
 */
class RegisterEvaluatorRequest extends FormRequest
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
            "first_name"          => "required|min:3",
            "last_name"           => "required|min:3",
            "email"               => "required|email|unique:prc_users,email,null,id",
            // "dob"                 => "date_format:Y-m-d",
            // "phone"               => "string"
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
