<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 Copy this file to config-local.php (git-ignored) and fill in the values for
 this server. config.php includes it before applying defaults.
*/

// Public hostname of this install, without scheme or path.
$config['server_name'] = 'example.com';

// Secret used to sign session cookies. Generate one with:
//   php -r "echo bin2hex(openssl_random_pseudo_bytes(32)), PHP_EOL;"
// Changing it logs every user out.
$config['encryption_key'] = '';

// 'enforce' (default) or 'log'. See config.php.
// $config['twilio_signature_mode'] = 'log';
