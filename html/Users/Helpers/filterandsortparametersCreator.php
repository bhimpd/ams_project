<?php 
namespace Helpers;

trait FilterandSort {

  //picks the parameters that matches the ones present in $parametersToCheck and the ones provided by user in request parameters
  public static function callingParameters( $T = NULL){
   
    //empty array to store filter-sort... parameters
    $callingParameters = [];

    // Define the list of parameters to check
    $parametersToCheck =  $T == NULL ?  ["orderby", "sortorder", "filterbyCategory", "filterbyStatus", "filterbyDesignation" , "filterbyDepartment" , "filterbyAssignedDate", "searchKeyword", "type"] : $T;

    // dynamically set if data is coming from frontend and is not empty
    foreach ($parametersToCheck as $param) {
      // Check if the parameter is set in $_GET
      if (isset($_GET[$param]) && !empty($_GET[$param])) {

        // Sanitize the parameter value before storing
        $sanitizedValue = self::sanitizeInput($_GET[$param]);

        // Pushing the parameter into $callingParameters
        $callingParameters[$param] = $sanitizedValue;

      }
    }

    return $callingParameters;

  }

   // Function to sanitize input data
   private static function sanitizeInput($input) {
    // Example sanitization - you should customize this based on your specific needs
    $sanitizedValue = trim($input); // Remove leading/trailing whitespace
    $sanitizedValue = htmlspecialchars($sanitizedValue); // Convert special characters to HTML entities to prevent XSS attacks
    // Add more sanitization rules as needed
    return $sanitizedValue;
  }
}