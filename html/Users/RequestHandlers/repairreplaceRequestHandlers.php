<?php
namespace RequestHandlers;

use Exception;
use Model\Category;
use Configg\DBConnect;
use Model\Repairreplace;
use Validate\Validator;
use Middleware\Authorization;

class RepairreplaceRequestHandlers implements Authorizer
{
  public static function run()
  {
    //reuseable function for authorization in /repairreplace


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

  public static function get()
  {
    

    //token and role check 
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }

    //database object and model object creation
    $repairreplaceObj = new Repairreplace(new DBConnect);

    //empty array to store filter-sort... parameters
    $callingParameters = [];

    // Define the list of parameters to check
    $parametersToCheck = ["orderby", "sortorder", "filterbyCategory", "filterbyStatus", "filterbyAssignedDate", "searchKeyword", "type"];

    // dynamically set if data is coming from frontend and is not empty
    foreach ($parametersToCheck as $param) {
      // Check if the parameter is set in $_GET
      if (isset($_GET[$param])) {
        // Push the parameter into $callingParameters

        $callingParameters[$param] = $_GET[$param];

        //making frontend friendly keyword i.e. repairreplace_type in to 'type'
        if ($param == "type") {
          $callingParameters["repairreplace_type"] = $_GET[$param];
          //removing unnecessary code
          unset($callingParameters["type"]);

        }
      }
    }
    //query sending to model
    $response = $repairreplaceObj->get($callingParameters);

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

    $repairreplaceObj = new Repairreplace(new DBConnect);
    $jsonData = file_get_contents('php://input');
    $decodedData = json_decode($jsonData, true);
    print_r($decodedData);
    $response = $repairreplaceObj->get($decodedData["assets_id"]);


    // make ammendments in get by id 

    // $repairreplaceObj = new Repairreplace();
  }
}
