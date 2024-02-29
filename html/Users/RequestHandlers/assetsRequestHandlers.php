<?php

namespace RequestHandlers;

use Exception;
use Configg\DBConnect;
use Model\Assets;
use Validate\Validator;
use Middleware\Authorization;
use ImageValidation\Imagevalidator;

class AssetsRequestHandlers
{

    public static function createAssets()
    {
        try {
            $assetsObj = new Assets(new DBConnect());

            // Get other form-data parameters
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $assetsType = isset($_POST['assets_type']) ? $_POST['assets_type'] : '';
            $category = isset($_POST['category']) ? $_POST['category'] : '';
            $subCategory = isset($_POST['sub_category']) ? $_POST['sub_category'] : '';
            $brand = isset($_POST['brand']) ? $_POST['brand'] : '';
            $location = isset($_POST['location']) ? $_POST['location'] : '';
            $assignedTo = isset($_POST['assigned_to']) ? $_POST['assigned_to'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : '';

            $decodedData = [
                'name' => $name,
                'assets_type' => $assetsType,
                'category' => $category,
                'sub_category' => $subCategory,
                'brand' => $brand,
                'location' => $location,
                'assigned_to' => $assignedTo,
                'status' => $status,
            ];


            $keys = [
                'name' => ['empty', 'minLength', 'maxLength'],
                'assets_type' => ['empty', 'assets_format'],
                'category' => ['empty'],
                'sub_category' => ['empty'],
                'brand' => ['empty', 'minlength', 'maxLength', 'category_nameFormat'],
                'location' => ['required', 'empty'],
                'assigned_to' => ['empty'],
                'status' => ['empty', 'required'],
            ];

            $validationResult = Validator::validate($decodedData, $keys);

            if (!$validationResult["validate"]) {
                return [
                    "status" => false,
                    "statusCode" => "422",
                    "message" => $validationResult
                ];
            }


            if (isset($_FILES['assets_image'])) {
                $image = $_FILES['assets_image'];

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
                $uploadDirectory = dirname(__DIR__) . '/public/assets/uploaded_images/';
                $uploadedFilePath = $uploadDirectory . $imageName;

                $relativeImagePath = 'public/assets/uploaded_images/' . $imageName;
                $decodedData['image_name'] = $relativeImagePath;

                if (!move_uploaded_file($image['tmp_name'], $uploadedFilePath)) {
                    throw new Exception("Failed to move uploaded file");
                }
            } else {
                throw new Exception("No image file uploaded");
            }


            $result = $assetsObj->create($decodedData);

            if (!$result) {
                return [
                    "status" => false,
                    "statusCode" => "409",
                    "message" => "Unable to create assets",
                ];
            }
            return [
                "status" => true,
                "message" => "Assets has been added",
                "statusCode" => "201",
                "data" => $decodedData
            ];
        } catch (\Exception $e) {
            return [
                "status" => false,
                "statusCode" => "409",
                "message" => $e->getMessage(),
                "data" => $decodedData
            ];
        }
    }

    public static function getAssets()
    {
        $response = Authorization::verifyToken();
        if (!$response["status"]) {
            return [
                "status" => false,
                "statusCode" => "401",
                "message" => $response["message"],
                "data" => []
            ];
        }
        if ($response["data"]["user_type"] !== "admin") {
            return [
                "status" => false,
                "statusCode" => 401,
                "message" => "User unauthorised",
                "data" => $response["data"]
            ];
        }

        $id = $_GET["id"] ?? NULL;

        if ($id == NULL) {
            return self::getAllAssets();
        }

        return self::getAssetById();
    }


    public static function getAllAssets()
    {
        try {
            $assetsObj = new Assets(new DBConnect());
            $assets_type = isset($_GET['assets_type']) ? $_GET['assets_type'] : 'hardware';
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'id';
            $order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

            $filters = [];

            if (isset($_GET['category'])) {
                $filters['category'] = $_GET['category'];
            }
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['assigned_date'])) {
                $filters['assigned_date'] = $_GET['assigned_date'];
            }
           
            if (isset($_GET['assigned_to'])) {
                $filters['assigned_to'] = $_GET['assigned_to'];
            }

            //  var_dump($filters);die;
            $result = $assetsObj->getAll($assets_type, $search, $sortBy, $order, $filters, $currentPage);

            if (!$result) {
                throw new Exception("Cannot get data !!");
            }

            return [
                "statusCode" => "200",
                "message" => "Data extracted.",
                "data" => $result
            ];
        } catch (Exception $e) {
            return [
                "statusCode" => 404,
                "status" => false,
                "message" => $e->getMessage()
            ];
        } finally {
            $assetsObj->DBconn->disconnectFromDatabase();
        }
    }

    public static function getAssetById()
    {
        $assetsObj = new Assets(new DBConnect());

        $id = $_GET["id"] ?? NULL;
        $result = $assetsObj->get($id);
        if ($result["status"] == "false") {
            return [
                "status" => "false",
                "statusCode" => 404,
                "message" => "Assets id $id not found"
            ];
        }
        return [
            "status" => true,
            "statusCode" => "200",
            "message" => "Data extracted.",
            "data" => $result
        ];
    }

    public static function deleteAsset()
    {
        try {
            $assetsObj = new Assets(new DBConnect());
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
                    "message" => "User type unauthorised !",
                    "data" => $response["data"]
                ];
            }
            $id = $_GET["id"];

            if (empty($id)) {
                throw new Exception("Id not provided !!");
            }
            $result = $assetsObj->get($id);

            if ($result["status"] == "false") {
                unset($result);
                return [
                    "status" => false,
                    "statusCode" => 404,
                    "message" => "asset of Id :$id not found"
                ];
            }
            $deleteStatus = $assetsObj->delete($id);

            if ($deleteStatus["status"] == true) {
                return [
                    "status" => true,
                    "statusCode" => 200,
                    "message" => "asset of Id :$id deleted successfully"
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
        } finally {
            //disconnecting from database
            $assetsObj->DBconn->disconnectFromDatabase();
        }
    }

    public static function updateAsset()
    {
        try {
            $assetsObj = new Assets(new DBConnect());
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
            $id = $_GET["id"];

            if (!$id) {
                throw new Exception("Id not provided !!");
            }

            $result = $assetsObj->get($id);

            if ($result["status"] == "false") {
                unset($result);
                throw new Exception("assets not found to update!!");
            }
            $keys = [
                'name' => ['empty'],
                'assets_type' => ['empty'],
                'category' => ['empty'],
                'sub_category' => [],
                'brand' => [],
                'location' => ['empty', 'maxlength'],
                'assigned_to' => ['empty'],
                'status' => [],
                'assets_image' => []
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

            $updateStatus = $assetsObj->update($id, $jsonData);

            if ($updateStatus["result"] == true) {

                return [
                    "status" => true,
                    "statusCode" => "201",
                    "message" => "assets Updated successfully",
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
            $assetsObj->DBconn->disconnectFromDatabase();
        }
    }
}
