<?php
/*
 * File: userController.php
 * Holds: The UserController-class with all the methods for the user-calls
 * Last updated: 17.10.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class UserController extends REST {

    //
    // The constructor for this subclass
    //

    public function __construct($response) {
        // Loading the class-name, setting it in the REST-class, so we can check if it holds the method being called
        $this->className = get_class($this);

        // Calling RESTs constructor
        parent::__construct($response);
    }

    //
    // Api-methods
    //

    // Returning the current user
    protected function get_user() {
        $get_user = "SELECT id, email, name
        FROM user 
        WHERE id = :id";
        
        $get_user_query = $this->db->prepare($get_user);
        $get_user_query->execute(array(':id' => $this->id));
        $row = $get_user_query->fetch(PDO::FETCH_ASSOC);
        
        // Fetch systemname
        $get_system = "SELECT name
        FROM system 
        WHERE id = :system";
        
        $get_system_query = $this->db->prepare($get_system);
        $get_system_query->execute(array(':system' => $this->system));
        $temp_row = $get_system_query->fetch(PDO::FETCH_ASSOC);
        
        // Combine arrays
        $row['system'] = $temp_row['name'];
        
        // Check to see if the got a user or not
        if (isset($row['id']) and strlen($row['id']) > 0) {
            return $row;
        }
        else {
            $this->setReponseState(131, 'No such user');
            return false;
        }
    }
    
    // Update the current user
    protected function put_user() {
        // First check that we got everything we need
        if (strlen($_POST['email']) == 0 or strlen($_POST['name']) == 0 or strlen($_POST['system']) == 0) {
            $this->setReponseState(170, 'Missing data');
        }
        else {
            // Update user
            $update_user = "UPDATE user
            SET name = :name,
            email = :email
            WHERE id = :id";

            $update_user_query = $this->db->prepare($update_user);
            $update_user_query->execute(array(':name' => $_POST['name'], ':email' => $_POST['email'], ':id' => $this->id));
            
            // Update system
            $update_system = "UPDATE system
            SET name = :system
            WHERE id = :id";

            $update_system_query = $this->db->prepare($update_system);
            $update_system_query->execute(array(':system' => $_POST['system'], ':id' => $this->system));
        }
    }
    
    // Method to update the password for the current user
    protected function put_user_login() {
        // First check that we got everything we need
        if (strlen($_POST['current_password']) < 5 or strlen($_POST['new_password1']) < 5 or strlen($_POST['new_password2']) < 5) {
            $this->setReponseState(190, 'Missing data');
        }
        else {
            // Check if passwords match
            if ($_POST['new_password1'] != $_POST['new_password2']) {
                $this->setReponseState(191, 'Passwords do not match');
            }
            else {
                // Get old password so we can check if the input matches it
                $get_user = "SELECT pswd
                FROM user 
                WHERE id = :id";
                
                $get_user_query = $this->db->prepare($get_user);
                $get_user_query->execute(array(':id' => $this->id));
                $row = $get_user_query->fetch(PDO::FETCH_ASSOC);
                
                // Check if the current password was inputted correctly
                if ($row['pswd'] != $_POST['current_password']) {
                    $this->setReponseState(192, 'Old password is incorrect');
                }
                else {
                    $update_password = "UPDATE user
                    SET pswd = :pswd 
                    WHERE id = :id";
                    
                    $update_password_query = $this->db->prepare($update_password);
                    $update_password_query->execute(array(':pswd' => $_POST['new_password2'], ':id' => $this->id));
                }
            }
        }
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