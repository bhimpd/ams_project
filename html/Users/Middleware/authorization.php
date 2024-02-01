<?php

namespace Middleware;

use \Firebase\JWT\JWT;
use \Firebase\JWT\key;
use Middleware\JWTTokenHandlerAndAuthentication;

/**
 *
 */

interface AuthorizationInterface
{
  public static function checkPermission(string $permission_type): bool;

  public static function verifyToken(): array;
}

class Authorization implements AuthorizationInterface
{

  /**
   * verifies bearer token from auhotrization header
   * @return array
   * @param none
   */
  public static function verifyToken(): array
  {
    try {
      $result = self::getBrearerToken();
      print_r( $_SERVER );
      print_r( $result );
      
      if (!$result["status"]) {
        throw new \Exception($result["message"]);
      }

      $token = $result["data"]["token"];

      $decoded = JWT::decode($token, JWTTokenHandlerAndAuthentication::$secret, array("HS256"));

      // $payload = JWT::decode($token, new key(JWTTokenHandlerAndAuthentication::$secret, JWTTokenHandlerAndAuthentication::$alg));
      print_r($decoded);
      die( "code is fire");
      if (!$decoded) {
        throw new \Exception("Payload not found!!");
      }

      $providedToken = $token;
      $sessionToken = $_SESSION["authToken"];
      //check if the token is same one present in session
      if ($providedToken != $sessionToken) {
        throw new \Firebase\JWT\ExpiredException("Token Invalid !!!!");
      }
      return [
        "status" => true,
        "message" => "User authorised using authToken.",
        "data" => [
          "id" => $decoded,
          // "username" => $payload->data->username,
          "user_type" =>""
        ],
        "authToken" => $token
      ];

    } catch (\Firebase\JWT\ExpiredException $e) {
      // echo "Token Expired";
      error_log($e->getMessage());
      return [
        "status" => false,
        "message" => $e->getMessage(),
        "data" => []
      ];

    } catch (\Firebase\JWT\SignatureInvalidException $e) {
      // echo "Invalid token provided";
      error_log($e->getMessage());
      return [
        "status" => false,
        "message" => $e->getMessage(),
        "data" => []
      ];
    } catch (\Exception $e) {
      error_log($e->getMessage());
      return [
        "status" => false,
        "message" => "Invalid Token : " . $e->getMessage(),
        "data" => []
      ];
    }
  }

  /**
   * gets bearer token from auhtorization header of request 
   * @param none
   * @return array
   */
  public static function getBrearerToken(): array
  {
    try {
      $authToken = $_SERVER["HTTP_AUTHORIZATION"] ?? false;

      if ($authToken === false) {
        throw new \Exception("Authorization header not present!!");
      }
      $authToken = explode(" ", $authToken);

      if (count($authToken) !== 2 || $authToken[0] !== "Bearer") {
        throw new \Exception("Invalid bearer token format.");
      }
      return [
        "status" => true,
        "message" => "Token extracted successully .",
        "data" => ["token" => $authToken[1]]
      ];

    } catch (\Exception $e) {

      return [
        "status" => false,
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }

  //concept code
  public static function checkPermission(string $permission_type): bool
  {
    session_start();
    $user_type = $_SESSION["user_type"] ?? NULL;
    $permissions = [
      "" => [],
      "admin" => ["PUT", "DELETE"],
      "employee" => ["POST"]
    ];


    if (in_array($permission_type, $permissions[$user_type])) {
      /////code remaining
      return true;
    } else {
      return false;
    }
  }
}

