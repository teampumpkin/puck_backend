<?php

namespace App\Http\Requests\API;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ZapierUserEditRequest extends FormRequest
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
            'first_name' => 'alpha',
            'last_name'  => 'alpha',
            'email' => 'email|required',
            'type' => 'alpha',
            'dob' => "date_format:Y-m-d",
            'city_id' => 'integer',
            'state_id' => 'integer',
            'country_id' => 'integer',
            'marketplace_email_allowed' => 'boolean',
            'is_email_verified' => 'boolean',
        ];
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'marketplace_email_allowed' => $this->toBoolean($this->marketplace_email_allowed),
            'is_email_verified' => $this->toBoolean($this->is_email_verified),
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data'    => [],
        ]);
        throw new ValidationException($validator, $response);
    }

    /**
     * Convert to boolean
     *
     * @param $booleable
     * @return boolean
     */
    private function toBoolean($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
