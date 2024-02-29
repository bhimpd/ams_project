<?php
namespace RequestHandlers;

use Exception;
use Model\Category;
use Configg\DBConnect;
use Model\Repairreplace;
use Validate\Validator;
use Middleware\Authorization;

class RepairreplaceRequestHandlers
{
  public static function get(){
    $response = Authorization::verifyToken();
    if (!$response["status"]) {
      return [
        "status" => $response["status"],
        "statusCode" => 401,
        "message" => $response["message"],
        "data" => $response["data"]
      ];
    }
    //checks if user is not admin
    if ($response["data"]["user_type"] !== "admin") {
      return [
        "status" => false,
        "statusCode" => 401,
        "message" => "User unauthorised",
        "data" => $response["data"]
      ];
    }

    $repairreplaceObj = new Repairreplace(new DBConnect);
    $response = $repairreplaceObj -> get();

    return [
      "statusCode" => 200,
      "status" => $response["status"],
      "message" => $response["message"],
      "data" => $response["data"]
    ];


  }
  public static function create()
  {
    //Authorizaiton
    $response = Authorization::verifyToken();
    if (!$response["status"]) {
      return [
        "status" => $response["status"],
        "statusCode" => 401,
        "message" => $response["message"],
        "data" => $response["data"]
      ];
    }
    //checks if user is not admin
    if ($response["data"]["user_type"] !== "admin") {
      return [
        "status" => false,
        "statusCode" => 401,
        "message" => "User unauthorised",
        "data" => $response["data"]
      ];
    }

    // $repairreplaceObj = new Repairreplace();
  }


}
