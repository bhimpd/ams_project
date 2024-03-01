<?php 
namespace Helpers;

trait FilterandSort {
  public static function callingParameters(){
   
    //empty array to store filter-sort... parameters
    $callingParameters = [];

    // Define the list of parameters to check
    $parametersToCheck = ["orderby", "sortorder", "filterbyCategory", "filterbyStatus", "filterbyDesignation" , "filterbyDepartment" , "filterbyAssignedDate", "searchKeyword", "type"];

    // dynamically set if data is coming from frontend and is not empty
    foreach ($parametersToCheck as $param) {
      // Check if the parameter is set in $_GET
      if (isset($_GET[$param])) {
        // Push the parameter into $callingParameters

        $callingParameters[$param] = $_GET[$param];

      }
    }

    return $callingParameters;

  }
}