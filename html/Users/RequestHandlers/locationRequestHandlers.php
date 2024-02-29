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
        "message" => "Location alredy exists",
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

      //if status is true , location name already exists
      if ($checkIfIdExists["status"] == "false") {

        throw new Exception("Id does not exists !!");
      }

      //checking new if new name already exsist in database
      $checkIfNewNameAlreadyExists = $locationObj->get($decodedData["newLocation"]);

      //if status is true , location name already exists
      if ($checkIfNewNameAlreadyExists["status"] == "true") {

        throw new Exception("New name provided already exists !!");
      }

      $response = $locationObj->updateLocation($decodedData);

      if (!$response["status"]) {
        throw new Exception("Unalbe to update in database!!");
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

      //if status is true , location name already exists
      if ($checkIfIdExists["status"] == "false") {

        throw new Exception("Id does not exists !!");
      }

      //calling model function to delete locaotion  using id provided
      $response = $locationObj->deleteLocationById($decodedData["id"]);

      if (!$response["status"]) {
        throw new Exception($response["message"]);
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
