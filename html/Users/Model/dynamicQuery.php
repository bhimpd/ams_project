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

  public function insert()
  {

  }
}