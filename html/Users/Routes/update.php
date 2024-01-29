<?php

  namespace Routes;

  use RequestHandlers\RequestHandlers;
  use Middleware\Response;

  class Update{
    public static function update(){
      $response = RequestHandlers::updateUser();
      Response::respondWithJson($response, $response["statusCode"]);
    
    }
  }

?>