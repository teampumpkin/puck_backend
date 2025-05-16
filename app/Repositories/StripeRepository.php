<?php
namespace App\Repositories;

class StripeRepository {
	public $stripe;

	public function __construct() {
		$this->stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
	}

	public function CreateStripeCustomer($data){

		$customer = $this->stripe->customers->create([
            'name' => $data['name'],
            'email' => $data['email'],
			'metadata' => $data['metadata']
        ]);

		return $customer;
	}

	public function getStripeCustomer($token){
		$user = getUserIdAndType($token);

		$customer = $this->stripe->customers->search([
			'query' => 'metadata[\'puck_user_id\']:\''. $user->id . '\'',
		]);

		if (!empty($customer['data'])){
			return $customer['data'][0];
		}

		return null;
	}

	public function createStripePaymentMethod($data){

		$paymentMethod = $this->stripe->paymentMethods->create([
			'type' => $data['type'],
			'card' => [
			  'number' => $data['card']['number'],
			  'exp_month' => $data['card']['exp_month'],
			  'exp_year' => $data['card']['exp_year'],
			  'cvc' => $data['card']['cvc'],
			],
			'billing_details' => [
				'name' => $data['billing_details']['name'],
				'email' => $data['billing_details']['email']
			]
		  ]);

		if (!empty($paymentMethod['data'])){
			return $paymentMethod['data'][0];
		}

		return $paymentMethod;
	}

	public function attachStripePaymentMethod($data){

		$paymentMethod = $this->stripe->paymentMethods->attach(
			$data['payment_method'],
			['customer' => $data['customer']]
		);

		if (!empty($paymentMethod['data'])){
			return $paymentMethod['data'][0];
		}

		return $paymentMethod;
	}

	public function detachStripePaymentMethod($data){

		$paymentMethod = $this->stripe->paymentMethods->detach(
			$data['payment_method']
		);

		if (!empty($paymentMethod['data'])){
			return $paymentMethod['data'][0];
		}

		return $paymentMethod;
	}

	public function getStripePaymentMethods($data){

		$paymentMethods = $this->stripe->paymentMethods->all([
			'customer' => $data['customer'],
			'type' => !empty($data['type'])?$data['type']:'card',
		]);

		return $paymentMethods['data'];
	}

	public function createStripePaymentIntent($data){

		$paymentIntent = $this->stripe->paymentIntents->create([
			'amount' => $data['amount'],
			'currency' => $data['currency'],
			'confirmation_method' => $data['confirmation_method'],
			'confirm' => $data['confirm'],
			'customer' => $data['customer'],
			'payment_method' => $data['payment_method'],
			'description' => $data['description']
		]);

		if (!empty($paymentIntent['data'])){
			return $paymentIntent['data'][0];
		}

		return $paymentIntent;
	}

	public function confirmStripePaymentIntent($data){

		$paymentIntent = $this->stripe->paymentIntents->confirm(
			$data['payment_intent']
		);

		return $paymentIntent;
	}

    public function getPaymentsList($query, $limit = 100) {
        $payments = $this->stripe->paymentIntents->search([
            'query' => $query,
            'limit' => $limit,
        ]);

        if (!empty($payments['data'])){
			return $payments['data'];
		}
        return [];
    }
}
