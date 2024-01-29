<?php 

namespace Routes\Location;
use Middleware\Response;
use RequestHandlers\LocationRequestHandlers;


class Location 
{
  public static function run(){

    switch($_SERVER['REQUEST_METHOD']){
      case 'GET':
        self::get();
        break;
      case 'POST':
       self::create();
        break;
      case 'PUT':
        self::update();
        break;
      case 'DELETE':
        self::delete();
        break;
      
    }
  }
  public static function create(){
    $response = LocationRequestHandlers::createLocation();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function get(){
    $response = LocationRequestHandlers::getAllLocation();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function update(){
    $response = LocationRequestHandlers::updateLocation();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function delete(){
    $response = LocationRequestHandlers::deleteLocation();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}

