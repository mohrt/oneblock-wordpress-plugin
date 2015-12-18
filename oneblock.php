<?php
/*
Plugin Name: 1Block Authentication
Plugin URI:  http://1block.io/
Description: 1Block is a passwordless authentication protocol using Blockchain technology.
Version:     1.0
Author:      Monte Ohrt
Author URI:  http://1block.io/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/* variables */
global $oneblock_db_version;
$oneblock_db_version = '1.0';

/* registration of hooks */
register_activation_hook( __FILE__, 'oneblock_install' );
register_uninstall_hook( __FILE__, 'oneblock_uninstall' );

/* actions */
add_action('login_enqueue_scripts', 'oneblock_enqueue_login');
add_action('login_head', 'oneblock_login_head');
add_action('login_message', 'oneblock_login_message');
add_action('wp_ajax_nopriv_oneblock_getchallenge','oneblock_getchallenge');

/* functions */

/* setup login page */
function oneblock_enqueue_login() {
  wp_enqueue_script( 'jquery' );
  wp_enqueue_script( 'oneblock_qrcode', plugin_dir_url( __FILE__ ) . '/qrcode.min.js' );
  wp_enqueue_script( 'oneblock_js', plugin_dir_url( __FILE__ ) . '/oneblock.js');
}

/* login */

function oneblock_login_head() {
  $admin_url = admin_url('admin-ajax.php');
  $html = <<<EOF
<script type="text/javascript">
var ajaxurl = '{$admin_url}';
</script>
EOF;
  echo $html;
}


function oneblock_login_message() {
  $html = <<<EOF
   <p class="message"><a href="" id="oneblock_link">Login with 1Block</a></p>
   <p id="oneblock_qrcode" class="message"></p>
EOF;
  return $html;
}

function oneblock_getchallenge() {
  if(function_exists('openssl_random_pseudo_bytes')) {
    $nonce = bin2hex(openssl_random_pseudo_bytes(16));
  } else {
    $nonce = substr(md5(rand()), 0, 16);
  }
  $url = admin_url('admin-ajax.php');
  $url_parts = parse_url($url);
  $challenge_url = preg_replace('!^https?!','oneblock',$url);
  $challenge_url .= '?x=' . $nonce;
  if($url_parts['scheme'] == 'http') {
    $challenge_url .= '&u=1';
  }
  echo $challenge_url;
  wp_die();
}

/* install function */
function oneblock_install() {
  global $wpdb;
  global $oneblock_db_version;
  $nonce_table = $wpdb->prefix . 'oneblock_nonce';
  $users_table = $wpdb->prefix . 'oneblock_users';

  $charset_collate = $wpdb->get_charset_collate();

  $sql_nonce = "CREATE TABLE IF NOT EXISTS $nonce_table (
    id varchar(64) NOT NULL,
    ip varchar(16) DEFAULT NULL,
    add_date datetime DEFAULT NULL,
    logged_in enum('N','Y') DEFAULT NULL,
    PRIMARY KEY  (id)
  ) $charset_collate;";

  $sql_users = "CREATE TABLE IF NOT EXISTS $users_table (
    id varchar(64) NOT NULL,
    users_id bigint(20),
    logins int(11) DEFAULT NULL,
    user_data text,
    last_login datetime DEFAULT NULL,
    last_nonce varchar(32) DEFAULT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY last_nonce (last_nonce)
  ) $charset_collate;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql_nonce );
  dbDelta( $sql_users );

  add_option('oneblock_db_version', $oneblock_db_version );
  add_option('oneblock_register_key', '');
}

/* uninstall function */
function oneblock_uninstall() {
  global $wpdb;
  $nonce_table = $wpdb->prefix . 'oneblock_nonce';
  $users_table = $wpdb->prefix . 'oneblock_users';
  $wpdb->query("DROP TABLE IF EXISTS $nonce_table"); 
  $wpdb->query("DROP TABLE IF EXISTS $users_table"); 
  delete_option('oneblock_db_version');
  delete_option('oneblock_register_key');
}
