<?php

namespace RequestHandlers;

use Exception;
use Configg\DBConnect;
use Model\Assets;
use Validate\Validator;
use Middleware\Authorization;


class AssetsRequestHandlers
{

    public static function createAssets()
    {
        try {
            $assetsObj = new Assets(new DBConnect());
            // $jsonData = file_get_contents('php://input');
            // $decodedData = json_decode($jsonData, true);

            $name = ucfirst($_POST['name']);  
        $assets_type = ucfirst($_POST['assets_type']);
        $category = $_POST['category'];
        $sub_category = $_POST['sub_category'];
        $brand = $_POST['brand'];
        $location = $_POST['location'];
        $assigned_to = $_POST['assigned_to'];
        $status = $_POST['status'];
        $image = $_FILES['assets_image'];

        if ($image['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Failed to upload image");
        }
        $imageName = uniqid() . '_' . $image['name'];
        $uploadDirectory = 'assets-images/';
        $uploadedFilePath = $uploadDirectory . $imageName;
        
        if (!move_uploaded_file($image['tmp_name'], $uploadedFilePath)) {
            throw new Exception("Failed to move uploaded file");
        }
        $data = [
            'name' => $name,
            'assets_type' => $assets_type,
            'category' => $category,
            'sub_category' => $sub_category,
            'brand' => $brand,
            'location' => $location,
            'assigned_to' => $assigned_to,
            'status' => $status,
            'image_name' => $imageName
        ];

            $keys = [
                // 'name' => ['empty'],
                // 'assets_type' => ['empty'],
                // 'category' => ['empty'],
                // 'sub_category' => [],
                // 'brand' => [],
                // 'location' => ['empty', 'maxlength'],
                // 'assigned_to' => ['empty'],
                // 'status' => [],
                // 'assets_image' => []
            ];

            // $validationResult = Validator::validate($decodedData, $keys);

            // if (!$validationResult["validate"]) {
            //     return [
            //         "status" => false,
            //         "statusCode" => "422",
            //         "message" => $validationResult
            //     ];
            // }
            $result = $assetsObj->create($data);

            if (!$result) {
                return [
                    "status" => false,
                    "statusCode" => "409",
                    "message" => "Unable to create assets",
                ];
            }
            return [
                "status" => true,
                "message" => "Assets created successfully!",
                "statusCode" => "201",
                "data" => $data
            ];
        } catch (\Exception $e) {
            return [
                "status" => false,
                "statusCode" => "409",
                "message" => $e->getMessage(),
                "data" => $data
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
            // $filterBy = isset($_GET['filterBy']) ? $_GET['filterBy'] : '';
            // $filterValue = isset($_GET[$filterBy]) ? $_GET[$filterBy] : '';

             // Define an array for filters
        $filters = [];

        // Check for individual parameters
        if (isset($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['assigned_date'])) {
            $filters['assigned_date'] = $_GET['assigned_date'];
        }

        $result = $assetsObj->getAll($assets_type, $search, $sortBy, $order, $filters);

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
