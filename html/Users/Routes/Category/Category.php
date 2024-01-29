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
        if (isset($_GET['childCategory'])) {
          self::deleteChild();

        } else if (isset($_GET['parentCategory'])) {
          self::deleteParent();
        } else {
          Response::respondWithJson([
            "status" => "false",
            "message" => "Parameters are not provided!!"
          ], 400);
        }
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
  public static function updateParent()
  {
    $response = CategoryRequestHandlers::updateParent();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function updateChild()
  {
    $response = CategoryRequestHandlers::updateChild();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function deleteChild()
  {
    $response = CategoryRequestHandlers::deleteChild();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function deleteParent(){
    $response = CategoryRequestHandlers::deleteParent();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}

