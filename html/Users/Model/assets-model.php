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
            throw new \Exception("Not json data");
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
                "message" => "Assets created successfully!"
            ];
        }
    }

    public function getAll($assets_type,$search)
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
         LEFT JOIN categories AS c ON a.category = c.id
         LEFT JOIN users AS u ON a.assigned_to = u.id
         LEFT JOIN locations AS l ON a.location = l.id
         WHERE a.assets_type = '$assets_type'";

        //  var_dump($sql);die;

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
             LEFT JOIN categories AS c ON a.category = c.id
             LEFT JOIN users AS u ON a.assigned_to = u.id
             LEFT JOIN locations AS l ON a.location = l.id"
             ." WHERE a.id='$id'";

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
}
