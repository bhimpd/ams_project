<?php

namespace RequestHandlers;

use Exception;
use Validate\Validator;
use Model\Department;
use Configg\DBConnect;
use Middleware\Authorization;

class DepartmentRequestHandlers
{
  /**
   * @return array
   * Takes data as json , validates , checks if already exists and Creates department in database
   */
  public static function createDepartment(): array
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
    $departmentObj = new Department(new DBConnect());
    $jsonData = file_get_contents('php://input');
    $decodedData = json_decode($jsonData, true);

    $keys = [
      'department' => ['empty', 'required']
    ];
    $validationResult = Validator::validate($decodedData, $keys);

    if (!$validationResult["validate"]) {
      return [
        "status" => "false",
        "statusCode" => "409",
        "message" => $validationResult,
        "data" => json_decode($jsonData, true)
      ];
    }

    $checkIfDepartmentExists = $departmentObj->get($decodedData["department"]);

    if ($checkIfDepartmentExists["status"] === "true") {
      return [
        "status" => "false",
        "statusCode" => 403,
        "message" => "Department alredy exists",
        "data" => []
      ];
    }
    $response = $departmentObj->create($jsonData);

    if ($response["status"] === "false") {
      return [
        "status" => "false",
        "statusCode" => 403,
        "message" => "Unalble to create department in database.",
        "data" => []
      ];
    }
    return [
      "status" => "true",
      "statusCode" => 200,
      "message" => "Department created succsessfully!!",
      "data" => $decodedData
    ];
  }

  /**
   * @return  array
   * gets all the avaliable locations in database 
   */
  public static function getAllDepartment(): array
  {
    try {
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
      $departmentObj = new Department(new DBConnect());
      $response = $departmentObj->getAll();
      if (!$response['status']) {
        throw new Exception("Unable to fetch from database!!");
      }

      return [
        "status" => "true",
        "statusCode" => 200,
        "message" => $response['message'],
        "data" => $response['data']
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "statusCode" => 404,
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }

  public static function updateDepartment()
  {
    try {
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
      $departmentObj = new Department(new DBConnect());
      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);

      //validation
      $keys = [
        "previousDepartment" => ['empty', 'required'],
        "newDepartment" => ['empty', 'required']
      ];

      $validationResult = Validator::validate($decodedData, $keys);


      if (!$validationResult["validate"]) {
        return [
          "status" => "false",
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => $decodedData
        ];
      }

      //check if is present in database
      $result = $departmentObj->get($decodedData["previousDepartment"]);

      if ($result["status"] == "false") {
        throw new Exception("Department not found in database to update!!");
      }

      $response = $departmentObj->updateDepartment($decodedData);

      if (!$response["status"]) {
        throw new Exception("Unalbe to update deaprtment in database!!");
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => $response["message"],
        "data" => $decodedData
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "statusCode" => 409,
        "message" => $e->getMessage(),
        "data" => $decodedData
      ];
    }
  }

  public static function deleteDepartment(): array
  {
    try {
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
      $departmentObj = new Department(new DBConnect());
      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);

      //validation
      $keys = [
        "department" => ['empty', 'required']
      ];
      $validationResult = Validator::validate($decodedData, $keys);
      if (!$validationResult["validate"]) {
        return [
          "status" => "false",
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => $decodedData
        ];
      }
      //check if its in database
      //check if is present in database
      $result = $departmentObj->get($decodedData["department"]);

      if ($result["status"] == "false") {
        throw new Exception("Department not found in database to delete!!");
      }

      $response = $departmentObj->deleteDepartment($decodedData);
      if (!$response["status"]) {
        return [
          "status" => $response["status"],
          "message" => $response["message"],
          "statusCode" => 500
        ];
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => "Department deleted successfully",
        "data" => $decodedData
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "statusCode" => 409,
        "message" => $e->getMessage(),
        "data" => $decodedData
      ];
    }
  }
}