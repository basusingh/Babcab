<?php
$res = '{
"kind": "identitytoolkit#GetAccountInfoResponse",
"users": [
  {
"localId": "1g5nHFTT77VuUAJYSr4GetRdQ9k2",
"providerUserInfo": [
  {
"providerId": "phone",
"rawId": "+917654393954",
"phoneNumber": "+917654393954"
}
],
"lastLoginAt": "1598741129340",
"createdAt": "1596726721175",
"phoneNumber": "+917654393954",
"lastRefreshAt": "2020-08-29T22:45:29.340Z"
}
],
}';

$obj = json_decode($res);
$data = $obj->users;
$users = $data[0];
$mobile = $users->phoneNumber;