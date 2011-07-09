<?php
/**
 * Receive the snda token and save it to session.
 */
function hmbkp_set_snda_token(){
  session_start();
  if ( !isset( $_GET['sdid'] ) || empty( $_GET['sdid'] ) )
    return false;
  if ( !isset( $_GET['snda_access_token'] ) || empty( $_GET['snda_access_token'] ) )
    return false;
  if ( !isset( $_GET['snda_expires_in'] ) || empty( $_GET['snda_expires_in'] ) )
    return false;

  $sdid =  $_GET['sdid'];
  $access_token = $_GET['snda_access_token'];
  $expires_in = $_GET['snda_expires_in'];

  $_SESSION['sdid'] = $sdid;
  $_SESSION['snda_access_token'] = $access_token;
  $_SESSION['snda_expires_in'] = $expires_in;

  session_write_close();

  wp_redirect( remove_query_arg( array('sdid', 'snda_access_token', 'snda_expires_in') ));
  exit;
}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_set_snda_token' );

/**
 * Save the download file to everbox,
 * and then redirect back to the backups page.
 * If do not have a token or the token is invalid,
 * it will request a token first.
 */
function hmbkp_request_save_to_everbox() {

  if ( !isset( $_GET['hmbkp_save_to_everbox'] ) || empty( $_GET['hmbkp_save_to_everbox'] ) )
    return false;

  session_start();
  if ( !isset( $_SESSION['sdid'] ) ) {
    hmbkp_request_everbox_token();
  }
  
  hmbkp_save_to_everbox( hmbkp_urlsafe_base64_decode( $_GET['hmbkp_save_to_everbox'] ) );

  wp_redirect( remove_query_arg( 'hmbkp_save_to_everbox' ));
  exit;
}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_save_to_everbox' );

/**
 * Show all files in Everbox.
 */
function hmbkp_show_all_files_in_everbox(){
  if ( !isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_show_all_files_in_everbox' )
    return false;

  session_start();
  if ( !isset( $_SESSION['sdid'] ) ) {
    hmbkp_request_everbox_token();
  }
  hmbkp_redirect_to_everbox_for_wp();
  exit;
}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_show_all_files_in_everbox');

?>
