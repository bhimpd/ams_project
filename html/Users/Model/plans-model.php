<?php

namespace Model;

use Exception;

use Configg\DbConnect;

class Plan
{
    const TABLE = "plans";
    public $DBconn;

    public function __construct(DbConnect $DBconn)
    {
        $this->DBconn = $DBconn;
    }

    public static function isJSON(string $jsonData)
    {
        json_decode($jsonData);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function create($data)
    {

        if (!is_array($data)) {
            throw new Exception("Invalid data format. Data must be an array.");
        }

        $plan_name = ucfirst($data['plan_name']);
        $plan_type = ucfirst($data['plan_type']);
        $name = $data['name'];
        $email = $data['email'];
        $country = $data['country'];
        $zip_code = $data['zip_code'];
        $phone_number = $data['phone_number'];
        $name_on_card = $data['name_on_card'];
        $card_number = $data['card_number'];
        $expire_date = $data['expire_date'];
        $security_code = $data['security_code'];

        // Prepare the SQL query
        $sql = "INSERT INTO " . self::TABLE . " (plan_name, plan_type, name, email, country, zip_code, phone_number, name_on_card, card_number,expire_date,security_code) 
            VALUES ('$plan_name', '$plan_type', '$name', '$email', '$country', '$zip_code', '$phone_number', '$name_on_card', '$card_number','$expire_date','$security_code')";

        // Execute the SQL query
        $result = $this->DBconn->conn->query($sql);

        if (!$result) {
            throw new Exception("Could not insert into database. Error: " . $this->DBconn->conn->error);
        }

        return true;
    }
}
