<?php

namespace Routes\Category;

use Middleware\Response;
use RequestHandlers\CategoryRequestHandlers;

class Category
{
  public static function run()
  {

    switch ($_SERVER['REQUEST_METHOD']) {
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
  public static function create()
  { 
    $response = CategoryRequestHandlers::createCategory();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function get(){
    $response = CategoryRequestHandlers::get();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function update(){
    $response = CategoryRequestHandlers::update();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function delete(){
    $response = CategoryRequestHandlers::delete();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  // public static function deleteChild()
  // {
  //   $response = CategoryRequestHandlers::deleteChild();
  //   Response::respondWithJson($response, $response["statusCode"]);
  // }
  // public static function deleteParent(){
  //   $response = CategoryRequestHandlers::deleteParent();
  //   Response::respondWithJson($response, $response["statusCode"]);
  // }
}

