<?php
namespace Index;
session_start();

use Routes\Route;
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$pathOptions = [
                "/department",
                "/location",
                "/category",
                "/logout",
                "/login",
                "/signup",
                "/user" ,
                "/repairreplace",
                "/procurement",
                "/assets"
              ];
              
//dyniamically creating callback names
if (in_array($path, $pathOptions)) {

  $trimmedPath = trim($path, '/');
  $className = ucfirst($trimmedPath);
  Route::route($path, "Routes\\" . $className . '\\' . $className . '::run');
  //expected format  'Routes\Location\\Location::run'
  exit();
}


