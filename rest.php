<?php
/*
 * File: rest.php
 * Holds: The REST-api with all its methods minus the actual api-methods being called
 * Last updated: 24.10.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class REST {

    //
    // The internal variables
    //

    protected $className; // Holding the name of the current class
    protected $db; // The PDO-wrapper
    protected $response = array(); // The response to be coded to json
    protected $id; // Holds the current user's id
    protected $system; // Holds the current system id
    protected $user_name; // Holds the name of the current user
    protected $method_url; // Holds the arrays with the path and method being called
    protected $master_password; // Holds the master-password for the master-methods

    //
    // Defining the different paths, methods nd what calls don't need an access_token
    //

    private $path = array(
        // Auth
        '/auth' => 'auth', // GET, PUT
        '/auth/validate' => 'auth_validate', // GET
        
        // User
        '/user' => 'user', // GET, PUT
        '/user/login' => 'user_login', // PUT
        
        // Sheep
        '/sheep' => 'sheep', // GET, POST
        '/sheep/(:id)' => 'sheep_single', // GET, PUT, DELETE
        
        // Map
        '/map' => 'map', // GET
        '/map/(:id)' => 'map_highlight', // GET
        
        // Notification
        '/notification' => 'notification', // GET
        '/notification/(:id)' => 'notification_page', // GET
        '/notification/dropdown' => 'notification_dropdown', // GET
        '/notification/num' => 'notification_num', // GET
        
        // Log
        '/log' => 'log', // GET
        '/log/(:id)' => 'log_page', // GET
        
        // Contact
        '/contact' => 'contact', // GET, PUT
        
        // Chip
        '/chip/(:id)' => 'chip', // PUT
    );

    private $ignore_no_at = array(
        '/auth' => 'put', // Logging in requires no access_token
        '/chip/(:id)' => 'put', // Updating sheep-info requires only the sheep-hash
    );

    //
    // Constructor
    //

    public function __construct($response) {
        // Setting initial response
        $this->response = $response;
        
        // Trying to connect to the database
        try {
            $this->db = new PDO("mysql:host=".DATABASE_HOST.";dbname=".DATABASE_TABLE, DATABASE_USER, DATABASE_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        } catch (Exception $e) {
            // Catch exception and returning the error
            $this->setReponseState(100, 'Could not connect to database');
            $this->db = null;
        }

        // Authenticate if database-connection was successful
        if ($this->db) {
            $this->doAuth();
        }
    }

    //
    // Authenticate the user
    //

    private function doAuth() {
        // Decode and prettify the method being requested
        $this->method_url = $this->getMethodUrl();
        
        if (!isset($_GET['method'])) {
            // No method-type has been decleared, returning error
            $this->setReponseState(112, 'No call method specified');
        }
        else {
            // Checking to see if we are dealing with an ignore-case
            if (array_key_exists($this->method_url['real'], $this->ignore_no_at)) {
                if ($this->ignore_no_at[$this->method_url['real']] == '*') {
                    // Ignore all method-types
                    $this->doRequest();
                }
                else {
                    if (strpos($this->ignore_no_at[$this->method_url['real']],',') !== false) {
                        // This ignore-case has several ignore-methods, explode it and check for the one being called
                        $ignore_methods = explode(',',$this->ignore_no_at[$this->method_url['real']]);
                        $ignore_was_found = false;

                        // Itterate over the ignore-methods
                        foreach ($ignore_methods as $v) {
                            if ($v == $_GET['method']) {
                                // The method currently being called was found in this methods' ignore-case.
                                $ignore_was_found = true;
                                break;
                            }
                        }

                        // If the method was found as one of the ignore-cases, execute the required. Otherwise, return errormessage to the user
                        if ($ignore_was_found) {
                            $this->doRequest();
                        }
                        else {
                            if (!isset($_GET['access_token']) or strlen($_GET['access_token']) < 5 or $_GET['access_token'] == '(null)') {
                                // Turns out this was not an ignore-case after all, and the access_token was not sat
                                $this->setReponseState(111, 'No access_token!');
                            }
                            else {
                                // Not an ignore-case and we have a access_token! Validate it
                                $access_token = $_GET['access_token'];
                                $this->checkToken($access_token);
                            }
                        }
                    }
                    else {
                        // This ignore-case only has one ignore-method, check to see if it matches the one being called or return error otherwise
                        if ($this->ignore_no_at[$this->method_url['real']] == $_GET['method']) {
                            $this->doRequest();
                        }
                        else {
                            if (!isset($_GET['access_token']) or strlen($_GET['access_token']) < 5 or $_GET['access_token'] == '(null)') {
                                // Turns out this was not an ignore-case after all, and the access_token was not sat
                                $this->setReponseState(111, 'No access_token!');
                            }
                            else {
                                // Not an ignore-case and we have a access_token! Validate it
                                $access_token = $_GET['access_token'];
                                $this->checkToken($access_token);
                            }
                        }
                    }
                }
            }
            else {
                // We are not dealing with an ignore-case. Decide what to do here.s
                if (!isset($_GET['access_token']) or strlen($_GET['access_token']) < 5 or $_GET['access_token'] == '(null)') {
                    // There's no access_token and we're not dealing with a ignore-case
                    $this->setReponseState(111, 'No access_token!');
                }
                else {
                    // Not an ignore-case and we have a access_token! Validate it
                    $access_token = $_GET['access_token'];
                    $this->checkToken($access_token);
                }
            }
        }
    }
    
    //
    // Access token
    //

    private function checkToken() {
        $get_access_token = "SELECT id, name
        FROM user
        WHERE access_token = :access_token";

        $get_token_query = $this->db->prepare($get_access_token);
        $get_token_query->execute(array(':access_token' => $_GET['access_token']));
        $row = $get_token_query->fetch(PDO::FETCH_ASSOC);

        if (isset($row['id']) and strlen($row['id']) > 0) {
            // Storing the current userid
            $this->id = $row['id'];
            
            // Storing the current name
            $this->user_name = $row['name'];
            
            // Load the current system and store the id for later
            $get_system = "SELECT sys.id
            FROM system sys
            LEFT JOIN system_user sys_usr ON sys_usr.system = sys.id
            WHERE sys_usr.user = :user_id";
            
            $get_system_query = $this->db->prepare($get_system);
            $get_system_query->execute(array(':user_id' => $this->id));
            $sys = $get_system_query->fetch(PDO::FETCH_ASSOC);
            
            // Storing the current systemid
            $this->system = $sys['id'];
            
            $this->doRequest();
        }
        else {
            // No user has this access_token
            $this->setReponseState(121, 'No user has this access_token');
        }
    }

    //
    // This function proccesses the request and finds out which method to call
    //

    private function doRequest() {
        // Only continue if we are dealing with the correct types of method-types
        if ($_GET['method'] == 'get' || $_GET['method'] == 'post' || $_GET['method'] == 'put' || $_GET['method'] == 'delete') {
            // We're good to go, continuing the request. Checking if the method exsists #1
            if (array_key_exists($this->method_url['real'], $this->path)) {
                $method_name = strtolower($_GET['method']).'_'.$this->path[$this->method_url['real']];

                // Checking if the method exsists #2
                if (method_exists($this->className,$method_name)) {
                    // Check to see if we have the required number of arguments represented
                    $ReflectionClass = new ReflectionClass($this->className);
                    if ($ReflectionClass->getMethod($method_name)->getNumberOfParameters() == count($this->method_url['args'])) {
                        // Setting the rest of the response-codes
                        $this->setReponseState(200, 'ok');

                        // The request goes into the response-element
                        $this->response['response'] = call_user_func_array(array($this, $method_name), $this->method_url['args']);
                    }
                    else {
                        // We're missing arguments for the function
                        $this->setReponseState(114, 'Required arguments missing');   
                    }                         
                }
                else {
                    $this->setReponseState(115, 'Unknown method');
                }
            }
            else {
                $this->setReponseState(101, 'Unknown method');
            }
        }
        else {
            $this->setReponseState(113, 'No such call method');
        }
    }
    
    //
    // Method to generate the url for matching patterns
    //
    
    private function getMethodUrl() {
        // Clean the path and matching the paths with the request
        $call_arr = array();
        $call_match = array();
        $call_args = array();
        
        // Checking if there is a query
        if (array_key_exists('q',$_GET)) {
            // Splitting on forward slash to investigate each of the elements
            $call_temp = explode('/',$_GET['q']);
            
            // Looping the elements
            foreach ($call_temp as $t) {
                // Clean out any elements that is actually empty
                if (strlen($t) > 0) {
                    // Dividing each arg based on the elements
                    if (is_numeric($t)) {
                        // We are dealing with a numeric element, this is placeholded by (:id) in the url-resolver
                        $call_match[] = '(:id)';
                        
                        // This is used as an argument in the method being called
                        $call_args[] = $t;
                    }
                    else {
                        // Just a regulare element
                        $call_match[] = $t;
                    }
                    
                    // Simply appending the element to the raw array
                    $call_arr[] = $t;
                }
            }
        }
        else {
            $call_match[] = '';
        }

        // Returning array with all the sub-arrays we just created
        return array('match' => $call_match, 
                    'args' => $call_args, 
                    'arr' => $call_arr, 
                    'real' => '/'.implode('/',$call_match));
    }

    //
    // Chcking if all the required parameters are set
    //

    protected function checkRequiredParams($arr,$source) {
        // Checking to see if we have some actual required params
        if (count($arr) > 0) {
            // We have required params, loop them
            $missing = false;

            // Looping the array, checking the source for its contents
            foreach ($arr as $v) {
                if (!array_key_exists($v,$source)) {
                    // If the required key is not in the source-array, we have a missing param
                    $missing = true;
                    break;
                }
            }
            // Returning the results
            return !$missing;
        }
        else {
            // No required params (this method should not have been called...)
            return true;
        }
    }

    //
    // Setting the error-code and msg
    //

    protected function setReponseState($c,$msg) {
        $this->response['code'] = $c;
        $this->response['msg'] = $msg;
    }
    
    //
    // Entering an log-entry to the database
    //
    
    protected function log($text) {
        $log = "INSERT INTO log
        (system, text)
        VALUES (:system, :text)";

        $log_query = $this->db->prepare($log);
        $log_query->execute(array(':system' => $this->system, ':text' => $text));
    }

    //
    // Printing the reponse
    //

    public function printResponse() {
        // Killing the database-connection
        $this->db = null;
        
        // Outputting the content
        echo json_encode($this->response);
    }
}
?>