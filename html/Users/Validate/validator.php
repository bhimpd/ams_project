<?php

namespace Validate;

class Validator
{

  /**
   * @param $data Associative array of eg : [username => "Rikesh" , password => passkey ]
   * @return NULL|array
   * eg:
   * $data = [
  'username' => 'Aadssa',
  'password' => '2342',
  'email' => 'example@example.com',
];
$keys = [
  'username' => ['empty', 'maxLength', 'format'],
  'password' => ['empty', 'maxLength', 'minLength']
];
Avaiable validations : 
required , emmpty , maxLength , minLength ,usernameFormat , passwordFormat , emailFormat , user_typeFormat
   */
  public static function validate($data, $keys)
  {
    $validateData = [];

    foreach ($keys as $key => $value) {

      if (in_array('required', $value)) {
        if (!isset($data[$key])) {

          $validateData[$key][] = [
            $key . " is required field!!"
          ];
          continue;
        } else {
        }
      }
      //check empty 
      if (in_array('empty', $value)) {

        if (isset($data[$key]) && !empty($data[$key])) {
        } else {
          $validateData[$key] = [
            $key . " cannot be empty!"
          ];
          continue;
        }
      }

      //check max length
      if (in_array("maxLength", $value)) {
        $maxAllowedLength = [
          "password" => "16",
          "username" => 20,
          "email" => 64,
          "name" => 64,
          "category_name" => 64,
          "parent" => 64,
          "department" => 32,
          "phone_number" => 15,
          "designation" => 64,
          "location" => 64,
          "brand"=>16,
          "reason" => 1000
        ];

        $inputLength = strlen($data[$key]);

        if ($inputLength > $maxAllowedLength[$key]) {
          $validateData[$key] = [
            $key . " exceeded max length " . $maxAllowedLength[$key] . " !"
          ];
          continue;
        }
      }

      if (in_array("minLength", $value)) {
        //rules for keys

        $minAllowedLength = [
          "password" => "8",
          "username" => 4,
          "email" => 7,
          "name" => 2,
          "category_name" => 1,
          "parent" => 1,
          "department" => 1,
          "phone_numebr" => 6,
          "designation" => 1,
          "location" => 1,
          "brand"=>2,
          "reason" => 1
        ];

        $inputLength = strlen($data[$key]);

        if ($inputLength < $minAllowedLength[$key]) {
          $validateData[$key] = [
            $key . " must be of minimum length $minAllowedLength[$key]!"
          ];
          continue;
        }
      }

      //check format of the data --Alphnumeric format
      if (in_array("usernameFormat", $value)) {
        function isValidUsername($username)
        {
          if (!preg_match("/^\S+$/", $username)) {
            return "should not contain spaces !!";
          }

          if (!preg_match('/^[^._-]*[-._]?[^._-]*$/', $username)) {
            return "should contain single occurance of underscore , hyphen or dot !!";
          }

          if (preg_match('/[!@#$%^&*()+{}\[\]:;<>,?~\\/]/', $username)) {
            return " should not contain  special characters  !!";
          }
          if (!preg_match('/^[A-Za-z].*/', $username)) {
            return " must contain  alphabet in start !!";
          }
          return true;
        }
        $result = isValidUsername($data[$key]);

        if ($result === true) {
          //do nothing if true

        } else {
          $validateData[$key] = [$key . " $result"];
          continue;
        }
      }

      if (in_array("passwordFormat", $value)) {

        function isValidPassword($password)
        {
          if (!preg_match('/[A-Z]/', $password)) {
            return "at least one uppercase letter !!";
          }

          if (!preg_match('/[a-z]/', $password)) {
            return "at least one lowercase letter !!";
          }

          if (!preg_match('/[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]/', $password)) {
            return "at least one special character  !!";
          }
          return true;
        }
        $result = isValidPassword($data[$key]);

        if ($result === true) {
          //do nothing if true

        } else {
          $validateData[$key] = [$key . " must contain  $result"];
          continue;
        }
      }

      if (in_array("emailFormat", $value)) {

        function isValidEmail($email)
        {
          $tempp = filter_var($email, FILTER_VALIDATE_EMAIL);
          if ($tempp !== false) {
            $temp = explode('@', $email);
            if (substr($temp[0], -1) === '+') {
              return false;
            }
            return true;
          } else {
            echo "hehe";
            return false;
          }
        }
        isValidEmail($data[$key]) ? [] : $validateData[$key] = [$key . " is not valid."];
        continue;
      }

      if (in_array("user_typeFormat", $value)) {
        $allowedUsers = ['employee', 'admin'];

        if (!in_array($data[$key], $allowedUsers)) {
          $validateData[$key] = [$key . " is not valid."];
        } else {
          continue;
        }
      }

      if (in_array("phone_numberFormat", $value)) {
        if (preg_match('/^(?:\+?977)?9\d{9}$/', $data[$key])) {
          continue;
        } else {
          $validateData[$key] = [$key . " is not valid."];
        }
      }

      if (in_array("designationFormat", $value)) {
        if (preg_match('/^\s|\s$/', $data[$key])) {
          // There is a space at the beginning or end of the string
          $validateData[$key] = [$key . ": No spaces allowed in start and end."];
          continue;
        }

        if (preg_match('/^[A-Za-z\s]+$/', $data[$key])) {
          continue;
        } else {
          $validateData[$key] = [$key . " should contain only alphabets."];
        }
      }

      if (in_array("category_nameFormat", $value)) {
        if (preg_match('/^[A-Za-z]+$/', $data[$key])) {
          continue;
        } else {
          $validateData[$key] = [$key . " should contain only alphabets."];
        }
      }

      if (in_array("parent_categoryFormat", $value)) {
        if (preg_match('/^[A-Za-z0-9]+$/', $data[$key])) {
          continue;

        }else{
          $validateData[$key] = [$key . " should contain only alphanumerals."];
        }
      }

      if(in_array('locationFormat',$value)){
      
        if (preg_match('/^\s|\s$/', $data[$key])) {
          // There is a space at the beginning or end of the string
          $validateData[$key] = [$key . ": No spaces allowed in start and end."];
          continue;
        }
      

        if (preg_match('/^[A-Za-z0-9\s]+$/', $data[$key])) {
          continue;
        }else{
          $validateData[$key] = [$key . " should contain only alphanumerals."];
        }
      }

      if(in_array('departmentFormat',$value)){
        if (preg_match('/^\s|\s$/', $data[$key])) {
          // There is a space at the beginning or end of the string
          $validateData[$key] = [$key . ": No spaces allowed in start and end."];
          continue;
        }
      
        if(preg_match('/^[A-Za-z\s]+$/',$data[$key])){

     
          continue;
        } else {
          $validateData[$key] = [$key . " should contain only alphabetical characters."];
        }
      }

      if (in_array('assets_format', $value)) {
        $format = ["hardware", "software"];
        $assets_type_value = $data[$key];

        if (in_array($assets_type_value, $format,)) {
          continue;
        } else {
          $validateData[$key] = [$key . " should contain either hardware or software"];
        }
      }

    }
    if (count($validateData) > 0) {
      return [
        "validate" => false,
        "message" => $validateData
      ];
    }
    return [
      "validate" => true,
      "message" => $validateData
    ];
  }
}
