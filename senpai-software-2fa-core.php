<?php

namespace SenpaiSoftware2FA;

/**
 * Load CSS and JS files for login page
 */
function login_css_js() {
    // Load CSS
    wp_register_style( 'senpai-software-2fa', plugin_dir_url( __FILE__ ) . 'css/senpai-software-2fa.css', false );
    wp_enqueue_style( 'senpai-software-2fa' );

    // Load JS
    wp_register_script( 'senpai-software-2fa', plugin_dir_url( __FILE__ ) . 'js/senpai-software-2fa.js', false );
    wp_enqueue_script( 'senpai-software-2fa' );
}
add_action( 'login_enqueue_scripts', __NAMESPACE__.'\login_css_js' );

/**
 * Add form field for uploading key file
 */
function form_field() {
    ?>
    <div id="senpai_software_2fa_block">
        <p>
            <label>
                <span class="dashicons dashicons-admin-network"></span> <?php echo esc_html(__( 'Select key file','senpai-software-2fa' )); ?>
                <input type="file" id="senpai_software_2fa_file" onchange="senpai_software_2fa_upload();">
            </label>
            <input type="hidden" id="senpai_software_2fa_hash" name="senpai_software_2fa_hash">
        </p>
        <p id="senpai_software_2fa_name"></p>
        <p id="senpai_software_2fa_progress"></p>
        <p id="senpai_software_2fa_error"><?php echo esc_html(__( 'File is too big','senpai-software-2fa' )); ?></p>
    </div>
    <?php
}
add_action( 'login_form', __NAMESPACE__.'\form_field' );

/**
 * Validate key file
 */
function validation( $user, $password ) {
    $status = get_user_meta( $user->ID, 'senpai_software_2fa_status', true );
    $db_hash = get_user_meta( $user->ID, 'senpai_software_2fa_hash', true );
    if ( $status == 'enable' ) {
        if( isset( $_POST['senpai_software_2fa_hash'] ) && !empty($_POST['senpai_software_2fa_hash']) ){

            $file_hash = sanitize_text_field($_POST['senpai_software_2fa_hash']); // Sanitized

            if ( preg_match( '/^[a-f0-9]{40}$/', $file_hash ) ) { // Validated

                $file_hash=sha1($file_hash);

                if ($db_hash !== $file_hash) {
                    return new \WP_Error('access denied', __('Wrong key file','senpai-software-2fa'));
                }
            } else {
                return new \WP_Error('access denied', __('File error','senpai-software-2fa'));
            }
        } else {
            if(!empty($db_hash)) {
                return new \WP_Error( 'access denied', __( 'Upload your key file', 'senpai-software-2fa' ) );
            }
        }
    }
    return $user;
}
add_action( 'wp_authenticate_user', __NAMESPACE__.'\validation', 10, 3 );

/**
 * Check xmlrpc settings
 */
if(get_option( 'snp_2fa_xmlrpc' )==1){
    add_filter('xmlrpc_enabled', '__return_false');
}

/**
 * Check hints settings
 */
if(!empty(get_option( 'snp_2fa_hint' ))) {

    function hints(){
        return __( sanitize_text_field(get_option( 'snp_2fa_hint' )),'default' );
    }
    add_filter( 'login_errors', __NAMESPACE__.'\hints' );
}

/**
 * Check IP
 */
if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {

    function check() {

        $attempts=get_option('snp_2fa_attempts');
        $ip=sanitize_text_field($_SERVER['REMOTE_ADDR']);

        global $wpdb;
        $table = $wpdb->prefix . 'snp_2fa_ip';

        $results = $wpdb->get_results( "SELECT `counter`,`blockdate` FROM `{$table}` WHERE `ip`='{$ip}' LIMIT 1" );

        if($results && (!empty($attempts))) {

            $counter    = $results[0]->counter;
            $block_time = $results[0]->blockdate;

            if ( $counter >= $attempts ) {

                $duration=sanitize_text_field(get_option('snp_2fa_block_period'));
                if(empty($duration)){ $duration=15; }
                $duration='PT'.$duration.'M';

                $currentDateTime   = new \DateTime();
                $specifiedDateTime = new \DateTime( $block_time );
                $specifiedDateTime->add( new \DateInterval( $duration ) );

                if ( $currentDateTime > $specifiedDateTime ) {
                    $wpdb->get_results( "DELETE FROM `{$table}` WHERE `ip`='{$ip}' LIMIT 1" );
                } else {
                    wp_die( 'Access temporarily restricted', 'Blocked', array( 'response' => 403 ) );
                }
            }
        }
    }
    add_filter( 'init', __NAMESPACE__.'\check' );
}

function login_failed(){

    $attempts=get_option('snp_2fa_attempts');
    $ip=sanitize_text_field($_SERVER['REMOTE_ADDR']);

    global $wpdb;
    $table = $wpdb->prefix . 'snp_2fa_ip';

    $results = $wpdb->get_results( "SELECT `counter`,`blockdate` FROM `{$table}` WHERE `ip`='{$ip}' LIMIT 1" );

    $currentDateTime = new \DateTime();
    $date=$currentDateTime->format('Y-m-d H:i:s');

    if(!empty($attempts)) {

        if ( $results ) {

            $counter = $results[0]->counter;
            $counter ++;

            $block_date = new \DateTime( $results[0]->blockdate );
            $block_date->add( new \DateInterval( 'PT5M' ) );

            if ( $currentDateTime > $block_date ) {
                $counter = 1;
            }

            $data  = array(
                'counter'   => $counter,
                'blockdate' => $date
            );
            $where = array(
                'ip' => $ip
            );
            $wpdb->update( $table, $data, $where );

        } else {
            $data = array(
                'ip'        => $ip,
                'counter'   => 1,
                'blockdate' => $date
            );
            $wpdb->insert( $table, $data );
        }
    }
}
add_action('wp_login_failed', __NAMESPACE__.'\login_failed');