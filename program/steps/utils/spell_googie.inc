<?php

/*
 +-----------------------------------------------------------------------+
 | program/steps/utils/spell_googie.inc                                  |
 |                                                                       |
 | This file is part of the Crystal Mail client                     |
 | Copyright (C) 2005-2007, crystalmail Dev. - Switzerland                 |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Submit request to Google's spell checking engine                    |
 |                                                                       |
 | CREDITS:                                                              |
 |   Script from GoogieSpell by amix.dk                                  |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <roundcube@gmail.com>                        |
 +-----------------------------------------------------------------------+

 $Id$

*/

<?php
/*
+----------------------------------------------------------------+
| ./program/steps/utils/spell_googie.inc                         |
|                                                                |
| This file was based on Roundcube Webmail Client                |
| Copyright (C) 2005-2010, RoundCube Dev. - Switzerland          |
|                                                                |
| This file is part of the Crystal Mail Client                   |
| Copyright (C) 2010, Crystal Mail Dev. Team - United States     |
|                                                                |
| Licensed under the GNU GPL                                     |
|                                                                |
| PURPOSE:                                                       |
|   Submit request to Google's spell checking engine             |
|                                                                |
+--------------------- RoundCube Dev. Team ----------------------+
| Thomas Bruederli <roundcube@gmail.com>                         |
+----------------------- Studio 182 Team ------------------------+
| Hunter Dolan <hunter@crystalmail.net>                          |
+------------------------- Credits ------------------------------+
| Script from GoogieSpell by amix.dk							 |
+----------------------------------------------------------------+
*/


$REMOTE_REQUEST = TRUE;

// default settings
$host = "ssl://www.google.com";
$port = 443;
$lang = get_input_value('lang', crystal_INPUT_GET);
$path = "/tbproxy/spell?lang=$lang";

// spell check uri is configured
if (!empty($CONFIG['spellcheck_uri']))
  {
  $a_uri = parse_url($CONFIG['spellcheck_uri']);
  $ssl = ($a_uri['scheme']=='https' || $a_uri['scheme']=='ssl');
  $port = $a_uri['port'] ? $a_uri['port'] : ($ssl ? 443 : 80);
  $host = ($ssl ? 'ssl://' : '') . $a_uri['host'];
  $path = $a_uri['path'] . ($a_uri['query'] ? '?'.$a_uri['query'] : '') . $lang;
  }

$data = file_get_contents('php://input');
$store = "";

if ($fp = fsockopen($host, $port, $errno, $errstr, 30))
  {
  $out = "POST $path HTTP/1.0\r\n";
  $out .= "Host: " . str_replace('ssl://', '', $host) . "\r\n";
  $out .= "Content-Length: " . strlen($data) . "\r\n";
  $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $out .= "Connection: Close\r\n\r\n";
  $out .= $data;
  fwrite($fp, $out);

  while (!feof($fp))
    $store .= fgets($fp, 128);
  fclose($fp);
  }

// remove headers
$pos = strpos($store, '<?xml');
$store = substr($store, $pos);

// set response length
header("Content-Length: " . strlen($store));

// Don't use server's default Content-Type charset (#1486406)
header("Content-Type: text/xml; charset=".cmail_CHARSET);
print $store;
exit;


