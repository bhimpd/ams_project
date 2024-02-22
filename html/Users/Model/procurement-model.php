<?php

namespace Model;

use Configg\DBConnect;

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
                               VALUES ('$procurementData[requested_by_id]', '$procurementData[status]','$procurementData[request_urgency]',
                                       '$procurementData[approved_by_id]')";

            $resultProcurement = $this->DBconn->conn->query($sqlProcurement);

            if (!$resultProcurement) {
                return [
                    "status" => false,
                    "message" => []
                ];
            }
            $procurement_id = $this->DBconn->conn->insert_id;

            foreach ($data['products'] as $product) {
                $sqlProduct = "INSERT INTO procurements_products (product_name, procurement_id, category_id, brand, estimated_price, link)
                               VALUES ('$product[product_name]', '$procurement_id', '$product[category_id]', '$product[brand]', '$product[estimated_price]', '$product[link]')";
            
                $resultProduct = $this->DBconn->conn->query($sqlProduct);
            
                if (!$resultProduct) {
                    return [
                        "status" => false,
                        "message" => "Failed to insert data into procurements_products table"
                    ];
                }
            }
            return [
                "status" => true,
                "procurement_id" => $procurement_id,
            ];
        }
    }

    public function getAll($search, $sortBy, $order, $filterKey, $filterValue)
    {
        $sql = "SELECT pp.id, 
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
            procurements_products pp
        JOIN 
            procurements pr ON pp.procurement_id = pr.id
        JOIN 
            user u_requested ON pr.requested_by_id = u_requested.id
        JOIN 
            user u_approved ON pr.approved_by_id = u_approved.id
        JOIN 
            category c ON pp.category_id = c.id";

        if (!empty($search)) {
            $sql .= " WHERE pp.product_name LIKE '%$search%'";
        }

        if (!empty($filterKey) && !empty($filterValue)) {
            if (!in_array($filterKey, ['category', 'status', 'approved_date'])) {
                throw new \Exception("Invalid filter key.");
            }

            if ($filterKey === 'approved_date') {
                $filterValue = date('Y-m-d', strtotime($filterValue));
            }

            if (!empty($search)) {
                $sql .= " AND ";
            } else {
                $sql .= " WHERE ";
            }

            switch ($filterKey) {
                case 'category':
                    $sql .= "c.category_name = '$filterValue'";
                    break;
                case 'status':
                    $sql .= "pr.status = '$filterValue'";
                    break;
                case 'approved_date':
                    $sql .= "pr.approved_date = DATE('$filterValue')";
                    break;
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
            $sql = "SELECT pp.id, 
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
            procurements_products pp
        JOIN 
            procurements pr ON pp.procurement_id = pr.id
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
        DELETE FROM procurements_products
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
            throw new \Exception("The data is not json data.");
        } else {

            $data = json_decode($data, true);

            $productData = [
                'procurement_id' => $data['procurement_id'],
                'product_name' => $data['product_name'],
                'category_id' => $data['category_id'],
                'brand' => $data['brand'],
                'estimated_price' => $data['estimated_price'],
                'link' => $data['link'],

            ];


            $sqlProduct = "UPDATE procurements_products 
            SET 
            procurement_id = '{$productData['procurement_id']}', 
                product_name = '{$productData['product_name']}', 
                category_id = '{$productData['category_id']}', 
                brand = '{$productData['brand']}', 
                estimated_price = '{$productData['estimated_price']}', 
                link = '{$productData['link']}',  
                updated_at = NOW()
            WHERE id = $id";

            $resultProduct = $this->DBconn->conn->query($sqlProduct);

            if (!$resultProduct) {
                throw new \Exception("Error updating product data");
            }
            $procurementData = [
                'requested_by_id' => $data['requested_by_id'],
                'status' => $data['status'],
                'request_urgency' => $data['request_urgency'],
                'approved_by_id' => $data['approved_by_id'],
            ];

            $id = $data['procurement_id'];
            // die("procure" . $id);

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

            return array("result" => true);
        }
    }
}
