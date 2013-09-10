<?php
/*
 * File: userController.php
 * Holds: The UserController-class with all the methods for the user-calls
 * Written by: Thomas Gautvedt
 * Last updated: 02.06.13
 * Project: GeoParty-REST
 * 
*/

//
// The REST-class doing most of the magic
//

class UserController extends REST {

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
    // Api-methods
    //

    // Returning the current user
    protected function get_user_me() {
        // Just calling the get_user_id-method with the ID for this user
        return $this->get_user_id($this->id);
    }

    // Returning information about the given user
    protected function get_user_id($id) {
        $get_user = "SELECT id, email, name, ".(($id == $this->id)?"lng, lat,":"")." last_seen, picture
        FROM user 
        WHERE id = :id";
        
        $get_user_query = $this->db->prepare($get_user);
        $get_user_query->execute(array(':id' => $id));
        $row = $get_user_query->fetch(PDO::FETCH_ASSOC);
        
        // Check to see if the got a user or not
       if (isset($row['id']) and strlen($row['id']) > 0) {
            return $row;
        }
        else {
            $this->setReponseState(131, 'No such user');
            return false;
        }
    }

    // Method that updates the position for the current user
    protected function put_user_me() {
        // Pasing the post-variables for this method
        $sql_vars = $this->pdo_parsing(array('name','lng','lat','picture','display_pos', 'device_type', 'ios_device_token', 'android_device_token'),$_POST,'update',true);

        // Adding the rest of the params for the binding
        $sql_vars['sql_qry'] .= ', last_seen = NOW()';

        // Creating the sql-statement itself
        $update_user = "UPDATE user
        SET ".$sql_vars['sql_qry']."
        WHERE id = :id";
        
        $update_user_query = $this->db->prepare($update_user);
        $update_user_query->execute($sql_vars['execute_arr']);
        
        // Returns true if the call was successful and false otherwise
        return true;
    }
    
    // Method to get the feed for a user
    protected function get_user_feed() {
        // Getting the correct offset
        if (!isset($_GET['offset']) or !is_numeric($_GET['offset']))
            $offset = 0;
        else
            $offset = $_GET['offset'];
        
        // Getting the latest statuses from the database, based on what the current user can see
        $statuses = array();
        $get_feed = "SELECT s.*, u.name, g.name as 'group_name'
        FROM status s
        LEFT JOIN user AS u ON s.uid = u.id
        LEFT JOIN groups AS g ON s.gid = g.id
            WHERE (s.gid IN (
                SELECT m.gid 
                FROM membership m
                WHERE m.uid = :id
            )
            OR s.uid IN (
                SELECT id2
                FROM friends 
                WHERE id1 = :id
            )
        )
        AND s.expire > NOW()
        ORDER BY s.posted DESC
        LIMIT $offset, 20";
        
        $get_feed_query = $this->db->prepare($get_feed);
        $get_feed_query->execute(array(':id' => $this->id));
        while ($row = $get_feed_query->fetch(PDO::FETCH_ASSOC)) {
            // Supplying the name Friends if it is actually a friend
            if ($row['group_name'] == null) {
                $row['group_name'] = 'Friends';
            }
            
            $statuses[] = $row;
        }
        
        // Returns true if the call was successful and false otherwise
        return array('result_statuses' => count($statuses), 'statuses' => $statuses);;
    }
    
    // Method to display the main map with selected groups
    protected function get_user_map() {
        return $this->put_user_map();
    }
    
    // Method to display the main map with selected groups
    protected function put_user_map() {
        $groups_query_string = '';
        
        // Checking if the groups were sent, or if we should fetch it from the database
        if (isset($_POST['groups'])) {
            // The groups are here
            $groups = $_POST['groups'];
            
            // TODO, update the current display_groups_map
            $update_display_groups_map = "UPDATE user
            SET display_groups_map = :groups
            WHERE id = :id";
            
            $update_display_groups_map_query = $this->db->prepare($update_display_groups_map);
            $update_display_groups_map_query->execute(array(':groups' => $groups, ':id' => $this->id));
        }
        else {
            // No groups supplied, do a query
            $get_display_groups_map = "SELECT display_groups_map
            FROM user
            WHERE id = :id";
            
            $get_display_groups_map_query = $this->db->prepare($get_display_groups_map);
            $get_display_groups_map_query->execute(array(':id' => $this->id));
            $row = $get_display_groups_map_query->fetch(PDO::FETCH_ASSOC);
            $groups = $row['display_groups_map'];
        }
        
        // Checking if we actually got some groups
        if (strlen($groups) > 0) {
            // The actuall query getting all the markers
            $statuses = array();
            $get_statuses = "SELECT s.id, s.uid, s.gid, s.type, s.posted, s.lat, s.lng, s.color, s.text, s.address, u.name, g.name as 'group_name'
            FROM status s
            LEFT JOIN user AS u ON s.uid = u.id
            LEFT JOIN groups AS g ON s.gid = g.id
            WHERE (s.gid IN (
                    SELECT m.gid 
                    FROM membership m
                    WHERE m.uid = :id
                    AND m.gid IN (".$groups.")
                )
                OR s.uid IN (
                    SELECT id2
                    FROM friends 
                    WHERE id1 = :id
                )
            )
            AND s.gid IN (".$groups.")
            AND s.type = '1'
            AND s.expire > NOW()
            ORDER BY s.posted DESC";
            
            $get_statuses_query = $this->db->prepare($get_statuses);
            $get_statuses_query->execute(array(':id' => $this->id));
            while ($row = $get_statuses_query->fetch(PDO::FETCH_ASSOC)) {
                $statuses[] = $row;
            }
            
            // The acutal query for all the positions for the people in all the groups TODO MANGER VENNER
            $users = array();
            $get_users = "SELECT u.id, u.name, u.lat, u.lng, u.last_seen
            FROM user u
            LEFT JOIN groups AS g ON u.id = g.id
            WHERE (g.id IN (
                    SELECT m.gid 
                    FROM membership m
                    WHERE m.uid = :id
                    AND m.display_pos = '1'
                    AND m.gid IN (".$groups.")
                    AND m.display_pos = '1'
                )
            )
            AND u.id != :id
            AND u.display_pos = '1'
            ORDER BY u.last_seen DESC";
            
            $get_users_query = $this->db->prepare($get_users);
            $get_users_query->execute(array(':id' => $this->id, ':groups' => $groups));
            while ($row = $get_users_query->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $row;
            }
            
            return array('result_users' => count($users), 'result_statuses' => count($statuses), 'users' => $users, 'statuses' => $statuses);
        }
        else {
            $this->setReponseState(161, 'No groups selected');
            return false;
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