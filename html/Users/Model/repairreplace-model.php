<?php

namespace Model;

use Configg\DBConnect;

class Repairreplace
{

  private $DBconn;

  public function __construct(DBConnect $DBconn)
  {
    $this->DBconn = $DBconn;
  }

 public function  getByProductCode ($productCode){

  $dynamicQuery = new DynamicQuery($this->DBconn);
  return $dynamicQuery->get("repairandreplace" , "assets_id" , $productCode);

 }

  public function get(...$options)
  {
    try {

      $defaultOptions = [
        "orderby" => "Product_Code",
        "sortorder" => "ASC",
        "Limit" => 7,
        "repairreplace_type" => "Repair"
      ];
      $parameters = array_merge($defaultOptions, ...$options);

      $sql = "
      SELECT repairandreplace.id as 'id' ,
      repairandreplace.assets_id as 'Product_Code', 
      category.parent as 'Category', 
      repairandreplace.status as 'Status',
     repairandreplace.assigned_to as 'Assigned_to',
      repairandreplace.assigned_date as 'Assigned_Date',
      repairandreplace.repairreplace_type as 'Type'

      FROM repairandreplace
      LEFT JOIN assets ON repairandreplace.assets_id = assets.id
      LEFT JOIN category ON repairandreplace.category_id = category.id
      LEFT JOIN user ON repairandreplace.assigned_to = user.id
    ";

      //where part for type of repairand replace
      $sql .= "WHERE repairandreplace.repairreplace_type = '$parameters[repairreplace_type]' 
    ";
      //conditions will add more conditions on where clause
      $conditions = [];

      //  the conditions to check and their columns in database
      $conditionsToCheck = [
        "filterbyCategory" => "category.parent",
        "filterbyStatus" => "repairandreplace.status",
        "filterbyAssignedDate" => "Date(repairandreplace.assigned_date)"
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
      $searchColumns = ['repairandreplace.id', 'repairandreplace.assets_id', 'assets.name', 'category.parent', 'repairandreplace.status', 'user.name'];
    

      // Constructing the WHERE clause dynamically based on the search keyword and columns
      $whereClause = "";
      foreach ($searchColumns as $column) {
        $whereClause .= "$column LIKE '%$searchKeyword%' OR "; // Construct LIKE condition for each column
      }

      // Remove the trailing " OR " from the last condition
      $whereClause = rtrim($whereClause, " OR ");
      // Construct the SQL query

      if (!empty($whereClause)) {
        $sql .= " 
        AND ($whereClause)
        ";
      }


      //orderby and sort order part
      $sql .= " ORDER BY `$parameters[orderby]` $parameters[sortorder] ";

      // query to database
      $result = $this->DBconn->conn->query($sql);


      if ($result->num_rows > 0) {
        $data = array();
        while ($row = $result->fetch_assoc()) {
          $data[] = $row;
        }
      }
      
     //getting name of category for each data 
      foreach($data as $key => $value){
        if(isset($value["Category"])){
          $categoryObj = new Category($this->DBconn);
          $categoryRow =$categoryObj->getById($value["Category"]);

         //getting category row and setting the id and value as name
          $data[$key]["Category"] = [
            "id" => $value["Category"],
            "name" => $categoryRow["data"]["category_name"]
          ];
        }
        if(isset($value["Assigned_to"])){
          $userObj = new User($this->DBconn);
          $userRow =$userObj->get($value["Assigned_to"] , NULL);
         

         //getting category row and setting the id and value as name
          $data[$key]["Assigned_to"] = [
            "id" => $value["Assigned_to"],
            "name" => $userRow["name"]
          ];
        }
        if(isset($value["Product_Code"])){
          $assetObj = new Assets($this->DBconn);
          $assetRow =$assetObj->getDataById($value["Product_Code"]);
          
         
       
         //getting category row and setting the id and value as name
          $data[$key]["Product_Code"] = [
            "id" => $value["Product_Code"],
            "name" => $assetRow["data"]["name"]
          ];
        }
      
        
      }
 
      return [
        "status" => "true",
        "message" => "Data extracted successfully",
        "data" => $data ?? []
      ];

    } catch (\Exception $e) {
      return [
        "status" => "false",
        "message" => $e->getMessage(),
        "data" => []
      ];
    } catch (\mysqli_sql_exception $e) {
      return [
        "status" => "false",
        "message" => "Database error: " . $e->getMessage(),
        "data" => []
      ];
    }
  }
}