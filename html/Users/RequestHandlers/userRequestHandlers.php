<?php
namespace RequestHandlers;

use Exception;
use Configg\DBConnect;
use Helpers\FilterandSort;
use Helpers\ImageHandler;
use Model\User;
use Validate\Validator;
use Middleware\Authorization;
use Model\DynamicQuery;
use ImageValidation\Imagevalidator;



/**
 * handles all users related requests like user create/edit/delete 
 */
class UserRequestHandlers implements Authorizer
{
  use FilterandSort;
  private static  $exceptionMessageFormat = [
    "status" => "false",
    "statusCode" => "409",
    "message" => [
      "validation" => false,
      "message" => []
    ]
  ];
  //reuseable function for authorization in /user
  public static function run()
  {
    //Authorizaiton
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
  public static function getUser()
  {
    //token and role check 
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }


    $id = $_GET["id"] ?? NULL;
    $username = $_GET["username"] ?? NULL;
    if ($id == NULL && $username == NULL) {
      return self::getAllUser();
    }
    return self::getByIdOrUsername();
  }

  //gets all the users
  public static function getAllUser()
  { 
    try {
     
      //token and role check 
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }
    //database object and model object creation

      $userObj = new User(new DBConnect());
      
      // using traits to put recurring functions in different file and callign it here
      $callingParameters = self::callingParameters();

     

      $result = $userObj->getAll($callingParameters);
      if (!$result) {
        self::$exceptionMessageFormat["message"]["message"]["0"] = "Data not found !!";
        self::$exceptionMessageFormat["statusCode"] = 404;
        return self::$exceptionMessageFormat;
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
    }
  }


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
      //token and role check
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }

      //object of User model where database object is injected
      $userObj = new User(new DBConnect());

      //taking image first
      if (isset($_FILES['user_image'])) {
        $image = $_FILES['user_image'];

        if ($image['error'] !== UPLOAD_ERR_OK) {
          self::$exceptionMessageFormat["message"]["message"]["user_image"] = "Failed to upload image !!";
          
          return self::$exceptionMessageFormat;
        }

        $image_validation = Imagevalidator::imagevalidation($image);

        if (!$image_validation["status"]) {
          self::$exceptionMessageFormat["message"]["message"]["user_image"] = $image_validation["message"];
          
          return self::$exceptionMessageFormat;

        }



      //TAKING USER PROVIDED DATA

      // Define the list of expected form-data parameters
      $parameters = ['name', 'job_type', 'designation', 'department', 'email', 'phone_number'];

      // Initialize an empty array to store the parameter values
      $formData = [];

      // Loop through the expected parameters and fetch their values from $_POST
      foreach ($parameters as $param) {
        if (isset($_POST[$param])) {
          $formData[$param] = $_POST[$param];
        }
      }


      $decodedData = $formData;
      

      //explicitly assignning employee as user_type so that admin can only be created from database
      $decodedData["user_type"] = "employee";
      $jsonData = json_encode($decodedData);



      //VALIDATION OF PROVIDED DATA
      $keys = [
        'name' => ['required', 'empty', 'maxLength', 'minLength'],
        'job_type' => ['required', 'empty'],
        'designation' => ['required', 'empty', 'designationFormat'],
        'department' => ['required', 'empty', 'maxLength', 'minLength'],
        'email' => ['required', 'empty', 'maxLength', 'minLength', 'emailFormat'],
        'phone_number' => ['required', 'empty', 'maxLength', 'minLength', 'phone_numberFormat'],

      ];

      $validationResult = Validator::validate($decodedData, $keys);
      if (!$validationResult["validate"]) {
        return [
          "status" => false,
          "statusCode" => "409",
          "message" => $validationResult
        ];
      }

      //uploading the photo after every other validation is ok
      $imageName = uniqid() . '_' . $image['name'];
      $uploadDirectory = dirname(__DIR__) . '/public/user/uploaded_images/';
      $uploadedFilePath = $uploadDirectory . $imageName;

      $relativeImagePath = '/Users/public/user/uploaded_images/' . $imageName;
   
      if (!move_uploaded_file($image['tmp_name'], $uploadedFilePath)) {
       
        throw new Exception("Failed to move uploaded file");
      }
    } else {
      throw new Exception("No image file uploaded");
    }

    //putting image path in decoded data
    $decodedData['user_image'] = $relativeImagePath;

      //checking if email already exists

      $dynamicQuery = new DynamicQuery(new DBConnect);

      $queryResponse = $dynamicQuery->get("user", "email", $decodedData["email"]);
      if (!$queryResponse["data"]["nor"] < 1) {

        throw new Exception("Email already exists !!");
      }

      $result = $userObj->create(json_encode($decodedData, true));

     
      //injecting id into response from $result
      $decodedData["id"] = $result["data"]["id"];
      if (!$result) {
        return [
          "status" => false,
          "statusCode" => "409",
          "message" => "Unable to create user",
          "data" => "$decodedData"
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
    }
  }

  use ImageHandler;
  public static function updateUser()
  {
    
    try {
       //token and role check 
       $auhtorize = self::run();
      
       if ($auhtorize["status"] === false) {
         return $auhtorize;
       }
      
      
       //TAKING USER PROVIDED DATA

      // Define the list of expected form-data parameters
      $parameters = ['name', 'job_type', 'designation', 'department', 'email', 'phone_number' , 'user_image'];

      // Initialize an empty array to store the parameter values
      $formData = [];

      // Loop through the expected parameters and fetch their values from $_POST
      foreach ($parameters as $param) {
        if (isset($_POST[$param])) {
          $formData[$param] = $_POST[$param];
         
        }
      }
      $decodedData = $formData;
    
      $id = $_GET["id"] ?? null;
      if (!$id) {
        self::$exceptionMessageFormat["message"]["message"]["id"] = "Id is required !!";
        
        return self::$exceptionMessageFormat;
      
      }

     
      $userObj = new User(new DBConnect());
      $result = $userObj->get($id, NULL);
      if ($result["status"] == "false") {
        unset($result);
        self::$exceptionMessageFormat["message"]["message"]["id"] = "User not found to update!! !!";
        self::$exceptionMessageFormat["statusCode"] = 404;
        return self::$exceptionMessageFormat;
      }
    //VALIDATION OF PROVIDED DATA
    $keys = [
      'name' => ['required', 'empty', 'maxLength', 'minLength'],
      'job_type' => ['required', 'empty'],
      'designation' => ['required', 'empty', 'designationFormat'],
      'department' => ['required', 'empty', 'maxLength', 'minLength'],
      'email' => ['required', 'empty', 'maxLength', 'minLength', 'emailFormat'],
      'phone_number' => ['required', 'empty', 'maxLength', 'minLength', 'phone_numberFormat'],

    ];

      $validationResult = Validator::validate($decodedData, $keys);
      if (!$validationResult["validate"]) {
        $response = array(
          "status" => false,
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => []
        );
        return $response;
      }

      //checking if email already exists

      $dynamicQuery = new DynamicQuery(new DBConnect);

      $queryResponse = $dynamicQuery->get("user", "email", $decodedData["email"]);
     
      if ($queryResponse["status"] && ($queryResponse["data"]["id"] != $decodedData["id"])) {
        self::$exceptionMessageFormat["message"]["message"]["email"] = "Email already exists !!";
        return self::$exceptionMessageFormat;
      }

      //checking if the user image is same(sends the image path) or if new image is uploaded($_FILE is set)
      if(isset($_POST["user_image"]) && !empty($_POST["user_image"])){
        $decodedData["user_image"] = $_POST["user_image"];
      }
      else if(isset($_FILES['user_image'])) {
        //image uplodaer moves the uploaded file and returns path
       $response = self ::imageUploader();
       
       if(!$response["status"]){
        return $response;
       }
       if(isset($response["data"]["user_image"])){
        $decodedData["user_image"] = $response["data"]["user_image"];
       }
       
    }else{
      self::$exceptionMessageFormat["message"]["message"]["user_image"] = "Image data must be provided !";
      return self::$exceptionMessageFormat;
    }

  
  
      $updateStatus = $userObj->update($id, $decodedData);

      if ($updateStatus["result"] == true) {
        $decodedData["id"] = $id;
        return [
          "status" => true,
          "statusCode" => "200",
          "message" => "User Updated successfully",
          "updatedData" => $decodedData
        ];
      } else {
          self::$exceptionMessageFormat["message"]["message"]["update"] = "Unable to update in database!!". ": $updateStatus[message] ";
        return self::$exceptionMessageFormat;
      }

    } catch (Exception $e) {
      return [
        "status" => false,
        "statusCode" => 401,
        "message" => $e->getMessage()
      ];
    }
  }
  public static function deleteUser()
  {
    try {
          //token and role check 
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }

      $userObj = new User(new DBConnect());
      $id = $_GET["id"];
      if (!$id) {
        self::$exceptionMessageFormat["message"]["message"]["id"] = "Id is required field !!";
        
        return self::$exceptionMessageFormat;
      }
      
      
      $result = $userObj->get($id, NULL);
      if ($result["status"] == "false") {
        unset($result);
        self::$exceptionMessageFormat["message"]["message"]["id"] = "User not found to delete!! !!";
        self::$exceptionMessageFormat["statusCode"] = 404;
        return self::$exceptionMessageFormat;
       
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
    }
  }
}

