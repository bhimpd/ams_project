<?php
namespace RequestHandlers;

use Exception;
use Model\Category;
use Configg\DBConnect;
use Validate\Validator;
use Middleware\Authorization;

interface Authorizer
{
  public static function run();
}
class CategoryRequestHandlers implements Authorizer
{

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
  /**
   * creates category
   */
  public static function createCategory(): array
  {
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }


    $categoryObj = new Category(new DBConnect());
    $jsonData = file_get_contents('php://input');
    $decodedData = json_decode($jsonData, true);

    //empty parent means category_name is set to be parent
    if (empty($decodedData["parent"])) {
      $keys = [
        'category_name' => ['required', 'empty', 'parent_categoryFormat']
      ];
    } else {
      $keys = [
        'category_name' => ['required', 'empty', 'category_nameFormat'],

      ];
    }

    $validationResult = Validator::validate($decodedData, $keys);

    if (!$validationResult["validate"]) {
      return [
        "status" => "false",
        "statusCode" => "409",
        "message" => $validationResult,
        "data" => json_decode($jsonData, true)
      ];
    }
    //getting parents in an array
    $parentArr = $categoryObj->getParent();

    //parent is empty --->  creation of parent category
    if (empty($decodedData["parent"])) {
      $parentCreation = $decodedData;
      $parentCreation["parent"] = $parentCreation["category_name"];
      $parentCreation["category_name"] = NULL;


      //checking in database ... by using  all parent data


      //self executing function 
      $parentFound = (function ($parentArr, $parentCreation) {
        foreach ($parentArr['data'] as $key => $value) {
          if ($parentCreation['parent'] == $value['category_name']) {
            return true;
          }
        }
        return false;
      })($parentArr, $parentCreation);

      if ($parentFound === true) {
        return [
          "status" => false,
          "statusCode" => 403,
          "message" => "Parent Category alredy exists",
          "data" => []
        ];
      }

      $response = $categoryObj->createParent(json_encode($parentCreation));


      if ($response["status"] == "false") {
        return [
          "status" => "false",
          "statusCode" => 403,
          "message" => $response["message"],
          "data" => []
        ];
      }
      return [
        "status" => "true",
        "statusCode" => 200,
        "message" => "Category created succsessfully!!",
        "data" => $response["data"]
      ];
    }
    ////case for no empty parent i.e creation of sub category under available parent


    //checking in database

    $parentidToSearch = $decodedData["parent"];

    $checkIfParentExists = (function ($parentArr, $parentidToSearch) {
      foreach ($parentArr['data'] as $key => $value) {
        if ($value['id'] == $parentidToSearch) {
          return true;
        }
      }
      return false;
    })($parentArr, $parentidToSearch);


    //return if parent provided does not ecist in database
    if (!$checkIfParentExists) {
      return [
        "status" => "false",
        "statusCode" => 403,
        "message" => "Parent category does not  exists",
        "data" => []
      ];
    }
    //get child of the provide d parent id
    $getChildofParent = $categoryObj->getChild($decodedData["parent"]);
    //check if the child array already contains the new child

    foreach ($getChildofParent["data"] as $key => $value) {
      if ($value['category_name'] == $decodedData["category_name"]) {
        return [
          "status" => "false",
          "statusCode" => 403,
          "message" => "Child  category already exists !!",
          "data" => [
            $value
          ]
        ];
      }
    }

    //creatin in database
    $response = $categoryObj->create($jsonData);

    ///gunction to get id of the new categiry created
    $newId = $categoryObj->getIdbyNameandParent($decodedData['category_name'], $decodedData['parent']);
    $newId = $newId["data"];
    $decodedData["Id"] = $newId;

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
      "message" => "Category created succsessfully!!",
      "data" => $decodedData
    ];
  }
  public static function get()
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

    $categoryObj = new Category(new DBConnect());
    $response = $categoryObj->get($_GET["category_name"], $_GET["parent"]);
    $data = $response['data'];
    $res = [];
    foreach ($data as $key => $value) {
      $child = $categoryObj->getChild($value['id']);
      $child = $child['data'];
      $res[] = [
        'parent' => $value['category_name'],
        'id' => $value['id'],
        'child' => count($child) > 0 ? $child : []
      ];
    }
    return [
      "status" => $response["status"],
      "statusCode" => 200,
      "message" => $response["message"],
      "data" => $res
    ];
  }


  /**
   *  takes preParent from params and newParent name from 
   *  body as json value
   */
  public static function update(): array
  {
    try {

      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }

      $categoryModelObj = new Category(new DBConnect());

      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);
      if (isset($_GET["Id"])) {
        $decodedData["Id"] = $_GET["Id"];

      } else {
        throw new Exception(" Id not provided to update!!");
      }

      if (!isset($decodedData["newParent"])) {
        if (!isset($decodedData["newChild"])) {
          throw new Exception("New value  is required for update !!");
        }

      }
      //check if id exists in database

      $result = $categoryModelObj->getById($decodedData["Id"]);
   

      if (!$result["status"]) {
        throw new exception("Id not found in database !!");
      }

      //check if new Value is already assigned to other 
      $newValue ="hfgdgfd";
      if(isset($decodedData["newParent"] )){
        $newValue = $decodedData["newParent"];
      }elseif(isset($decodedData["newChild"])){
        $newValue = $decodedData["newChild"];
      }
     
     
      $result  = $categoryModelObj->getByName($newValue);
      if($result["status"]){
        throw new Exception("The name is already assigned to other id !!");
      }
      
      //new value validation
      if(isset($decodedData["newParent"])){
        $keys = [
          'newParent' => ['empty', 'parent_categoryFormat']
        ];
      }else{//else part meand newChild is set

        $keys = [
          'newChild' => [  'category_nameFormat'],
        ];
      }
      $validationResult = Validator::validate($decodedData, $keys);

      if (!$validationResult["validate"]) {
        return [
          "status" => "false",
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => json_decode($jsonData, true)
        ];
      }

      //now update in database
      $categoryModelObj-> update($decodedData);

      return [
        "status" => true,
        "message" => "Data updated successfully",
        "data" => []
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }

static function delete(){
  try{
    //authorization
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }
    $categoryObj = new Category(new DBConnect());

    //check if id is provided
    $idToDelete = $_GET["id"];
    if(empty($idToDelete)){
      throw new Exception ("Id is required  to delete!!");
    }

    $result =$categoryObj->getById($idToDelete);

    print_r($result);
    if($result["parent"] == NULL){
      print_r("herer");
    }
  }catch(Exception $e){

  }
}
  public static function deleteChild()
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
      $categoryModelObj = new Category(new DBConnect());

      $childCategory = $_GET["childCategory"];

      if (empty($childCategory)) {
        throw new Exception(" Child Category not provided!!");
      }

      $result = $categoryModelObj->get($childCategory, NULL);

      if ($result["status"] === "false") {
        throw new Exception("Child category not found to delete!!");
      }

      $response = $categoryModelObj->deleteChild($childCategory);

      if ($response["status"] == false) {
        return [
          "status" => $response["status"],
          "message" => $response["message"],
          "statusCode" => 500
        ];
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => "Child deleted successfully",
        "data" => [
          "childCategory" => $childCategory
        ]
      ];
    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "statusCode" => 500
      ];
    }

  }

  public static function deleteParent()
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
      $categoryModelObj = new Category(new DBConnect());
      $parentCategory = $_GET["parentCategory"];

      if (empty($parentCategory)) {
        throw new Exception(" Parent Category not provided!!");
      }

      $result = $categoryModelObj->get(NULL, $parentCategory);

      if ($result["status"] === "false") {
        throw new Exception("Parent category not found to delete!!");
      }
      $response = $categoryModelObj->deleteParent($parentCategory);

      if ($response["status"] == false) {
        return [
          "status" => $response["status"],
          "message" => $response["message"],
          "statusCode" => 500
        ];
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => "Parent Category deleted successfully",
        "data" => [
          "parentCategory" => $parentCategory
        ]
      ];
    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "statusCode" => 500
      ];
    }
  }
}
