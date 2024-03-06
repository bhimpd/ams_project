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

  //softdelete states if the table uses soft delete concept
  public function get($tableName, $columnName, $valueToFind, ?bool $softDelete = false)
  {
    try {
      // Prepare the SQL statement with placeholders
      $sql = "SELECT * FROM $tableName WHERE $columnName = ?";

      //if softdelete is made true , then fetch those which are not deleted
      if ($softDelete) {
        $sql .= " AND is_deleted = 0";

      }

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
  public function update($tableName , $data , $whereCondition){
   
      try {
       // Prepare the SET part of the SQL statement
       $setClause = '';
       foreach ($data as $column => $value) {
           $setClause .= "$column = ?, ";
       }
       $setClause = rtrim($setClause, ', '); // Remove the trailing comma and space

       // Prepare the WHERE part of the SQL statement
       $whereClause = '';
       foreach ($whereCondition as $column => $value) {
           $whereClause .= "$column = ? AND ";
       }
       $whereClause = rtrim($whereClause, 'AND '); // Remove the trailing AND

       
       // Prepare the SQL statement
       $sql = "UPDATE $tableName SET $setClause WHERE $whereClause";
      
       
  
  
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
          $setParams[] = $value;
        }

          // Bind parameters for WHERE clause
          foreach ($whereCondition as $value) {
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
            $setParams[] = $value;
        }
  
        // Bind parameters dynamically based on their types
        $stmt->bind_param($types, ...$setParams);
  
        // Execute the statement
        $stmt->execute();
  
        return [
          "status" => true,
          "message" => "Data inserted successfully!",
          "data" => []
        ];
      } catch (Exception $e) {
        return [
          "status" => false,
          "message" => $e->getMessage(),
          "data" => []
        ];
      
    }
  }

  public function insert($tableName, $data )
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

  protected function prepareAndExecutor($sql, $data)
  {


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
    return $response = $stmt->execute();

  }

  public function delete($tableName, $id, ?bool $softDelete = false)
  {
    try {
      $sql = "DELETE from $tableName WHERE id = '$id'";

      if ($softDelete) {
        $sql = " UPDATE $tableName
          SET is_deleted = 1
          WHERE id = '$id'
        ";
      }


      // Prepare the statement
      $stmt = $this->DBconn->conn->prepare($sql);


      // Execute the statement
      $response = $stmt->execute();

      if (!$response) {
        throw new Exception("Unable to execute the request in database !");
      }

      return [
        "status" => true,
        "message" => "Data deleted successfully!",
        "data" => [
          "id" => $id
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