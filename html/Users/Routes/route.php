<?php

namespace Routes;

class Route
{
  public static function route($endpoint , $callbackFunction){

    $callbackFunction();
    
  }
  // public static function post($endpoint, $callbackFunction)
  // {
  //   // $endpoint = explode("/", trim($endpoint, "/"));
  //   // if ($endpoint[0] == "create") {
  //   //   $callbackFunction();
  //   // }
  //   $callbackFunction();
  // }

  // public static function get($endpoint, $callbackFunction)
  // {
  //   // $endpoint = explode("/", trim($endpoint, "/"));
  //   // if (substr($endpoint[0], 0, 3) === "get") {
  //   //   $callbackFunction();
  //   // }

  //   $callbackFunction();
  // }
  // public static function put($endpoint, $callbackFunction)
  // {
  //   $endpoint = explode("/", trim($endpoint, "/"));

  //   if (substr($endpoint[0], 0, 6) == "update") {
  //     $callbackFunction();
  //   }
  // }
  // public static function delete($endpoint, $callbackFunction)
  // {
  //   $endpoint = explode("/", trim($endpoint, "/"));

  //   if (substr($endpoint[0], 0, 6) == "delete") {
  //     $callbackFunction();
  //   }
  // }
}
