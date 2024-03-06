<?php

namespace Routes\Signup;

use Middleware\Response;
use RequestHandlers\SignupRequestHandlers;

class Signup
{
  public static function run()
  {
    switch ($_SERVER['REQUEST_METHOD']) {
     
      case 'POST':
   
        self::create();
        break;
  
      default:
        echo "Requested method not defined!!";
        break;
    }
  }
  public static function create()
  {
    $response = SignupRequestHandlers::createUser();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}