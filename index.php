<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\StreamInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require_once ("include/DbOperation.php");
require_once ("include/CurlRequest.php");
require_once("MaintenanceMiddleware.php");

$displayErrorDetails = true;

$app = AppFactory::create();
#$app->setBasePath("/SalveServer");

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);

//TODO
//Server Maintenance
//$maintenanceMiddle = new MaintenanceMiddleware();
//$app->add($maintenanceMiddle);

$app->post('/user/register', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);
    $empty = verifyEmptyParams(array($data['fName'], $data['lName'], $data['phoneNumber'], $data['email'], $data['college'], $data['graduationYear'], $data['userType'], $data['streetAddress'], $data['city'], $data['state'], $data['country'], $data['pincode'], $data['password'], $data['device'], $data['firebaseToken']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X104-Register";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    //Token to verify mobile number
    /**
    $token = $data['firebaseToken'];
    $cr = new CurlRequest();
    $userFound = $cr->verifyMobileTokenWithFirebase($token, $data['phone']);
    if(!$userFound){
        $message["error"] = true;
        $message["message"] = "Invalid credentials found. Please try again.";
        $message["code"] = "X105-Register-Donor";

        $response->getBody()->write(json_encode($message));
        return $response;
    }
    **/
    $res = $db->registerUser($data['fName'], $data['lName'], $data['phoneNumber'], $data['email'], $data['college'], $data['graduationYear'], $data['userType'], $data['streetAddress'], $data['city'], $data['state'], $data['country'], $data['pincode'], $data['password'], $data['device'], $data['firebaseToken'], getClientIP());

    //$res = $db->registerDonor($data['nameOfPerson'], $data['donorType'], $data['nameOfDonor'], $data['email'], $data['phone'], $data['address'], $data['pincode'], $data['city'], $data['state'], $data['country'], $data['isVolunteer'], $data['distance'], $data['device'], getClientIP());

    if($res == 0){
        $message["error"] = true;
        $message["message"] = "An error occurred while registering. Please try again later.";
        $message["code"] = "X101-Register";
    } else if($res == 1){
        $message["error"] = true;
        $message["message"] = "An error occurred while registering. Please try again later.";
        $message["code"] = "X102-Register";
    } else if($res == 2){
        $message["error"] = true;
        $message["message"] = "An error occurred while registering. Please try again later.";
        $message["code"] = "X103-Register";
    } else if($res == 3){
        $message["error"] = true;
        $message["message"] = "An error occurred while registering. Please try again later.";
        $message["code"] = "X104-Register";
    } else {
        $message["error"] = false;
        $message["message"] = $res;
    }
    $response->getBody()->write(json_encode($message));
    return $response;
});

$app->post('/user/check-user', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['email'], $data['phoneNumber'], $data['type']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Check-User-Existence";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    $res = $db->checkIfUserExists($data['email'], $data['phoneNumber']);
    $message["error"] = false;
    $message["exist"] = $res;
    if($res){
        if($data['type'] == 'signup'){
            $message["message"] = "Email or mobile already exist! Please login.";
        } else {
            $message["message"] = "User account found!";
        }
    } else {
        $message["message"] = "You are not registered. Please try again.";
    }
    $response->getBody()->write(json_encode($message));
    return $response;
});





//TODO
$app->post('/user/login-mobile', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['firebaseToken'], $data['phone'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Login-Mobile";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    $token = $data['firebaseToken'];
    $cr = new CurlRequest();
    $userFound = $cr->verifyMobileTokenWithFirebase($token, $data['phone']);
    if($userFound){
        $message["error"] = false;
        $message["message"] = $db->getUserDetailsLogin($data['phone'], $data['device']);
        $db->storeLoginAttemptInformation('phone', $data['phone'], $data['device'], getClientIP(), '1');
    } else {
        $message["error"] = true;
        $message["message"] = 'Oops, that is an error. Code: X-201';
    }

    $response->getBody()->write(json_encode($message));

    return $response;
});


$app->post('/user/login-email-otp', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['email'], $data['otp'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X103-Login-Email-OTP";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    $res = $db->verifyEmailOtp($data['email'], $data['otp']);
    if($res){
        $message["error"] = false;
        $message["message"] = $db->getUserDetailsByEmailLogin($data['email'], $data['device']);
        $db->storeLoginAttemptInformation('email', $data['email'], $data['device'], getClientIP(), '1');
    } else if(!$res){
        $message["error"] = true;
        $message["message"] = "OTP Verification Failed. Please check OTP or try again later.";
        $message['code'] = 'X101-Verify-Email-OTP';
        $db->addEmailOTPRetryCount($data['email'], $data['otp']);
    } else {
        $message["error"] = true;
        $message["message"] = "An error occurred. Please try again later.";
        $message['code'] = 'X102-Verify-Email-OTP';
    }

    $response->getBody()->write(json_encode($message));

    return $response;
});

$app->post('/user/login-google', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $cr = new CurlRequest();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['googleIdToken'], $data['email'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X104-Login-Google";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($data['googleIdToken']);
    $v3 = $cr->callAPI('POST', $url, null);
    $v4 = json_decode($v3);
    if($v4->email == $data['email']){
        $message["error"] = false;
        $message["message"] = $db->getUserDetailsByEmailLogin($data['email'], $data['device']);
        $db->storeLoginAttemptInformation('email-google', $data['email'], $data['device'], getClientIP(), '1');
    } else {
        $message["error"] = true;
        $message["message"] = 'Invalid credentials. Please try again.';
        $message['code'] = 'X103-Login-Google';
    }

    $response->getBody()->write(json_encode($message));

    return $response;
});

$app->post('/user/login-facebook', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $cr = new CurlRequest();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['facebookIdToken'], $data['email'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X104-Login-Facebook";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($data['facebookIdToken']);
    $v3 = $cr->callAPI('POST', $url, null);
    $v4 = json_decode($v3);
    if($v4->email == $data['email']){
        $message["error"] = false;
        $message["message"] = $db->getUserDetailsByEmailLogin($data['email'], $data['device']);
        $db->storeLoginAttemptInformation('email-facebook', $data['email'], $data['device'], getClientIP(), '1');
    } else {
        $message["error"] = true;
        $message["message"] = 'Invalid credentials. Please try again.';
        $message['code'] = 'X103-Login-Facebook';
    }

    $response->getBody()->write(json_encode($message));

    return $response;
});


$app->post('/user/send-email-verification', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['name'], $data['userId'], $data['email'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X105-Send-Email-Verification";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUserWithEmail($data['userId'], $api[0], $data['email']);
    if($valid){
        $res = $db->checkEmailVerificationCountAndSend($data['name'], $data['userId'], $data['email'], $data['device']);
        if($res == 0){
            $message["error"] = true;
            $message["message"] = "An error occurred! Please try again later [Code:X101]";
            $message['code'] = 'X101-Send-Email-Verification';
            $message['blocked'] = false;
            $status = 202;
        } else if($res == 1){
            $message["error"] = true;
            $message["message"] = "An error occurred! Please try again later [Code:X102]";
            $message['code'] = 'X102-Send-Email-Verification';
            $message['blocked'] = false;
            $status = 202;
        } else if($res == 2){
            $message["error"] = false;
            $message["message"] = "Verification Email Sent. Please check your email.";
            $message["verified"] = false;
            $status = 202;
        } else if($res == 3){
            $message["error"] = true;
            $message["message"] = "Too many verification email sent. Please contact support.";
            $message['code'] = 'X103-Send-Email-Verification';
            $message['blocked'] = true;
            $status = 202;
        } else if($res == 4){
            $message["error"] = false;
            $message["message"] = "Email already verified.";
            $message["verified"] = true;
            $status = 202;
        } else {
            $message["error"] = true;
            $message["message"] = "An error occurred! Please try again later.";
            $message['code'] = 'X104-Send-Email-Verification';
            $message['blocked'] = false;
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/user/send-email-otp', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['email'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X104-Send-Email-OTP";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    $res = $db->sendEmailOtp($data['email'], $data['device'], getClientIP());
    if($res == 0){
        $message["error"] = true;
        $message["message"] = "An error occurred! Please try again later.";
        $message['code'] = 'X101-Send-Email-OTP';
    } else if($res == 1){
        $message["error"] = true;
        $message["message"] = "An error occurred! Please try again later.";
        $message['code'] = 'X102-Send-Email-OTP';
    } else if($res == 2){
        $message["error"] = false;
        $message["message"] = "OTP Sent. Please check your email.";
    } else if($res == 3){
        $message["error"] = true;
        $message["message"] = "An error occurred! Please check email and try again later.";
        $message['code'] = 'X102-Send-Email-OTP';
    } else {
        $message["error"] = true;
        $message["message"] = "An error occurred! Please try again later.";
        $message['code'] = 'X103-Send-Email-OTP';
    }
    $response->getBody()->write(json_encode($message));
    return $response;
});


$app->get('/user/verify-email', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getQueryParams();

    $empty = verifyEmptyParams(array($body['email'], $body['token']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Verify-Email";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    $res = $db->verifyEmail($body['email'], $body['token']);
    if($res){
        $message = 'Email Verified';
    } else {
        $message = 'Invalid request or token expired';
    }


    $response->getBody()->write($message);
    return $response;
});


$app->post('/user/get-donors', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);


    $empty = verifyEmptyParams(array($data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X105-Get-Donors";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUser($data['user_id'], $api[0]);
    if($valid){
        $res = $db->getAllDonors();
        $message["error"] = false;
        $message["message"] = $res;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/user/get-recipients', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X105-Get-Recipient";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUser($data['user_id'], $api[0]);
    if($valid){
        $res = $db->getAllRecipients();
        $message["error"] = false;
        $message["message"] = $res;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/user/update-fcmid', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['fcm_id'], $data['phone'], $data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Update-FCM";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUserWithPhone($data['user_id'], $api[0], $data['phone']);
    if($valid){
        $db->updateFcmId($data['fcm_id'], $data['user_id'], $api[0]);
        $message["error"] = false;
        $message["message"] = "FCM Updated Successfully!";
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/user/update-user-location', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['latitude'], $data['longitude'], $data['user_id'], $data['email'], $data['user_type'], $data['type_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Update-Location";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUserWithEmail($data['user_id'], $api[0], $data['email']);

    if($valid){
        $res = $db->updateUserLocation($data['latitude'], $data['longitude'], $data['user_id'], $data['user_type'], $data['type_id']);
        if($res == 1){
            $message["error"] = true;
            $message["message"] = "Looks like you have an active order. Please retry after that order has been delivered!";
        } else {
            $message["error"] = false;
            $message["message"] = "Location updated successfully!";
        }
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/user/check-user-status', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-User-Check";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUser($data['user_id'], $api[0]);
    if($valid){
        $message["blocked"] = false;
        $status = 202;
    } else {
        $message["blocked"] = true;
        $status = 202;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/user/email-verification-status', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['phone'], $data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Email-Verification-Status";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUserWithPhone($data['user_id'], $api[0], $data['phone']);
    if($valid){
        $res = $db->getEmailVerificationStatus($data['phone']);
        $message["error"] = false;
        $message["message"] = $res;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/user/invalidate-api-key', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Invalidate-Api-Key";

        $response->getBody()->write(json_encode($message));
        return $response;
    }

    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUser($data['user_id'], $api[0]);
    if($valid){
        $db->invalidateApiKey($data['user_id'], $api[0]);
        $message["error"] = false;
        $message["message"] = "Success";
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


//Donor
$app->post('/food/donor/create-food-request', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['donorId'], $data['nameOfFood'], $data['foodType'], $data['numberOfPeople'], $data['foodReadyDate'], $data['foodReadyTime'], $data['foodValidity'], $data['device'], $data['user_id'], $data['donorSelfDeliver']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Create-Food-Request";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');
    $valid = $db->isValidUserWithDonorId($data['user_id'], $api[0], $data['donorId']);
    if($valid){
        $res = $db->createFoodRequest($data['donorId'], $data['nameOfFood'], $data['foodType'], $data['numberOfPeople'], $data['foodReadyDate'], $data['foodReadyTime'], $data['foodValidity'], $data['donorSelfDeliver'], $data['device'], $data['user_id'], $api[0]);
        if($res == 0){
            $message["error"] = true;
            $message["message"] = "An error occurred! Please try again later [Code:X101]";
            $message['code'] = 'X101-Create-Food-Request';
            $status = 202;
        } else if($res == 1){
            $message["error"] = true;
            $message["message"] = "An error occurred! Please try again later [Code:X102]";
            $message['code'] = 'X102-Create-Food-Request';
            $status = 202;
        } else if($res == 2){
            $message["error"] = true;
            $message["message"] = "You already have an active order. You can place a new order after it has been delivered.";
            $message['code'] = 'X103-Create-Food-Request';
            $status = 202;
        } else {
            $message["error"] = false;
            $message["message"] = "Food Request Created Successfully";
            $message["data"] = $res;
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/food/donor/donor-order-action', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id'], $data['order_id'], $data['action']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Donor-Order-Action";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = $db->isValidUserWithDonorId($data['user_id'], $api[0], $data['typeId']);

    if($valid){
        $res = $db->donorAcceptedFoodOrderAction($data['user_id'], $api[0], $data['typeId'], $data['order_id'], $data['action']);
        if($res == 0){
            //Order picked
            $message["error"] = false;
            $message["message"] = "Order picked up successfully!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 1){
            //Picking up order
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-101.";
            $status = 202;
        } else if($res == 2){
            //Order delivered
            $message["error"] = false;
            $message["message"] = "Order marked up delivered!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 3){
            //Marking order delivered
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-102.";
            $status = 202;
        } else if($res == 4){
            //Already delivered
            $message["error"] = true;
            $message["message"] = "Oops, this order is already marked delivered.";
            $status = 202;
        } else if($res == 5){
            //Incorrect value
            $message["error"] = true;
            $message["message"] = "Incorrect value for picking up order.";
            $status = 202;
        } else if($res == 6){
            //Incorrect value
            $message["error"] = true;
            $message["message"] = "Incorrect value for delivery.";
            $status = 202;
        } else if($res == 7){
            //Volunteer id didn't match
            $message["error"] = true;
            $message["message"] = "Order mismatch.";
            $status = 202;
        } else if($res == 8){
            //Volunteer id didn't match
            $message["error"] = true;
            $message["message"] = "Order Marking Error";
            $status = 202;
        } else if($res == 9){
            //Donor id didn't match
            $message["error"] = true;
            $message["message"] = "Delivery Marking Error";
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/food/donor/donor-self-deliver-expand-recipient', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id'], $data['order_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Donor-Self-Deliver-Expand_Recipient";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = $db->isValidUserWithDonorId($data['user_id'], $api[0], $data['typeId']);

    if($valid){
        $res = $db->donorSelfDeliverExpandRecipeint($data['user_id'], $data['typeId'], $data['order_id']);
        if($res == 0){
            //Recipients notified
            $message["error"] = false;
            $message["message"] = "Recipients notified";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 1){
            //Order already assigned
            $message["error"] = true;
            $message["message"] = "No longer valid.";
            $status = 202;
        } else if($res == 2){
            //Donor id didn't match
            $message["error"] = true;
            $message["message"] = "Order mismatch.";
            $status = 202;
        } else if($res == 3){
            //Donor id didn't match
            $message["error"] = false;
            $message["message"] = "Recipients already notified";
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/food/get-all-packet-details', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Get-All-Packets";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = false;
    $type = $data['type'];
    if($type == 'Donor'){
        $valid = $db->isValidUserWithDonorId($data['user_id'], $api[0], $data['typeId']);
    } else if ($type == 'Volunteer'){
        $valid = $db->isValidUserWithVolunteerId($data['user_id'], $api[0], $data['typeId']);
    } else if ($type == 'Recipient'){
        $valid = $db->isValidUserWithRecipientId($data['user_id'], $api[0], $data['typeId']);
    }

    if($valid){
        $res = $db->getAllFoodDetails($data['typeId'], $data['type']);
        $message["error"] = false;
        $message["message"] = $res;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

//Recipient
$app->post('/food/active-food-request-search', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id'], $data['type']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Active-Food-Requests";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = false;
    $type = $data['type'];
    if($type == 'Donor'){

        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Donors not allowed to access this endpoint.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);

    } else if ($type == 'Volunteer'){

        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Volunteers not allowed to access this endpoint.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);

    } else if ($type == 'Recipient'){
        $valid = $db->isValidUserWithRecipientId($data['user_id'], $api[0], $data['typeId']);
    }

    if($valid){
        $res = $db->getActiveFoodRequests();
        $message["error"] = false;
        $message["message"] = $res;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/food/recipient-accept-order', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id'], $data['order_id'], $data['device'], $data['recipientSelfAccept']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Recipient-Accept-Order";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = $db->isValidUserWithRecipientId($data['user_id'], $api[0], $data['typeId']);

    if($valid){
        $res = $db->acceptFoodOrderByRecipient($data['user_id'], $api[0], $data['typeId'], $data['order_id'], $data['device'], $data['recipientSelfAccept']);
        if($res == 0){
            $message["error"] = false;
            $message["message"] = "Food Request Accepted Successfully!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 1){
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again.";
            $status = 202;
        } else if($res == 2){
            $message["error"] = true;
            $message["message"] = "Oops, another recipient has already accepted the order.";
            $status = 202;
        } else if($res == 3){
            $message["error"] = true;
            $message["message"] = "You already have an active order. You can accept a new order after it has been delivered.";
            $status = 202;
        } else if($res == 4){
            $message["error"] = true;
            $message["message"] = "You are not yet eligible to accept this order right now. Please try after some time.";
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }


    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/food/recipient-order-action', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id'], $data['order_id'], $data['action']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Recipient-Order-Action";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = $db->isValidUserWithRecipientId($data['user_id'], $api[0], $data['typeId']);

    if($valid){
        $res = $db->recipientAcceptedFoodOrderAction($data['user_id'], $api[0], $data['typeId'], $data['order_id'], $data['action']);
        if($res == 0){
            //Order picked
            $message["error"] = false;
            $message["message"] = "Notified successfully!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 1){
            //Picking up order
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-101.";
            $status = 202;
        } else if($res == 2){
            //Order delivered
            $message["error"] = false;
            $message["message"] = "Order marked received!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 3){
            //Marking order delivered
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-102.";
            $status = 202;
        } else if($res == 4){
            //Already delivered
            $message["error"] = true;
            $message["message"] = "Oops, this order is already marked received.";
            $status = 202;
        } else if($res == 5){
            //Incorrect value
            $message["error"] = true;
            $message["message"] = "Incorrect value for notifying";
            $status = 202;
        } else if($res == 6){
            //Incorrect value
            $message["error"] = true;
            $message["message"] = "Incorrect value for receiving";
            $status = 202;
        } else if($res == 7){
            //Volunteer id didn't match
            $message["error"] = true;
            $message["message"] = "Order mismatch.";
            $status = 202;
        } else if($res == 8){
            //Volunteer id didn't match
            $message["error"] = true;
            $message["message"] = "Notifying Error";
            $status = 202;
        } else if($res == 9){
            //Volunteer id didn't match
            $message["error"] = true;
            $message["message"] = "Receiving Marking Error";
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

//Volunteer
$app->post('/food/active-food-request-search-volunteer', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Active-Food-Requests-Volunteer";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = false;
    $type = $data['type'];
    if($type == 'Donor'){

        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Donors not allowed to access this endpoint.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);

    } else if ($type == 'Recipient'){

        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Recipients not allowed to access this endpoint.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);

    } else if ($type == 'Volunteer'){
        $valid = $db->isValidUserWithVolunteerId($data['user_id'], $api[0], $data['typeId']);
    }

    if($valid){
        $res = $db->getActiveFoodRequestsVolunteer();
        $message["error"] = false;
        $message["message"] = $res;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/food/volunteer-accept-order', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id'], $data['order_id'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Volunteer-Accept-Order";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = $db->isValidUserWithVolunteerId($data['user_id'], $api[0], $data['typeId']);

    if($valid){
        $res = $db->acceptFoodOrderByVolunteer($data['user_id'], $api[0], $data['typeId'], $data['order_id'], $data['device']);
        if($res == 0){
            $message["error"] = false;
            $message["message"] = "Food Request Accepted Successfully!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 1){
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-101.";
            $status = 202;
        } else if($res == 2){
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-102.";
            $status = 202;
        } else if($res == 3){
            $message["error"] = true;
            $message["message"] = "Oops, another volunteer has already accepted the order.";
            $status = 202;
        } else if($res == 4){
            $message["error"] = true;
            $message["message"] = "You already have an active order. You can accept a new order after you deliver it.";
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/food/volunteer-order-action', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['typeId'], $data['user_id'], $data['order_id'], $data['action']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Volunteer-Order-Action";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $api = $request->getHeader('api_key');

    $valid = $db->isValidUserWithVolunteerId($data['user_id'], $api[0], $data['typeId']);

    if($valid){
        $res = $db->volunteerAcceptedFoodOrderAction($data['user_id'], $api[0], $data['typeId'], $data['order_id'], $data['action']);
        if($res == 0){
            //Order picked
            $message["error"] = false;
            $message["message"] = "Order picked up successfully!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 1){
            //Picking up order
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-101.";
            $status = 202;
        } else if($res == 2){
            //Order delivered
            $message["error"] = false;
            $message["message"] = "Order marked up delivered!";
            $message["data"] = $db->getFoodDetails($data['order_id']);
            $status = 202;
        } else if($res == 3){
            //Marking order delivered
            $message["error"] = true;
            $message["message"] = "Oops, this seems to be an error. Please try again. Code X-102.";
            $status = 202;
        } else if($res == 4){
            //Already delivered
            $message["error"] = true;
            $message["message"] = "Oops, this order is already marked delivered.";
            $status = 202;
        } else if($res == 5){
            //Incorrect value
            $message["error"] = true;
            $message["message"] = "Incorrect value for picking up order.";
            $status = 202;
        } else if($res == 6){
            //Incorrect value
            $message["error"] = true;
            $message["message"] = "Incorrect value for delivery.";
            $status = 202;
        } else if($res == 7){
            //Volunteer id didn't match
            $message["error"] = true;
            $message["message"] = "Order mismatch.";
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/report-bug-testing', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    $empty = verifyEmptyParams(array($data['title'], $data['user_id'], $data['description'], $data['device']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Report-Bug";

        $response->getBody()->write(json_encode($message));
        return $response;
    }


    if (!$request->hasHeader('api_key')) {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access. Invalid API key or missing.";
        $message["code"] = "401";
        $status = 401;

        $response->getBody()->write(json_encode($message));
        return $response->withStatus($status);
    }

    $valid = true;

    if($valid){
        $res = $db->reportBug($data['user_id'], $data['title'], $data['description'], $data['device']);
        if($res){
            $message["error"] = false;
            $message["message"] = "Reported successfully!";
            $status = 202;
        } else {
            $message["error"] = true;
            $message["message"] = "An error occurred!";
            $status = 202;
        }
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});

$app->post('/test/1', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    /**
    $empty = verifyEmptyParams(array($data['city']));
    if($empty){
        $message["error"] = true;
        $message["message"] = "Parameters missing or empty";
        $message["code"] = "X101-Test-1";

        $response->getBody()->write(json_encode($message));
        return $response;
    }
     * **/

    $valid = 'true';

    if($valid){
        //$res = $db->getAllVolunteerFCM();
        $mPushNotification = $db->getPushNotificationObject("", "", "", "", "", "email_update", "Any");
        $uid = $db->logNotificationToDatabase($mPushNotification);
        if(!$uid){
            return 'Error';
        }
        $firebase = new Firebase();
        $mPushNotification['notification_id'] = $uid;
        $deviceToken = $db->getAllRecipientFCM();
        $result = $firebase->send($deviceToken, $mPushNotification);
        $db->updateNotificationSentResult($result, $uid);

        $message["error"] = false;
        $message["result"] = $result;
        $message['notification'] = $mPushNotification;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->post('/test/email-template', function (Request $request, Response $response, $args) {
    $db = new DbOperation();
    $body = $request->getBody();
    $data = json_decode($body->getContents(), true);

    /**
    $empty = verifyEmptyParams(array($data['city']));
    if($empty){
    $message["error"] = true;
    $message["message"] = "Parameters missing or empty";
    $message["code"] = "X101-Test-1";

    $response->getBody()->write(json_encode($message));
    return $response;
    }
     * **/

    $valid = 'true';

    if($valid){
        //$res = $db->getAllVolunteerFCM();
        $res = $db->sendTestEmail($data['email'], $data['link']);
        $message["result"] = $res;
        $status = 202;
    } else {
        $message["error"] = true;
        $message["Message"] = "Unauthorized access.";
        $message["code"] = "401";
        $status = 401;
    }

    $response->getBody()->write(json_encode($message));
    return $response->withStatus($status);
});


$app->get('/', function (Request $request, Response $response, $args) {
    return $response->withHeader('Location', "https://foodandsmile.org")
        ->withStatus(301);
});

$app->get('', function (Request $request, Response $response, $args) {

    $response->getBody()->write("Hello");
    return $response->withStatus(202);
});

function getClientIP() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function verifyEmptyParams(array $required_fields)
{
    $error = false;
    foreach ($required_fields as $field) {
        if($field == ''){
            $error = true;
            break;
        }
    }
    return $error;
}


$app->run();
