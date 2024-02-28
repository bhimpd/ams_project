<?php

namespace Model;

use Configg\DBConnect;
// use EmailProcurement\ProcurementEmailSender;
// include __DIR__ . ".../../Email/EmailSender.php";

class Procurement
{
    public $DBconn;

    public function __construct(DbConnect $DBconn)
    {
        $this->DBconn = $DBconn;
    }

    public static function isJSON(string $jsonData)
    {
        json_decode($jsonData);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function create($data)
    {
        if (!Procurement::isJson($data)) {
            throw new \Exception("Not json data");
        } else {
            $data = json_decode($data, true);

            $procurementData = [
                'requested_by_id' => $data['requested_by_id'],
                'status' => $data['status'],
                'request_urgency' => $data['request_urgency'],
                // 'approved_by_id' => $data['approved_by_id'],
            ];

            $resultInsertProcurement = null;
            $procurement_id = null; // Initialize procurement_id

            // checking whether req_by_id already exist or not
            $sqlCheckReqId = "SELECT id, number_of_items FROM procurements WHERE requested_by_id = '$procurementData[requested_by_id]'";
            $resultCheckProcurement = $this->DBconn->conn->query($sqlCheckReqId);
            if ($resultCheckProcurement) {
                if ($resultCheckProcurement->num_rows > 0) {
                    // User has previous procurement records, update the number_of_items
                    $row = $resultCheckProcurement->fetch_assoc();
                    $procurement_id = $row['id']; // Retrieve existing procurement_id
                    $number_of_items = $row['number_of_items'] + count($data['products']);

                    $sqlUpdateProcurement = "UPDATE procurements SET number_of_items = '$number_of_items' WHERE requested_by_id = '$procurementData[requested_by_id]'";
                    $resultUpdateProcurement = $this->DBconn->conn->query($sqlUpdateProcurement);
                } else {
                    // User does not have previous procurement records, insert a new row
                    $number_of_items = count($data['products']);

                    $sqlInsertProcurement = "INSERT INTO procurements (requested_by_id, number_of_items, status, request_urgency)
                                             VALUES ('$procurementData[requested_by_id]', '$number_of_items', '$procurementData[status]', '$procurementData[request_urgency]')";
                    $resultInsertProcurement = $this->DBconn->conn->query($sqlInsertProcurement);
                    $procurement_id = $this->DBconn->conn->insert_id; // Retrieve the new procurement_id
                }
            }

            if (!$resultCheckProcurement || (!$resultInsertProcurement && !$resultUpdateProcurement)) {
                return [
                    "status" => false,
                    "message" => "Failed to insert or update data in procurements table"
                ];
            }

            foreach ($data['products'] as $product) {
                $product_name = ucfirst($product['product_name']);
                $estimated_price = number_format($product['estimated_price'], 2, '.', '');
                $sqlProduct = "INSERT INTO procurements_products (product_name, procurement_id, category_id, brand, estimated_price, link)
                               VALUES ('$product_name', '$procurement_id', '$product[category_id]', '$product[brand]', '$estimated_price', '$product[link]')";

                $resultProduct = $this->DBconn->conn->query($sqlProduct);

                if (!$resultProduct) {
                    return [
                        "status" => false,
                        "message" => "Failed to insert data into procurements_products table"
                    ];
                }
            }

            // $recipientEmail = "dreamypd73@gmail.com"; 
            // ProcurementEmailSender::sendProcurementEmail($recipientEmail, $data);

            return [
                "status" => true,
                "procurement_id" => $procurement_id,
            ];
        }
    }


    public function getAll($search, $sortBy, $order, $filters)
    {
        $sql = "SELECT pr.id, 
        u_requested.name AS requested_by,
        pr.number_of_items,
        pr.status,
        u_approved.name AS approved_by
       
        FROM 
            procurements pr
       LEFT  JOIN 
            user u_requested ON pr.requested_by_id = u_requested.id
       LEFT JOIN 
            user u_approved ON pr.approved_by_id = u_approved.id
       WHERE 1=1"; // Start the WHERE clause

        if (!empty($search)) {
            $columns = ['u_requested.name', 'pr.status', 'u_approved.name', 'pr.approved_date'];
            $searchConditions = [];

            foreach ($columns as $column) {
                if ($column === 'pr.approved_date') {
                    $searchConditions[] = "DATE($column) = '$search'";
                } else {
                    $searchConditions[] = "LOWER($column) LIKE LOWER('%$search%')";
                }
            }

            $sql .= " AND (" . implode(" OR ", $searchConditions) . ")";
        }

        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'approvedBy':
                    $sql .= " AND u_approved.name = '$value'";
                    break;
                case 'requestedBy':
                    $sql .= " AND u_requested.name = '$value'";
                    break;
                case 'status':
                    $sql .= " AND pr.status = '$value'";
                    break;
                case 'approvedDate':
                    $sql .= " AND DATE(pr.approved_date) = '$value'";
                    break;
                default:
                    // Handle invalid filter key
                    throw new \Exception("Invalid filter key.");
            }
        }

        $sql .= " ORDER BY pr.$sortBy $order";

        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new \Exception("Error executing the query: " . $this->DBconn->conn->error);
        }

        $data = $result->fetch_all(MYSQLI_ASSOC);
        $total_rows = $result->num_rows;

        if ($total_rows == 0) {

            throw new \Exception("No data found for the provided search term.");
        }

        return [
            "total data" => $total_rows,
            "data" => $data
        ];
    }

    public function get(?int $id): array
    {

        if (!isset($id)) {
            throw new \Exception("id field cannot be empty");
        }

        if (isset($id)) {
            $sql = "SELECT 
            pp.id AS products_id,
            pp.product_name, 
            c.category_name,
            pp.brand,
            pp.estimated_price,
            pp.link,
            pr.status            
            
        FROM 
            procurements pr
        LEFT JOIN 
            procurements_products pp ON pr.id = pp.procurement_id 
        LEFT JOIN 
            user u_requested ON pr.requested_by_id = u_requested.id
        LEFT JOIN 
            user u_approved ON pr.approved_by_id = u_approved.id
        LEFT JOIN 
            category c ON pp.category_id = c.id "

                . " WHERE pr.id='$id'";

            $result = $this->DBconn->conn->query($sql);

            $total_data = $result->num_rows;
            if ($result->num_rows == 0) {
                return [
                    "status" => "false",
                    "message" => "unable to fetch data of given $id"
                ];
            } else {
                $rows = [];

                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                return [
                    "status" => "true",
                    "message" => "data fetched successfully for id $id",
                    "total_data" => $total_data,
                    "data" => $rows
                ];
            }
        }
        return [
            "status" => "false",
            "message" => "Unable to get data"
        ];
    }

    // deleting the procurement id and its corresponding products
    public function deleteProcurement(int $id)
    {
        // Check if the ID exists in the procurements table
        $sqlCheckId = "SELECT * FROM procurements WHERE id = '$id'";
        $resultCheckId = $this->DBconn->conn->query($sqlCheckId);

        if ($resultCheckId === false || $resultCheckId->num_rows === 0) {
            return [
                "status" => false,
                "message" => "Procurement ID $id not found "
            ];
        }


        // Delete associated products first
        $sqlDeleteProducts = "DELETE FROM procurements_products WHERE procurement_id = '$id'";
        $resultDeleteProducts = $this->DBconn->conn->query($sqlDeleteProducts);

        if ($resultDeleteProducts === false) {
            throw new \Exception("Error deleting procurements products  " . $this->DBconn->conn->error);
        }

        $sqlDeleteProcurement = "DELETE FROM procurements WHERE id = '$id'";
        $resultDeleteProcurement = $this->DBconn->conn->query($sqlDeleteProcurement);

        if (!$resultDeleteProcurement) {
            throw new \Exception("Unable to delete procurement");
        }

        return [
            "status" => true,
            "message" => "Procurement ID $id  deleted successfully."
        ];
    }

    public function deleteProduct(int $productId)
    {
        $sqlFetchProcurementId = "SELECT procurement_id FROM procurements_products WHERE id = '$productId'";
        $resultFetchProcurementId = $this->DBconn->conn->query($sqlFetchProcurementId);

        if (!$resultFetchProcurementId || $resultFetchProcurementId->num_rows == 0) {
            return [
                "status" => false,
                "message" => "Product ID $productId not found."
            ];
        }

        $row = $resultFetchProcurementId->fetch_assoc();
        $procurementId = $row['procurement_id'];

        $sqlDeleteProduct = "DELETE FROM procurements_products WHERE id = '$productId'";
        $resultDeleteProduct = $this->DBconn->conn->query($sqlDeleteProduct);

        if (!$resultDeleteProduct) {
            throw new \Exception("Unable to delete product ID $productId");
        }

        $sqlUpdateProcurement = "UPDATE procurements SET number_of_items = (SELECT COUNT(*) FROM procurements_products WHERE procurement_id = '$procurementId') WHERE id = '$procurementId'";
        $resultUpdateProcurement = $this->DBconn->conn->query($sqlUpdateProcurement);

        if (!$resultUpdateProcurement) {
            throw new \Exception("Unable to update number_of_items.");
        }

        return [
            "status" => true,
            "message" => "Procurement product ID $productId deleted successfully."
        ];
    }

    public function update(int $id, string $data): array
    {
        if (!Procurement::isJson($data)) {
            throw new \Exception("The data is not JSON formatted.");
        } else {
            $data = json_decode($data, true);

            $procurementData = [
                'requested_by_id' => $data['requested_by_id'],
                'status' => ucfirst($data['status']),
                'request_urgency' => $data['request_urgency'],
                // 'approved_by_id' => $data['approved_by_id'],
            ];

            $sqlProcurement = "UPDATE procurements 
                            SET 
                            requested_by_id = '{$procurementData['requested_by_id']}',
                            status = '{$procurementData['status']}',
                            request_urgency = '{$procurementData['request_urgency']}',
                            updated_at = NOW()
                            WHERE id = $id";

            $resultProcurement = $this->DBconn->conn->query($sqlProcurement);

            if (!$resultProcurement) {
                throw new \Exception("Error updating procurement data");
            }

            foreach ($data['products'] as $product) {
                $product_name = ucfirst($product['product_name']);
                $estimated_price = number_format($product['estimated_price'], 2, '.', '');

                $sqlProduct = "UPDATE procurements_products 
                            SET 
                            product_name = '$product_name', 
                            category_id = '{$product['category_id']}', 
                            brand = '{$product['brand']}', 
                            estimated_price = '$estimated_price', 
                            link = '{$product['link']}',  
                            updated_at = NOW()
                            WHERE id = {$product['product_id']}";

                $resultProduct = $this->DBconn->conn->query($sqlProduct);

                if (!$resultProduct) {
                    throw new \Exception("Error updating product data");
                }
            }

            return ["result" => true];
        }
    }


    public function getProcurementInfo($id)
    {
        $procurementsql = "SELECT pr.*, 
                            u_requested.name AS requested_by, 
                            pr.status AS status,
                            pr.request_urgency
                        FROM procurements pr
                        LEFT JOIN user u_requested ON pr.requested_by_id = u_requested.id
                        WHERE pr.id='$id'";

        $proresult = $this->DBconn->conn->query($procurementsql);

        if ($proresult->num_rows == 0) {
            return [
                "status" => false,
                "message" => "Procurement id $id not found",
                "requested_by" => null,
                "status" => null
            ];
        } else {
            $req = $proresult->fetch_assoc();
            return [
                "status" => true,
                "message" => "Data fetched successfully for id $id",
                "requested_by" => $req['requested_by'],
                "urgency" => $req['request_urgency'],
            ];
        }
    }
}
