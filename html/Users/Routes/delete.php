<?php 
namespace Routes;

use RequestHandlers\RequestHandlers;
use Middleware\Response;

class Delete {
  public static function delete(){
    Response::respondWithJson(RequestHandlers::deleteUser(), 200);
  }
}

?>