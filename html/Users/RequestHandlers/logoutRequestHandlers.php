<?php

namespace RequestHandlers;

use Exception;
use Middleware\JWTTokenHandlerAndAuthentication;


class LogoutRequestHandlers
{
  public static function logout()
  {
    try {
      $response = JWTTokenHandlerAndAuthentication::expireToken();

      if (!$response["status"]) {
        throw new Exception($response["message"]);
      }
      return [
        "status" => true,
        "message" => $response["message"]
      ];

    } catch (Exception $e) {
      return [
        "status" => false,
        "statusCode" => 401,
        "message" => $e->getMessage()
      ];
    }
  }
}