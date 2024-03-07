<?php

namespace PlansRequestHandler;

use Exception;
use Configg\DBConnect;
use Model\Plan;
use Validate\Validator;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../html/');
$dotenv->load();

class PlansRequestHandler
{
    public static function createPlans()
    {
        try {
            $plansObj = new Plan(new DBConnect());
            $jsonData = file_get_contents('php://input');
            $decodedData = json_decode($jsonData, true);

            // Retrieve payment_method from URL parameters
            $paymentMethod = $_GET['payment_method'] ?? null;

            // Validate payment_method
            if (!$paymentMethod) {
                return [
                    "statusCode" => 400,
                    "status" => false,
                    "message" => "Payment method not specified in the URL."
                ];
            }
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
            // Determine the payment method

            var_dump("strpe here");die;
            if ($paymentMethod === 'stripe') {
                // Process payment with Stripe
                $stripeKey = $_ENV['STRIPE_SECRET_KEY'];
                $stripe = new \Stripe\StripeClient($stripeKey);

                $customer = $stripe->paymentIntents->create([
                    'amount' => 250,
                    'currency' => 'usd',
                    'description' =>  'Payment for ' . $decodedData['plan_name'],
                    'payment_method' => 'pm_card_visa', // Placeholder for Stripe payment method
                    // 'return_url' => 'https://dashboard.stripe.com/test/balance'
                ]);

                $stripe->paymentIntents->confirm(
                    $customer->id,
                    [
                        'payment_method' => 'pm_card_visa', // Placeholder for Stripe payment method
                        'return_url' => 'https://dashboard.stripe.com/test/balance',
                    ]
                );
            } elseif ($paymentMethod === 'paypal') {
                // Process payment with PayPal
                $clientId = $_ENV['CLIENTID'];
                $clientSecret = $_ENV['CLIENT_SECRET_KEY'];
                $environment = new SandboxEnvironment($clientId, $clientSecret);
                $client = new PayPalHttpClient($environment);
var_dump($clientId);die;

                // Implement PayPal payment logic here
                $request = new OrdersCreateRequest();
                $request->prefer('return=representation');
                $request->body = [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => '10.00'
                        ]
                    ]]
                ];

                try {
                    $response = $client->execute($request);

                    // Handle PayPal response
                    if ($response->statusCode == 201) {
                        $order = $response->result;
                        // Redirect to PayPal for payment approval
                        header('Location: ' . $order->links[1]->href);
                    } else {
                        // Handle errors
                        echo "Error: " . $response->statusCode;
                    }
                } catch (Exception $e) {
                    // Handle exceptions
                    echo "Exception: " . $e->getMessage();
                }
                // Placeholder for PayPal payment processing
                return [
                    "status" => false,
                    "statusCode" => "501",
                    "message" => "PayPal payment method is not yet implemented",
                ];
            } else {
                return [
                    "status" => false,
                    "statusCode" => "400",
                    "message" => "Invalid payment method specified",
                ];
            }
            // Save plan details to the database
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
