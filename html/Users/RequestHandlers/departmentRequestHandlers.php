<?php

namespace RequestHandlers;

use Exception;
use Validate\Validator;
use Model\Department;
use Configg\DBConnect;
use Middleware\Authorization;

class DepartmentRequestHandlers implements Authorizer
{
  public static function run()
  {
    //reuseable function for authorization in /location


    $response = Authorization::verifyToken();
    if (!$response["status"]) {
      return [
        "status" => false,
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
  }
  /**
   * @return array
   * Takes data as json , validates , checks if already exists and Creates department in database
   */
  public static function createDepartment(): array
  {
    //token and role check 
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }

    $departmentObj = new Department(new DBConnect());
    $jsonData = file_get_contents('php://input');
    $decodedData = json_decode($jsonData, true);

    $keys = [
      'department' => ['required', 'empty', 'departmentFormat']
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
        "data" => [
          "id" => $checkIfDepartmentExists["data"]["id"]
        ]
      ];
    }
    $response = $departmentObj->create($jsonData);
    $decodedData["id"] = $response["data"]["id"];

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

      //token and role check 
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }

      $departmentObj = new Department(new DBConnect());

      //empty array to store filter-sort... parameters
      $callingParameters = [];

      // Define the list of parameters to check
      $parametersToCheck = ["orderby", "sortorder",];
      // dynamically set if data is coming from frontend and is not empty
      foreach ($parametersToCheck as $param) {
        // Check if the parameter is set in $_GET
        if (isset($_GET[$param])) {
          // Push the parameter into $callingParameters

          $callingParameters[$param] = $_GET[$param];

        }
      }

      $response = $departmentObj->getAll($callingParameters);
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
      //token and role check 
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }

      $departmentObj = new Department(new DBConnect());
      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);

      if (isset($_GET["id"])) {
        $decodedData["id"] = $_GET["id"];
      }

      //validation
      $keys = [
        "id" => ['required', 'empty'],
        "newDepartment" => ['required', 'empty', 'departmentFormat']
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
      //check if the id exists in database
      $checkIfIdExists = $departmentObj->getById($decodedData["id"]);

      $exceptionMessageFormat = [
        "status" => "false",
        "statusCode" => "409",
        "message" => [
          "validation" => false,
          "message" => []
        ]
      ];

      //if status is true , location name already exists
      if ($checkIfIdExists["status"] == "false") {
        $exceptionMessageFormat["message"]["message"]["id"] = "Id not found in database !!";
        return $exceptionMessageFormat;
      }

      ///
      //checking new if new name already exsist in database
      $result = $departmentObj->get($decodedData["newDepartment"]);
     
      //if id provided and id fetched by name is not same means it is not same row 
      if ($result["status"] && ($result["data"]["id"] != $decodedData["id"])) {
        $exceptionMessageFormat["message"]["message"]["newDepartment"] = "The name is already assigned to other id !!";
        return $exceptionMessageFormat;

      }

      $response = $departmentObj->updateDepartment($decodedData);


      if (!$response["status"]) {
        $exceptionMessageFormat["message"]["message"]["newLocation"] = "Unable to update in database!!";
        return $exceptionMessageFormat;
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

      //token and role check 
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }
      $departmentObj = new Department(new DBConnect());
      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);

      //getting id ffrom paramter
      if (isset($_GET["id"])) {
        $decodedData["id"] = $_GET["id"];
      }

      //validation
      $keys = [
        "id" => ['required', 'empty']
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

      //check if the id exists in database
      $checkIfIdExists = $departmentObj->getById($decodedData["id"]);
      $exceptionMessageFormat = [
        "status" => "false",
        "statusCode" => "409",
        "message" => [
          "validation" => false,
          "message" => []
        ]
      ];
      //if status is true , location name already exists
      if ($checkIfIdExists["status"] == "false") {
        $exceptionMessageFormat["message"]["message"]["id"] = "Id not found in database !!";
        return $exceptionMessageFormat;
      }


      //calling model function to delete department  using id provided
      $response = $departmentObj->deleteDepartmentById($decodedData["id"]);


      if (!$response["status"]) {
        $exceptionMessageFormat["message"]["message"]["id"] = "$response[message]";
        return $exceptionMessageFormat;
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