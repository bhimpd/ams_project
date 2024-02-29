<?php 

namespace Model;
use Configg\DBConnect;
use Exception;

class Location {
  public $DBConn;

  public function __construct(DBConnect $DBconn){
    $this-> DBConn = $DBconn;
  }

  /**
   * gets all from location table
   */
  public function getAll(...$options)
  {
    try{
      $defaultOptions = [
        "orderby" => "id",
        "sortorder" => "ASC",
      ];
      $parameters = array_merge($defaultOptions, ...$options);
      $sql = "
        SELECT * FROM location
      ";

       //orderby and sort order part
       $sql .= " ORDER BY `$parameters[orderby]` $parameters[sortorder] ";
      $result = $this->DBConn->conn->query($sql);
      if (!$result->num_rows > 0) {
        throw new Exception("Cannot get form database!!");
      }
      $data = array();
      while ($row = $result->fetch_assoc()) {
        $data[] = $row;
      }
      return [
        "status" => "true",
        "message" => "Data extracted successfully",
        "data" => $data
      ];

    }catch(Exception $e){
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data"=> []
      ];
    }
  }
public function getById($id){
  try{
    $sql = "SELECT * from location 
    WHERE id = '$id'
    ";
    $result = $this->DBConn->conn->query($sql);
    if (!$result->num_rows > 0) {
      throw new Exception("Unable to fetch on the given  data");
    } else {
      return [
        "status" => "true",
        "message" => "Data extracted successfully!!",
        "data" => $result->fetch_assoc()
      ];
    }
  }catch(Exception $e){
    return [
      "status" => "false",
      "message" => $e->getMessage(),
      "data"=> []
    ];
  }

}
  public function get(string $location){
    try{
      $sql = "SELECT * from location 
      WHERE location = '$location'
      ";
      $result = $this->DBConn->conn->query($sql);
      if (!$result->num_rows > 0) {
        throw new Exception("Unable to fetch on the given  data");
      } else {
        return [
          "status" => "true",
          "message" => "Data extracted successfully!!",
          "data" => $result->fetch_assoc()
        ];
      }
    }catch(Exception $e){
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data"=> []
      ];
    }
  }

  public function create($data){
    try{
      $data = json_decode($data , true);
      $sql = "
        INSERT INTO location
        (location)
        VALUES 
        ('$data[location]')
      ";
      $result = $this->DBConn->conn->query($sql);
      
      if(!$result){
        throw new Exception("Could not insert into database!!");
      }

      return[
        "status" => "true",
        "message" => "Location created successfully!",
        "data" =>[
          "id"=> $this->DBConn->conn->insert_id
        ]
      ];

    }catch(Exception $e){
      return[
        "status" => "false",
        "message" => $e->getMessage(),
        "data" =>[]
      ];
    }
    
  }

  public  function updateLocation($dataToUpdate):array{

    try{
      $sql = "
        UPDATE location 
        SET location = '$dataToUpdate[newLocation]'
        WHERE id = '$dataToUpdate[id]'
      ";
      $result = $this->DBConn->conn->query($sql);

      if (!$result) {
        throw new Exception("Unable to update in database!!");
      }
      return [
        "status" => "true",
        "message" => "Location updated successfully",
      ];
    }catch(Exception $e){
      return[
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }

  public function deleteLocationById($id):array{
    try{
      $sql = "
        DELETE from location 
        WHERE id = '$id'
      ";
      $result = $this->DBConn->conn->query($sql);
      if(!$result){
        throw new Exception("Unable to delete parent from database!!");
      }
      return [
        "status" => true,
        "message" => "Location deleted successfully.",
        "data" => [
          "id" => $id
        ]
      ];
    }catch(Exception $e){
      return [
        "status" => false,
        "message" => $e->getMessage(),
        "data" => [
          "id" => $id
        ]
      ];
    }
  }
}