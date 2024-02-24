<?php

namespace ImageValidation;

class Imagevalidator
{
    public static function imagevalidation($image)
    {

        $imageDetails = pathinfo($image['name']);
        $imageExtension = strtolower($imageDetails['extension']);
        $validImageType = ["jpg", "jpeg", "png"];

        if (!in_array($imageExtension, $validImageType)) {
            return [
                "status" => false,
                "message" => "Only png, jpeg, jpg image types are accepted."
            ];
        }
        return [
            "status" => true,
        ];
    }
}
