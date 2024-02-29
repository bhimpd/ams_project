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
  /**
   * @param  string
   * @return  array
   * gets based on category_name or parent name
   */
  // public function get(?string $category_name, ?string $parent): array
  // {
  //   try {
  //     if (!isset($category_name) && !isset($parent)) {
  //       throw new \Exception("Category name or parent name cannot be empty!!");
  //     }
  //     ///to get data based on category_name only
  //     if (isset($category_name)) {
  //       $sql = "SELECT * FROM category WHERE category_name = '$category_name'";

  //       $result = $this->DBconn->conn->query($sql);

  //       if (!$result->num_rows > 0) {
  //         throw new \Exception("Unable to fetch the given id data");
  //       } else {
  //         return [
  //           "status" => "true",
  //           "message" => "Data extracted successfully!!",
  //           "data" => $result->fetch_assoc()
  //         ];
  //       }
  //     }

  //     //extracts data based on parent name only
  //     if (isset($parent)) {
  //       $sql = "SELECT * FROM category WHERE parent = '$parent'";
  //       $result = $this->DBconn->conn->query($sql);

  //       if (!$result->num_rows > 0) {
  //         throw new \Exception("Unable ot find the parent category!!");
  //       } else {
  //         $data = array();
  //         while ($row = $result->fetch_assoc()) {
  //           $data[] = $row;
  //         }
  //         return [
  //           "status" => "true",
  //           "message" => "Parent data extracted successfully!!",
  //           "data" => $data
  //         ];
  //       }
  //     }
  //     throw new \Exception("unknown error in getting category");
  //   } catch (\Exception $e) {
  //     return [
  //       "status" => "false",
  //       "message" => $e->getMessage(),
  //       "data" => []
  //     ];
  //   }
  // }

  public function get(string $category_name = NULL, string $parent = NULL , $id = NULL): array
  {
    try {
     $sql = "SELECT * FROM category";

      ///to get data based on category_name only
      if(isset($parent)){
          $sql .= " WHERE parent = '$parent'";
      }
      else if (isset($category_name)) {
        $sql .= " WHERE category_name = '$category_name'";
      }
      else if (isset($id)){
        $sql.= " WHERE id = '$id'";
      }
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
  public function update($data){
    try {
      $sql = "UPDATE category ";

      if(isset($data["previousParent"])){
        $sql .= "
         SET parent = '$data[newParent]'
      WHERE parent = '$data[previousParent]'
        ";
      }
      else if (isset($data["previouscategory_name"])){
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
      return [
        "status" => "true",
        "message" => "Category created successfully.",
      ];
    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }
}
