<?php

namespace Configg;

use Exception;
use mysqli;

class DBConnect
{

  private $hostname;
  private $username;
  private $password;
  private $database = "ams"; //"ams"

  public $conn;

  //begins connection on object instantiation
  public function __construct()
  {
    $this->hostname = "amsdb";  //"amsdb";
    $this->username = "sanchay";    //"sanchay";
    $this->password = "sanchay";  //"sanchay";
    $this->connectToDatabase();
  }
public function __destruct()
{
  // $this->disconnectFromDatabase();
}
  public function connectToDatabase()
  {
    try {
      $this->conn = new mysqli($this->hostname, $this->username, $this->password, $this->database);

      if ($this->conn->connect_error) {
        throw new Exception($this->conn->connect_error);
      }
    } catch (Exception $e) {
      echo "\n" . $e->getMessage() . "\n";
    }
  }

  public function disconnectFromDatabase()
  {
    try {

      if ($this->conn) {
        $this->conn->close();
      } else {
        throw new Exception("Unable to disconnect from database.");
      }
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }
}
