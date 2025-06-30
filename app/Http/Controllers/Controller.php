<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function sendRes($message = 'Success', $data = [], $http_code = 200)
    {
      return response()->json(['success' => true,'message' => __($message), 'data' => $data], $http_code);
    }

    public function sendError($message = 'Failed', $data = [], $http_code = 500)
    {
      return response()->json(['success' => false,'message' => __($message), 'data' => $data], $http_code);
    }
}
