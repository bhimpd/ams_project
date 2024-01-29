<?php
namespace Routes;

use RequestHandlers\RequestHandlers;
use Middleware\Response;

class Get
{
  public static function get()
  {
    $response = RequestHandlers::getByIdOrUsername();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}
?>