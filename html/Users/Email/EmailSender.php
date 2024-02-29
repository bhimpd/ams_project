<?php

namespace EmailProcurement;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

class ProcurementEmailSender
{

    public static function sendProcurementEmail($recipientEmail, $procurementData)
    {
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                       //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                    //Enable SMTP authentication
            $mail->Username   = 'dreamypd73@gmail.com';                     //SMTP who sends gmail
            $mail->Password   = 'uyvd nwgo dnxn avsv';                               //SMTP password
            $mail->SMTPSecure = "tls";            //Enable implicit TLS encryption
            $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('dreamypd73@gmail.com', 'bhim');
            $mail->addAddress($recipientEmail);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'New Procurement Created';
            $emailBody = '<h1>New Procurement Created</h1>';
            $emailBody .= '<p><strong>Requested By ID:</strong> ' . $procurementData['requested_by_id'] . '</p>';
            $emailBody .= '<p><strong>Status:</strong> ' . $procurementData['status'] . '</p>';
            $emailBody .= '<p><strong>Request Urgency:</strong> ' . $procurementData['request_urgency'] . '</p>';
            $emailBody .= '<p><strong>Approved By ID:</strong> ' . $procurementData['approved_by_id'] . '</p>';

            $emailBody .= '<h2>Products:</h2>';
            foreach ($procurementData['products'] as $product) {
                $emailBody .= '<p><strong>Product Name:</strong> ' . $product['product_name'] . '</p>';
                $emailBody .= '<p><strong>Category ID:</strong> ' . $product['category_id'] . '</p>';
                $emailBody .= '<p><strong>Brand:</strong> ' . $product['brand'] . '</p>';
                $emailBody .= '<p><strong>Estimated Price:</strong> ' . $product['estimated_price'] . '</p>';
                $emailBody .= '<p><strong>Link:</strong> ' . $product['link'] . '</p>';
                $emailBody .= '<hr>';
            }

            $mail->Body = $emailBody;

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
