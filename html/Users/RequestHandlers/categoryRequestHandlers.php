<?php
namespace RequestHandlers;

use Exception;
use Model\Category;
use Configg\DBConnect;
use Validate\Validator;
use Middleware\Authorization;


class CategoryRequestHandlers implements Authorizer
{
  //reuseable function for authorization
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
    $auhtorize = self::run();
    if ($auhtorize["status"] === false) {
      return $auhtorize;
    }

    $categoryObj = new Category(new DBConnect());

    $callingParameters = [];
    if (isset($_GET["orderby"])) {
      $callingParameters["orderby"] = $_GET["orderby"];
    }
    if (isset($_GET["sortorder"])) {
      $callingParameters["sortorder"] = $_GET["sortorder"];
    }
    $response = $categoryObj->get($callingParameters);
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
   * Authenticates  , authorises 
   * takes params form body and parameter
   * cheks id in database
   * checks if given name is already assigned to other id
   * validates the parent or child as provided using the validation constraints provided
   * updates the data
   *  from
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
      $decodedData["id"] = $_GET["id"];


      $keys = [
        'id' => ['required', 'empty']
      ];


      if (isset($decodedData["newParent"])) {
        $keys['newParent'] =
          ['empty', 'parent_categoryFormat']
        ;
      } else {//else part means newChild is set

        $keys['newChild'] = [
          ['required', 'category_nameFormat'],
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

      $exceptionMessageFormat = [
        "status" => "false",
        "statusCode" => "409",
        "message" => [
          "validation" => false,
          "message" => []
        ]
      ];

      //check if id exists in database

      $result = $categoryModelObj->getById($decodedData["id"]);
      

      if (!$result["status"]) {

        $exceptionMessageFormat["message"]["message"]["id"] = "Id not found in database !!";
        return $exceptionMessageFormat;
      }
    

      //check if new Value is already assigned to other 
      $newValue = "";
      $tempKey = "";
      if (isset($decodedData["newParent"])) {
        $tempKey = "newParent";
        $newValue = $decodedData["newParent"];
      } elseif (isset($decodedData["newChild"])) {
        $tempKey = "newChild";
        $newValue = $decodedData["newChild"];
      }

      //getting by name to check if the neam exists
      $result = $categoryModelObj->getByName($newValue );

      
     //if id provided and id fetched by name is not same means it is not same row 
      if ($result["status"] && ($result["data"]["id"] != $decodedData["id"])) {
                 $exceptionMessageFormat["message"]["message"][$tempKey] = "The name is already assigned to other id !!";
          return $exceptionMessageFormat;
      
      }
 
            //now update in database
      $response = $categoryModelObj->update($decodedData);

      return [
        "status" => true,
        "message" => "Data updated successfully",
        "data" => $response["data"]
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }

  static function delete()
  {
    try {
      //authorization
      $auhtorize = self::run();
      if ($auhtorize["status"] === false) {
        return $auhtorize;
      }
      $categoryObj = new Category(new DBConnect());

      //format for sending erro message
      $exceptionMessageFormat = [
        "status" => "false",
        "statusCode" => "409",
        "message" => [
          "validation" => false,
          "message" => []
        ]
      ];
      //check if id is provided
      $idToDelete = $_GET["id"];
      if (empty($idToDelete)) {
        $exceptionMessageFormat["message"]["message"]["id"] = "Id is required  to delete !!";
        return $exceptionMessageFormat;

      }

      //getting data that needs to be deleted to chek if its parent of sub category
      $result = $categoryObj->getById($idToDelete);

      //check if the id is sub category id and delete directly if its child
      if ($result['data']['parent'] != null) {
        //reaches here if its jsut sub category
        $result = $categoryObj->delete($idToDelete);
      } else {
        //if it reaches here means id is parent id
        // so delete parent first
        $result1 = $categoryObj->delete($idToDelete);
        if (!$result1["status"]) {
          $exceptionMessageFormat["message"]["message"]["id"] = "Could not delete parent !!";
          return $exceptionMessageFormat;
         
        }
        //delet all child of the parent ID
        $result2 = $categoryObj->deleteChildBasedOnParentId($idToDelete);
        if (!$result2["status"]) {
          $exceptionMessageFormat["message"]["message"]["id"] = "Could not delete chlids of given Id !!";
        return $exceptionMessageFormat;
         
        }
      }

      return [
        "status" => true,
        "message" => "Data deleted successfully !",
        "data" => [
          "id" => $idToDelete
        ]
      ];
    } catch (Exception $e) {
      return [
        "status" => false,
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }
}
