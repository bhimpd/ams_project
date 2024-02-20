<?php
namespace RequestHandlers;

use Exception;
use Configg\DBConnect;
use Model\User;
use Validate\Validator;
use Middleware\Authorization;

/**
 * handles all users related requests like user create/edit/delete 
 */
class UserRequestHandlers
{
  public static function getUser()
  {
    $response = Authorization::verifyToken();
    if (!$response["status"]) {
      return [
        "status" => false,
        "statusCode" => "401",
        "message" => $response["message"],
        "data" => []
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
    $id = $_GET["id"] ?? NULL;
    $username = $_GET["username"] ?? NULL;
    if ($id == NULL && $username == NULL) {
      return self::getAllUser();
    }
    return self::getByIdOrUsername();
  }
  public static function getAllUser()
  {
    try {
      $userObj = new User(new DBConnect());
      $result = $userObj->getAll();
      if (!$result) {
        throw new Exception("Cannot get data !!");
      }
      return [
        "status" => true,
        "statusCode" => "200",
        "message" => "Data extracted.",
        "data" => $result
      ];
    } catch (Exception $e) {
      return [
        "status" => true,
        "statusCode" => "200",
        "message" => "Data extraceted.",
        "data" => $result
      ];
    } finally {
      $userObj->DBconn->disconnectFromDatabase();
    }
  }
  /** 
   * takes auth  ,verifies , gives  response
   */
  public static function getByIdOrUsername()
  {
    $userObj = new User(new DBConnect());

    $id = $_GET["id"] ?? NULL;
    $username = $_GET["username"] ?? NULL;

    $result = $userObj->get($id, $username);
    if ($result["status"] == "false") {
      return [
        "status" => "false",
        "statusCode" => 404,
        "message" => "User requested not available!!"
      ];
    }
    unset($result["password"]);
    return [
      "status" => true,
      "statusCode" => "200",
      "message" => "Data extraceted.",
      "data" => $result
    ];


  }
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

      if (isset($checkIfUsernameExists["id"])) {
        unset($checkIfUsernameExists["password"]);
        throw new Exception("Username already exists!!");
      }

      //VALIDATION OF PROVIDED DATA
      $keys = [
        'username' => ['empty', 'maxLength', 'minLength', 'usernameFormat'],
        'password' => ['required', 'empty', 'maxLength', 'minLength', 'passwordFormat'],
        'email' => ['maxLength', 'minLength','emailFormat'],
        'name' => ['maxLength', 'minLength'],
        'user_type' => ['user_typeFormat'],
        'phone_number'=>['phone_numberFormat'],
        'designation' => ['designationFormat'],
        
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
      $fetchUserId = $userObj->get(NULL, $decodedData["username"]);
      $userId = $fetchUserId["id"];
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
        "data" => json_decode($jsonData, true)
      ];
    } finally {
      $userObj->DBconn->disconnectFromDatabase();
    }
  }

  public static function updateUser()
  {
    try {
      $userObj = new User(new DBConnect());
      $response = Authorization::verifyToken();
      if (!$response["status"]) {
        return [
          "status" => false,
          "statusCode" => "401",
          "message" => $response["message"],
          "data" => []
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

      $jsonData = file_get_contents('php://input');
      //to validatte in the keys
      $decodedData = json_decode($jsonData, true);
      $id = $_GET["id"];
      if (!$id) {
        throw new Exception("Id not provided !!");
      }
      $result = $userObj->get($id, NULL);
      if ($result["status"] == "false") {
        unset($result);
        return throw new Exception("User not found to update!!");
      }
      $keys = [
        'username' => ['required', 'maxlength', 'format'],
        'password' => ['required', 'maxlength', 'minLength'],
        'email' => ['required', 'email'],
        'name' => ['required', 'empty']
      ];

      $validationResult = Validator::validate($decodedData, $keys);
      if (!$validationResult["validate"]) {
        $response = array(
          "status" => false,
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => json_decode($jsonData, true)
        );
        return $response;
      }

      $updateStatus = $userObj->update($id, $jsonData);

      if ($updateStatus["result"] == true) {

        return [
          "status" => true,
          "statusCode" => "201",
          "message" => "User Updated successfully",
          "updatedData" => json_decode($jsonData)
        ];
      } else {
        return [
          "status" => false,
          "statusCode" => 409,
          // "data" => $updateStatus
        ];
      }

    } catch (Exception $e) {
      return [
        "status" => false,
        "statusCode" => 401,
        "message" => $e->getMessage()
      ];
    } finally {
      //disconnecting from database
      $userObj->DBconn->disconnectFromDatabase();
    }
  }
  public static function deleteUser()
  {
    try {
      $userObj = new User(new DBConnect());
      $response = Authorization::verifyToken();
      if (!$response["status"]) {
        return [
          "status" => false,
          "statusCode" => "401",
          "message" => $response["message"],
          "data" => []
        ];
      }
      echo "here";
      //checks if user is not admin
      if ($response["data"]["user_type"] !== "admin") {
        return [
          "status" => false,
          "statusCode" => 401,
          "message" => "User type unauthorised !",
          "data" => $response["data"]
        ];
      }
      $id = $_GET["id"];
      if (!$id) {
        throw new Exception("Id not provided !!");
      }
      $result = $userObj->get($id, NULL);
      if ($result["status"] == "false") {
        unset($result);
        return throw new Exception("User not found to delete!!");
      }
      $deleteStatus = $userObj->delete($id);

      if ($deleteStatus["status"] == true) {
        return [
          "status" => true,
          "statusCode" => 200,
          "message" => "User of Id :$id deleted successfully"
        ];
      } else {
        return [
          "status" => false,
          "statusCode" => 400,
          "message" => "$deleteStatus[message]"
        ];
      }
    } catch (Exception $e) {
      return [
        "status" => false,
        "message" => $e->getMessage()
      ];
    } finally {
      //disconnecting from database
      $userObj->DBconn->disconnectFromDatabase();
    }
  }
}

