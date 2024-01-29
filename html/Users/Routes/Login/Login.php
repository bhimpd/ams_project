<?php 

namespace Routes\Login;
use Middleware\Response;
use RequestHandlers\LoginRequestHandlers;

class Login 
{
  public static function run(){
    switch($_SERVER['REQUEST_METHOD']){
      case 'POST':
        self::login();
        break;
      default:
        echo "Route for request not found .";
    }
  }
  public static function login(){
    $response = LoginRequestHandlers::login();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}