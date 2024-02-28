<?php

namespace RequestHandlers;

use Exception;
use Configg\DBConnect;
use Model\Procurement;
use Validate\Validator;
use Middleware\Authorization;
use ProcurementValidator\ProcurementsValidation;

class ProcurementRequestHandlers
{
    public static function createProcurement()
    {
        try {

            $procurementObj = new Procurement(new DBConnect());
            $jsonData = file_get_contents('php://input');
            $decodedData = json_decode($jsonData, true);

            //VALIDATION OF PROVIDED DATA
        
            $validatekeys = [
                'requested_by_id' => ['empty'],
                'status' => ['empty'],
                'request_urgency' => ['empty'],
                'product_name' => ['required'],
                'category_id' => ['required'],
                'brand' => ['required'],
                'estimated_price' => ['required'],
                'link' => ['required']
            ];
            $procurementValidate = ProcurementsValidation::validateProcurement($decodedData, $validatekeys);
            
            if ($procurementValidate["status"] === false) {
                return [
                    "statusCode"=>422,
                    "status" => false,
                    "message" => $procurementValidate["message"] // Return validation errors
                ];
            }
            
            $result = $procurementObj->create($jsonData);

            if (!$result) {
                return [
                    "status" => false,
                    "statusCode" => "409",
                    "message" => "Unable to create procurement",
                    "data" => json_decode($jsonData, true)
                ];
            }
            return [
                "status" => true,
                "statusCode" => "201",
                "message" => "Data inserted successfully",
                "data" => $decodedData
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "statusCode" => "409",
                "message" => $e->getMessage(),
                "data" => json_decode($jsonData, true)
            ];
        } finally {
            $procurementObj->DBconn->disconnectFromDatabase();
        }
    }

    public static function getProcurements()
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
        //checks if user is not admin
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
            return self::getAllProcurements();
        }

        return self::getProcurementById($id);
    }


    public static function getAllProcurements()
    {
        try {
            $proObj = new Procurement(new DBConnect());

            $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'id';
            $order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';
            $search = isset($_GET['search']) ? $_GET['search'] : '';

            $filters = [];

            // Check for individual parameters
            if (isset($_GET['requestedBy'])) {
                $filters['requestedBy'] = $_GET['requestedBy'];
            }
            if (isset($_GET['approvedBy'])) {
                $filters['approvedBy'] = $_GET['approvedBy'];
            }
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['approvedDate'])) {
                $filters['approvedDate'] = $_GET['approvedDate'];
            }

            $result = $proObj->getAll($search, $sortBy, $order, $filters);

            if (!$result) {
                throw new Exception("Cannot get data !!");
            }

            return [
                "statusCode" => "200",
                "status" => true,
                "message" => "Data extracted",
                "data" => $result
            ];
        } catch (Exception $e) {
            return [
                "statusCode" => 404,
                "status" => false,
                "message" => $e->getMessage()
            ];
        } finally {
            $proObj->DBconn->disconnectFromDatabase();
        }
    }


    public static function getProcurementById()
    {
        $proObj = new Procurement(new DBConnect());
        $id = $_GET["id"] ?? NULL;
        $result = $proObj->get($id);
        $procurementInfoResult = $proObj->getProcurementInfo($id);

        if ($result["status"] == "false") {
            return [
                "status" => "false",
                "statusCode" => 404,
                "message" => "procurement id $id not found"
            ];
        }

        return [
            "status" => true,
            "statusCode" => "200",
            "message" => $result['message'],
            "procurementInfo" => [
                "requested_by" => $procurementInfoResult['requested_by'],
                "urgency" => $procurementInfoResult['urgency'],
                "total_data" => $result['total_data'],
                "data" => $result['data']
            ],

        ];
    }

    public static function deleteProcurement()
    {
        try {
            $proObj = new Procurement(new DBConnect());
            $response = Authorization::verifyToken();
            if (!$response["status"]) {
                return [
                    "status" => false,
                    "statusCode" => "401",
                    "message" => $response["message"],
                    "data" => []
                ];
            }
            // Checks if the user is not an admin
            if ($response["data"]["user_type"] !== "admin") {
                return [
                    "status" => false,
                    "statusCode" => 401,
                    "message" => "User type unauthorised !",
                    "data" => $response["data"]
                ];
            }

            // Check if the request is for deleting a product or procurement
            if (isset($_GET["product_id"])) {
                $productID = $_GET["product_id"];
                $deleteStatus = $proObj->deleteProduct($productID);
            } else {
                $procurementID = $_GET["id"];
                $deleteStatus = $proObj->deleteProcurement($procurementID);
            }

            if ($deleteStatus["status"] == true) {
                return [
                    "status" => true,
                    "statusCode" => 200,
                    "message" => $deleteStatus["message"]
                ];
            } else {
                return [
                    "status" => false,
                    "statusCode" => 400,
                    "message" => $deleteStatus["message"]
                ];
            }
        } catch (Exception $e) {
            return [
                "status" => false,
                "message" => $e->getMessage()
            ];
        } finally {
            // Disconnecting from the database
            $proObj->DBconn->disconnectFromDatabase();
        }
    }


    public static function updateProcurement()
    {
        try {
            $proObj = new Procurement(new DBConnect());
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

            $result = $proObj->get($id);

            if ($result["status"] == "false") {
                unset($result);
                throw new Exception("Procurement not found to update!!");
            }
            $keys = [
                // 'product_name' => ['empty', 'maxlength', 'format'],
                // 'category_id' => ['empty'],
                // 'requested_by_id' => ['empty'],
                // 'approved_by_id' => ['empty'],
                'status' => ['empty'],
                'brand' => [],
                'estimated_price' => [],
                'link' => [],
                'request_urgency' => []
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

            $updateStatus = $proObj->update($id, $jsonData);

            if ($updateStatus["result"] == true) {

                return [
                    "status" => true,
                    "statusCode" => "201",
                    "message" => "Procurement Updated successfully",
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
            $proObj->DBconn->disconnectFromDatabase();
        }
    }
}
