<?php
/*
+----------------------------------------------------------------+
| ./admin/auth-imap.php                                          |
|                                                                |
| This file is part of the Crystal Mail Client                   |
| Copyright (C) 2010, Crystal Mail Dev. Team - United States     |
|                                                                |
| Licensed under the GNU GPL                                     |
|                                                                |
| PURPOSE:                                                       |
|   Crystal Mail Admin Panel IMAP Authentication Script          |
|                                                                |
+----------------------- Studio 182 Team ------------------------+
| Hunter Dolan <hunter@crystalmail.net>                          |
+----------------------------------------------------------------+
*/
  
  //Start Session
  ini_set("display_errors", 0);
  session_start();
  
  include('../config/main.inc.php');
  
  //Append domain to email if not already there, check if the mail_domain var is even set
  if (isset($cmail_config['mail_domain'])) {
      //Check if the domain is already on the username by searching for the @ sign in the username
      if (strpos($cmail_config['admin_allowed'], '@')) {
          //If it is there we don't need to do anything
      } else {
          //If not lets add it
          $cmail_config['admin_allowed'] = $cmail_config['admin_allowed'] . '@' . $cmail_config['mail_domain'];
      }
  }
  if (file_exists('../skins/' . $cmail_config['skin'] . '/admin/login.php')) {
      $login = '../skins/' . $cmail_config['skin'] . '/admin/login.php';
  } else {
      $login = '../skins/crystal/admin/login.php';
  }
  
  
  
  
  if ($_GET['plugin'] == true) {
  } else {
  }
  //Check if we are logging out
  if ($_GET['_action'] == "logout") {
      session_destroy();
      echo '<meta http-equiv="refresh" content="0;url=index.php">';
      die();
  }
  
  //Check if there is already an active session for this user
  if (empty($_SESSION['user'])) {
      //Check if we are trying to login
      if (empty($_POST['user'])) {
          //If not show the login screen
          include($login);
          die();
      } else {
          //Append domain to email if not already there, check if the mail_domain var is even set
          if (isset($cmail_config['mail_domain'])) {
              //Check if the domain is already on the username by searching for the @ sign in the username
              if (strpos($_POST['user'], '@')) {
                  //If it is there we don't need to do anything
              } else {
                  //If not lets add it
                  $_POST['user'] = $_POST['user'] . '@' . $cmail_config['mail_domain'];
              }
          }
          //If yes try to authenticate with the IMAP server
          include_once("imap.inc.php");
          $imap = new IMAPMAIL;
          
          if (!$imap->open($cmail_config['default_host'], $cmail_config['default_port'])) {
              echo "Server Connection Failed!";
              exit;
          }
          $imap->login($_POST['user'], $_POST['pass']);
          //Try to pull up a simple folder to see if we are authenticated
          if (!$response = $imap->open_mailbox("INBOX")) {
              //If we can't pull it up send them back to the login screen
              $_GET = array();
              $_GET['failed'] = '1';
              include($login);
              die();
          } else {
              //If we can check to see if the user is on the allowed list (Multiple Admin Mode)
              if (in_array($_POST['user'], $cmail_config['admin_allowed'])) {
                  //If yes set some session vars
                  $_SESSION['last_active'] = time();
                  $_SESSION['user'] = $_POST['user'];
                  $_SESSION['pass'] = $_POST['pass'];
              }
              //If we can check to see if the user is on the allowed list (Single Admin Mode)
              elseif ($_POST['user'] == $cmail_config['admin_allowed']) {
                  //If yes set some session vars
                  $_SESSION['last_active'] = time();
                  $_SESSION['user'] = $_POST['user'];
                  $_SESSION['pass'] = $_POST['pass'];
              }
              
              else {
                  //If they are not on the list they will be sent to the login screen
                  $_GET = array();
                  $_GET['failed'] = '2';
                  include($login);
                  die();
              }
          }
      }
  } else {
      //If the session is active make sure user can still authenticate with the IMAP server
      include_once("imap.inc.php");
      $imap = new IMAPMAIL;
      
      if (!$imap->open($cmail_config['default_host'], $cmail_config['default_port'])) {
          echo "Server Connection Failed!";
          exit;
      }
      
      $imap->login($_SESSION['user'], $_SESSION['pass']);
      //If they can't back to login screen  
      if (!$response = $imap->open_mailbox("INBOX")) {
          $_GET = array();
          $_GET['failed'] = '1';
          include($login);
          die();
      } else {
          //If they can make sure they are on the allowed list
          if (in_array($_SESSION['user'], $cmail_config['admin_allowed'])) {
              //If they are make sure session hasn't expired    
              if (time() - $_SESSION['last_active'] <= '500') {
                  //If it hasn't reset last_active time to current time
                  $_SESSION['last_active'] = time();
              }
              
              else {
                  //If the session has expired destroy the session and bring them back to the login screen
                  session_destroy();
                  $_GET = array();
                  $_GET['failed'] = '3';
                  include($login);
                  die();
              }
          } elseif ($_SESSION['user'] == $cmail_config['admin_allowed']) {
              //If they are make sure session hasn't expired    
              if (time() - $_SESSION['last_active'] <= '500') {
                  //If it hasn't reset last_active time to current time
                  $_SESSION['last_active'] = time();
              }
              
              else {
                  //If the session has expired destroy the session and bring them back to the login screen
                  session_destroy();
                  $_GET = array();
                  $_GET['failed'] = '3';
                  include($login);
                  die();
              }
          }
          
          else {
              //If they are not on the allowed list bring them back to the login screen
              $_GET = array();
              $_GET['failed'] = '2';
              include($login);
              die();
          }
      }
  }
?>
