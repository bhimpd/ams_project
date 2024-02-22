<?php
namespace RequestHandlers;

use Exception;
use Model\Category;
use Configg\DBConnect;
use Validate\Validator;
use Middleware\Authorization;

class CategoryRequestHandlers
{
  /**
   * creates category
   */
  public static function createCategory(): array
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
    $jsonData = file_get_contents('php://input');
    $decodedData = json_decode($jsonData, true);
    $keys = [
      'category_name' => ['required', 'empty', 'category_nameFormat'],

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

    //parent is empty --->  creation of parent category
    if (empty($decodedData["parent"])) {
      $parentCreation = $decodedData;
      $parentCreation["parent"] = $parentCreation["category_name"];
      $parentCreation["category_name"] = NULL;

      //checking in database
      $checkIfParentCategoryExists = $categoryObj->get(NULL, $parentCreation["parent"]);

      if ($checkIfParentCategoryExists["status"] === "true") {
        return [
          "status" => "false",
          "statusCode" => 403,
          "message" => "Parent Category alredy exists",
          "data" => []
        ];
      }

      $parentCreation["parent"] = ucfirst($parentCreation["parent"]);
      $response = $categoryObj->create(json_encode($parentCreation));


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
        "data" => $parentCreation
      ];
    }

    //checking in database
    $checkIfCategoryExists = $categoryObj->get($decodedData["category_name"], NULL);

    if ($checkIfCategoryExists["status"] === "true") {
      return [
        "status" => "false",
        "statusCode" => 403,
        "message" => "Category alredy exists",
        "data" => []
      ];
    }

    $response = $categoryObj->create($jsonData);

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
      "data" => json_decode($jsonData, true)
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
    $response = $categoryObj->get($_GET["category_name"], $_GET["parent"], $_GET["id"]);


    function buildCategoryTree(array $categories)
    {

      $tree = [];


      foreach ($categories as $category) {
        $parent = $category['parent'];

        if (!isset($tree[$parent])) {
          $tree[$parent] = [];
        }
        if ($category['category_name'] == "") {
          continue;
        }
        $tree[$parent][] = [
          'id' => $category['id'],
          'category_name' => $category['category_name'],

        ];
      }
      return $tree;
    }
    $categoryTree = buildCategoryTree($response["data"]);


    return [
      "statusCode" => 200,
      "status" => $response["status"],
      "message" => $response["message"],
      "data" => $categoryTree
    ];
  }
  /**
   *  takes preParent from params and newParent name from 
   *  body as json value
   */
  public static function update(): array
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

      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);
      if (isset($decodedData["previousParent"])) {
        $previous = $decodedData["previousParent"];
        $new = $decodedData["newParent"];
        if (empty($previous)) {
          throw new Exception("Previous parent/category value not provided!!");
        }
        $result = $categoryModelObj->get(NULL, $previous);

      } else if (isset($decodedData["previouscategory_name"])) {
        $previous = $decodedData["previouscategory_name"];
        $new = $decodedData["newcategory_name"];
        if (empty($previous)) {
          throw new Exception("Previous parent/category value not provided!!");
        }
        $result = $categoryModelObj->get($previous, NULL);
      }

      if ($result["status"] == "false") {
        throw new Exception("Previous value not found in database!!");
      }
      //validation
      $dataToValidate = [
        "previous" => $previous,
        "new" => $new,
      ];
      $keys = [
        'new' => ['required', 'empty', 'parent_categoryFormat'],
        'previous' => ['required', 'empty']
      ];

      $validationResult = Validator::validate($dataToValidate, $keys);
      if (!$validationResult["validate"]) {
        $response = array(
          "status" => "false",
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => $dataToValidate
        );
        return $response;
      }
         $response = $categoryModelObj->update( $decodedData);

      if (!$response["status"]) {
        throw new Exception("Unalbe to update in database!!");
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => $response["message"]
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }
  public static function updateParent(): array
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

      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);
      $previousParent = $_GET["previousParent"];
      if (empty($previousParent)) {
        throw new Exception("Previous parent not provided!!");
      }
      $result = $categoryModelObj->get(NULL, $previousParent);

      if ($result["status"] == "false") {
        throw new Exception("Parent category not found in database!!");
      }

      //validation
      $dataToValidate = [
        "previousParent" => $previousParent,
        "newParent" => $decodedData["newParent"],
      ];
      $keys = [
        'newParent' => ['required', 'empty', 'parent_categoryFormat'],
        'previousParent' => ['required', 'empty',]
      ];

      $validationResult = Validator::validate($dataToValidate, $keys);
      if (!$validationResult["validate"]) {
        $response = array(
          "status" => "false",
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => $dataToValidate
        );
        return $response;
      }

      $response = $categoryModelObj->updateParent($_GET["previousParent"], $decodedData["newParent"]);

      if (!$response["status"]) {
        throw new Exception("Unalbe to update in database!!");
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => $response["message"]
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }

  public static function updateChild()
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

      $jsonData = file_get_contents("php://input");
      $decodedData = json_decode($jsonData, true);
      $previousChild = $_GET["previousChild"];
      if (empty($previousChild)) {
        throw new Exception("Previous child not provided!!");
      }
      $result = $categoryModelObj->get($previousChild, NULL);

      if ($result["status"] == "false") {
        throw new Exception("Child category not found in database to update!!");
      }

      //validation
      $dataToValidate = [
        "previousChild" => $previousChild,
        "newChild" => $decodedData["newChild"],
      ];
      $keys = [
        'newChild' => ['empty', 'required', 'category_nameFormat'],
        'previousChild' => ['empty', 'required']
      ];

      $validationResult = Validator::validate($dataToValidate, $keys);
      if (!$validationResult["validate"]) {
        $response = array(
          "status" => "false",
          "statusCode" => "409",
          "message" => $validationResult,
          "data" => $dataToValidate
        );
        return $response;
      }

      $response = $categoryModelObj->updateCategory($previousChild, $decodedData["newChild"]);

      if (!$response["status"]) {
        throw new Exception("Unalbe to update in database!!");
      }
      return [
        "status" => $response["status"],
        "statusCode" => 200,
        "message" => $response["message"]
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "statusCode" => 500
      ];
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
