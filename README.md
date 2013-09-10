G6 - Backend
============

Work in progress PHP REST-api.

Requirements
============

* PHP
* MySQL
* Apache2

How to use
==========

Download the files to your webserver and  
put the files in your local webroot-folder.

How to install
==============

You'll need an MySQL-database for this API to 
work. Once the database it online, you may copy 
the file local-example.php and rename it local.php. 
In this file you fill out your database info (login, 
name of table etc). Once all of this is done you may 
go to http://[webserver-location]/install.php and click 
on the 'Sync'-button. This will sync your database with 
the latest database from GitHub. Note that you need a 
working environment before you can sync. Required modules, 
versions etc are listed in the 'System info'-section  
in install.php. 