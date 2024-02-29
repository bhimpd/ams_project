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

            case 'GET':
                return self::get();

            case 'DELETE':
                return self::delete();

                case 'PUT':
                    return self::update();

            default:
                echo "Route for given request type not found!!";
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

    public static function delete()
    {
        $response = AssetsRequestHandlers::deleteAsset();
        return Response::respondWithJson($response, $response["statusCode"]);
    }

    public static function update()
    {
        $response = AssetsRequestHandlers::updateAsset();
        return Response::respondWithJson($response, $response["statusCode"]);
    }
}
