<?php

namespace Helpers;

use ImageValidation\Imagevalidator;

trait ImageHandler
{

  public static function imageUploader(string $fieldname= "user_image")
  {
   

    $exceptionMessageFormat = [
      "status" => false,
      "statusCode" => "409",
      "message" => [
        "validation" => false,
        "message" => []
      ]
    ];

    if (!isset($_FILES[$fieldname])) {
      self::$exceptionMessageFormat["message"]["message"][$fieldname] = "No image file uploaded !!";
      return $exceptionMessageFormat;
    }


    $image = $_FILES[$fieldname];

    if ($image['error'] !== UPLOAD_ERR_OK) {
      self::$exceptionMessageFormat["message"]["message"][$fieldname] = "Failed to upload image !!";

      return $exceptionMessageFormat;
    }

    $image_validation = Imagevalidator::imagevalidation($image);

    if (!$image_validation["status"]) {
      self::$exceptionMessageFormat["message"]["message"][$fieldname] = $image_validation["message"];
      return $exceptionMessageFormat;

    }
    //uploading the photo after every other validation is ok
      $imageName = uniqid() . '_' . $image['name'];
      $uploadDirectory = dirname(__DIR__) . '/public/user/uploaded_images/';
   
      $uploadedFilePath = $uploadDirectory . $imageName;
   
      $relativeImagePath = '/Users/public/user/uploaded_images/' . $imageName;
    

    if (!move_uploaded_file($image['tmp_name'], $uploadedFilePath)) {
      $error = error_get_last();
      $exceptionMessageFormat["message"]["message"][$fieldname] = "Failed to move uploaded file !!" . $error['message'];
      return $exceptionMessageFormat;
    }
      return [
        "status" => true ,
        "message" => "Image uploaded successfully",
        "data" => [
          $fieldname => $relativeImagePath
        ]
        ];
    
  }
}