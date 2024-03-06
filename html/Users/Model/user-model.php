<?php
namespace Model;

// include_once "../Configuration/database-connection.php";

use Configg\DBConnect;

/**
 * default return from User class methods 
 * @return array(
        "status" => false|true,
        "message" => $e->getMessage() | arrray of required data
      );
 * 
 */

class User
{
  public $DBconn;
  /* 
    Dependency injection to use Database connection class properties
    */
  public function __construct(DbConnect $DBconn)
  {
    $this->DBconn = $DBconn;
  }

  /**
   * static funciton to check if data is JSON format data
   * @param string jsontype 
   * @return bool
   */

  public static function isJSON(string $jsonData)
  {
    json_decode($jsonData);
    return(json_last_error() == JSON_ERROR_NONE);
  }



  /**
   * @param NULL
   * @return array|FALSE
   * gets all data from user tablee
   */


  public function getAll(...$options)
  {
    try {
      $defaultOptions = [
        "orderby" => "id",
        "sortorder" => "ASC",
        "Limit" => 7,
      ];
      $parameters = array_merge($defaultOptions, ...$options);

      $sql = "
      SELECT * FROM user 
      
      WHERE is_deleted = '0' ";

      //conditions will add more filter conditions 
      $conditions = [];
      //  the conditions to check and their columns in database
      $conditionsToCheck = [
        "filterbyDesignation" => "designation",
        "filterbyDepartment" => "department",
      ];

      // Iterate over the conditions to check
      foreach ($conditionsToCheck as $param => $column) {
        // Check if the parameter is set in $parameters and not empty
        if (isset($parameters[$param]) && !empty($parameters[$param])) {
          //  adding the condition to the $conditions array

          $conditions[] = "$column = '" . $parameters[$param] . "'";
        }
      }
      // Construct the SQL query with conditions if any

      if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
      }
      // getting search keyword from $parameters
      $searchKeyword = $parameters["searchKeyword"];

      //colums to search into
      $searchColumns = ['id', 'name', 'email', 'designation', 'department', 'phone_number'];


      // Constructing the WHERE clause dynamically based on the search keyword and columns
      $whereClause = "";
      if (!empty($searchKeyword)) {
        foreach ($searchColumns as $column) {
          $whereClause .= "$column LIKE '%$searchKeyword%' OR "; // Construct LIKE condition for each column
        }
        // Remove the trailing " OR " from the last condition
        $whereClause = rtrim($whereClause, " OR ");
        // Construct the SQL query

        if (!empty($whereClause)) {
          $sql .= " AND 
         ($whereClause)
        ";
        }
      }
      //orderby and sort order part
      $sql .= " ORDER BY user.`$parameters[orderby]` $parameters[sortorder] ";


      $result = $this->DBconn->conn->query($sql);

      $data = $result->fetch_all(MYSQLI_ASSOC);


      //removing password fom response
      foreach ($data as &$row) {
        unset($row['password']);
        unset($row['is_deleted']);
        unset($row['updated_at']);
        $departmentObj = new Department(new DBConnect);
        $departmentData = $departmentObj->getById($row["department"]);
        $row["department"] = [
          "id" => $departmentData["data"]["id"],
          "name" => $departmentData["data"]["department"]
        ];
       
      }
      if (empty($data)) {
        throw new \Exception("Unable to fetch data form DB");
      }

     

      return $data;

    } catch (\Exception $e) {
      error_log($e->getMessage());
      return false;
    }
  }
  /**
   * gets users data using either id or username 
   * when both sent in parameter prioritizes id
   * @param int|null , string|null
   */
  public function get(?int $id, ?string $username): array
  {
    try {
      if (!isset($id) && !isset($username)) {
        throw new \Exception("Username and id field cannot be empty");
      }

      //checks for id
      if (isset($id)) {

        $sql = "SELECT * FROM user where id = $id AND is_deleted=0";
        $result = $this->DBconn->conn->query($sql);

        if (!$result->num_rows > 0) {
          throw new \Exception("Unable to fetch the given id data");
        } else {
          $row = $result->fetch_assoc();

          unset($row['updated_at']);
          unset($row["is_deleted"]);

          $departmentObj = new Department(new DBConnect);
          $departmentData = $departmentObj->getById($row["department"]);
          $row["department"] = [
            "id" => $departmentData["data"]["id"],
            "name" => $departmentData["data"]["department"]
          ];
          return $row;
        }
      }

      //checks for username
      if (isset($username)) {

        $sql = "SELECT * FROM user where username = '$username' AND is_deleted=0";
        $result = $this->DBconn->conn->query($sql);

        if ($result->num_rows == 0) {
          throw new \Exception("Unable to fetch the given username data");
        } else {
          $row = $result->fetch_assoc();

          unset($row['updated_at']);
          unset($row["is_deleted"]);

          $departmentObj = new Department(new DBConnect);
          $departmentData = $departmentObj->getById($row["department"]);
          $row["department"] = [
            "id" => $departmentData["data"]["id"],
            "name" => $departmentData["data"]["department"]
          ];
          return $row;
        }
      }
      return [
        "status" => "false",
        "message" => "Unable to get data"
      ];

    } catch (\Exception $e) {
      $error = $e->getMessage();
      error_log($e->getMessage());
      return array(
        "status" => "false",
        "message" => "$error"
      );
    }
  }
  /**
   * updates the database using id as reference
   * @param int|string
   * @return array
   */

  public function update(int $id,  $data): array
  {
    try {
      
        
        $sql = "UPDATE user 
      SET ";
      $updateClauses = array();
      foreach ($data as $column => $value) {
          $updateClauses[] = "$column = '$value'";
      }
      $sql .= implode(", ", $updateClauses);
      $sql .= " WHERE id = '$id'";
    
        $result = $this->DBconn->conn->query($sql);
        return array("result" => $result);
      

    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }

  /**
   * Creates new user / Inserts into user table
   * @param //jsondata
   * @return bool|array
   */

  public function create($data)
  {
    try {
      if (!User::isJson($data)) {
        throw new \Exception("Not json data");

      } else {

        $data = json_decode($data, true);
      
        //if password field is set mean it came from signup route most probably
        if (isset($data["password"])) {
          //hashing the inserted password
          $data["password"] = password_hash($data["password"], PASSWORD_BCRYPT);
        }

        // Generating column names and values dynamically
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";

        // Construct the SQL query
        $sql = "INSERT INTO user ($columns) VALUES ($values)";


     
        $result = $this->DBconn->conn->query($sql);

        
        if (!$result) {
          throw new \Exception("Unable to insert user into database");
        }
        $lastInsertedId = $this->DBconn->conn->insert_id;

        return [
          "status " => true,
          "message" => "User created successfully",
          "data" => [
            "id" => $lastInsertedId
          ]
        ];
      }
    } catch (\Exception $e) {
      return array(
        "status" => "false",
        "message" => $e->getMessage()
      );
    }
  }
  /**
   * deletes a user using id
   * @param int id
   * @ return bool || array
   * 
   */
  public function delete(int $id)
  {
    try {
      $sql = "
      Update user 
      SET is_deleted = 1
      WHERE id = '$id' AND is_deleted = 0
      ";
      $result = $this->DBconn->conn->query($sql);
      if (!$result) {
        throw new \Exception("Unable to delete user from database!!");
      }
      return [
        "status" => "true",
        "message" => "User deleted successfully.",
      ];

    } catch (\Exception $e) {
      return array(
        "status" => "false",
        "message" => $e->getMessage()
      );
    }
  }
}