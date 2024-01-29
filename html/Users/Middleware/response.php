<?php
namespace Middleware;

class Response
{

  public static function respondWithJson($data, $status = 200)
  {
    // header('Content-type : application/json');
    http_response_code($status);
    unset($data["statusCode"]);
    echo json_encode($data, JSON_PRETTY_PRINT);
  }
}


