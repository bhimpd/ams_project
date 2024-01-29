<?php

namespace AccessControl;

use Routes\Route;


class Admin
{
  public static function run()
  {
    $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);  

    switch ($_SERVER["REQUEST_METHOD"]) {
      case "GET":
     
        // Route::get($uri , "Routes\\Get::get");
        Route::user($uri , "Routes\\Get::get");

        break;

      case "POST":

      //  Route::post($uri , "Routes\\Create::create");
       Route::user($uri , "Routes\\Create::create");

        break;

      case "PUT":
       
        Route::user($uri ,'Routes\\Update::update');
        // Route::put($uri ,'Routes\\Update::update');
        break;

      case "DELETE":
      
        // Route::delete($uri ,'Routes\\Delete::delete');
        Route::user($uri ,'Routes\\Delete::delete');
        break;
      

      default:
      echo "Route/request not found";
        break;
    }
  }
}

