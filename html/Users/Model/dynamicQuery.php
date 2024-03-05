<?php
namespace Model;

// include_once "../Configuration/database-connection.php";

use Configg\DBConnect;
use Exception;

class DynamicQuery
{
  public $DBconn;
  /* 
    Dependency injection to use Database connection class properties
    */
  public function __construct(DbConnect $DBconn)
  {
    $this->DBconn = $DBconn;
  }

  public function get($tableName, $columnName, $valueToFind)
  {
    try {
      // Prepare the SQL statement with placeholders
      $sql = "SELECT * FROM $tableName WHERE $columnName = ?";

      // Prepare 
      $stmt = $this->DBconn->conn->prepare($sql);

      // Bind 
      $stmt->bind_param("s", $valueToFind);

      // Execute 
      $stmt->execute();

      // Get the result set
      $result = $stmt->get_result();

      // Fetch 
      $data = $result->fetch_all(MYSQLI_ASSOC);



      return [
        "status" => true,
        "message" => "Data extracted successfully !",
        "data" => [
          "data" => $data,
          "nor" => $result->num_rows,
          
        ]
      ];
    } catch (Exception $e) {
      return [
        "status" => false,
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }

  public function insert($tableName, $data)
  {
      try {
          // Prepare placeholders for the columns and values
          $columns = implode(', ', array_keys($data));
          $placeholders = implode(', ', array_fill(0, count($data), '?'));
  
          // Prepare the SQL statement
          $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
  
          // Prepare the statement
          $stmt = $this->DBconn->conn->prepare($sql);
  
          // Bind parameters
          $types = ''; // Initialize a string to hold the types of parameters
          $params = []; // Initialize an array to hold the parameters
          foreach ($data as $value) {
              // Determine the type of each parameter and add it to the types string
              if (is_int($value)) {
                  $types .= 'i'; // Integer
              } elseif (is_float($value)) {
                  $types .= 'd'; // Double
              } elseif (is_string($value)) {
                  $types .= 's'; // String
              } else {
                  $types .= 's'; // Default to string
              }
              // Add the parameter to the parameters array
              $params[] = $value;
          }
  
          // Bind parameters dynamically based on their types
          $stmt->bind_param($types, ...$params);
  
          // Execute the statement
          $stmt->execute();
  
          return [
              "status" => true,
              "message" => "Data inserted successfully!",
              "data" => [
                  "inserted_id" => $this->DBconn->conn->insert_id
              ]
          ];
      } catch (Exception $e) {
          return [
              "status" => false,
              "message" => $e->getMessage(),
              "data" => []
          ];
      }
  }
  
}