<?php
namespace RequestHandlers;

use Exception;
use Configg\DBConnect;
use Model\User;
use Validate\Validator;

/**
 * 
 */
//extending UserRequestHandler to use its functions
class SignupRequestHandlers extends UserRequestHandlers
{
  private static  $exceptionMessageFormat = [
    "status" => "false",
    "statusCode" => "409",
    "message" => [
      "validation" => false,
      "message" => []
    ]
  ];

  //overriding createUser functoin fo UserRequestHandlers
  public static function createUser()
  {

    try {
      $userObj = new User(new DBConnect());
      //TAKING USER PROVIDED DATA
      $jsonData = file_get_contents('php://input');
      $decodedData = json_decode($jsonData, true);

      //explicitly assignning employee as user_type so that admin can only be created from database
      $decodedData["user_type"] = "employee";
      $jsonData = json_encode($decodedData);

      //CEHCK IF USER ALREADY EXISTS
      $checkIfUsernameExists = $userObj->get(NULL, $decodedData["username"]);
     $foundId = $checkIfUsernameExists["id"];
     $tempData = json_decode($jsonData , true);
     $tempData["id"] = $foundId;
    
      if (isset($checkIfUsernameExists["id"])) {
        unset($checkIfUsernameExists["password"]);
        self::$exceptionMessageFormat["message"]["message"]["username"] = "Username already exists!!";
        return self::$exceptionMessageFormat;

      
      }
     
      //VALIDATION OF PROVIDED DATA
      $keys = [
        'username' => ['required','empty', 'maxLength', 'minLength', 'usernameFormat'],
        'password' => ['required', 'empty', 'maxLength', 'minLength', 'passwordFormat'],
        'email' => ['maxLength', 'minLength', 'emailFormat'],
        'name' => ['maxLength', 'minLength'],
        'retyped_password' => ['required', 'empty']
      ];

      $validationResult = Validator::validate($decodedData, $keys);
      //check if retyped password is same as passwrod
      if($decodedData["password"] != $decodedData["retyped_password"]){
        $validationResult["validate"] = false;
        $validationResult["message"]["retyped_password"][] = "retyped_password must match !!";
      }

      if (!$validationResult["validate"]) {
        return [
          "status" => false,
          "statusCode" => "422",
          "message" => $validationResult
        ];
      }
      unset($decodedData["retyped_password"]);
      $jsonData =json_encode($decodedData , true);
      
      $result = $userObj->create($jsonData);

   
      $fetchUser = $userObj->get(NULL, $decodedData["username"]);
      $userId = $fetchUser["id"];

      //unsetting to prevent possible fuurther usage as it contains password as well
      unset($fetchUserId);

      
      $decodedData["id"] = $userId;
     
      if (!$result) {
        return [
          "status" => false,
          "statusCode" => "409",
          "message" => "Unable to create user",
          "data" => json_decode($jsonData, true)
        ];
      }

      return [
        "status" => true,
        "statusCode" => "201",
        "message" => "User created successfully",
        "data" => $decodedData
      ];
    } catch (Exception $e) {
      return [
        "status" => false,
        "statusCode" => "409",
        "message" => $e->getMessage(),
        "data" => $tempData
      ];
    }
  }
}