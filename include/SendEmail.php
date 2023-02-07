<?php

use SendGrid\Mail\Mail;

require './sendgridapi/vendor/autoload.php';
require_once './sendgridapi/sendgrid-php.php';

class SendEmail
{

    function __construct()
    {

    }
    public function sendEmail($to, $from, $subject, $message){
        $email = new Mail();
        $email->setFrom($from, "Food and Smile");
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent(
            "text/html", $message
        );
        $sendgrid = new \SendGrid('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');
        try {
            $response = $sendgrid->send($email);
            if($response->statusCode() == 202){
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            //TODO
            //Logger
            #'Caught exception: '. $e->getMessage() ."\n"
            return false;
        }
    }
}