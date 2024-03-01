<?php
namespace RequestHandlers;

use Exception;
use Configg\DBConnect;
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

      $userObj = new User(new DBConnect());
      $result = $userObj->getAll();
      if (!$result) {
        self::$exceptionMessageFormat["message"]["message"]["0"] = "Cannot get data !!";
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
          throw new Exception("Failed to upload image");
        }

        $image_validation = Imagevalidator::imagevalidation($image);

        if (!$image_validation["status"]) {
          return [
            "status" => false,
            "statusCode" => "422",
            "message" => "image validation failed",
            "error" => $image_validation["message"]
          ];
        }

        $imageName = uniqid() . '_' . $image['name'];
        $uploadDirectory = dirname(__DIR__) . '/public/employees/uploaded_images/';
        $uploadedFilePath = $uploadDirectory . $imageName;

        $relativeImagePath = 'public/employees/uploaded_images/' . $imageName;


        if (!move_uploaded_file($image['tmp_name'], $uploadedFilePath)) {
          print_r($uploadedFilePath);
          throw new Exception("Failed to move uploaded file");
        }
      } else {
        throw new Exception("No image file uploaded");
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
      //putting image path in decoded data
      $decodedData['user_image'] = $relativeImagePath;

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
          "data" => $decodedData
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
      $id = $_GET["id"] ?? null;
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
    }
  }
}

