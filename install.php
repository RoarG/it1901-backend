<?php
/*
 * File: install.php
 * Holds: Checks if system is up do date. Possible to sync the database etc
 * Last updated: 10.09.13
 * Project: Prosjekt1
 * 
*/

//
// Validate system
//

$validate_system = '';
$local_exists = true;
$db_connected = true;
$all_ok = false;

// Validate PHP
if (version_compare(phpversion(), '5.2.10', '<')) {
    $validate_system .= '<p style="color: red">ERROR: PHP-version is '.phpversion().', should be at least 5.2.10</p>';
}
else {
    $validate_system .= '<p style="color: green">OK: PHP-version is '.phpversion().', must be at least 5.2.10</p>';
}

// Validate PDO
if (!defined('PDO::ATTR_DRIVER_NAME')) {
    $validate_system .= '<p style="color: red">ERROR: PDO is not installed</p>';
}
else {
    $validate_system .= '<p style="color: green">OK: PDO is installed</p>';
}

// Local.php exists
if (!file_exists(dirname(__FILE__).'/local.php')) {
    $validate_system .= '<p style="color: red">ERROR: local.php does not exist</p>';
    $local_exists = false;
}
else {
    require_once "local.php";
    $validate_system .= '<p style="color: green">OK: local.php does exist</p>';
}

// Connect to database
if ($local_exists) {
    try {
        $db = new PDO("mysql:host=".DATABASE_HOST.";dbname=".DATABASE_TABLE, DATABASE_USER, DATABASE_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    } catch (Exception $e) {
        $validate_system .= '<p style="color: red">ERROR: Could not connect to database</p>';
        $db_connected = false;
    }
    
    if ($db_connected) {
        $validate_system .= '<p style="color: green">OK: Connected to database</p>';
    }
}
else {
    $validate_system .= '<p style="color: red">ERROR: Could not connect to database</p>';
    $db_connected = false;
}

// Check tables
if ($db_connected) {
    $results = $db->query("SHOW TABLES LIKE 'log'");
    if(!$results) {
       $validate_system .= '<p style="color: red">ERROR: Table does not exist</p>';     
    }
    else {
        if($results->rowCount() > 0){
            $validate_system .= '<p style="color: green">OK: Table does exist</p>';
            $all_ok = true;
        }
        else {
            $validate_system .= '<p style="color: red">ERROR: Table does not exist</p>';
        }
    }
}
else {
    $validate_system .= '<p style="color: red">ERROR: Table might not exist</p>';
}

//
// Syncdb
//


if (isset($_POST['sync'])) {
    //
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>G6 - Backend install</title>
</head>
<body>
<h1>G6</h1>
<p>Lorem ipsum</p>
<h1>Validate system</h1>
<?php echo $validate_system; ?>
<h1>Sync database</h1>
<p>While developing, syncing the database is required from time to time.<br />Sync by clicking the button under. Note that the entire database will be overwritten.</p>
<form action="" method="post">
    <input type="submit" value="Sync" name="sync" <?php echo ((!$all_ok)?'disabled="disabled"':''); ?> /> <?php echo ((!$all_ok)?'<span style="color: red; font-size: 13px;">You may only sync if there are no problems with the system</span>':''); ?> 
</form>
</body>
</html>