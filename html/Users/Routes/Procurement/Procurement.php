<?php

namespace Routes\Procurement;

use Middleware\Response;
use RequestHandlers\ProcurementRequestHandlers;


class Procurement
{
  public static function run()
  {

    switch ($_SERVER['REQUEST_METHOD']) {

      case 'GET':
        return  self::get();

      case 'POST':
        self::create();
        break;

      case 'PUT':
        self::update();
        break;

      case 'DELETE':
        self::delete();
        break;
    }
  }
  public static function create()
  {

    $response = ProcurementRequestHandlers::createProcurement();

    return Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function get()
  {

    
    $response = ProcurementRequestHandlers::getProcurements();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function update()
  {
    $response = ProcurementRequestHandlers::updateProcurement();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function delete()
  {
    $response = ProcurementRequestHandlers::deleteProcurement();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}
