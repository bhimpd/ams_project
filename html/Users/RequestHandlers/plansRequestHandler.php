<?php

namespace PlansRequestHandler;

use Exception;
use Configg\DBConnect;
use Model\Plan;
use Validate\Validator;


// $dotenvPath = __DIR__ . ''; 

// require_once __DIR__ . '/../../../vendor/autoload.php'; 

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../html/'); 
$dotenv->load(); 

class PlansRequestHandler
{
    public static function createPlans()
    {
        try {
            $stripeKey = $_ENV['STRIPE_SECRET_KEY'];
            $stripe = new \Stripe\StripeClient($stripeKey);

            $plansObj = new Plan(new DBConnect());
            $jsonData = file_get_contents('php://input');
            $decodedData = json_decode($jsonData, true);

            //VALIDATION OF PROVIDED DATA

            $keys = [
                'plan_name' => ['empty', 'minLength', 'maxLength'],
                'plan_type' => ['empty', 'minLength', 'maxLength'],
                'name' => ['empty', 'minLength', 'maxLength'],
                'email' => ['maxLength', 'minLength', 'emailFormat'],
                'country' => ['required'],
                'zip_code' => ['required'],
                'phone_number' => ['required', 'phone_numberFormat'],
                'name_on_card' => ['empty'],
                'card_number' => ['required'],
                'expire_date' => ['required'],
                'security_code' => ['required']
            ];

            $planValidate = Validator::validate($decodedData, $keys);

            if ($planValidate["validate"] === false) {
                return [
                    "statusCode" => 422,
                    "status" => false,
                    "message" => $planValidate["message"]
                ];
            }
            // Process payment with Stripe
            $customer = $stripe->paymentIntents->create([
                'amount' => 250,
                'currency' => 'usd',
                // 'name'=>$decodedData['name'],
                // 'paid_from' => 'mastercard',
                // 'email' => $decodedData['email'],
                // 'card_number' => $decodedData['card_number'],
                // 'expire_date' => $decodedData['expire_date'],
                'description' =>  'Payment for ' . $decodedData['plan_name'],
                'payment_method' => 'pm_card_visa',
                // 'return_url' => 'https://dashboard.stripe.com/test/balance'

            ]);
// var_dump($customer);die;
            $stripe->paymentIntents->confirm(
                $customer->id,
                [
                    'payment_method' => 'pm_card_visa',
                    'return_url' => 'https://dashboard.stripe.com/test/balance',
                ]
            );

            // var_dump($customer);die;


            $result = $plansObj->create($decodedData);

            if (!$result) {
                return [
                    "status" => false,
                    "statusCode" => "409",
                    "message" => "Unable to create plan details",
                    "data" => json_decode($jsonData, true)
                ];
            }
            return [
                "status" => true,
                "statusCode" => "201",
                "message" => "Data inserted successfully",
                "data" => $decodedData,
                "plans_details" => $customer
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "statusCode" => "409",
                "message" => $e->getMessage(),
                "data" => json_decode($jsonData, true)
            ];
        } finally {
            $plansObj->DBconn->disconnectFromDatabase();
        }
    }
}
