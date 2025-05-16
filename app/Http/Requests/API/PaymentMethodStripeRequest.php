<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodStripeRequest extends FormRequest
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
            'type'    => 'required|string',
            'card.number' => 'required|string',
            'card.exp_month' => 'required|string',
            'card.exp_year' => 'required|string',
            'card.cvc' => 'required|string',
            'billing_details.name' => 'required|string',
            'billing_details.email' => 'required|string',
        ];
    }
}
