<?php
/*
 * File: chipController.php
 * Holds: The chipController-class with all the updates from a chip mounted on a sheep
 * Last updated: 04.11.13
 * Project: Prosjekt1
 * 
*/

//
// The REST-class doing most of the magic
//

class ChipController extends REST {

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
    // Send mail
    //
    
    private function send_mail ($type, $mails, $placeholders) {
        // Get the file-content
        $mail_html = file_get_contents(dirname(__FILE__).'/mail/'.$type.'.txt');
        
        // Replace the keywords
        $mail_html = utf8_decode(str_replace(array_keys($placeholders),array_values($placeholders), $mail_html));
        
        // Boundary 
        $innerboundary ="=_".time()."_=";
        $semi_rand = md5(time()); 
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

        // Mail-Header 
        $mail_head  = "MIME-Version: 1.0\n"; 
        $mail_head .= "From: noreply@sheep-locator.no\n"; 
        $mail_head .= "Reply-To: noreply@sheep-locator.no\n"; 
        $mail_head .= "X-Mailer: kmPHP-Mailer\n";
        $mail_head .= "Content-Type: multipart/alternative;\n\tboundary=\"".$innerboundary."\"\n";

        // Mail-subject
        $mail_subj = 'Sheep Locator - '.(($type == 'killed')?'Sau drept!':'Sau skadet!');
        
        // The body
        $mail_body  = "";
        $mail_body .= "\n--".$innerboundary."\n"; 
        $mail_body .= "Content-Type: text/html;\n\tcharset=\"iso-8859-1\"\n"; 
        $mail_body .= "Content-Transfer-Encoding: base64\n\n"; 
        $mail_body .= chunk_split(base64_encode(($mail_html)))."\n\n"; 
        $mail_body .= "\n--".$innerboundary."--\n"; 
        $mail_body .= "\n\n"; 
        
        // Send the actual mail
        foreach ($mails as $mail) {
            mail($mail, utf8_decode($mail_subj), $mail_body, $mail_head);
        }
    }

    //
    // Api-methods
    //
    
    // Update a sheep with chip-data
    protected function put_chip ($id) {
        // Check if the sheep and/or hash exists
        if (isset($_GET['sheep_token'])) {
            $get_sheep = "SELECT sh.*, sh_sys.system
            FROM sheep sh 
            LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
            LEFT JOIN system AS sys ON sh_sys.system = sys.id
            WHERE sys.sheep_token = :sheep_token
            AND sh.id = :id
            ORDER BY sh.id ASC";
            
            $get_sheep_query = $this->db->prepare($get_sheep);
            $get_sheep_query->execute(array(':sheep_token' => $_GET['sheep_token'], ':id' => $id));
            $row = $get_sheep_query->fetch(PDO::FETCH_ASSOC);
            
            // Checking if sheep exists
            if (!isset($row['id'])) {
                $this->setReponseState(141, 'No such sheep');
            }
            else {
                // Check what kind of update we are talking about
                if (!isset($_POST['type'])) {
                    // Missing update-type
                    $this->setReponseState(178, 'Missing update-type');
                }
                else {
                    // Check if valid update-type
                    if (in_array($_POST['type'], array('position', 'wounded', 'killed'))) {
                        if ($_POST['type'] != 'position') {
                            $mails = array();
                            $temp_mails = array();
                            
                            // Get list of mails we should notifiy!
                            $get_system_contact = "SELECT contact
                            FROM system
                            WHERE id = :id";
                            
                            $get_system_contact_query = $this->db->prepare($get_system_contact);
                            $get_system_contact_query->execute(array(':id' => $row['system']));
                            $contact_json = $get_system_contact_query->fetch(PDO::FETCH_ASSOC);
                            $contact_pure = json_decode($contact_json['contact'], true);
                            
                            // Get the contact for all the users connected to the system
                            $get_all_system_users = "SELECT us.email as 'epost'
                            FROM user us 
                            LEFT JOIN system_user AS sys_u ON sys_u.user = us.id
                            WHERE sys_u.system = :system";
                            
                            $get_all_system_users_query = $this->db->prepare($get_all_system_users);
                            $get_all_system_users_query->execute(array(':system' => $row['system']));
                            while ($rows = $get_all_system_users_query->fetch(PDO::FETCH_ASSOC)) {
                                // Adding the row to the array
                                $temp_mails[] = $rows;
                            }
                            
                            // Loop all mails and make sure noone is listed twice
                            if (count($contact_pure) > 0) {
                                foreach ($contact_pure as $v) {
                                    if (!in_array($v['epost'], $mails)) {
                                        $mails[] = $v['epost'];
                                    }
                                }
                            }
                            if (count($temp_mails) > 0) {
                                foreach ($contact_pure as $v) {
                                    if (!in_array($v['epost'], $mails)) {
                                        $mails[] = $v['epost'];
                                    }
                                }
                            }
                        }
                        
                        // Handle each type of update-type
                        if ($_POST['type'] == 'position') {
                            // Update position only
                            $post_sheep = "UPDATE sheep
                            SET lat = :lat,
                            lng = :lng
                            WHERE id = :id";
                            
                            $post_sheep_query = $this->db->prepare($post_sheep);
                            $post_sheep_query->execute(array(':lat' => $_POST['lat'], ':lng' => $_POST['lng'], ':id' => $id));
                        }
                        else if ($_POST['type'] == 'wounded') {
                            // Dummy update to force "last updated" to match
                            $post_sheep = "UPDATE sheep
                            SET alive = 1
                            WHERE id = :id";
                            
                            $post_sheep_query = $this->db->prepare($post_sheep);
                            $post_sheep_query->execute(array(':id' => $id));
                            
                            // Notification!
                            $send_notification = "INSERT INTO notification
                            (system, text, sheep)
                            VALUES (:system, :text, :sheep)";
                    
                            $send_notification_query = $this->db->prepare($send_notification);
                            $send_notification_query->execute(array(':system' => $row['system'], ':text' => $row['name']. ' (#'.$row['identification'].') er skadet!', ':sheep' => $id));
                            
                            // Mail
                            $this->send_mail($_POST['type'], $mails, array('{{NAVN}}' =>  $row['name']. ' (#'.$row['identification'].')',
                                                                           '{{TID}}' => date('G:i:s, d-m-Y'),
                                                                           '{{KOORDINATER}}' => '['.$row['lat'].','.$row['lng'].']'));
                        }
                        else {
                            // Sheep died!!!
                            $post_sheep = "UPDATE sheep
                            SET alive = 0
                            WHERE id = :id";
                            
                            $post_sheep_query = $this->db->prepare($post_sheep);
                            $post_sheep_query->execute(array(':id' => $id));
                            
                            // Notification!
                            $send_notification = "INSERT INTO notification
                            (system, text, sheep)
                            VALUES (:system, :text, :sheep)";
                    
                            $send_notification_query = $this->db->prepare($send_notification);
                            $send_notification_query->execute(array(':system' => $row['system'], ':text' => $row['name']. ' (#'.$row['identification'].') er drept!', ':sheep' => $id));
                            
                            $this->send_mail($_POST['type'], $mails, array('{{NAVN}}' =>  $row['name']. ' (#'.$row['identification'].')',
                                                                           '{{TID}}' => date('G:i:s, d-m-Y'),
                                                                           '{{KOORDINATER}}' => '['.$row['lat'].','.$row['lng'].']'));
                        }
                        
                        // Fetch the updates!
                        $get_sheep = "SELECT sh.*
                        FROM sheep sh 
                        LEFT JOIN system_sheep AS sh_sys ON sh_sys.sheep = sh.id
                        WHERE sh_sys.system = :system
                        AND sh_sys.sheep = :id
                        ORDER BY sh.id ASC";
                        
                        $get_sheep_query = $this->db->prepare($get_sheep);
                        $get_sheep_query->execute(array(':system' => $row['system'], ':id' => $id));
                        $row = $get_sheep_query->fetch(PDO::FETCH_ASSOC);
                        
                        return $row;
                    }
                    else {
                        // Invalid update-type
                        $this->setReponseState(179, 'Wrong update-type');
                    }
                }
            }
        }
        else {
            // Missing sheep_token
            $this->setReponseState(177, 'Missing sheep_token');
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