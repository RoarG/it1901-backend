<?php
/*
 * File: authController.php
 * Holds: The AuthController-class with all the methods for the auth-calls
 * Written by: Thomas Gautvedt
 * Last updated: 02.06.13
 * Project: GeoParty-REST
 * 
*/

//
// The REST-class doing most of the magic
//

class AuthController extends REST {

    //
    // The constructor for this subclass
    //

    public function __construct() {
        // Loading the class-name, setting it in the REST-class, so we can check if it holds the method being called
        $this->className = get_class($this);

        // Calling RESTs constructor
        parent::__construct();
    }

    //
    // Hashing password
    //

    private function generateAccessToken($usern, $salt) {
        // Generate true random numbers
        $randomGen = new Random();
        $randomArr = $randomGen->getRnd();

        // Using the password_hash-function to hash the access_token
        $hash = 'geo'.password_hash(substr($usern,1).'+'.$randomArr[rand(0,(count($randomArr)-1))].'+'.rand(0,10000).time(), PASSWORD_BCRYPT, array('cost' => 12, 'salt' => $salt));
        return $hash;
    }

    //
    // Api-methods
    //
    
    // Logging the current user with the given access_token out of the system
    protected function get_auth() {
        // Update the access_token to null
        $reset_access_token = "UPDATE user
        SET access_token = ''
        WHERE access_token = :access_token";
        
        $reset_access_token_query = $this->db->prepare($reset_access_token);
        $reset_access_token_query->execute(array(':access_token' => $_GET['access_token']));
        
        // Empty return here
        return true;
    }
    
    // Logging in with password and username, and returning the new access_token
    protected function put_auth() {
        // Variable for returning values
        $ret = array();

        // Checking to see if we have all the parameters we need
        if ($this->checkRequiredParams(array('email','pswd'),$_POST)) {

            // We have all the parameters, is the "username" a valid email?
            if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $_POST['email'])) {
                // Valid email, let's try to login!
                $get_auth_values = "SELECT id, salt, display_pos, display_groups_map, access_token 
                FROM user 
                WHERE email = :email 
                AND pswd = :pswd";
                
                $get_auth_values_query = $this->db->prepare($get_auth_values);
                $get_auth_values_query->execute(array(':email' => $_POST['email'], ':pswd' => $_POST['pswd']));
                $row = $get_auth_values_query->fetch(PDO::FETCH_ASSOC);
                
                // Check if we have an actuall user here
                if (isset($row['id']) and strlen($row['id']) > 0) {
                    // Setting the ID
                    $this->id = $row['id'];

                    // Okey, the password and username is valid, let's return a hash!
                    $access_token = $this->generateAccessToken($_POST['email'], $row['salt']);

                    // Update the access_token in the database
                    $statement = $this->db->prepare("UPDATE user SET access_token = :access_token WHERE id = :id");
                    $statement->execute(array(':access_token' => $access_token, ':id' => $this->id));
                    
                    // Getting total number of invites pendling
                    $get_invites_user = "SELECT COUNT(id) as 'number_invites' 
                    FROM invite 
                    WHERE uid = :uid";
                    
                    $get_invites_user_query = $this->db->prepare($get_invites_user);
                    $get_invites_user_query->execute(array(':uid' => $this->id));
                    $invites_row = $get_invites_user_query->fetch(PDO::FETCH_ASSOC);
                    
                    // Returning successful message to user with the new access_token
                    $ret = array('access_token' => $access_token, 'id' => $row['id'], 'display_pos' => $row['display_pos'], 'display_groups_map' => $row['display_groups_map'], 'invites' => $invites_row['number_invites']);
                }
                else {
                    $this->setReponseState(131, 'No such user');
                }
            }
            else {
                $this->setReponseState(132, 'Password and/or username not set or incorrect');
            }
        }
        else {
            $this->setReponseState(132, 'Password and/or username not set or incorrect');
        }

        // Returning
        return $ret;
    }
    
    // Returning information about the given user
    protected function get_auth_validate() {
        $get_validate = "SELECT id, display_pos, display_groups_map, access_token
        FROM user
        WHERE id = :id";
        
        $get_validate_query = $this->db->prepare($get_validate);
        $get_validate_query->execute(array(':id' => $this->id));
        $row = $get_validate_query->fetch(PDO::FETCH_ASSOC);
        
        // Checking to see if the actual user exists
        if (isset($row['id']) and strlen($row['id']) > 0) {
            // Getting total number of invites pendling
            $get_invites_user = "SELECT COUNT(id) as 'number_invites' 
            FROM invite 
            WHERE uid = :uid";
            
            $get_invites_user_query = $this->db->prepare($get_invites_user);
            $get_invites_user_query->execute(array(':uid' => $this->id));
            $invites_row = $get_invites_user_query->fetch(PDO::FETCH_ASSOC);
            $row['invites'] = $invites_row['number_invites'];
            
            return $row;
        }
        else {
            $this->setReponseState(131, 'No such user');
            return;
        }
    }
    
        // Method that validates the current user
    /*
    protected function get_user_validate() {
        // This method checks the user-agent and checks if the device-token is set. This method runs at app-startup once
        $checkDevice = false;
        $deviceType = 1;
        $response = array();

        if(strstr($_SERVER['HTTP_USER_AGENT'],'CFNetwork')) {
            // iPhone or iPad-device
            $checkDevice = true;
        }
        else if (2 == 3) {
            // Android-device
            $checkDevice = true;
            $deviceType = 0;
        }

        if ($checkDevice) {
            $statement = $this->db->prepare("SELECT device_type, ios_device_token, android_device_token FROM user WHERE id = :id");
            $statement->execute(array(':id' => $this->id));
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if ($deviceType == $row['device_type']) {
                    if ($deviceType == 1) {
                        $response = array('state' => 'ok', 'token' => $row['ios_device_token']);
                    }
                    else if ($deviceType == 0) {
                        $response = array('state' => 'ok', 'token' => $row['android_device_token']);
                    }
                    else {
                        // Update device_type
                        $response = array('state' => 'error');
                    }
                }
                else {
                    // Update device_type
                }
            }
            else {
                $this->setReponseState(131, 'No such user');
                $response = array('state' => 'error');
            }
        }
        else {
            $response = array('state' => 'nodevice');
        }

        return $response;
    }*/
}

//
// Loading the class-name dynamically and creating an instance doing our magic
//

// Getting the current file-path
$path = explode('/',__FILE__);

// Including the run-script to execute it all
include_once "run.php";
?>