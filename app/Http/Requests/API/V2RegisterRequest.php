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
class V2RegisterRequest extends FormRequest
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
        $dob = $this->dob;
        $age = Carbon::parse($dob)->age;

        return [
            "first_name"          => "required|min:3",
            "last_name"           => "required|min:3",
            "email"               => "required|email|unique:prc_users,email,null,id",
            "password"            => "required",
            "type"                => "required",
            "dob"                 => "required|date_format:Y-m-d",
            "guardian_first_name" => ($this->type == 2 && ($age >= 14 && $age <= 16)) ? "required" : "",
            "guardian_last_name"  => ($this->type == 2 && ($age >= 14 && $age <= 16)) ? "required" : "",
            "guardian_email"      => ($this->type == 2 && ($age >= 14 && $age <= 16)) ? "required|email" : "",
        ];
    }

    /**
     * @param Validator $validator
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'code' => 400,
            'success' => false,
            'message' => $validator->errors()->first(),
            'data'    => [],
            'version' => "1.0"
        ]);
        throw new ValidationException($validator, $response);
    }
}
