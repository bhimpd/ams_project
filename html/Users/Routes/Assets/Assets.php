<?php

namespace Routes\Assets;

use Middleware\Response;
use RequestHandlers\AssetsRequestHandlers;

class Assets
{
    public static function run()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        switch ($method) {
            case 'POST':
                return self::create();
                break;

                case 'GET':
                    return self::get();
                    break;

            default:
                # code...
                break;
        }
    }
    public static function create()
    {
        $response = AssetsRequestHandlers::createAssets();
        return Response::respondWithJson($response, $response["statusCode"]);
    }
    public static function get()
    {
        $response = AssetsRequestHandlers::getAssets();
        return Response::respondWithJson($response, $response["statusCode"]);
    }
    
}
