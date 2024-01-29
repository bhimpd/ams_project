<?php


namespace Routes\Department;
use Middleware\Response;
use RequestHandlers\DepartmentRequestHandlers;

class Department
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
    $response = DepartmentRequestHandlers::createDepartment();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function get(){
    $response = DepartmentRequestHandlers::getAllDepartment();
    Response::respondWithJson($response, $response["statusCode"]);
  }

  public static function update(){
    $response = DepartmentRequestHandlers::updateDepartment();
    Response::respondWithJson($response, $response["statusCode"]);
  }
  public static function delete(){
    $response = DepartmentRequestHandlers::deleteDepartment();
    Response::respondWithJson($response, $response["statusCode"]);
  }
}
