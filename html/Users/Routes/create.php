<?php 
namespace Routes;

use RequestHandlers\RequestHandlers;
use Middleware\Response;

class Create{
  public static function create(){
     $response = RequestHandlers::createUser();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}
 

