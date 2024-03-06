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
        if ($_GET["_method"] == "PUT") {
          goto y;
        }

        self::create();
        break;


        //label  for update
        y:
        self::update();
        break;


      case 'DELETE' :
        self::delete();
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

  public static function delete()
  {
    $response = RepairreplaceRequestHandlers::delete();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function update()
  {
    $response = RepairreplaceRequestHandlers::update();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  
}

