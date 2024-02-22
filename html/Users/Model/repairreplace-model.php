<?php

namespace Model;

use Configg\DBConnect;

class Repairreplace
{

  public $DBconn;

  public function __construct(DBConnect $DBconn)
  {
    $this->DBconn = $DBconn;
  }

  public function create($data)
  {
    $data = json_decode($data, true);
    print_r($data);

    die("create re[air");
    $sql = "
    INSERT INTO repairandreplace
    (assets_id , category_id , status , assigned_to)
    VALUES
    ()
    
    ";

  }

  public function get(int $id = null)
  {
    try {
      $sql = "
      SELECT repairandreplace.id as 'Product-Code', 
      assets.name as 'Name' , 
      category.parent as 'Category', 
      repairandreplace.status as 'Status',
      user.name as 'Assigned-to',
      repairandreplace.assigned_date as 'Assigned Date'

      FROM repairandreplace
      JOIN assets ON repairandreplace.assets_id = assets.id
      JOIN category ON repairandreplace.category_id = category.id
      JOIN user ON repairandreplace.assigned_to = user.id
    ";

    
      $result = $this->DBconn->conn->query($sql);
    
      if (!$result->num_rows > 0) {
        throw new \Exception("Cannot find data in database!!");
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

    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }
}