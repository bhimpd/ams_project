<?php

namespace Model;

use Configg\DbConnect;
use Exception;

class Assets
{
    const TABLE = "assets";
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
        if (!Assets::isJson($data)) {
            throw new Exception("Not json data");
        } else {
            $data = json_decode($data, true);

            $name = $data['name'];
            $assets_type = $data['assets_type'];
            $category = $data['category'];
            $sub_category = $data['sub_category'];
            $brand = $data['brand'];
            $location = $data['location'];
            $assigned_to = $data['assigned_to'];
            $status = $data['status'];
            $assets_image = $data['assets_image'];


            $sql = "INSERT INTO " . self::TABLE . " (name, assets_type, category, sub_category, brand, location, assigned_to, status, assets_image) 
            VALUES ('$name','$assets_type','$category','$sub_category','$brand','$location','$assigned_to','$status','$assets_image')";

            $result = $this->DBconn->conn->query($sql);

            if (!$result) {
                throw new Exception("Could not insert into database!!");
            }
            return [
                "status" => "true",
            ];
        }
    }

    public function getAll($assets_type, $search, $sortBy = 'id', $order = 'ASC', $filterBy = '', $filterValue = '')
    {
        $sql = "SELECT 
            a.id,
            a.name,
            a.assets_type,
            c.category_name AS category,
            a.brand,
            l.location AS location,
            u.name AS assigned_to_name,
            a.status,
            a.assets_image
        FROM " . self::TABLE . " AS a
        LEFT JOIN category AS c ON a.category = c.id
        LEFT JOIN user AS u ON a.assigned_to = u.id
        LEFT JOIN location AS l ON a.location = l.id
        WHERE a.assets_type = '$assets_type'";

        if (!empty($search)) {
            $sql .= " AND a.name LIKE '%$search%'";
        }

        if (!empty($filterBy) && !empty($filterValue)) {
            switch ($filterBy) {
                case 'category':
                    $sql .= " AND c.category_name = '$filterValue'";
                    break;
                case 'status':
                    $sql .= " AND a.status = '$filterValue'";
                    break;
                case 'created_at':
                    $sql .= " AND DATE(a.created_at) = DATE('$filterValue')";
                    break;
                default:
                    // Handle invalid filter key
                    throw new Exception("Invalid filter key.");
            }
        }

        // Add sorting condition
        $sql .= " ORDER BY $sortBy $order";
        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new Exception("Error executing the query: " . $this->DBconn->conn->error);
        }

        $data = $result->fetch_all(MYSQLI_ASSOC);
        $total_rows = $result->num_rows;

        if ($total_rows == 0) {
            throw new Exception("No data found for the provided search term.");
        }

        return [
            "total data" => $total_rows,
            "data" => $data
        ];
    }

    public function get(?int $id)
    {

        if (!isset($id)) {
            throw new Exception("id field cannot be empty");
        }

        if (isset($id)) {
            $sql = "SELECT 
            a.id,
            a.name,
            a.assets_type,
            c.category_name AS category,
            a.brand,
            l.location AS location,
            u.name AS assigned_to_name,
            a.status,
            a.assets_image
             FROM " . self::TABLE . " AS a
             LEFT JOIN category AS c ON a.category = c.id
             LEFT JOIN user AS u ON a.assigned_to = u.id
             LEFT JOIN location AS l ON a.location = l.id"
                . " WHERE a.id='$id'";

            $result = $this->DBconn->conn->query($sql);

            if ($result->num_rows == 0) {
                return [
                    "status" => "false",
                    "message" => "unable to fetch data of given $id"
                ];
            } else {
                $row = $result->fetch_assoc();
                return [
                    "status" => "true",
                    "message" => "given $id data",
                    "data" => $row
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
        DELETE FROM assets
        WHERE id = '$id'
        ";
        $result = $this->DBconn->conn->query($sql);
        if (!$result) {
            throw new \Exception("Unable to delete asset from database!!");
        }
        return [
            "status" => true,
            "message" => "asset deleted successfully.",
        ];
    }

    public function update(int $id, string $jsonData): array
    {
        $data = json_decode($jsonData, true);

        $name = $data['name'];
        $assets_type = $data['assets_type'];
        $category = $data['category'];
        $sub_category = $data['sub_category'];
        $brand = $data['brand'];
        $location = $data['location'];
        $assigned_to = $data['assigned_to'];
        $status = $data['status'];
        $assets_image = $data['assets_image'];

        $sql = "UPDATE " . self::TABLE . " 
            SET 
                name = '$name',
                assets_type = '$assets_type',
                category = '$category',
                sub_category = '$sub_category',
                brand = '$brand',
                location = '$location',
                assigned_to = '$assigned_to',
                status = '$status',
                assets_image = '$assets_image',
                updated_at = NOW()
            WHERE id = $id";

        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new \Exception("Error updating asset data: " . $this->DBconn->conn->error);
        }

        return ["result" => true];
    }
}
