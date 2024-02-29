<?php

namespace Routes\Logout;

use Middleware\Response;
use RequestHandlers\LogoutRequestHandlers;

class Logout
{
  public static function run()
  {
    switch ($_SERVER['REQUEST_METHOD']) {
      case 'POST':
        self::logout();
        break;
        
      default:
        echo "Route for given request type not found!!";
        break;
    }
  }

  public static function logout()
  {
    $response = LogoutRequestHandlers::logout();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}