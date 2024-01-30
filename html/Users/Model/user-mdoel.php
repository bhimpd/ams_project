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
    return (json_last_error() == JSON_ERROR_NONE);
  }



  /**
   * @param NULL
   * @return array|FALSE
   * gets all data from user tablee
   */


  public function getAll()
  {
    try {
      $sql = "SELECT * FROM user";
      $result = $this->DBconn->conn->query($sql);

      $data = $result->fetch_all(MYSQLI_ASSOC);

      //removing password fom response
      foreach ($data as &$row) {
        unset($row['password']);
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

        $sql = "SELECT * FROM user where id = $id";
        $result = $this->DBconn->conn->query($sql);

        if (!$result->num_rows > 0) {
          throw new \Exception("Unable to fetch the given id data");
        } else {
          $row = $result->fetch_assoc();
          return $row;
        }
      }

      //checks for username
      if (isset($username)) {

        $sql = "SELECT * FROM user where username = '$username'";
        $result = $this->DBconn->conn->query($sql);

        if ($result->num_rows == 0) {
          throw new \Exception("Unable to fetch the given username data");
        } else {
          $row = $result->fetch_assoc();

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

  public function update(int $id, string $data): array
  {
    try {

      if (!User::isJson($data)) {
        throw new \Exception("The data is not json data.");
      } else {

        $data = json_decode($data, true);
        $data["password"] = password_hash($data["password"], PASSWORD_BCRYPT);
        $sql = "UPDATE User 
      SET email = '$data[email]' ,
          password = '$data[password]' ,
          username = '$data[username]' ,
          name = '$data[name]',
          user_type = '$data[user_type]'
      WHERE id = '$id'
       ";
        $result = $this->DBconn->conn->query($sql);
        return array("result" => $result);
      }

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
        //hashing the inserted password
        $data["password"] = password_hash($data["password"], PASSWORD_BCRYPT);
        $sql = "
        INSERT INTO user 
      (email , password , username,  name  , user_type)
      VALUES
      ('$data[email]' , '$data[password]' ,'$data[username]' ,'$data[name]' ,'$data[user_type]')
      ";

        $result = $this->DBconn->conn->query($sql);
        return $result;
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
      DELETE FROM user 
      WHERE id = '$id'
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