<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class PaymentIntentStripeRequest extends FormRequest
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
            'amount' => 'required|numeric',
			'currency' => 'required|string',
			'confirmation_method' => 'required|string',
			'confirm' => 'required|boolean',
			'customer' => 'required|string',
			'payment_method' => 'required|string',
			'description' => 'required|string'
        ];
    }
}
