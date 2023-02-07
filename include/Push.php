<?php 

class Push {

    private $title;
    private $message;
    private $url;
    private $extra_info;
    private $version_name;
    private $type;
    private $scope;

    //initializing values in this constructor
    function __construct($title, $message, $url, $extra_info, $version_name, $type, $scope) {
         $this->title = $title;
         $this->message = $message; 
         $this->url = $url;
         $this->extra_info = $extra_info;
         $this->version_name = $version_name;
         $this->type = $type;
         $this->scope = $scope;
    }
    
    //getting the push notification
    public function getPush() {
        $res = array();
        $res['data']['title'] = $this->title;
        $res['data']['message'] = $this->message;
        $res['data']['url'] = $this->url;
        $res['data']['extra_info'] = $this->extra_info;
        $res['data']['version_name'] = $this->version_name;
        $res['data']['type'] = $this->type;
        $res['data']['scope'] = $this->scope;
        return $res;
    }
 
}