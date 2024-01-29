<?php

namespace RequestHandlers;


use Configg\DBConnect;
use Middleware\JWTTokenHandlerAndAuthentication;
use Model\User;
use Validate\Validator;

class LoginRequestHandlers
{
  public static function login()
  {

    $userObj = new User(new DBConnect());

    $authenticationObj = new JWTTokenHandlerAndAuthentication($userObj);

    //validation
    $keys = [
      "username" => ['required'],
      "password" => ['required']
    ];
    $data = [
      "username" => $_POST["username"],
      "password" => $_POST["password"]
    ];
    $validationResult = Validator::validate($data, $keys);


    if (!$validationResult["validate"]) {
      return [
        "status" => "false",
        "statusCode" => "409",
        "message" => $validationResult,
        "data" => $data
      ];
    }
    $status = $authenticationObj->authenticate($_POST["username"], $_POST["password"]);
    if (!$status) {
      return [
        "status" => "false",
        "message" => "Unable to authenticate the user.",
        "statusCode" => 401
      ];

    }
    $userData = $userObj->get(NULL, $_POST["username"]);
    
    if($userData["user_type"] == "employee"){
      return [
        "status" => "false",
        "message" => "Employees cannot use AMS system yet. Ask auhtorizaiton from admin.",
        "statusCode" => 401
      ];
    }
    //defining payload
    $payload = array(
      "user_type" => $userData["user_type"],
      "id" => $userData["id"],
    );
    //creating JWT token 
    $authToken = JWTTokenHandlerAndAuthentication::createToken($payload);

    //storing access_token in session
    session_start();
    $_SESSION["authToken"] = $authToken;
    session_write_close();

    // print_r($_SESSION);
    $respose_payload = [
      "access_token" => $authToken,
      "user_id" => $userData["id"],
      "user_type" => $userData["user_type"]
    ];

    return [
      "status" => true,
      "message" => "User authenticated successfully.",
      "payload" => $respose_payload
    ];
  }

}