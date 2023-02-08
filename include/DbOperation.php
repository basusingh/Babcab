/** @noinspection ALL */<?php
 
class DbOperation
{
    //Database connection link
    private $con;
    private $orderStatusCreated = 'Created';
    private $orderStatusAcceptedRecipient = 'Accepted By Recipient';
    private $orderStatusAcceptedRecipientVolunteer = 'Accepted By Volunteer';
    private $orderStatusDeliveryOnWay = 'On Way';
    private $orderStatusDelivered = 'Delivered';

    private $apiKeyDisableSourceLogout = 'Logout';
    private $apiKeyDisableSourceBlocked = 'Blocked';



    //Class constructor
    function __construct()
    {
        //Getting the DbConnect.php file
        require_once 'DbConnect.php';
        require_once ("SendEmail.php");
        require_once ("Push.php");
        require_once ("Firebase.php");
        require_once ("template/ui/EmailVerification.php");
        //require_once ("template/ui/OTPValidation.html");
 
        //Creating a DbConnect object to connect to the database
        $db = new DbConnect();
 
        //Initializing our connection link of this class
        //by calling the method connect of DbConnect class
        $this->con = $db->connect();
    }


    //TODO
    //Register User
    public function registerDonor($nameOfPerson, $donorType, $nameOfDonor, $email, $phone, $address, $pincode, $city, $state, $country, $isVolunteer, $distance, $device, $ipaddress){
        $timestamp = $this->getCurrentTimeStamp();
        $api_key = $this->generateApiKey();
        $stmt = $this->con->prepare("INSERT INTO users (name, email, phone, address, pincode, city, state, country, reg_date, device_detail, type, registration_ip, max_distance) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $userType = 'Donor';
        $stmt->bind_param("sssssssssssss", $nameOfPerson, $email, $phone, $address, $pincode, $city, $state, $country, $timestamp, $device, $userType, $ipaddress, $distance);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            $userId = $this->getUserID($phone);
            $donorResult = $this->addDonorDetails($userId, $nameOfDonor, $donorType, $isVolunteer, $distance, $address, $phone);
            if($donorResult){
                $apiResult = $this->addApiKey($userId, $api_key, $device);
                if($apiResult){
                    $this->sendVerificationEmail($nameOfDonor, $userId, $email, $device);
                    $complete = $this->setRegistrationComplete($userId);
                    if($complete){
                        $message = $this->getUserDetails($phone);
                        $message["api_key"] = $api_key;
                        return $message;
                    } else {
                        return 3;
                    }
                } else {
                    return 2;
                }
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }

    public function addDonorDetails($userId, $nameOfDonor, $donorType, $isVolunteer, $distance, $address, $phone){
        $stmt = $this->con->prepare("INSERT INTO donor (user_id, donor_name_place, donor_type, is_volunteer, donor_max_distance, donor_address, donor_phone) values(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $userId, $nameOfDonor, $donorType, $isVolunteer, $distance, $address, $phone);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function registerVolunteer($nameOfPerson, $email, $phone, $address, $pincode, $city, $state, $country, $distance, $device, $ipaddress){
        $timestamp = $this->getCurrentTimeStamp();
        $api_key = $this->generateApiKey();
        $userType = 'Volunteer';
        $stmt = $this->con->prepare("INSERT INTO users (name, email, phone, address, pincode, city, state, country, reg_date, device_detail, type, registration_ip, max_distance) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssss", $nameOfPerson, $email, $phone, $address, $pincode, $city, $state, $country, $timestamp, $device, $userType, $ipaddress, $distance);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            $userId = $this->getUserID($phone);
            $volunteerResult = $this->addVolunteerDetails($userId, $nameOfPerson, $phone, $distance);
            if($volunteerResult){
                $apiResult = $this->addApiKey($userId, $api_key, $device);
                if($apiResult){
                    $this->sendVerificationEmail($nameOfPerson, $userId, $email, $device);
                    $complete = $this->setRegistrationComplete($userId);
                    if($complete){
                        $message = $this->getUserDetails($phone);
                        $message["api_key"] = $api_key;
                        return $message;
                    } else {
                        return 3;
                    }
                } else {
                    return 2;
                }
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }

    public function addVolunteerDetails($userId, $nameOfPerson, $phone, $distance){
        $stmt = $this->con->prepare("INSERT INTO volunteer (user_id, volunteer_name, volunteer_phone, volunteer_max_distance) values(?, ?, ?, ?)");
        $stmt->bind_param("ssss", $userId, $nameOfPerson, $phone, $distance);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function registerRecipient($nameOfPerson, $recipientType, $nameOfRecipient, $email, $phone, $address, $pincode, $city, $state, $country, $isVolunteer, $distance, $device, $ipaddress){
        $timestamp = $this->getCurrentTimeStamp();
        $api_key = $this->generateApiKey();
        $stmt = $this->con->prepare("INSERT INTO users (name, email, phone, address, pincode, city, state, country, reg_date, device_detail, type, registration_ip, max_distance) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $userType = 'Recipient';
        $stmt->bind_param("sssssssssssss", $nameOfPerson, $email, $phone, $address, $pincode, $city, $state, $country, $timestamp, $device, $userType, $ipaddress, $distance);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            $userId = $this->getUserID($phone);
            $recipientResult = $this->addRecipientDetails($userId, $nameOfRecipient, $recipientType, $isVolunteer, $distance, $address, $phone);
            if($recipientResult){
                $apiResult = $this->addApiKey($userId, $api_key, $device);
                if($apiResult){
                    $this->sendVerificationEmail($nameOfRecipient, $userId, $email, $device);
                    $complete = $this->setRegistrationComplete($userId);
                    if($complete){
                        $message = $this->getUserDetails($phone);
                        $message["api_key"] = $api_key;
                        return $message;
                    } else {
                        return 3;
                    }
                } else {
                    return 2;
                }
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }

    public function addRecipientDetails($userId, $nameOfRecipient, $recipientType, $isVolunteer, $distance, $address, $phone){
        $stmt = $this->con->prepare("INSERT INTO recipient (user_id, recipient_name_place, recipient_type, is_volunteer, recipient_max_distance, recipient_address, recipient_phone) values(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $userId, $nameOfRecipient, $recipientType, $isVolunteer, $distance, $address, $phone);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function setRegistrationComplete($userId){
        $stmt = $this->con->prepare("UPDATE users SET registered = 1 WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $row = $stmt->affected_rows;
        $stmt->close();
        return $row > 0;
    }

    public function addApiKey($userId, $api_key, $device){
        $timestamp = $this->getCurrentTimeStamp();
        $valid = 'yes';
        $stmt = $this->con->prepare("INSERT INTO apikey (user_id, api_key, device, timestamp, valid) values(?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $userId, $api_key, $device, $timestamp, $valid);
        return $stmt->execute();
    }

    public function getUserID($phone){
        $stmt = $this->con->prepare("SELECT user_id FROM users WHERE phone = ? LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['user_id'];
    }

    public function getUserIDByEmail($email){
        $stmt = $this->con->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['user_id'];
    }

    public function getUserIDByDonorId($donorId){
        $stmt = $this->con->prepare("SELECT user_id FROM donor WHERE donor_id = ? LIMIT 1");
        $stmt->bind_param("s", $donorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['user_id'];
    }

    public function getUserIDByRecipientId($recipientId){
        $stmt = $this->con->prepare("SELECT user_id FROM recipient WHERE recip_id = ? LIMIT 1");
        $stmt->bind_param("s", $recipientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['user_id'];
    }

    public function getDonorIdByUserId($userId){
        $stmt = $this->con->prepare("SELECT donor_id FROM donor WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['donor_id'];
    }

    public function getRecipientIdByUserId($userId){
        $stmt = $this->con->prepare("SELECT recip_id FROM recipient WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['recip_id'];
    }

    public function getVolunteerIdByUserId($userId){
        $stmt = $this->con->prepare("SELECT vol_id FROM volunteer WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['vol_id'];
    }

    public function checkEmailVerificationCountAndSend($nameOfPerson, $userId, $email, $device){
        $stmt = $this->con->prepare("SELECT verification_email_sent_count from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        $count = $token['verification_email_sent_count'];
        if($count >= 5){
            return 3;
        }

        $alreadyVerified = $this->getEmailVerificationStatusByEmail($email);
        if($alreadyVerified == '1'){
            return 4;
        }

        return $this->sendVerificationEmail($nameOfPerson, $userId, $email, $device);
    }

    public function sendVerificationEmail($nameOfPerson, $userId, $email, $device){
        $token = $this->generateRandomToken(70);
        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("INSERT INTO email_verification (user_id, email, token, ipaddress, device, time_sent) values(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $userId, $email, $token, $this->getClientIP(), $device, $timestamp);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
            $to = $email;
            $sub = 'Verify your Email for Food and Smile';
            $verifyUrl = 'http://api.foodandsmile.org/user/verify-email?email='.$email.'&token='.$token;
            $message = "
                 <html>
                 <head></head>
                 <body>
                 <strong>".explode(' ',trim($nameOfPerson))[0]."</strong>, welcome to Food and Smile.
                 <br>
                 <br>
                 Please click here to verify your account: ".$verifyUrl."
                 <br>
                 <br>
                 <br>
                 <br>
                 Didn't signup? Please let us know by <a href='http://api.foodandsmile.org/report-email-signup'>clicking here</a>
                 </body>
                 </html>";

            $res = $this->sendEmail($to, $sub, $message);
            if($res){
                $this->setVerificationEmailSent($email);
                $this->increaseVerificationEmailSentCount($email);
                return 2;
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }


    public function sendTestEmail($email, $link){
        $view = new Template();

        $to = $email;
        $sub = 'Test Email';

        $res = $this->sendEmail($to, $sub, $view->getTemplate($link));
        if($res){
            $this->setVerificationEmailSent($email);
            $this->increaseVerificationEmailSentCount($email);
            return 2;
        } else {
            return 0;
        }
    }


    public function setVerificationEmailSent($email){
        $stmt = $this->con->prepare("UPDATE email_verification SET is_email_sent = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }

    public function increaseVerificationEmailSentCount($email){
        $stmt = $this->con->prepare("UPDATE users SET verification_email_sent_count = verification_email_sent_count + 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }

    public function verifyEmail($email, $token){
        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("SELECT * from email_verification WHERE email = ? AND token = ? AND ? <= DATE_ADD(time_sent, INTERVAL 2 DAY)");
        $stmt->bind_param("sss", $email, $token, $timestamp);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if($num_rows > 0){
            $this->setEmailVerified($email);
            return true;
        } else {
            return false;
        }
    }

    public function setEmailVerified($email){
        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("UPDATE users SET email_veri_time = ?, is_email_verified = 1 WHERE email = ?");
        $stmt->bind_param("ss", $timestamp, $email);
        $stmt->execute();
        $stmt->close();
        $this->notifyDeviceOfEmailVerification($email);
    }

    public function notifyDeviceOfEmailVerification($email){
        $fcmIds = $this->getUserDevicesFCM($this->getUserIDByEmail($email));
        $mPushNotification = $this->getPushNotificationObject("", "", "", "", "", "email_update", "Any");
        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        return $res;
    }

    public function sendEmail($to, $sub, $message){
        $sm = new SendEmail();
        return $sm->sendEmail($to, "info@foodandsmile.org", $sub, $message);
    }

    public function sendEmailOtp($email, $device, $ipaddress){
        $otp = $this->generateOtp();
        $this->deletePreviousEmailOTP($email);
        $userId = $this->getUserIDByEmail($email);
        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("INSERT INTO email_otp (user_id, email, otp, ipaddress, device, time_sent) values(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $userId, $email, $otp, $ipaddress, $device, $timestamp);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
            $to = $email;
            $sub = "".$otp." is your OTP for Salve";
            $message = "
                 <html>
                 <head></head>
                 <body>
                 <strong>".$otp."</strong> is your One Time Password (OTP) for Food and Smile.
                 <br>
                 <br>
                 <br>
                 <br>
                 Happy Sharing!
                 <br>
                 <br>
                 Not you? Please let us know by <a href='http://api.foodandsmile.org/report-email-otp'>clicking here</a>
                 </body>
                 </html>";
            try{
                $res = $this->sendEmail($to, $sub, $message);
                if($res){
                    return 2;
                } else {
                    return 0;
                }
            } catch (Exception $exception){
                return 3;
            }
        } else {
            return 1;
        }
    }

    public function deletePreviousEmailOTP($email){
        $stmt = $this->con->prepare("DELETE FROM email_otp WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }

    public function verifyEmailOtp($email, $otp){
        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("SELECT otp_id from email_otp WHERE email = ? AND otp = ? AND ? <= DATE_ADD(time_sent, INTERVAL 10 MINUTE) AND retry_count <=3");
        $stmt->bind_param("sss", $email, $otp, $timestamp);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    public function addEmailOTPRetryCount($email, $otp){
        $stmt = $this->con->prepare("UPDATE email_otp SET retry_count = retry_count + 1 WHERE email = ?");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $stmt->close();
    }

    public function checkIfUserExists($email, $phone){
        $stmt = $this->con->prepare("SELECT user_id from users WHERE (email = ? OR phone = ?) AND registered = 1");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    public function getUserDetailsLogin($phone, $device){
        $api_key = $this->generateApiKey();
        $userId = $this->getUserID($phone);
        $this-> addApiKey($userId, $api_key, $device);

        $stmt = $this->con->prepare("SELECT * FROM users WHERE phone = ? AND registered = 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetail = $result->fetch_assoc();
        $userTypeDetail = $this->getUserTypeDetails($userDetail['user_id'], $userDetail['type']);

        $message["userDetail"] = $userDetail;
        $message["userTypeDetail"] = $userTypeDetail;
        $message["api_key"] = $api_key;

        return $message;
    }

    public function getUserDetails($phone){
        $stmt = $this->con->prepare("SELECT * FROM users WHERE phone = ? AND registered = 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetail = $result->fetch_assoc();
        $userTypeDetail = $this->getUserTypeDetails($userDetail['user_id'], $userDetail['type']);

        $message["userDetail"] = $userDetail;
        $message["userTypeDetail"] = $userTypeDetail;

        return $message;
    }

    public function getUserDetailsByEmailLogin($email, $device){
        $api_key = $this->generateApiKey();
        $userId = $this->getUserIDByEmail($email);
        $this-> addApiKey($userId, $api_key, $device);

        $stmt = $this->con->prepare("SELECT * FROM users WHERE email = ? AND registered = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetail = $result->fetch_assoc();
        $userTypeDetail = $this->getUserTypeDetails($userDetail['user_id'], $userDetail['type']);

        $message["userDetail"] = $userDetail;
        $message["userTypeDetail"] = $userTypeDetail;
        $message["api_key"] = $api_key;

        return $message;
    }

    public function getUserDetailsByEmail($email){
        $stmt = $this->con->prepare("SELECT * FROM users WHERE email = ? AND registered = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetail = $result->fetch_assoc();
        $userTypeDetail = $this->getUserTypeDetails($userDetail['user_id'], $userDetail['type']);

        $message["userDetail"] = $userDetail;
        $message["userTypeDetail"] = $userTypeDetail;

        return $message;
    }

    public function getUserTypeDetails($user_id, $type){
        switch ($type) {
            case "Donor":
                $stmt = $this->con->prepare("SELECT * FROM donor WHERE user_id = ?");
                break;
            case "Volunteer":
                $stmt = $this->con->prepare("SELECT * FROM volunteer WHERE user_id = ?");
                break;
            case "Recipient":
                $stmt = $this->con->prepare("SELECT * FROM recipient WHERE user_id = ?");
                break;
        }

        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token;
    }

    public function getAllUserTypeDetails($type){
        switch ($type) {
            case "Donor":
                $stmt = $this->con->prepare("SELECT * FROM donor");
                break;
            case "Volunteer":
                $stmt = $this->con->prepare("SELECT * FROM volunteer");
                break;
            case "Recipient":
                $stmt = $this->con->prepare("SELECT * FROM recipient");
                break;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetail = array();
        while($token = $result->fetch_assoc()){
            array_push($userDetail, $token);
        }

        return $userDetail;
    }

    public function getAllDonors(){
        $stmt = $this->con->prepare("SELECT users.user_id, users.address, users.city, users.state, users.pincode, users.country, users.email, users.phone, users.reg_date, donor.donor_id, donor.donor_name_place, donor.donor_type, donor.is_verified, donor.is_volunteer, donor.donor_max_distance, (Select count(*) FROM orders WHERE donor.donor_id = orders.donor_id) as totalFoodCount, (Select sum(num_people) FROM foodpacket WHERE foodpacket.pkt_donor_id = donor.donor_id) as totalPeopleServed FROM users INNER JOIN donor ON users.user_id = donor.user_id");
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetail = array();
        while($token = $result->fetch_assoc()){
            array_push($userDetail, $token);
        }
        return $userDetail;
    }

    public function getAllRecipients(){
        $stmt = $this->con->prepare("SELECT users.user_id, users.address, users.city, users.state, users.pincode, users.country, users.email, users.phone, users.reg_date, recipient.recip_id, recipient.recipient_name_place, recipient.recipient_type, recipient.is_verified, recipient.is_volunteer, recipient.recipient_max_distance, (Select count(*) FROM orders WHERE recipient.recip_id = orders.recip_id) as totalFoodCount FROM users INNER JOIN recipient ON users.user_id = recipient.user_id LEFT JOIN orders ON recipient.recip_id = orders.recip_id");
        $stmt->execute();
        $result = $stmt->get_result();
        $userDetail = array();
        while($token = $result->fetch_assoc()){
            array_push($userDetail, $token);
        }
        return $userDetail;
    }

    public function storeLoginAttemptInformation($type, $credential, $device, $ipaddress, $login_status){
        if($type == 'email'){
            $user_id = $this->getUserIDByEmail($credential);
        } else {
            $user_id = $this->getUserID($credential);
        }

        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("INSERT INTO logins (user_id, login_time, ipaddress, login_status, device_detail, credential) values(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $user_id, $timestamp, $ipaddress, $login_status, $device, $credential);
        $stmt->execute();
        $stmt->close();
    }


    public function updateFcmId($fcm_id, $user_id, $apikey){
        if($this->doesAPIKeyExistInFCMTable($apikey, $user_id)){
            $stmt = $this->con->prepare("UPDATE fcmid SET fcm_id = ? WHERE user_id = ? AND api_key = ?");
            $stmt->bind_param("sss", $fcm_id, $user_id, $apikey);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } else {
            $timestamp = $this->getCurrentTimeStamp();
            $stmt = $this->con->prepare("INSERT INTO fcmid (user_id, fcm_id, api_key, timestamp) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user_id, $fcm_id, $apikey, $timestamp);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
    }

    public function updateUserLocation($latitude, $longitude, $userId, $userType, $typeId){
         switch ($userType){
            case 'Donor':
                if($this->getDonorActiveOrderCount($typeId) > 0){
                    return 1;
                }
                break;
            case "Recipient":
                if($this->getRecipientActiveOrderCount($typeId) > 0){
                    return 1;
                }
                break;
            case 'Volunteer':
                if($this->getVolunteerActiveOrderCount($typeId) > 0){
                    return 1;
                }
                break;
        }

        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("UPDATE users SET lati = ?, longi = ?, location_update_timestamp = ? WHERE user_id = ?");
        $stmt->bind_param("ssss", $latitude, $longitude, $timestamp, $userId);
        $stmt->execute();

        switch ($userType){
            case 'Donor':
                $stmt = $this->con->prepare("UPDATE donor SET donor_lati = ?, donor_longi = ? WHERE user_id = ?");
                break;
            case 'Volunteer':
                $stmt = $this->con->prepare("UPDATE volunteer SET volunteer_lati = ?, volunteer_longi = ? WHERE user_id = ?");
                break;
            case "Recipient":
                $stmt = $this->con->prepare("UPDATE recipient SET recipient_lati = ?, recipient_longi = ? WHERE user_id = ?");
                break;
        }

        $stmt->bind_param("sss", $latitude, $longitude, $userId);
        $stmt->execute();
        $stmt->close();

        return 0;
    }

    public function doesAPIKeyExistInFCMTable($apikey, $user_id){
        $stmt = $this->con->prepare("SELECT * from fcmid WHERE api_key = ? AND user_id = ?");
        $stmt->bind_param("ss", $apikey, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    public function getEmailVerificationStatus($phone){
        $stmt = $this->con->prepare("SELECT is_email_verified FROM users WHERE phone = ? LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['is_email_verified'];
    }

    public function invalidateApiKey($userId, $apiKey){
        $no = 'no';
        $stmt = $this->con->prepare("UPDATE apikey SET valid = ?, disabled_source = ? WHERE user_id = ? AND api_key = ?");
        $stmt->bind_param("ssss", $no, $this->apiKeyDisableSourceLogout, $userId, $apiKey);
        $stmt->execute();
        $stmt->close();
    }

    public function getEmailVerificationStatusByEmail($email){
        $stmt = $this->con->prepare("SELECT is_email_verified FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['is_email_verified'];
    }

    public function isValidUser($userId, $api_key) {
        $stmt = $this->con->prepare("SELECT * from apikey WHERE user_id = ? AND api_key = ? AND valid = 'yes'");
        $stmt->bind_param("ss", $userId, $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    public function isValidUserWithEmail($userId, $api_key, $email) {
        $stmt = $this->con->prepare("SELECT * from apikey WHERE user_id = ? AND api_key = ? AND valid = 'yes'");
        $stmt->bind_param("ss", $userId, $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if($num_rows > 0){
            $stmt = $this->con->prepare("SELECT * from users WHERE user_id = ? AND email = ?");
            $stmt->bind_param("ss", $userId, $email);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            return $num_rows > 0;
        } else {
            return false;
        }
    }

    public function isValidUserWithPhone($userId, $api_key, $phone) {
        $stmt = $this->con->prepare("SELECT * from apikey WHERE user_id = ? AND api_key = ? AND valid = 'yes'");
        $stmt->bind_param("ss", $userId, $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if($num_rows > 0){
            $stmt = $this->con->prepare("SELECT * from users WHERE user_id = ? AND phone = ?");
            $stmt->bind_param("ss", $userId, $phone);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            return $num_rows > 0;
        } else {
            return false;
        }
    }

    public function isValidUserWithDonorId($userId, $api_key, $donorId) {
        $stmt = $this->con->prepare("SELECT * from apikey WHERE user_id = ? AND api_key = ? AND valid = 'yes'");
        $stmt->bind_param("ss", $userId, $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if($num_rows > 0){
            $stmt = $this->con->prepare("SELECT * from donor WHERE user_id = ? AND donor_id = ?");
            $stmt->bind_param("ss", $userId, $donorId);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            return $num_rows > 0;
        } else {
            return false;
        }
    }

    public function isValidUserWithVolunteerId($userId, $api_key, $volunteerId) {
        $stmt = $this->con->prepare("SELECT * from apikey WHERE user_id = ? AND api_key = ? AND valid = 'yes'");
        $stmt->bind_param("ss", $userId, $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if($num_rows > 0){
            $stmt = $this->con->prepare("SELECT * from volunteer WHERE user_id = ? AND vol_id = ?");
            $stmt->bind_param("ss", $userId, $volunteerId);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            return $num_rows > 0;
        } else {
            return false;
        }
    }

    public function isValidUserWithRecipientId($userId, $api_key, $recipientId) {
        $stmt = $this->con->prepare("SELECT * from apikey WHERE user_id = ? AND api_key = ? AND valid = 'yes'");
        $stmt->bind_param("ss", $userId, $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if($num_rows > 0){
            $stmt = $this->con->prepare("SELECT * from recipient WHERE user_id = ? AND recip_id = ?");
            $stmt->bind_param("ss", $userId, $recipientId);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            return $num_rows > 0;
        } else {
            return false;
        }
    }

    public function getUserLatLong($userId){
        $stmt = $this->con->prepare("SELECT lati, longi FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getDonorLatLongMaxDistanceCity($userId){
        $stmt = $this->con->prepare("SELECT lati, longi, city, max_distance FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getRecipientLatLongMaxDistanceCity($userId){
        $stmt = $this->con->prepare("SELECT lati, longi, city, max_distance FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    //FoodRequests

    public function createFoodRequest($donorId, $nameOfFood, $foodType, $numberOfPeople, $foodReadyDate, $foodReadyTime, $foodValidity, $donorSelfDeliver, $device, $userId, $api_key){
        //TODO
        //Add rollback feature
        //max order

        if($this->getDonorActiveOrderCount($donorId) >= 200){
            return 2;
        }

        $timestamp = $this->getCurrentTimeStampDMY();
        $foodReadyTimestamp = $foodReadyDate. " " . $foodReadyTime;

        $result1 = $this->createFoodPacket($donorId, $nameOfFood, $foodType, $numberOfPeople, $foodReadyTimestamp, $foodValidity, $api_key);
        if(!$result1){
            return 0;
        }
        $pkt_id = $result1;

        $result2 = $this->createOrder($pkt_id, $donorId, $this->orderStatusCreated, $device, $timestamp, $donorSelfDeliver);
        if(!$result2){
            $this->deleteFoodPacket($pkt_id);
            return 1;
        }
        $order_id = $result2;

        $data["packetId"] = $pkt_id;
        $data["orderId"] = $order_id;
        $data["status"] = $this->orderStatusCreated;
        $data["timestamp"] = $timestamp;
        //TODO
        //Send notification through queue

        $this->syncDonorDevicesForNewFoodRequest($userId);
        $this->sendNewFoodRequestNotificationToRecipient($donorSelfDeliver, $this->getFoodDetails($order_id), $this->getDonorLatLongMaxDistanceCity($userId));

        return $data;
    }

    public function createFoodPacket($donorId, $nameOfFood, $foodType, $numberOfPeople, $foodReadyTimestamp, $foodValidity, $api_key){
        $stmt = $this->con->prepare("INSERT INTO foodpacket (pkt_donor_id, pkt_name, pkt_type, num_people, ready_time, food_validity, donor_api_key) values(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $donorId, $nameOfFood, $foodType, $numberOfPeople, $foodReadyTimestamp, $foodValidity, $api_key);
        $result = $stmt->execute();
        if(!$result){
            return false;
        } else {
            return  $stmt->insert_id;
        }
    }

    public function createOrder($pkt_id, $donorId, $status, $device, $timestamp, $donorSelfDeliver){
        if($donorSelfDeliver == "yes"){
            $deliveryAgentType = "Donor";
            $stmt = $this->con->prepare("INSERT INTO orders (pkt_id, donor_id, status, device_donor, donor_self_deliver, delivery_agent_type, timestamp) values(?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $pkt_id, $donorId, $status, $device, $donorSelfDeliver, $deliveryAgentType, $timestamp);
        } else {
            $stmt = $this->con->prepare("INSERT INTO orders (pkt_id, donor_id, status, device_donor, donor_self_deliver, timestamp) values(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $pkt_id, $donorId, $status, $device, $donorSelfDeliver, $timestamp);
        }
        $result = $stmt->execute();
        if(!$result){
            return false;
        } else {
            return $stmt->insert_id;
        }
    }

    public function donorAcceptedFoodOrderAction($userId, $apiKey, $donorId, $orderId, $action){
        $foodDetails = $this->getFoodDetails($orderId);
        if($foodDetails['pkt_donor_id'] == $donorId){
            switch ($foodDetails['status']){
                case $this->orderStatusDelivered:
                    return 4;
                case $this->orderStatusAcceptedRecipient:
                    if($action != $this->orderStatusDeliveryOnWay){
                        return 5;
                    } else {
                        $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND orders.status = ?");
                        $stmt->bind_param("sss", $this->orderStatusDeliveryOnWay, $orderId, $this->orderStatusAcceptedRecipient);
                        $stmt->execute();
                        if($stmt->affected_rows > 0){
                            $timestamp = $this->getCurrentTimeStampDMY();
                            $stmt = $this->con->prepare("INSERT INTO delivery (order_id, volunteer_time_accepted, delivery_status, vol_api_key) values (?, ?, ?, ?)");
                            $stmt->bind_param("ssss", $orderId, $timestamp, $this->orderStatusDeliveryOnWay, $apiKey);
                            $stmt->execute();
                            if($stmt->affected_rows > 0){
                                $foodDetails = $this->getFoodDetails($orderId);

                                $title = $foodDetails['donor_name_place'] . " has picked up the order";

                                $messageRecipient = 'They are on their way to deliver the order';

                                $this->sendFoodRequestUpdateNotificationToRecipient($title, $messageRecipient, $foodDetails);

                                $this->syncDonorDevicesForNewFoodRequest($userId);

                                return 0;
                            } else {
                                return 1;
                            }
                        } else {
                            return 8;
                        }
                    }
                case $this->orderStatusDeliveryOnWay:
                    if($action != $this->orderStatusDelivered){
                        return 6;
                    } else {
                        $timestamp = $this->getCurrentTimeStampDMY();
                        $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND orders.status = ?");
                        $stmt->bind_param("sss", $this->orderStatusDelivered, $orderId, $this->orderStatusDeliveryOnWay);
                        $stmt->execute();
                        if($stmt->affected_rows > 0){
                            $stmt = $this->con->prepare("UPDATE delivery SET delivery_status = ?, time_delivered = ? WHERE order_id = ?");
                            $stmt->bind_param("sss", $this->orderStatusDelivered, $timestamp, $orderId);
                            $stmt->execute();
                            if($stmt->affected_rows > 0){
                                $foodDetails = $this->getFoodDetails($orderId);

                                $title = $foodDetails['donor_name_place'] . " has delivered the order";

                                $messageRecipient = 'Happy Sharing!';

                                $this->sendFoodRequestUpdateNotificationToRecipient($title, $messageRecipient, $foodDetails);

                                $this->syncDonorDevicesForNewFoodRequest($userId);

                                return 2;
                            } else {
                                return 3;
                            }
                        } else {
                            return 9;
                        }
                    }
            }
        } else {
            return 7;
        }
    }


    public function donorSelfDeliverExpandRecipeint($userId, $donorId, $orderId){
        $foodDetails = $this->getFoodDetails($orderId);
        if($foodDetails['pkt_donor_id'] == $donorId){
            if($foodDetails['status'] == $this->orderStatusCreated){
                if($this->hasNotificationExpandedForOutsideRange($orderId)){
                    return 3;
                }
                $title = "Food Available for " . $foodDetails['num_people'] . " people";

                $donorDetails = $this->getDonorLatLongMaxDistanceCity($userId);
                $fcmIds = $this->getAllRecipientFCMByDonorDistanceCalculationOutside($donorDetails);

                $message = "Food request by " . $foodDetails['donor_name_place'];

                $mPushNotification = $this->getPushNotificationObject($title, $message, "", $foodDetails, "", "new_food_request_recipient", "Recipient");

                if(count($fcmIds) > 1){
                    $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
                } else {
                    $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
                }

                $this->setNotificationExpandedForOutsideRange($orderId);
                return 0;
            } else {
                return 1;
            }
        } else {
            return 2;
        }
    }

    public function hasNotificationExpandedForOutsideRange($orderId){
        $yes = "yes";
        $stmt = $this->con->prepare("SELECT * from orders WHERE notification_expanded = ? AND order_id = ?");
        $stmt->bind_param("ss", $yes, $orderId);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    public function setNotificationExpandedForOutsideRange($orderId){
        $yes = "yes";
        $stmt = $this->con->prepare("UPDATE orders SET notification_expanded = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $yes, $orderId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function deleteFoodPacket($pkt_id){
        $stmt = $this->con->prepare("DELETE FROM foodpacket WHERE pkt_id = ?");
        $stmt->bind_param("s", $pkt_id);
        $stmt->execute();
        $stmt->close();
    }

    public function syncDonorDevicesForNewFoodRequest($userId){
        $fcmIds = $this->getUserDevicesFCM($userId);
        $mPushNotification = $this->getPushNotificationObject("", "", "", "", "", "donor_food_update_self_device", "Donor");
        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        return $res;
    }

    public function sendNewFoodRequestNotificationToRecipient($donorSelfDeliver, $data, $donorDetails){
        $title = "Food Available for " . $data['num_people'] . " people";

        if($data['delivery_agent_type'] == "Donor"){
            $fcmIds = $this->getAllRecipientFCMByDonorDistanceCalculation($donorDetails);
            $message = "Food request by " . $data['donor_name_place'] . ". They will deliver the order themselves.";
        } else {
            $fcmIds = $this->getAllRecipientFCMByVolunteerDistanceCalculation($donorDetails);
            $message = "Food request by " . $data['donor_name_place'];
        }

        $mPushNotification = $this->getPushNotificationObject($title, $message, "", $data, "", "new_food_request_recipient", "Recipient");

        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }


        /**
        if($donorSelfDeliver == "yes"){
        $fcmIds1 = $this->getAllRecipientFCMByDonorDistanceCalculation($donorDetails);
        $message1 = "Food request by " . $data['donor_name_place'] . ". They will be delivering the order too.";
        $mPushNotification1 = $this->getPushNotificationObject($title, $message1, "", $data, "", "new_food_request_recipient", "Recipient");

        if(count($fcmIds1) > 1){
        $res1 = $this->sendPushNotificationMultiple($fcmIds1, $mPushNotification1);
        } else {
        $res1 = $this->sendPushNotificationSingle($fcmIds1[0], $mPushNotification1);
        }

        $fcmIds2 = $this->getAllRecipientFCMByVolunteerDistanceCalculation($donorDetails);
        $message2 = "Food request by " . $data['donor_name_place'];
        $mPushNotification2 = $this->getPushNotificationObject($title, $message2, "", $data, "", "new_food_request_recipient", "Recipient");

        if(count($fcmIds2) > 1){
        $res2 = $this->sendPushNotificationMultiple($fcmIds2, $mPushNotification2);
        } else {
        $res2 = $this->sendPushNotificationSingle($fcmIds2[0], $mPushNotification2);
        }

        $res = $res1 && $res2;
        } else {
        //TODO
        //Temporary getting all recipients in the city

        $fcmIds = $this->getAllRecipientFCMByVolunteerDistanceCalculation($donorDetails);
        $message = "Food request by " . $data['donor_name_place'];
        $mPushNotification = $this->getPushNotificationObject($title, $message, "", $data, "", "new_food_request_recipient", "Recipient");

        if(count($fcmIds) > 1){
        $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
        $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        }
         */

        return $res;
    }

    public function acceptFoodOrderByRecipient($userId, $apiKey, $recipientId, $orderId, $deviceRecipient, $recipientSelfAccept){

        //TODO
        //Get donor recip distance
        if($this->getRecipientActiveOrderCount($recipientId) >= 200){
            return 3;
        }
        $timestamp = $this->getCurrentTimeStampDMY();
        $foodDetails = $this->getFoodDetails($orderId);
        if($foodDetails['status'] == $this->orderStatusCreated){
            $deliveryAgentType = $foodDetails['delivery_agent_type'];
            $recipientDetails = $this->getRecipientLatLongMaxDistanceCity($userId);
            $donorRecipientDistance = $this->calculateDistance($recipientDetails['lati'], $recipientDetails['longi'], $foodDetails['donor_lati'], $foodDetails['donor_longi']);
            if($recipientSelfAccept == "yes"){
                $deliveryAgentType = "Recipient";
                $stmt = $this->con->prepare("UPDATE orders SET recip_id = ?, recip_api_key = ?, status = ?, recipient_time_accepted = ?, device_recipient = ?, recipient_self_accept = ?, delivery_agent_type = ?, donor_recipient_distance = ? WHERE order_id = ? AND status = ?");
                $stmt->bind_param("ssssssssss", $recipientId, $apiKey, $this->orderStatusAcceptedRecipient, $timestamp, $deviceRecipient, $recipientSelfAccept, $deliveryAgentType, $donorRecipientDistance, $orderId, $this->orderStatusCreated);
            } else {
                if($deliveryAgentType == "Donor" && $donorRecipientDistance > $foodDetails["donor_max_distance"] && $this->getTimeDifferentWithCurrentTime($foodDetails['timestamp']) <= 1130){
                    return 4;
                }
                if($deliveryAgentType == "Donor" && $donorRecipientDistance < $foodDetails["donor_max_distance"]){
                    $stmt = $this->con->prepare("UPDATE orders SET recip_id = ?, recip_api_key = ?, status = ?, recipient_time_accepted = ?, device_recipient = ?, recipient_self_accept = ?, donor_recipient_distance = ? WHERE order_id = ? AND status = ?");
                    $stmt->bind_param("sssssssss", $recipientId, $apiKey, $this->orderStatusAcceptedRecipient, $timestamp, $deviceRecipient, $recipientSelfAccept, $donorRecipientDistance, $orderId, $this->orderStatusCreated);
                } else {
                    $deliveryAgentType = "Volunteer";
                    $stmt = $this->con->prepare("UPDATE orders SET recip_id = ?, recip_api_key = ?, status = ?, recipient_time_accepted = ?, device_recipient = ?, recipient_self_accept = ?, delivery_agent_type = ?, donor_recipient_distance = ? WHERE order_id = ? AND status = ?");
                    $stmt->bind_param("ssssssssss", $recipientId, $apiKey, $this->orderStatusAcceptedRecipient, $timestamp, $deviceRecipient, $recipientSelfAccept, $deliveryAgentType, $donorRecipientDistance, $orderId, $this->orderStatusCreated);
                }
            }
            $stmt->execute();
            $row = $stmt->affected_rows;
            if($row > 0){
                $foodDetails = $this->getFoodDetails($orderId);
                $title = $foodDetails['recipient_name_place'] . " has accepted your order";
                if($deliveryAgentType == "Recipient"){
                    $message = 'They will be picking the food themselves. Click to view.';
                    $this->sendFoodRequestUpdateNotificationToDonor($title, $message, $foodDetails);
                } else if($deliveryAgentType == "Donor"){
                    $message = 'Please notify the restaurant when the food is ready to be delivered.';
                    $this->sendFoodRequestUpdateNotificationToDonor($title, $message, $foodDetails);
                } else {
                    $message = 'A volunteer will be assigned soon.';
                    $this->sendFoodRequestUpdateNotificationToDonor($title, $message, $foodDetails);
                    $this->sendNewFoodRequestNotificationToVolunteer($foodDetails, $recipientDetails['city']);
                }
                $this->syncRecipientDevicesForNewFoodRequest($userId);
                return 0;
            } else {
                return 1;
            }
        } else {
            return 2;
        }
    }

    public function getTimeDifferentWithCurrentTime($timestampStart){
        $since_start = $timestampStart->diff(new DateTime($this->getCurrentTimeStampDMY()));
        return $since_start->s;
    }

    public function recipientAcceptedFoodOrderAction($userId, $apiKey, $recipId, $orderId, $action){
        $foodDetails = $this->getFoodDetails($orderId);
        if($foodDetails['recip_id'] == $recipId){
            switch ($foodDetails['status']){
                case $this->orderStatusDelivered:
                    return 4;
                case $this->orderStatusAcceptedRecipient:
                    if($action != $this->orderStatusDeliveryOnWay){
                        return 5;
                    } else {
                        $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND orders.status = ?");
                        $stmt->bind_param("sss", $this->orderStatusDeliveryOnWay, $orderId, $this->orderStatusAcceptedRecipient);
                        $stmt->execute();
                        if($stmt->affected_rows > 0){
                            $timestamp = $this->getCurrentTimeStampDMY();
                            $stmt = $this->con->prepare("INSERT INTO delivery (order_id, volunteer_time_accepted, delivery_status, vol_api_key) values (?, ?, ?, ?)");
                            $stmt->bind_param("ssss", $orderId, $timestamp, $this->orderStatusDeliveryOnWay, $apiKey);
                            $stmt->execute();
                            if($stmt->affected_rows > 0){
                                $foodDetails = $this->getFoodDetails($orderId);

                                $title = $foodDetails['recipient_name_place'] . " is on their way to pick the order";

                                $messageRecipient = 'Click here to view details and contact';

                                $this->sendFoodRequestUpdateNotificationToDonor($title, $messageRecipient, $foodDetails);

                                $this->syncRecipientDevicesForNewFoodRequest($userId);

                                return 0;
                            } else {
                                return 1;
                            }
                        } else {
                            return 8;
                        }
                    }
                case $this->orderStatusDeliveryOnWay:
                    if($action != $this->orderStatusDelivered){
                        return 6;
                    } else {
                        $timestamp = $this->getCurrentTimeStampDMY();
                        $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND orders.status = ?");
                        $stmt->bind_param("sss", $this->orderStatusDelivered, $orderId, $this->orderStatusDeliveryOnWay);
                        $stmt->execute();
                        if($stmt->affected_rows > 0) {
                            $stmt = $this->con->prepare("UPDATE delivery SET delivery_status = ?, time_delivered = ? WHERE order_id = ?");
                            $stmt->bind_param("sss", $this->orderStatusDelivered, $timestamp, $orderId);
                            $stmt->execute();
                            if($stmt->affected_rows > 0){
                                $foodDetails = $this->getFoodDetails($orderId);

                                $title = $foodDetails['recipient_name_place'] . " has received the order";

                                $messageRecipient = 'Happy Sharing!';

                                $this->sendFoodRequestUpdateNotificationToDonor($title, $messageRecipient, $foodDetails);

                                $this->syncRecipientDevicesForNewFoodRequest($userId);

                                return 2;
                            } else {
                                return 3;
                            }
                        } else {
                            return 9;
                        }
                    }
            }
        } else {
            return 7;
        }
    }


    public function acceptFoodOrderByVolunteer($userId, $apiKey, $volunteerId, $orderId, $volunteerDevice){
        if($this->getVolunteerActiveOrderCount($volunteerId) > 200){
            return 4;
        }
        $timestamp = $this->getCurrentTimeStampDMY();
        $foodStatus = $this->getFoodStatus($orderId);
        if($foodStatus == $this->orderStatusAcceptedRecipient){
            $deliveryAgentType = "Volunteer";
            $stmt = $this->con->prepare("UPDATE orders SET status = ?, delivery_agent_type = ? WHERE order_id = ? AND orders.status = ?");
            $stmt->bind_param("ssss", $this->orderStatusAcceptedRecipientVolunteer, $deliveryAgentType, $orderId, $this->orderStatusAcceptedRecipient);
            $stmt->execute();
            $row = $stmt->affected_rows;
            if($row > 0){
                $stmt = $this->con->prepare("INSERT INTO delivery (order_id, vol_id, volunteer_time_accepted, delivery_status, vol_api_key, volunteer_device) values (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $orderId, $volunteerId, $timestamp, $this->orderStatusAcceptedRecipientVolunteer, $apiKey, $volunteerDevice);
                $stmt->execute();
                $row = $stmt->affected_rows;
                if($row > 0){
                    $foodDetails = $this->getFoodDetails($orderId);

                    $title = $foodDetails['volunteer_name'] . " has accepted your order";
                    $message = 'Click to view';
                    $this->sendFoodRequestUpdateNotificationToDonor($title, $message, $foodDetails);

                    $this->sendFoodRequestUpdateNotificationToRecipient($title, $message, $foodDetails);

                    $this->syncVolunteerDevicesForNewFoodRequest($userId);
                    return 0;
                } else {
                    return 1;
                }
            } else {
                return 2;
            }
        } else {
            return 3;
        }
    }

    public function calculateDistance($lat1, $long1, $lat2, $long2){
        $dLat = ($lat2 - $lat1) *
            M_PI / 180.0;
        $dLon = ($long2 - $long1) *
            M_PI / 180.0;

        // convert to radians
        $lat1 = ($lat1) * M_PI / 180.0;
        $lat2 = ($lat2) * M_PI / 180.0;

        // apply formulae
        $a = pow(sin($dLat / 2), 2) +
            pow(sin($dLon / 2), 2) *
            cos($lat1) * cos($lat2);
        $rad = 6371;
        $c = 2 * asin(sqrt($a));
        $dis = $rad * $c;
        return (1.45 * $dis);
    }
    public function sendNewFoodRequestNotificationToVolunteer($data, $city){
        $fcmIds = $this->getAllVolunteerFCMByCity($city);
        $title = "Food Available for " . $data['num_people'] . " people by " . $data['donor_name_place'];
        $message = "Food request accepted by " . $data['recipient_name_place'];
        $mPushNotification = $this->getPushNotificationObject($title, $message, "", $data, "", "new_food_request_volunteer", "Volunteer");
        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        return $res;
    }

    public function syncRecipientDevicesForNewFoodRequest($userId){
        $fcmIds = $this->getUserDevicesFCM($userId);
        $mPushNotification = $this->getPushNotificationObject("", "", "", "", "", "recipient_food_update_self_device", "Recipient");
        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        return $res;
    }

    public function sendFoodRequestUpdateNotificationToDonor($title, $message, $data){
        $fcmIds = $this->getUserDevicesFCM($this->getUserIDByDonorId($data['pkt_donor_id']));
        $mPushNotification = $this->getPushNotificationObject($title, $message, "", $data, "", "donor_food_request_update", "Donor");
        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        return $res;
    }

    public function sendFoodRequestUpdateNotificationToRecipient($title, $message, $data){
        $fcmIds = $this->getUserDevicesFCM($this->getUserIDByRecipientId($data['recip_id']));
        $mPushNotification = $this->getPushNotificationObject($title, $message, "", $data, "", "recipient_food_request_update", "Recipient");
        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        return $res;
    }

    public function syncVolunteerDevicesForNewFoodRequest($userId){
        $fcmIds = $this->getUserDevicesFCM($userId);
        $mPushNotification = $this->getPushNotificationObject("", "", "", "", "", "volunteer_food_update_self_device", "Volunteer");
        if(count($fcmIds) > 1){
            $res = $this->sendPushNotificationMultiple($fcmIds, $mPushNotification);
        } else {
            $res = $this->sendPushNotificationSingle($fcmIds[0], $mPushNotification);
        }
        return $res;
    }

    public function volunteerAcceptedFoodOrderAction($userId, $apiKey, $volunteerId, $orderId, $action){
        $foodDetails = $this->getFoodDetails($orderId);
        if($foodDetails['vol_id'] == $volunteerId){
            switch ($foodDetails['status']){
                case $this->orderStatusDelivered:
                    return 4;
                case $this->orderStatusAcceptedRecipientVolunteer:
                    if($action != $this->orderStatusDeliveryOnWay){
                        return 5;
                    } else {
                        $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND orders.status = ?");
                        $stmt->bind_param("sss", $this->orderStatusDeliveryOnWay, $orderId, $this->orderStatusAcceptedRecipientVolunteer);
                        $stmt->execute();
                        if($stmt->affected_rows > 0){
                            $stmt = $this->con->prepare("UPDATE delivery SET delivery_status = ? WHERE order_id = ?");
                            $stmt->bind_param("ss", $this->orderStatusDeliveryOnWay, $orderId);
                            $stmt->execute();
                            if($stmt->affected_rows > 0){
                                $foodDetails = $this->getFoodDetails($orderId);

                                $title = $foodDetails['volunteer_name'] . " has picked up your order";
                                $messageDonor = 'The order was picked at ' . $foodDetails['recipient_name_place'] . '.';
                                $messageRecipient = 'The order was picked at ' . $foodDetails['donor_name_place'] . '.';
                                $this->sendFoodRequestUpdateNotificationToDonor($title, $messageDonor, $foodDetails);

                                $this->sendFoodRequestUpdateNotificationToRecipient($title, $messageRecipient, $foodDetails);

                                $this->syncVolunteerDevicesForNewFoodRequest($userId);

                                return 0;
                            } else {
                                return 1;
                            }
                        } else {
                            return 1;
                        }
                    }
                case $this->orderStatusDeliveryOnWay:
                    if($action != $this->orderStatusDelivered){
                        return 6;
                    } else {
                        $timestamp = $this->getCurrentTimeStampDMY();
                        $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND orders.status = ?");
                        $stmt->bind_param("sss", $this->orderStatusDelivered, $orderId, $this->orderStatusDeliveryOnWay);
                        $stmt->execute();
                        if($stmt->affected_rows > 0){
                            $stmt = $this->con->prepare("UPDATE delivery SET delivery_status = ?, time_delivered = ? WHERE order_id = ?");
                            $stmt->bind_param("sss", $this->orderStatusDelivered, $timestamp, $orderId);
                            $stmt->execute();
                            if($stmt->affected_rows > 0){
                                $foodDetails = $this->getFoodDetails($orderId);

                                $title = $foodDetails['volunteer_name'] . " has delivered your order";
                                $messageDonor = 'The order was picked at ' . $foodDetails['recipient_name_place'] . '.';
                                $messageRecipient = 'The order was picked at ' . $foodDetails['donor_name_place'] . '.';
                                $this->sendFoodRequestUpdateNotificationToDonor($title, $messageDonor, $foodDetails);

                                $this->sendFoodRequestUpdateNotificationToRecipient($title, $messageRecipient, $foodDetails);

                                $this->syncVolunteerDevicesForNewFoodRequest($userId);

                                return 2;
                            } else {
                                return 3;
                            }
                        } else {
                            return 3;
                        }
                    }
            }
        } else {
            return 7;
        }
    }

    public function getDonorActiveOrderCount($donorId){
        $stmt = $this->con->prepare("SELECT count(*) as total from orders WHERE donor_id = ? AND (status = ? OR status = ? OR status = ? OR status = ?)");
        $stmt->bind_param("sssss", $donorId, $this->orderStatusCreated, $this->orderStatusAcceptedRecipient, $this->orderStatusAcceptedRecipientVolunteer, $this->orderStatusDeliveryOnWay);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    public function getRecipientActiveOrderCount($recipientId){
        $stmt = $this->con->prepare("SELECT count(*) as total from orders WHERE recip_id = ? AND (status = ? OR status = ? OR status = ?)");
        $stmt->bind_param("ssss", $recipientId, $this->orderStatusAcceptedRecipient, $this->orderStatusAcceptedRecipientVolunteer, $this->orderStatusDeliveryOnWay);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    public function getVolunteerActiveOrderCount($volunteerId){
        $stmt = $this->con->prepare("SELECT count(*) as total from delivery WHERE vol_id = ? AND (delivery_status = ? OR delivery_status = ?)");
        $stmt->bind_param("sss", $volunteerId, $this->orderStatusAcceptedRecipientVolunteer, $this->orderStatusDeliveryOnWay);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    public function getFoodDetails($orderId){
        $stmt = $this->con->prepare("SELECT foodpacket.pkt_id, foodpacket.pkt_donor_id, foodpacket.pkt_name, foodpacket.pkt_type, foodpacket.num_people, foodpacket.ready_time, foodpacket.food_validity, orders.status, orders.order_id, orders.recip_id, orders.timestamp, orders.recipient_time_accepted, orders.delivery_agent_type, orders.donor_self_deliver, orders.donor_recipient_distance, orders.recipient_self_accept, orders.notification_expanded, delivery.delivery_id, delivery.vol_id, delivery.volunteer_time_accepted, delivery.delivery_status, delivery.time_delivered, donor.donor_name_place, donor.donor_type, donor.donor_address, donor.donor_phone, donor.donor_lati, donor.donor_longi, donor.donor_max_distance, recipient.recipient_name_place, recipient.recipient_type, recipient.recipient_address, recipient.recipient_phone, recipient.recipient_lati, recipient.recipient_longi, recipient.recipient_max_distance, volunteer.volunteer_lati, volunteer.volunteer_longi, volunteer.volunteer_max_distance, volunteer.volunteer_name, volunteer.volunteer_phone FROM foodpacket LEFT JOIN orders ON foodpacket.pkt_id = orders.pkt_id LEFT JOIN donor ON orders.donor_id = donor.donor_id LEFT JOIN recipient ON orders.recip_id = recipient.recip_id LEFT JOIN delivery ON orders.order_id = delivery.order_id LEFT JOIN volunteer ON delivery.vol_id = volunteer.vol_id WHERE orders.order_id = ?");
        $stmt->bind_param("s", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token;
    }

    public function getAllFoodDetails($typeId, $type){
        if($type == 'Donor'){
            $stmt = $this->con->prepare("SELECT foodpacket.pkt_id, foodpacket.pkt_donor_id, foodpacket.pkt_name, foodpacket.pkt_type, foodpacket.num_people, foodpacket.ready_time, foodpacket.food_validity, orders.status, orders.order_id, orders.recip_id, orders.timestamp, orders.recipient_time_accepted, orders.delivery_agent_type, orders.donor_self_deliver, orders.donor_recipient_distance, orders.recipient_self_accept, orders.notification_expanded, delivery.delivery_id, delivery.vol_id, delivery.volunteer_time_accepted, delivery.delivery_status, delivery.time_delivered, donor.donor_name_place, donor.donor_type, donor.donor_address, donor.donor_phone, donor.donor_lati, donor.donor_longi, donor.donor_max_distance, recipient.recipient_name_place, recipient.recipient_type, recipient.recipient_address, recipient.recipient_phone, recipient.recipient_lati, recipient.recipient_longi, recipient.recipient_max_distance, volunteer.volunteer_lati, volunteer.volunteer_longi, volunteer.volunteer_max_distance, volunteer.volunteer_name, volunteer.volunteer_phone FROM foodpacket LEFT JOIN orders ON foodpacket.pkt_id = orders.pkt_id LEFT JOIN donor ON orders.donor_id = donor.donor_id LEFT JOIN recipient ON orders.recip_id = recipient.recip_id LEFT JOIN delivery ON orders.order_id = delivery.order_id LEFT JOIN volunteer ON delivery.vol_id = volunteer.vol_id WHERE foodpacket.pkt_donor_id = ? ORDER BY orders.pkt_id DESC");
        } else if($type == 'Volunteer'){
            $stmt = $this->con->prepare("SELECT foodpacket.pkt_id, foodpacket.pkt_donor_id, foodpacket.pkt_name, foodpacket.pkt_type, foodpacket.num_people, foodpacket.ready_time, foodpacket.food_validity, orders.status, orders.order_id, orders.recip_id, orders.timestamp, orders.recipient_time_accepted, orders.delivery_agent_type, orders.donor_self_deliver, orders.donor_recipient_distance, orders.recipient_self_accept, orders.notification_expanded, delivery.delivery_id, delivery.vol_id, delivery.volunteer_time_accepted, delivery.delivery_status, delivery.time_delivered, donor.donor_name_place, donor.donor_type, donor.donor_address, donor.donor_phone, donor.donor_lati, donor.donor_longi, donor.donor_max_distance, recipient.recipient_name_place, recipient.recipient_type, recipient.recipient_address, recipient.recipient_phone, recipient.recipient_lati, recipient.recipient_longi, recipient.recipient_max_distance, volunteer.volunteer_lati, volunteer.volunteer_longi, volunteer.volunteer_max_distance, volunteer.volunteer_name, volunteer.volunteer_phone FROM foodpacket LEFT JOIN orders ON foodpacket.pkt_id = orders.pkt_id LEFT JOIN donor ON orders.donor_id = donor.donor_id LEFT JOIN recipient ON orders.recip_id = recipient.recip_id LEFT JOIN delivery ON orders.order_id = delivery.order_id LEFT JOIN volunteer ON delivery.vol_id = volunteer.vol_id WHERE delivery.vol_id = ? ORDER BY orders.pkt_id DESC");
        } else if($type == 'Recipient'){
            $stmt = $this->con->prepare("SELECT foodpacket.pkt_id, foodpacket.pkt_donor_id, foodpacket.pkt_name, foodpacket.pkt_type, foodpacket.num_people, foodpacket.ready_time, foodpacket.food_validity, orders.status, orders.order_id, orders.recip_id, orders.timestamp, orders.recipient_time_accepted, orders.delivery_agent_type, orders.donor_self_deliver, orders.donor_recipient_distance, orders.recipient_self_accept, orders.notification_expanded, delivery.delivery_id, delivery.vol_id, delivery.volunteer_time_accepted, delivery.delivery_status, delivery.time_delivered, donor.donor_name_place, donor.donor_type, donor.donor_address, donor.donor_phone, donor.donor_lati, donor.donor_longi, donor.donor_max_distance, recipient.recipient_name_place, recipient.recipient_type, recipient.recipient_address, recipient.recipient_phone, recipient.recipient_lati, recipient.recipient_longi, recipient.recipient_max_distance, volunteer.volunteer_lati, volunteer.volunteer_longi, volunteer.volunteer_max_distance, volunteer.volunteer_name, volunteer.volunteer_phone FROM foodpacket LEFT JOIN orders ON foodpacket.pkt_id = orders.pkt_id LEFT JOIN donor ON orders.donor_id = donor.donor_id LEFT JOIN recipient ON orders.recip_id = recipient.recip_id LEFT JOIN delivery ON orders.order_id = delivery.order_id LEFT JOIN volunteer ON delivery.vol_id = volunteer.vol_id WHERE orders.recip_id = ? ORDER BY orders.pkt_id DESC");
        }
        $stmt->bind_param("s", $typeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $detail = array();
        while($token = $result->fetch_assoc()){
            array_push($detail, $token);
        }
        return $detail;
    }

    public function getActiveFoodRequests(){
        $stmt = $this->con->prepare("SELECT foodpacket.pkt_id, foodpacket.pkt_donor_id, foodpacket.pkt_name, foodpacket.pkt_type, foodpacket.num_people, foodpacket.ready_time, foodpacket.food_validity, orders.status, orders.order_id, orders.recip_id, orders.timestamp, orders.recipient_time_accepted, orders.delivery_agent_type, orders.donor_self_deliver, orders.donor_recipient_distance, orders.recipient_self_accept, orders.notification_expanded, delivery.delivery_id, delivery.vol_id, delivery.volunteer_time_accepted, delivery.delivery_status, delivery.time_delivered, donor.donor_name_place, donor.donor_type, donor.donor_address, donor.donor_phone, donor.donor_lati, donor.donor_longi, donor.donor_max_distance, recipient.recipient_name_place, recipient.recipient_type, recipient.recipient_address, recipient.recipient_phone, recipient.recipient_lati, recipient.recipient_longi, recipient.recipient_max_distance, volunteer.volunteer_lati, volunteer.volunteer_longi, volunteer.volunteer_max_distance, volunteer.volunteer_name, volunteer.volunteer_phone FROM foodpacket LEFT JOIN orders ON foodpacket.pkt_id = orders.pkt_id LEFT JOIN donor ON orders.donor_id = donor.donor_id LEFT JOIN recipient ON orders.recip_id = recipient.recip_id LEFT JOIN delivery ON orders.order_id = delivery.order_id LEFT JOIN volunteer ON delivery.vol_id = volunteer.vol_id WHERE orders.status = ? ORDER BY orders.pkt_id DESC");
        $stmt->bind_param("s", $this->orderStatusCreated);
        $stmt->execute();
        $result = $stmt->get_result();
        $detail = array();
        while($token = $result->fetch_assoc()){
            array_push($detail, $token);
        }
        return $detail;
    }

    public function getActiveFoodRequestsVolunteer(){
        $no = "no";
        $stmt = $this->con->prepare("SELECT foodpacket.pkt_id, foodpacket.pkt_donor_id, foodpacket.pkt_name, foodpacket.pkt_type, foodpacket.num_people, foodpacket.ready_time, foodpacket.food_validity, orders.status, orders.order_id, orders.recip_id, orders.timestamp, orders.recipient_time_accepted, orders.delivery_agent_type, orders.donor_self_deliver, orders.donor_recipient_distance, orders.recipient_self_accept, orders.notification_expanded, delivery.delivery_id, delivery.vol_id, delivery.volunteer_time_accepted, delivery.delivery_status, delivery.time_delivered, donor.donor_name_place, donor.donor_type, donor.donor_address, donor.donor_phone, donor.donor_lati, donor.donor_longi, donor.donor_max_distance, recipient.recipient_name_place, recipient.recipient_type, recipient.recipient_address, recipient.recipient_phone, recipient.recipient_lati, recipient.recipient_longi, recipient.recipient_max_distance, volunteer.volunteer_lati, volunteer.volunteer_longi, volunteer.volunteer_max_distance, volunteer.volunteer_name, volunteer.volunteer_phone FROM foodpacket LEFT JOIN orders ON foodpacket.pkt_id = orders.pkt_id LEFT JOIN donor ON orders.donor_id = donor.donor_id LEFT JOIN recipient ON orders.recip_id = recipient.recip_id LEFT JOIN delivery ON orders.order_id = delivery.order_id LEFT JOIN volunteer ON delivery.vol_id = volunteer.vol_id WHERE orders.status = ? AND orders.donor_self_deliver = ? AND orders.recipient_self_accept = ? ORDER BY orders.pkt_id DESC");
        $stmt->bind_param("sss", $this->orderStatusAcceptedRecipient, $no, $no);
        $stmt->execute();
        $result = $stmt->get_result();
        $detail = array();
        while($token = $result->fetch_assoc()){
            array_push($detail, $token);
        }
        return $detail;
    }

    public function getFoodStatus($orderId){
        $stmt = $this->con->prepare("SELECT status FROM orders WHERE order_id = ? LIMIT 1");
        $stmt->bind_param("s", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        return $token['status'];
    }


    //Helper
    public function getCurrentTimeStamp(){
        date_default_timezone_set("Asia/Kolkata");
        $timestamp = date("Y-m-d H:i:s");
        return $timestamp;
    }

    public function getCurrentTimeStampDMY(){
        date_default_timezone_set("Asia/Kolkata");
        $timestamp = date("d-m-Y H:i:s");
        return $timestamp;
    }

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

    public function getPushNotificationObject($title, $message, $url, $extra_info, $version_name, $type, $scope){
        $push = new Push($title, $message, $url, $extra_info, $version_name, $type, $scope);
        return $push->getPush();
    }

    public function sendPushNotificationSingle($deviceToken, $mPushNotification){
        //TODO
        // add a function to retry if fails
        $uid = $this->logNotificationToDatabase($mPushNotification);
        if(!$uid){
            return 'Error';
        }
        $firebase = new Firebase();
        $mPushNotification['data']['notification_id'] = $uid;
        $mPushNotification['data']['timestamp'] = $this->getCurrentTimeStampDMY();
        $result = $firebase->sendSingle($deviceToken, $mPushNotification);
        $this->updateNotificationSentResult($result, $uid);
        return $result;
    }

    public function sendPushNotificationMultiple($deviceToken, $mPushNotification){
        //TODO
        // add a function to retry if fails
        $uid = $this->logNotificationToDatabase($mPushNotification);
        if(!$uid){
            return 'Error';
        }
        $firebase = new Firebase();
        $mPushNotification['data']['notification_id'] = $uid;
        $mPushNotification['data']['timestamp'] = $this->getCurrentTimeStampDMY();
        $result = $firebase->send($deviceToken, $mPushNotification);
        $this->updateNotificationSentResult($result, $uid);
        return $result;
    }

    public function updateNotificationSentResult($result, $uid){
        //TODO
        //Filter data from result
        $stmt = $this->con->prepare("UPDATE notifications SET sent_status = ? WHERE noti_id = ?");
        $stmt->bind_param("ss", $result, $uid);
        $stmt->execute();
        $row = $stmt->affected_rows;
        $stmt->close();
        return $row > 0;
    }

    public function logNotificationToDatabase($data){
        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("INSERT INTO notifications (title, message, url, extra_info, version_name, type, scope, timestamp) values(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $data['data']['title'], $data['data']['message'], $data['data']['url'], $data['data']['extra_info'], $data['data']['version_name'], $data['data']['type'], $data['data']['scope'], $timestamp);
        $result = $stmt->execute();
        if(!$result){
            return false;
        } else {
            return  $stmt->insert_id;
        }
    }

    public function reportBug($userId, $title, $description, $device){
        $timestamp = $this->getCurrentTimeStamp();
        $stmt = $this->con->prepare("INSERT INTO reportedbugs (user_id, title, description, device, timestamp) values(?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $userId, $title, $description, $device, $timestamp);
        return $stmt->execute();
    }

    public function generateOtp(){
        return rand(100000, 999999);
    }

    public function generateRandomToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); // edited

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max-1)];
        }

        return $token;
    }

    public function crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);
        return $min + $rnd;
    }

    public function generateApiKey(){
        return $this->generateRandomToken(32);
    }

    public function getUserDevicesFCM($userId){
        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id = ? AND api_key IN (SELECT api_key FROM apikey WHERE user_id = ? AND valid = 'yes')");
        $stmt->bind_param("ss", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }

    public function getAllRecipientFCM(){
        //TODO
        //Check for valid FCM ID and API Key
        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM recipient)");
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }

    public function getAllRecipientFCMBySameCity(){
        //TODO
        //Check for valid FCM ID and API Key
        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE type = 'Recipient')");
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }

    public function getAllRecipientFCMByDonorDistanceCalculation($baseLocation){
        $baseLati = $baseLocation['lati'];
        $baseLongi = $baseLocation['longi'];
        $maxDistance = $baseLocation['max_distance'];

        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE (1.45 * getDistance(lati, longi, ?, ?)) <= ? AND type = 'Recipient')");
        $stmt->bind_param("sss", $baseLati, $baseLongi, $maxDistance);
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }

    public function getAllRecipientFCMByDonorDistanceCalculationOutside($baseLocation){
        $baseLati = $baseLocation['lati'];
        $baseLongi = $baseLocation['longi'];
        $maxDistance = $baseLocation['max_distance'];

        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE (1.45 * getDistance(lati, longi, ?, ?)) > ? AND type = 'Recipient')");
        $stmt->bind_param("sss", $baseLati, $baseLongi, $maxDistance);
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }


    public function getAllRecipientFCMByVolunteerDistanceCalculation($baseLocation){
        //TODO

        //$baseLati = $baseLocation['lati'];
        //$baseLongi = $baseLocation['longi'];
        //$volunteerData = $this->getMaxVolunteerLatiLongiDistanceFromDonor($baseLocation['city']);

        //$stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE (1.45 * getDistance(lati, longi, ?, ?)) + (1.45 * getDistance(?, ?, ?, ?)) <= ? AND type = ?)");
        //$stmt->bind_param("ssssssss", $baseLati, $baseLongi, $baseLati, $baseLongi, $volunteerData['lati'], $volunteerData['longi'], $volunteerData['max_distance'], $recipient);

        //TODO
        // Getting all recipient in the city
        $recipient = "Recipient";

        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE type = ? AND city = ?)");
        $stmt->bind_param("ss", $recipient, $baseLocation['city']);
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }

    public function getMaxVolunteerLatiLongiDistanceFromDonor($donorCity){
        //"SELECT tt.* FROM topten tt INNER JOIN (SELECT home, MAX(datetime) AS MaxDateTime FROM topten GROUP BY home) groupedtt  ON tt.home = groupedtt.home AND tt.datetime = groupedtt.MaxDateTime"
        $volunteer = "Volunteer";
        $stmt = $this->con->prepare("SELECT u.max_distance, u.lati, u.longi FROM users u JOIN (SELECT MAX(max_distance) AS volmax, user_id FROM users) max ON u.user_id = max.user_id WHERE type = ? AND city = ?");
        $stmt->bind_param("ss", $volunteer, $donorCity);

        $stmt->execute();
        $result = $stmt->get_result();
        $detail = array();
        while($token = $result->fetch_assoc()){
            array_push($detail, $token);
        }

        return $detail;

       /**
       $volunteer = "Volunteer";
       $stmt = $this->con->prepare("SELECT u.max_distance, u.lati, u.longi FROM users WHERE type = ? AND city = ?");
       $stmt->bind_param("ss", $volunteer, $donorCity);

       $stmt->execute();
       $result = $stmt->get_result();
       $detail = array();
       while($token = $result->fetch_assoc()){
       array_push($detail, $token);
       }

       return $detail;
        */
    }

    public function getTestRecipientList($maxDistance, $userId){
        return $this->getAllRecipientFCMByDistanceCalculationTest($maxDistance, $this->getDonorLatLongMaxDistanceCity($userId));
    }

    public function getAllRecipientFCMByDistanceCalculationTest($maxDistanceValue, $baseLocation){
        $baseLati = $baseLocation['lati'];
        $baseLongi = $baseLocation['longi'];
        $maxDistance = $baseLocation['max_distance'];
        $correctedMaxDistance = $maxDistance + (0.45 * $maxDistance);
        $check = 10;

        $stmt = $this->con->prepare("SELECT user_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE (1.45 * getDistance(lati, longi, ?, ?)) <= ? AND type = 'Recipient')");
        $stmt->bind_param("sss", $baseLati, $baseLongi, $maxDistanceValue);
        $stmt->execute();
        $result = $stmt->get_result();

        $detail = array();
        while($token = $result->fetch_assoc()){
            array_push($detail, $token);
        }

        return $detail;
    }

    public function getAllVolunteerFCM(){
        //TODO
        //Check for valid FCM ID and API Key
        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE type = 'Volunteer')");
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }

    public function getAllVolunteerFCMByCity($recipientCity){
        //TODO
        //Check for valid FCM ID and API Key
        $stmt = $this->con->prepare("SELECT fcm_id FROM fcmid WHERE user_id IN (SELECT user_id FROM users WHERE type = 'Volunteer' AND city = ?)");
        $stmt->bind_param("s", $recipientCity);
        $stmt->execute();
        $result = $stmt->get_result();
        $fcmIDs = array();
        while($token = $result->fetch_assoc()){
            array_push($fcmIDs, $token['fcm_id']);
        }
        return $fcmIDs;
    }
}