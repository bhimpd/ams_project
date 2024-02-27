<?php

namespace Model;

use Configg\DBConnect;

class Category
{
  public $DBconn;

  public function __construct(DBConnect $DBconn)
  {
    $this->DBconn = $DBconn;
  }

  public function getAll()
  {
    try {
      $sql = "
        SELECT * FROM category
      ";
      $result = $this->DBconn->conn->query($sql);

      if (!$result->num_rows > 0) {
        throw new \Exception("Cannot get form database!!");
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

  public function getChild( $parentId ) {
    $sql = "SELECT * FROM category WHERE parent='$parentId'";
    $result = $this->DBconn->conn->query($sql);
    $data = array();
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }
    return [
      "status" => "true",
      "message" => "Data extracted successfully!!",
      "data" => $data
    ];
  }

  public function get(string $category_name = NULL , string $parent = NULL)
  {
    try {
      
      $sql = "SELECT * FROM category WHERE parent IS NULL";  
      
      ///to get data based on category_name only
      // if (isset($parent)) {
    
      //   $sql .= " WHERE parent = '$parent'";
      // } else if (isset($category_name)) {
      //   $sql .= " WHERE category_name = '$category_name'";
      // } else if (isset($id)) {
      //   // $sql .= " WHERE id = '$id'";
      // }else {
      //   $sql .= "WHERE parent IS NULL";
      // }
    
      $result = $this->DBconn->conn->query($sql);
      $data = array();
        while ($row = $result->fetch_assoc()) {
          $data[] = $row;
        }
        

        return [
          "status" => "true",
          "message" => "Data extracted successfully!!",
          "data" => $data
        ];
   
    
      $result = $this->DBconn->conn->query($sql);

      if (!$result->num_rows > 0) {
        throw new \Exception("Unable to fetch the parameter provided !!");
      } else {
        $data = array();
        while ($row = $result->fetch_assoc()) {
          $data[] = $row;
        }
        return [
          "status" => "true",
          "message" => "Data extracted successfully!!",
          "data" => $data
        ];
      }
    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }
  public function update($data)
  {
    try {
      $sql = "UPDATE category ";

      if (isset($data["previousParent"])) {
        $sql .= "
         SET parent = '$data[newParent]'
      WHERE parent = '$data[previousParent]'
        ";
      } else if (isset($data["previouscategory_name"])) {
        $sql .= "
        SET category_name = '$data[newcategory_name]'
        WHERE category_name = '$data[previouscategory_name]'
        ";
      }
      $result = $this->DBconn->conn->query($sql);

      if (!$result) {
        throw new \Exception("Unable to update in database!!");
      }
      return [
        "status" => "true",
        "message" => "Value updated successfully",
      ];

    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }

  public function updateParent(string $previousParent, string $newParent)
  {
    try {
      $sql = "UPDATE category 
      SET parent = '$newParent'
      WHERE parent = '$previousParent'
      ";
      $result = $this->DBconn->conn->query($sql);

      if (!$result) {
        throw new \Exception("Unable to update in database!!");
      }
      return [
        "status" => "true",
        "message" => "Parent updated successfully",
      ];

    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }
  public function updateCategory(string $previousChild, string $newChild)
  {
    try {
      $sql = "UPDATE category
         SET category_name = '$newChild'
        WHERE category_name = '$previousChild'
      ";
      $result = $this->DBconn->conn->query($sql);
      if (!$result) {
        throw new \Exception("Unable to update category in database!!");
      }
      return [
        "status" => "true",
        "message" => "Category updated successfully."
      ];
    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
      ];
    }
  }

  public function deleteParent(string $parentCategory)
  {
    try {
      $sql = "
      DELETE FROM category
      WHERE parent = '$parentCategory'
      ";
      $result = $this->DBconn->conn->query($sql);
      if (!$result) {
        throw new \Exception("Unable to delete parent in database!!");
      }
      return [
        "status" => "true",
        "message" => "Parent Category deleted successfully.",
      ];

    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
      ];

    }
  }
  public function deleteChild(string $childCategory)
  {
    try {
      $sql = "
      DELETE FROM category
      WHERE category_name = '$childCategory'
      ";
      $result = $this->DBconn->conn->query($sql);
      if (!$result) {
        throw new \Exception("Unable to delete parent in database!!");
      }
      return [
        "status" => "true",
        "message" => "Child Category deleted successfully.",
      ];

    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
      ];
    }
  }

  public function create($data)
  {
    try {
      $data = json_decode($data, true);
      $sql = "
    INSERT INTO category
    (category_name , parent)
    VALUES 
    ('$data[category_name]' , '$data[parent]')
    ";
      $result = $this->DBconn->conn->query($sql);

      if (!$result) {
        throw new \Exception("Could not insert into database!!");
      }
      $sqlToGetId = "
      SELECT * FROM category 
      WHERE category_name = '{$data['category_name']}' AND parent = '{$data['parent']}'
  ";

      // to get the id of created row
      $result = $this->DBconn->conn->query($sqlToGetId);
      $row = $result->fetch_assoc();


      return [
        "status" => "true",
        "message" => "Category created successfully.",
        "data" => $row
          
        
      ];
    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }
}
