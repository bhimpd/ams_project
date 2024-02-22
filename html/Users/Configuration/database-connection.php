<?php

namespace Configg;

use Exception;
use mysqli;

class DBConnect
{

  private $hostname;
  private $username;
  private $password;
  private $database = "mainams";

  public $conn;

  //begins connection on object instantiation
  public function __construct()
  {
    $this->hostname = "localhost";   //"amsdb";
    $this->username = "root";    //"sanchay";
    $this->password = "";  //"sanchay";
    $this->connectToDatabase();
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
