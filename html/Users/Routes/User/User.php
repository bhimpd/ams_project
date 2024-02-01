<?php

namespace Routes\User;

use Middleware\Response;
use RequestHandlers\UserRequestHandlers;

class User
{
  public static function run()
  {
    switch ($_SERVER['REQUEST_METHOD']) {
      case 'GET':
        self::get();
        break;
      case 'POST':
        self::create();
        break;
      case 'PUT':
        self::update();
        break;
      case 'DELETE':
        self::delete();
        break;
      default:
        echo "Requested method not defined!!";
        break;
    }
  }
  public static function create()
  {
    $response = UserRequestHandlers::createUser();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function get()
  {
    $response = UserRequestHandlers::getUser();
    return Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function update()
  {
    $response = UserRequestHandlers::updateUser();
    Response::respondWithJson($response, $response["statusCode"]);

  }
  public static function delete()
  {
    $response = UserRequestHandlers::deleteUser();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}