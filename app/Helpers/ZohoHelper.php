<?php

namespace App\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class ZohoHelper
{
    /**
     * @var false|resource
     */
    private $curl;
    /**
     * @var mixed
     */
    private $access_token;

    /**
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->curl         = curl_init();
        $this->access_token = $this->generateAuthToken();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function generateAuthToken()
    {
        $url = 'https://accounts.zoho.com/oauth/v2/token';

        $post_param = [
            'refresh_token' => env('ZOHO_REFRESH_TOKEN'),
            'client_id'     => env('ZOHO_CLIENT_ID'),
            'client_secret' => env('ZOHO_CLIENT_SECRET'),
            'grant_type'    => 'refresh_token'
        ];

        $response = $this->curlRequest($url, 'POST', $post_param, true);
        if (empty($response->access_token)) {
            Log::info("Something went wrong in creating token");
            Log::critical("Response======>>>>>", [$response]);
            return;
//            throw new Exception('Something went wrong. Please try again', 200);
        }
        return $response->access_token;
    }

    /**
     * @param $url
     * @param string $method
     * @param array $post_field
     * @param false $for_generate_token
     * @return mixed
     * @throws Exception
     */
    public function curlRequest($url, $method = 'GET', $post_field = [], $for_generate_token = false)
    {
        $curl_option = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method
        ];

        if (!empty($post_field)) {
            $curl_option[ CURLOPT_POSTFIELDS ] = $post_field;
        }

        if (!$for_generate_token) {
            $curl_option[ CURLOPT_HTTPHEADER ] = [
                'X-com-zoho-subscriptions-organizationid: ' . env('ZOHO_ORGANIZATION_ID'),
                'Authorization: Zoho-oauthtoken ' . $this->access_token,
                'Content-Type: application/json'
            ];
        }

        curl_setopt_array($this->curl, $curl_option);

        $response = curl_exec($this->curl);
        $response = json_decode($response);


        curl_close($this->curl);

        return $response;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPlans()
    {
        $response = $this->curlRequest(env('ZOHO_DOMAIN_URL') . ZOHO_PLAN_API);

        if ($response->code != 0) {
            return [];
        }

        return $response->plans;
    }

    /**
     * @param $customer
     * @throws Exception
     */
    public function createCustomer($customer)
    {
        $post_param = json_encode([
            'display_name' => $customer->first_name . " " . $customer->email,
            'first_name'   => $customer->first_name,
            'last_name'    => $customer->last_name,
            'email'        => $customer->email,
            'phone'        => $customer->phone,
            'mobile'       => $customer->phone,
        ]);
        $response   = $this->curlRequest(env('ZOHO_DOMAIN_URL') . ZOHO_CREATE_CUSTOMER_API, 'POST', $post_param);

        return $response->customer->customer_id;
    }

    /**
     * @param $customer_id
     * @param $plan_code
     * @param int $assessment_request_id
     * @param bool $for_call
     * @return mixed
     * @throws Exception
     */
    public function createPaymentPage($customer_id, $plan_code, $assessment_request_id = 0, $for_call = false)
    {
        $post_param = [
            'customer_id'  => $customer_id,
            'plan'         => [
                'plan_code' => $plan_code
            ],
            'starts_at'    => Carbon::now()->format('Y-m-d'),
            'redirect_url' => url('api/save-subscription?assessment_request_id=' . $assessment_request_id . "&for_call=" . $for_call)
        ];
        $response   = $this->curlRequest(env('ZOHO_DOMAIN_URL') . ZOHO_CREATE_PAYMENT_PAGE_API, 'POST', json_encode($post_param));

        return $response->hostedpage->url;
    }

    /**
     * @param $customer_id
     * @param $plan_code
     * @return mixed
     * @throws Exception
     */
    public function createMentorshipPaymentPage($customer_id, $plan_code)
    {
        $post_param = [
            'customer_id'  => $customer_id,
            'plan'         => [
                'plan_code' => $plan_code
            ],
            'starts_at'    => Carbon::now()->format('Y-m-d'),
            'redirect_url' => url('api/save-mentorship-subscription')
        ];
        $response   = $this->curlRequest(env('ZOHO_DOMAIN_URL') . ZOHO_CREATE_PAYMENT_PAGE_API, 'POST', json_encode($post_param));

        return $response->hostedpage->url;
    }

    /**
     * @param $hosted_page_id
     * @return mixed
     * @throws Exception
     */
    public function getHostedPageDetail($hosted_page_id)
    {
        $url = env('ZOHO_DOMAIN_URL') . ZOHO_HOSTED_PAGE_DETAIL_API . $hosted_page_id;

        $response = $this->curlRequest($url);

        return $response->data->subscription;
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function createNewPlan($data)
    {
        $post_param = json_encode([
            'name'            => $data['plan_name'],
            'product_id'      => env('ZOHO_PRODUCT_ID'),
            'plan_code'       => $data['plan_code'],
            'recurring_price' => $data['plan_price'],
            'interval'        => $data['interval'],
            'interval_unit'   => $data['interval_unit'],
            'description'     => $data['plan_description'],
        ]);

        $url = env('ZOHO_DOMAIN_URL') . ZOHO_PLAN_CREATE_API;

        return $this->curlRequest($url, 'POST', $post_param);
    }

    /**
     * @param $subscription_id
     * @return mixed
     * @throws Exception
     */
    public function cancelSubscription($subscription_id)
    {
        $url = env('ZOHO_DOMAIN_URL') . ZOHO_CANCEL_PLAN_API;
        $url = str_replace('$subscription_id$', $subscription_id, $url);

        return $this->curlRequest($url, 'POST');
    }

    /**
     * @param $customer_id
     * @param $plan_code
     * @return mixed
     * @throws Exception
     */
    public function createOfflineSubscription($customer_id, $plan_code)
    {
        $url = env('ZOHO_DOMAIN_URL') . ZOHO_OFFLINE_SUBSCRIPTION_CREATE;

        $post_param = json_encode([
            'customer_id' => $customer_id,
            'plan'        => [
                "plan_code" => "free"
            ],
            "starts_at"   => Carbon::now()->format('Y-m-d')
        ]);

        return $this->curlRequest($url, 'POST', $post_param);
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function createOneTimePaymentAddOnPage($data)
    {
        $url = env('ZOHO_DOMAIN_URL') . ZOHO_CREATE_ONE_TIME_PAYMENT_PAGE_API;

        $post_param = [
            'subscription_id' => $data['subscription_id'],
            'addons'          => [
                [
                    'addon_code' => ZOHO_FREE_EVAL_ADDON_CODE
                ]
            ],
            'redirect_url'    => url('api/save-one-time-subscription')
        ];
        $response   = $this->curlRequest($url, 'POST', json_encode($post_param));

        return $response->hostedpage->url;
    }
}
