<?php
namespace RequestHandlers;

use Exception;
use Helpers\ImageHandler;
use Model\Category;
use Configg\DBConnect;
use Model\DynamicQuery;
use Model\Repairreplace;
use Validate\Validator;
use Middleware\Authorization;

class RepairreplaceRequestHandlers implements Authorizer
{
  private static $exceptionMessageFormat = [
    "status" => "false",
    "statusCode" => "409",
    "message" => [
      "validation" => false,
      "message" => []
    ]
  ];
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
  use ImageHandler;
  public static function create()
  {
    //token and role check 
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }

    //TAKING USER PROVIDED DATA

    // Define the list of expected form-data parameters
    $parameters = ['Assigned-to', 'Product-Code', 'Product-Name', 'Category', 'reason', 'product_image', 'repairreplace_type' ,'status'];

    // Initialize an empty array to store the parameter values
    $formData = [];

    // Loop through the expected parameters and fetch their values from $_POST
    foreach ($parameters as $param) {
      if (isset($_POST[$param])) {
        $formData[$param] = $_POST[$param];

      }
    }
    $decodedData = $formData;



    $repairreplaceObj = new Repairreplace(new DBConnect);


    //cehck if prodcut code already exsits in repairreplace table
    $response = $repairreplaceObj->getByProductCode($decodedData["Product-Code"]);


    if ($response["status"] && $response["data"]["nor"] != 0) {
      self::$exceptionMessageFormat["message"]["message"]["Product-Code"] = "Product already sent for repair or replace !";
      self::$exceptionMessageFormat["statusCode"] = 404;
      return self::$exceptionMessageFormat;
    }

    //validatoin of parameters

    //VALIDATION OF PROVIDED DATA
    $keys = [
      'Assigned-to' => ['required', 'empty', 'minLength'],
      'Product-Code' => ['required', 'empty'],
      'Product-Name' => ['required', 'empty'],
      'Category' => ['required', 'empty',],
      'reason' => ['required', 'empty', 'maxLength', 'minLength'],
      'repairreplace_type' => ['required', 'empty',]
    ];

    $validationResult = Validator::validate($decodedData, $keys);
    if (!$validationResult["validate"]) {
      return [
        "status" => false,
        "statusCode" => "409",
        "message" => $validationResult
      ];
    }


    //
    //check for image data (image data is not required to be sent by frontend)

    if (isset($_FILES['product_image'])) {
      //image uplodaer moves the uploaded file and returns path
      $response = self::imageUploader("product_image");
      $decodedData["product_image"] = $response["data"]["product_image"];

      if (!$response["status"]) {
        return $response;
      }
      if (isset($response["data"]["product_image"])) {
        $decodedData["product_image"] = $response["data"]["product_image"];
      }
    }

    //check if employee exists
    $dynamicQuery = new DynamicQuery(new DBConnect);

    $queryResponse = $dynamicQuery->get("user", "name", $decodedData["Assigned-to"]);
    if ($queryResponse["data"]["nor"] < 1) {
      self::$exceptionMessageFormat["message"]["message"]["Assigned-to"] = "'Owner name' is not an employee !!";
      return self::$exceptionMessageFormat;
    }
    $decodedData["Assigned-to"] = $queryResponse["data"]["data"][0]["id"];


    //check if category exists
    $queryResponse = $dynamicQuery->get("category", "id", $decodedData["Category"]);
    if ($queryResponse["data"]["nor"] < 1) {
      self::$exceptionMessageFormat["message"]["message"]["Category"] = "Category does not exists !!";
      return self::$exceptionMessageFormat;
    }

    //check if repair_replace type is Repair or Replace
    if ($decodedData["repairreplace_type"] != "Repair" && $decodedData["repairreplace_type"] != "Replace") {
      self::$exceptionMessageFormat["message"]["message"]["repairreplace_type"] = "Type should be Repair or Replace !!";
      return self::$exceptionMessageFormat;
    }

    //chek if status is sent or pending only
    if ($decodedData["status"] && ($decodedData["status"] != "Sent" && $decodedData["status"] != "Pending")) {
      self::$exceptionMessageFormat["message"]["message"]["status"] = "Type should be Sent or Pending !!";
      return self::$exceptionMessageFormat;
    }


    //defining insetion data based on database column names
    $insertionData = [];
    $mapping = [
      "Assigned-to" => "assigned_to",
      "Product-Code" => "assets_id",
      "Product-Name" => "assets_name",
      "Category" => "category_id",
      "reason" => "reason",
      "repairreplace_type" => "repairreplace_type",
      "status" => "status",
      "product_image" => "product_image"


    ];

    foreach ($mapping as $decodedKey => $insertionKey) {
      if (isset($decodedData[$decodedKey])) {
        $insertionData[$insertionKey] = $decodedData[$decodedKey];
      }
    }
   
 
    //add in database
    $queryResponse = $dynamicQuery->insert("repairandreplace", $insertionData);

    if (!$queryResponse["status"]) {
      self::$exceptionMessageFormat["message"]["message"] = "Error creating request for repair or replace !!";
      return self::$exceptionMessageFormat;
    }
    return $queryResponse;

  }
}
