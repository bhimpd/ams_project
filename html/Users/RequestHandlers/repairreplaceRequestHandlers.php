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
  private static $tableName = "repairandreplace";
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
  /**
   * CREATION OF DATA IN REPAIR AND REPLACE TABLE IN DATABASE
   * 
   * TAKES INPUTS FROM 
   * 
   * 
   * 
   */
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
    $parameters = ['Assigned_to', 'Product_Code', 'Product_Name', 'Category', 'reason', 'product_image', 'repairreplace_type' ,'status'];

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
    $response = $repairreplaceObj->getByProductCode($decodedData["Product_Code"]);


    if ($response["status"] && $response["data"]["nor"] != 0) {
      self::$exceptionMessageFormat["message"]["message"]["Product_Code"] = "Product already sent for repair or replace !";
      self::$exceptionMessageFormat["statusCode"] = 404;
      return self::$exceptionMessageFormat;
    }


    //VALIDATION OF PROVIDED DATA
    $keys = [
      'Assigned_to' => ['required', 'empty', 'minLength'],
      'Product_Code' => ['required', 'empty'],
      'Product_Name' => ['required', 'empty'],
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

    $queryResponse = $dynamicQuery->get("user", "name", $decodedData["Assigned_to"]);
    if ($queryResponse["data"]["nor"] < 1) {
      self::$exceptionMessageFormat["message"]["message"]["Assigned_to"] = "'Owner name' is not an employee !!";
      return self::$exceptionMessageFormat;
    }
    $decodedData["Assigned_to"] = $queryResponse["data"]["data"][0]["id"];


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
      "Assigned_to" => "assigned_to",
      "Product_Code" => "assets_id",
      "Product_Name" => "assets_name",
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
      self::$exceptionMessageFormat["message"]["message"] = "Error executing request for repair or replace !!";
      return self::$exceptionMessageFormat;
    }
    return $queryResponse;

  }
  public static function getFormDataFromBodyForUpdate(){
    //TAKING USER PROVIDED DATA

    // Define the list of expected form-data parameters
    $parameters = ['Assigned_to', 'Product_Code', 'Product_Name', 'Category', 'reason', 'product_image', 'repairreplace_type' ,'status'];

    // Initialize an empty array to store the parameter values
    $formData = [];

    // Loop through the expected parameters and fetch their values from $_POST
    foreach ($parameters as $param) {
      if (isset($_POST[$param])) {
        $formData[$param] = $_POST[$param];

      }
    }
    return $formData;
  }
  /**
   * UPDATING the table of repair and replace 
   * takes the data from form data
   * 
   * 
   */
  public static function update(){
     //token and role check 
     $auhtorize = self::run();
     if ($auhtorize["status"] === false) {
       return $auhtorize;
     }

     //getting id from request params
     $id = $_GET["id"];
     if (!$id) {
       self::$exceptionMessageFormat["message"]["message"]["id"] = "Id is required field !!";
       
       return self::$exceptionMessageFormat;
     }


     //check if id exists to update
     $dynamicQuery = new DynamicQuery(new DBConnect);

     $checkIfIdExists = $dynamicQuery->get( "repairandreplace" , "id" , $id , true);
  
     if ($checkIfIdExists["data"]["nor"] < 1) {
      self::$exceptionMessageFormat["message"]["message"]["id"] = "Id does not exists to update !!";
      return self::$exceptionMessageFormat;
    }

    

     //getting form data sent in request
     $decodedData = self::getFormDataFromBodyForUpdate();
     


     //validate the data
     $keys = [
      'Assigned_to' => ['required', 'empty'],
      'Product_Code' => ['required', 'empty'],
      'Product_Name' => ['required', 'empty'],
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

    //check if the employee / owner of device  exsist 
    $queryResponse = $dynamicQuery->get("user", "name", $decodedData["Assigned_to"]);
    if ($queryResponse["data"]["nor"] < 1) {
      self::$exceptionMessageFormat["message"]["message"]["Assigned_to"] = "'Owner name' is not an employee !!";
      return self::$exceptionMessageFormat;
    }
    $decodedData["Assigned_to"] = $queryResponse["data"]["data"][0]["id"];

    //check if prodcut code already exsits in repairreplace table
    $repairreplaceObj = new Repairreplace(new DBConnect);
    $response = $repairreplaceObj->getByProductCode($decodedData["Product_Code"]);


    if ($response["status"] && $response["data"]["nor"] != 0) {
      self::$exceptionMessageFormat["message"]["message"]["Product_Code"] = "Product already sent for repair or replace !";
      self::$exceptionMessageFormat["statusCode"] = 404;
      return self::$exceptionMessageFormat;
    }

     //defining updation data based on database column names
     $updatingData = [];
     $mapping = [
       "Assigned_to" => "assigned_to",
       "Product_Code" => "assets_id",
       "Product_Name" => "assets_name",
       "Category" => "category_id",
       "reason" => "reason",
       "repairreplace_type" => "repairreplace_type",
       "status" => "status",
       "product_image" => "product_image"
 
 
     ];
 
     foreach ($mapping as $decodedKey => $insertionKey) {
       if (isset($decodedData[$decodedKey])) {
         $updatingData[$insertionKey] = $decodedData[$decodedKey];
       }
     }
 
    //add in database
    $queryResponse = $dynamicQuery->update("repairandreplace", $updatingData  , ["id" => $id]);
     
    if (!$queryResponse["status"]) {
      self::$exceptionMessageFormat["message"]["message"] = "Error executing request for repair or replace !!";
      return self::$exceptionMessageFormat;
    }
   $queryResponse["statusCode"] = 200;
  return $queryResponse;

  
  }
  public static function delete(){

         //token and role check 
         $auhtorize = self::run();
         if ($auhtorize["status"] === false) {
           return $auhtorize;
         }

        
         $id = $_GET["id"];
         if (!$id) {
           self::$exceptionMessageFormat["message"]["message"]["id"] = "Id is required field !!";
           
           return self::$exceptionMessageFormat;
         }


         //check if id exists to delete 
         $dynamicQuery = new DynamicQuery(new DBConnect);

         $checkIfIdExists = $dynamicQuery->get( "repairandreplace" , "id" , $id , true);
      
         if ($checkIfIdExists["data"]["nor"] < 1) {
          self::$exceptionMessageFormat["message"]["message"]["id"] = "Id does not exists to delete !!";
          return self::$exceptionMessageFormat;
        }

         //delete using soft delete concept
        $response = $dynamicQuery->delete("repairandreplace",$id , true);

        //if exception is thrown from model
        if(!$response["status"]){
          self::$exceptionMessageFormat["message"]["message"] = $response["message"];
          return self::$exceptionMessageFormat;
        }

     return $response;

  }
}
