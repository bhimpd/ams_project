<?php

namespace Routes\Repairreplace;

use Middleware\Response;
use RequestHandlers\RepairreplaceRequestHandlers;

class Repairreplace
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

        }

    }
  
  public static function create()
  { 
    $response = RepairreplaceRequestHandlers::create();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function get()
  { 
    $response = RepairreplaceRequestHandlers::get();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  
}

