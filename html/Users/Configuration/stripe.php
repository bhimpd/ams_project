<?php

// require_once __DIR__ . '../../../vendor/stripe/stripe-php/init.php';

// $PublishableKey = "pk_test_51OqrmoCBdYVwEbE1T6huQbnUbrhjx1PREhZNnnQkiXYsDlHRumTYtaCj9MWy7FdIf4Qq7Z1rO1eDWKOjxFQrKO3500zDLZgrwC";

// $SecretKey =  "sk_test_51OqrmoCBdYVwEbE1xrjMrJAO2WdZGtgw6gXjK7djY51IX3vlBJwmIP8wQHS1s7jABkREV96FhhGFEChIeyI83NwV00Grg5kTSl";

// \Stripe\Stripe::setApiKey($SecretKey);


// $resultInsertProcurement = null;
// $procurement_id = null; // Initialize procurement_id

// // checking whether req_by_id already exist or not
// $sqlCheckReqId = "SELECT id, number_of_items FROM procurements WHERE requested_by_id = '$procurementData[requested_by_id]'";
// $resultCheckProcurement = $this->DBconn->conn->query($sqlCheckReqId);
// // var_dump($resultCheckProcurement);die;
// if ($resultCheckProcurement) {
//     if ($resultCheckProcurement->num_rows > 0) {
//         // User has previous procurement records, update the number_of_items
//         $row = $resultCheckProcurement->fetch_assoc();
//         $procurement_id = $row['id']; // Retrieve existing procurement_id
//         $number_of_items = $row['number_of_items'] + count($data['products']);

//         $sqlUpdateProcurement = "UPDATE procurements SET number_of_items = '$number_of_items' WHERE requested_by_id = '$procurementData[requested_by_id]'";
//         $resultUpdateProcurement = $this->DBconn->conn->query($sqlUpdateProcurement);
//     } else {
//         // User does not have previous procurement records, insert a new row
//         $number_of_items = count($data['products']);

//         $sqlInsertProcurement = "INSERT INTO procurements (requested_by_id, number_of_items, status, request_urgency)
//                                  VALUES ('$procurementData[requested_by_id]', '$number_of_items', '$procurementData[status]', '$procurementData[request_urgency]')";
//         $resultInsertProcurement = $this->DBconn->conn->query($sqlInsertProcurement);
//         $procurement_id = $this->DBconn->conn->insert_id; // Retrieve the new procurement_id
//     }
// }

// if (!$resultCheckProcurement || (!$resultInsertProcurement && !$resultUpdateProcurement)) {
//     return [
//         "status" => false,
//         "message" => "Failed to insert or update data in procurements table"
//     ];
// }

// foreach ($data['products'] as $product) {
//     $product_name = ucfirst($product['product_name']);
//     $estimated_price = number_format($product['estimated_price'], 2, '.', '');
//     $sqlProduct = "INSERT INTO procurements_products (product_name, procurement_id, category_id, brand, estimated_price, link)
//                    VALUES ('$product_name', '$procurement_id', '$product[category_id]', '$product[brand]', '$estimated_price', '$product[link]')";

//     $resultProduct = $this->DBconn->conn->query($sqlProduct);

//     if (!$resultProduct) {
//         return [
//             "status" => false,
//             "message" => "Failed to insert data into procurements_products table"
//         ];
//     }
// }