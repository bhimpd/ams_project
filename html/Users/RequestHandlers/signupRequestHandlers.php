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
        throw new Exception("Username already exists!!");
      }
      //VALIDATION OF PROVIDED DATA
      $keys = [
        'username' => ['empty', 'maxLength', 'minLength', 'usernameFormat'],
        'password' => ['required', 'empty', 'maxLength', 'minLength', 'passwordFormat'],
        'email' => ['maxLength', 'minLength', 'emailFormat'],
        'name' => ['maxLength', 'minLength'],


      ];

      $validationResult = Validator::validate($decodedData, $keys);
      if (!$validationResult["validate"]) {
        return [
          "status" => false,
          "statusCode" => "422",
          "message" => $validationResult
        ];
      }
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