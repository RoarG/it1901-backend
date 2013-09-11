<?php
/*
 * File: authController.php
 * Holds: The AuthController-class with all the methods for the auth-calls
 * Last updated: 10.09.13
 * Project: Prosjekt1
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
                $get_auth_values = "SELECT id, salt, access_token 
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
                    
                    // Loading information about the current system
                    $get_system = "SELECT sys.* 
                    FROM system sys
                    LEFT JOIN system_user sys_usr ON sys_usr.system = sys.id
                    WHERE sys_usr.user = :id";
                    $get_system_query = $this->db->prepare($get_system);
                    $get_system_query->execute(array(':id' => $this->id));
                    $system = $get_system_query->fetch(PDO::FETCH_ASSOC);
                    
                    // Returning successful message to user with the new access_token
                    $ret = array('access_token' => $access_token, 'user_id' => $row['id'], 'system_id' => $system['id'], 'system_name' => $system['name']);
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
}

//
// Loading the class-name dynamically and creating an instance doing our magic
//

// Getting the current file-path
$path = explode('/',__FILE__);

// Including the run-script to execute it all
include_once "run.php";
?>