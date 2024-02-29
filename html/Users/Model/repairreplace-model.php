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
    $sql = "
    
    ";

  }

  public function get()
  {
    try {
      $sql = "
      SELECT repairandreplace.id as 'Product-Code', 
      products.product_name as 'Name' , 
      category.parent as 'Category', 
      repairandreplace.status as 'Status',
      user.name as 'Assigned-to',
      repairandreplace.assigned_date as 'Assigned Date'

      FROM repairandreplace
      JOIN products ON repairandreplace.products_id = products.id
      JOIN category ON products.category_id = category.id
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