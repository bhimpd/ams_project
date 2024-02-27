<?php

namespace EmailProcurement;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

class ProcurementEmailSender
{

    public static function sendProcurementEmail($recipientEmail, $procurement_id)
    {
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'dreamypd73@gmail.com';                     //SMTP who sends gmail
            $mail->Password   = 'uyvd nwgo dnxn avsv';                               //SMTP password
            $mail->SMTPSecure = "tls";            //Enable implicit TLS encryption
            $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('dreamypd73@gmail.com', 'bhim');
            $mail->addAddress($recipientEmail);     //Add a recipient
           

            //Attachments
            // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments

            //Content
            $mail->isHTML(true);                                 
            $mail->Subject = 'New Procurement Created';
            $mail->Body = 'A new procurement with ID ' . $procurement_id . ' has been created successfully.';

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
