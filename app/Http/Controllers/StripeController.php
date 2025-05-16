<?php

namespace App\Http\Controllers;

use App\Http\Requests\API\AttachPaymentMethodStripeRequest;
use App\Http\Requests\API\CustomerStripeRequest;
use App\Http\Requests\API\DetachPaymentMethodStripeRequest;
use App\Http\Requests\API\PaymentIntentStripeRequest;
use App\Http\Requests\API\PaymentMethodStripeRequest;
use App\Repositories\StripeRepository;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    /**
     * @var StripeRepository
     */
    private $stripe_repository;

    public function __construct()
    {
        $this->stripe_repository = new StripeRepository();
    }

    // Create customer
    public function createCustomer(CustomerStripeRequest $request){
        try {
            $customer = $this->stripe_repository->CreateStripeCustomer($request->all());
            return prepare_response(200, true, __('Customer created succesfully'), $customer);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getCustomer(Request $request){
        try {
            $customer = $this->stripe_repository->getStripeCustomer($request->header('Authorization'));
            if(!$customer){
                return prepare_response(404, false, __('Customer not found'), null);
            }
            return prepare_response(200, true, __('Customer found'), $customer);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function createPaymentMethod(PaymentMethodStripeRequest $request){
        try {
            $payment_method = $this->stripe_repository->createStripePaymentMethod($request->all());
            return prepare_response(200, true, __('Payment method created'), $payment_method);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function attachPaymentMethod(AttachPaymentMethodStripeRequest $request){
        try {
            $payment_method = $this->stripe_repository->attachStripePaymentMethod($request->all());
            return prepare_response(200, true, __('Payment method attached'), $payment_method);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function detachPaymentMethod(DetachPaymentMethodStripeRequest $request){
        try {
            $payment_method = $this->stripe_repository->detachStripePaymentMethod($request->all());
            return prepare_response(200, true, __('Payment method detached'), $payment_method);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getPaymentMethods(Request $request){
        try {
            if(!$request->customer){
                return prepare_response(400, false, __('Invalid customer'), []);
            }
            $payment_methods = $this->stripe_repository->getStripePaymentMethods($request->all());
            return prepare_response(200, true, __('List of payment methods'), $payment_methods);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function createPaymentIntent(PaymentIntentStripeRequest $request){
        try {
            $payment_intent = $this->stripe_repository->createStripePaymentIntent($request->all());
            return prepare_response(200, true, __('Payment intent created'), $payment_intent);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function confirmPaymentIntent(Request $request){
        try {
            if(!$request->payment_intent){
                return prepare_response(400, false, __('Invalid payment intent'), null);
            }
            $payment_intent = $this->stripe_repository->confirmStripePaymentIntent($request->all());
            return prepare_response(200, true, __('Payment intent confirmed'), $payment_intent);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getPayments(Request $request){
        // Request
        // status: canceled, processing, requires_action, requires_capture, requires_confirmation, requires_payment_method, succeeded
        try {
            $request_data = $request->all();
            $status = !empty($request_data['status']) ? $request_data['status'] : 'succeeded';
            $limit = !empty($request_data['limit']) ? (int) $request_data['limit'] : 100;
            $page = !empty($request_data['page']) ? (int) $request_data['page'] : 1;

            $query = 'status:\''.$status.'\'';

            $customer = $this->stripe_repository->getStripeCustomer($request->header('Authorization'));
            if(!$customer){
                return prepare_response(404, false, __('Customer not found'), null);
            }

            $query = $query . ' AND customer:\'' . $customer->id . '\'';

            $payment_intent = $this->stripe_repository->getPaymentsList($query, $limit, $page);
            return prepare_response(200, true, __('Payments list'), $payment_intent);
        } catch (\Exception $e) {
            return exceptionMessage($e);
        }
    }
}
