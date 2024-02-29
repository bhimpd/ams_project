<?php

namespace Model;

use Configg\DBConnect;
use Exception;

class Department
{
  public $DBConn;

  public function __construct(DBConnect $DBoconn)
  {
    $this->DBConn = $DBoconn;
  }

  public function create($data)
  {
    try {
      $data = json_decode($data, true);
      $sql = "
        INSERT INTO department
        (department)
        VALUES 
        ('$data[department]')
      ";
      $result = $this->DBConn->conn->query($sql);

      if (!$result) {
        throw new Exception("Could not insert into database!!");
      }

      return [
        "status" => "true",
        "message" => "Department created successfully!"
      ];

    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }

  public function get(string $department)
  {
    try {
      $sql = "SELECT * from department
      WHERE department = '$department'
      ";
      $result = $this->DBConn->conn->query($sql);
      if (!$result->num_rows > 0) {
        throw new Exception("Unable to fetch the given id data");
      } else {
        return [
          "status" => "true",
          "message" => "Data extracted successfully!!",
          "data" => $result->fetch_assoc()
        ];
      }
    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }

  public function getAll()
  {
    try {
      $sql = "
        SELECT * FROM department
      ";
      $result = $this->DBConn->conn->query($sql);
      if (!$result->num_rows > 0) {
        throw new Exception("Cannot get department from database!!");
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

    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data" => []
      ];
    }
  }

  public function updateDepartment($dataToUpdate): array
  {

    try {
      $sql = "
        UPDATE department 
        SET department = '$dataToUpdate[newDepartment]'
        WHERE department = '$dataToUpdate[previousDepartment]'
      ";
      $result = $this->DBConn->conn->query($sql);

      if (!$result) {
        throw new Exception("Unable to update department in database!!");
      }
      return [
        "status" => "true",
        "message" => "Department updated successfully",
      ];
    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage()
      ];
    }
  }
  public function deleteDepartment($dataToDelete): array
  {
    try {
      $sql = "
        DELETE from department 
        WHERE department = '$dataToDelete[department]'
      ";
      $result = $this->DBConn->conn->query($sql);
      if (!$result) {
        throw new Exception("Unable to delete department from database!!");
      }
      return [
        "status" => "true",
        "message" => "Department deleted successfully.",
        "data" => $dataToDelete
      ];
    } catch (Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data" => $dataToDelete
      ];
    }
  }
}
