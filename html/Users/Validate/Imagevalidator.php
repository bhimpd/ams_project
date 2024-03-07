<?php

namespace ImageValidation;

class Imagevalidator
{
    public static function imagevalidation($image)
    {

        $imageDetails = pathinfo($image['name']);
        $imageExtension = strtolower($imageDetails['extension']);
        $validImageType = ["jpg", "png","webp"];
        $maxFileSize = 5 * 1024 * 1024; 

        if (!in_array($imageExtension, $validImageType)) {
            return [
                "status" => false,
                "message" => "Only png, jpg image types are accepted."
            ];
        }
        
        if ($image['size'] > $maxFileSize) {
            return [
                "status" => false,
                "message" => "Image size exceeds the maximum limit of 5 MB."
            ];
        }
       
        return [
            "status" => true,
        ];
    }
}
