<?php

namespace Model;

use Configg\DbConnect;
use Exception;
use PaginationHelper;

include __DIR__ . '/../Helpers/PaginationHelper.php';

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
        if (!is_array($data)) {
            throw new Exception("Invalid data format. Data must be an array.");
        }

        $name = ucfirst($data['name']);
        $assets_type = ucfirst($data['assets_type']);
        $category = $data['category'];
        $sub_category = $data['sub_category'];
        $brand = $data['brand'];
        $location = $data['location'];
        $assigned_to = $data['assigned_to'];
        $status = $data['status'];
        $image_name = $data['image_name'];

        // Prepare the SQL query
        $sql = "INSERT INTO " . self::TABLE . " (name, assets_type, category, sub_category, brand, location, assigned_to, status, image_name) 
            VALUES ('$name', '$assets_type', '$category', '$sub_category', '$brand', '$location', '$assigned_to', '$status', '$image_name')";

        // Execute the SQL query
        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new Exception("Could not insert into database. Error: " . $this->DBconn->conn->error);
        }

        return true;
    }

    public function getAll($assets_type, $search, $sortBy, $order, $filters, $currentPage = 1, $perPage = 7)
    {
        $totalData = $this->getTotalDataCount($assets_type, $search, $filters);
        $pagination = PaginationHelper::paginate($currentPage, $perPage, $totalData);
        $offSet = $pagination['offSet'];

        $sql = "SELECT 
        a.id AS id,
            a.name,
            a.assets_type,
            c.category_name AS category,
            a.brand,
            l.location AS location,
            u.name AS assigned_to_name,
            a.status,
            a.image_name
        FROM " . self::TABLE . " AS a
        LEFT JOIN category AS c ON a.category = c.id
        LEFT JOIN user AS u ON a.assigned_to = u.id
        LEFT JOIN location AS l ON a.location = l.id
        WHERE a.assets_type = '$assets_type'";

        if (!empty($search)) {
            $columns = ['a.id', 'a.name', 'c.category_name', 'u.name', 'a.status', 'assigned_date'];
            $searchConditions = [];

            foreach ($columns as $column) {
                $searchConditions[] = "$column LIKE '%$search%'";
            }

            $sql .= " AND (" . implode(" OR ", $searchConditions) . ")";
        }

        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'category':
                    $sql .= " AND c.category_name = '$value'";
                    break;
                case 'status':
                    $sql .= " AND a.status = '$value'";
                    break;
                case 'assigned_to':
                    $sql .= " AND u.name = '$value'";
                    break;
                case 'assigned_date':
                    // Check if the value contains a date range
                    if (strpos($value, 'to') !== false) {
                        // Date range provided, split the range
                        $dates = explode(' to ', $value);
                        $start_date = trim($dates[0]);
                        $end_date = trim($dates[1]);
                        // Add the date range condition to the SQL query
                        $sql .= " AND DATE(a.assigned_date) BETWEEN '$start_date' AND '$end_date'";
                    } else {
                        // Single date provided, filter by that date
                        $sql .= " AND DATE(a.assigned_date) = '$value'";
                    }
                    break;

                default:
                    // Handle invalid filter key
                    throw new Exception("Invalid filter key11.");
            }
        }

        // Add sorting condition
        $sql .= " ORDER BY a.$sortBy $order";
        $sql .= " LIMIT $perPage OFFSET $offSet";

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

    public function getDataById($id){
        try{
            $sql = "
         SELECT * from assets WHERE id = '$id'
         ";
         $result = $this->DBconn->conn->query($sql);
         $row = $result->fetch_assoc();
        
        if($result -> num_rows < 1){
         throw new Exception("Data not found.");
        }
        
         return [
           "status" => true ,
           "message" => "Row extracted.",
           "data" => $row
         ];
         }catch(Exception $e){
           return [
             "status" => false ,
             "message" => $e ->getMessage(),
             "data" => []
           ];
         }

    }
    public function get(?int $id)
    {

        if (!isset($id)) {
            throw new Exception("id field cannot be empty");
        }

        if (isset($id)) {
            $sql = "SELECT 
            a.id AS id,
            a.name,
            a.assets_type,
            c.category_name AS category,
            sc.category_name AS subcategory,
            a.brand,
            l.location AS location,
            u.name AS assigned_to_name,
            a.status,
            a.image_name
             FROM " . self::TABLE . " AS a
             LEFT JOIN category AS c ON a.category = c.id
             LEFT JOIN category AS sc ON a.sub_category = sc.id
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

        $name = ucfirst($data['name']);
        $assets_type = ucfirst($data['assets_type']);
        $category = $data['category'];
        $sub_category = $data['sub_category'];
        $brand = $data['brand'];
        $location = $data['location'];
        $assigned_to = $data['assigned_to'];
        $status = $data['status'];
        $image_name = $data['image_name'];

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
                image_name = '$image_name',
                updated_at = NOW()
            WHERE id = $id";

        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new \Exception("Error updating asset data: " . $this->DBconn->conn->error);
        }

        return ["result" => true];
    }

    private function getTotalDataCount($assets_type, $search, $filters)
    {
        $sql = "SELECT COUNT(*) AS total_count FROM " . self::TABLE . " AS a
            LEFT JOIN category AS c ON a.category = c.id
            LEFT JOIN user AS u ON a.assigned_to = u.id
            LEFT JOIN location AS l ON a.location = l.id
            WHERE a.assets_type = '$assets_type'";

        if (!empty($search)) {
            $sql .= " AND a.name LIKE '%$search%'";
        }

        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'category':
                    $sql .= " AND c.category_name = '$value'";
                    break;
                case 'status':
                    $sql .= " AND a.status = '$value'";
                    break;
                case 'assigned_date':
                    $sql .= " AND DATE(a.assigned_date) = '$value'";
                    break;
                case 'assigned_to':
                    $sql .= " AND u.name = '$value'";
                    break;
                default:
                    // Handle invalid filter key
                    throw new Exception("Invalid filter key.");
            }
        }

        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new Exception("Error executing the query: " . $this->DBconn->conn->error);
        }

        $row = $result->fetch_assoc();
        $total_count = $row['total_count'];

        return $total_count;
    }
}
