<?php

namespace Model;

use Configg\DBConnect;
use EmailProcurement\ProcurementEmailSender;
include __DIR__ . ".../../Email/EmailSender.php";

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
                'approved_by_id' => $data['approved_by_id'],
            ];

            $sqlProcurement = "INSERT INTO procurements (requested_by_id, status, request_urgency, approved_by_id)
                               VALUES ('$procurementData[requested_by_id]', '$procurementData[status]', '$procurementData[request_urgency]', '$procurementData[approved_by_id]')";
            $resultProcurement = $this->DBconn->conn->query($sqlProcurement);

            if (!$resultProcurement) {
                return [
                    "status" => false,
                    "message" => []
                ];
            }
            $procurement_id = $this->DBconn->conn->insert_id;

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

            $recipientEmail = "dreamypd73@gmail.com"; 
            $emailSent = ProcurementEmailSender::sendProcurementEmail($recipientEmail, $procurement_id);

            return [
                "status" => true,
                "procurement_id" => $procurement_id,
            ];
        }
    }

    public function getAll($search, $sortBy, $order, $filters)
    {
        $sql = "SELECT pr.id, 
        pp.id AS procurement_id,
        pp.product_name, 
        c.category_name,
        pp.brand,
        pp.estimated_price,
        pp.link,
        pr.status,
        pr.request_urgency, 
        u_requested.name AS requested_by,
        u_approved.name AS approved_by
        FROM 
            procurements pr
        JOIN 
            procurements_products pp ON pr.id = pp.procurement_id 
        JOIN 
            user u_requested ON pr.requested_by_id = u_requested.id
        JOIN 
            user u_approved ON pr.approved_by_id = u_approved.id
        JOIN 
            category c ON pp.category_id = c.id";

        if (!empty($search)) {
            $sql .= " AND pp.product_name LIKE '%$search%'";
        }

        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'category':
                    $sql .= " AND c.category_name = '$value'";
                    break;
                case 'status':
                    $sql .= " AND pr.status = '$value'";
                    break;
                case 'approved_date':
                    $sql .= " AND DATE(pr.approved_date) = '$value'";
                    break;
                default:
                    // Handle invalid filter key
                    throw new \Exception("Invalid filter key.");
            }
        }



        $sql .= " ORDER BY pp.$sortBy $order";
        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new \Exception("Error executing the query: " . $this->DBconn->conn->error);
        }

        $data = $result->fetch_all(MYSQLI_ASSOC);
        $total_rows = $result->num_rows;

        foreach ($data as &$row) {
            unset($row['password']);
        }
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
            pr.id,
            pp.id AS procurements_id,
            pp.product_name, 
            c.category_name,
            pp.brand,
            pp.estimated_price,
            pp.link,
            pr.status,
            pr.request_urgency, 
            u_requested.name AS requested_by,
            u_approved.name AS approved_by
        FROM 
            procurements pr
        JOIN 
            procurements_products pp ON pr.id = pp.procurement_id 
        JOIN 
            user u_requested ON pr.requested_by_id = u_requested.id
        JOIN 
            user u_approved ON pr.approved_by_id = u_approved.id
        JOIN 
            category c ON pp.category_id = c.id 
            
            WHERE pr.id='$id'";

            $result = $this->DBconn->conn->query($sql);

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
                    "message" => "given $id data",
                    "data" => $rows
                ];
            }
        }
        return [
            "status" => "false",
            "message" => "Unable to get data"
        ];
    }

    public function delete(int $id)
    {
        $sql = "
        DELETE FROM procurements
        WHERE id = '$id'
        ";
        $result = $this->DBconn->conn->query($sql);
        if (!$result) {
            throw new \Exception("Unable to delete procurement from database!!");
        }
        return [
            "status" => true,
            "message" => "procurement deleted successfully.",
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
                'approved_by_id' => $data['approved_by_id'],
            ];

            $sqlProcurement = "UPDATE procurements 
                            SET 
                            requested_by_id = '{$procurementData['requested_by_id']}',
                            status = '{$procurementData['status']}',
                            request_urgency = '{$procurementData['request_urgency']}',
                            approved_by_id = '{$procurementData['approved_by_id']}',
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
}
