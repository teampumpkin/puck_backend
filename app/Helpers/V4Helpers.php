<?php

if (!function_exists('prepare_response')) {
    /**
     * @param $code
     * @param $status
     * @param $message
     * @param array $data
     *
     * @return JsonResponse
     */
    function prepare_response($code, $status, $message, $data = [])
    {

        return response()->json([
            'code'    => $code,
            'status'  => $status,
            'message' => $message,
            'data'    => $data,

        ]);
    }
}
