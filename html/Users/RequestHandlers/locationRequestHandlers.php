<?php

namespace RequestHandlers;

use Exception;
use Validate\Validator;
use Model\Location;
use Configg\DBConnect;
use Middleware\Authorization;


class LocationRequestHandlers implements Authorizer
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
   * Takes data as json , validates , checks if already exists and Creates location in database
   */
  public static function createLocation(): array
  {
    //token and role check 
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }

    $locationObj = new Location(new DBConnect());
    $jsonData = file_get_contents('php://input');
    $decodedData = json_decode($jsonData, true);

    $keys = [
      'location' => ['required', 'empty', 'locationFormat']
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

    $checkIfLocationExists = $locationObj->get($decodedData["location"]);

    if ($checkIfLocationExists["status"] === "true") {
      return [
        "status" => "false",
        "statusCode" => 403,
        "message" => "Location already exists",
        "data" => [
          "id" => $checkIfLocationExists["data"]["id"]
        ]
      ];
    }
    $response = $locationObj->create($jsonData);
    $decodedData["id"] = $response["data"]["id"];
    if ($response["status"] === "false") {
      return [
        "status" => "false",
        "statusCode" => 403,
        "message" => "Unalble to create in database.",
        "data" => []
      ];
    }
    return [
      "status" => "true",
      "statusCode" => 200,
      "message" => "Locaiton created succsessfully!!",
      "data" => $decodedData
    ];
  }

  /**
   * @return  array
   * gets all the avaliable locations in database 
   */
  public static function getAllLocation(): array
  {
    try {
      //token and role check 
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }
      $locationObj = new Location(new DBConnect());

      //sorting 

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


      $response = $locationObj->getAll($callingParameters);
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

  public static function updateLocation()
  {
    try {

      //token and role check 
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }
      $locationObj = new Location(new DBConnect());
      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);

      if (isset($_GET["id"])) {
        $decodedData["id"] = $_GET["id"];
      }

      //validation
      $keys = [
        "id" => ['required', 'empty'],
        "newLocation" => ['required', 'empty', 'locationFormat']
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
      $checkIfIdExists = $locationObj->getById($decodedData["id"]);
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
      //checking new if new name already exsist in database
      $result = $locationObj->get($decodedData["newLocation"]);
      //if id provided and id fetched by name is not same means it is not same row 
      
      if ($result["status"]=="true" && ($result["data"]["id"] != $decodedData["id"])) {
        $exceptionMessageFormat["message"]["message"]["newLocation"] = "The name is already assigned to other id !!";
        return $exceptionMessageFormat;

      }




      $response = $locationObj->updateLocation($decodedData);

      if (!$response["status"]) {
        $exceptionMessageFormat["message"]["message"]["newLocation"] = "Unalbe to update in database!!";
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

  public static function deleteLocation(): array
  {
    try {
      //token and role check 
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }

      $locationObj = new Location(new DBConnect());
      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);

      if (isset($_GET["id"])) {
        $decodedData["id"] = $_GET["id"];
      }

      //validation
      $keys = [
        "id" => ['required', 'empty'],

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
      $checkIfIdExists = $locationObj->getById($decodedData["id"]);
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

      //calling model function to delete locaotion  using id provided
      $response = $locationObj->deleteLocationById($decodedData["id"]);

      if (!$response["status"]) {
        $exceptionMessageFormat["message"]["message"]["id"] = "$response[message]";
        return $exceptionMessageFormat;
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => "Location deleted successfully",
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
