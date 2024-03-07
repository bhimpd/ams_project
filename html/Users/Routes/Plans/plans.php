<?php

namespace Routes\Plans;

use Middleware\Response;
use PlansRequestHandler\PlansRequestHandler as PlansRequestHandler;

class Plans
{
  public static function run()
  {
    switch ($_SERVER['REQUEST_METHOD']) {
      case 'POST':
        self::create();
        break;

      default:
        echo "Route for given request type not found!!";
        break;
    }
  }

  public static function create()
  {
    $response = PlansRequestHandler::createPlans();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}
