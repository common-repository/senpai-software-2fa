<?php

namespace SenpaiSoftware2FA;

/**
 * Enqueue CSS and JS for admin area.
 */
function css_js(){
    // loading css
    wp_register_style( 'senpai-software-2fa-admin', plugin_dir_url( __DIR__ ) . 'css/senpai-software-2fa.css', false);
    wp_enqueue_style( 'senpai-software-2fa-admin' );

    // loading js
    wp_register_script( 'senpai-software-2fa-admin', plugin_dir_url( __DIR__ ).'js/senpai-software-2fa.js',false );
    wp_enqueue_script( 'senpai-software-2fa-admin' );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\css_js' );

/**
 * Display user profile fields.
 */
function profile_fields($user){

    $user_id = $user->ID;
    $hash = get_user_meta($user_id, 'senpai_software_2fa_hash', true);
    $status = get_user_meta($user_id, 'senpai_software_2fa_status', true);

    $checked_disable=null;
    $checked_enable=null;

    if($status=="disable"){
        $checked_disable="checked";
    } elseif ($status=="enable"){
        $checked_enable="checked";
    }

    if(empty($hash)){
        $file_hash_text=__( 'Select your file','senpai-software-2fa' );
    } else {
        $file_hash_text=__( 'The file key has been accepted, but you can update it','senpai-software-2fa' );
    }
    ?>
    <div id="keyfile_block">
        <h3><?php echo esc_html(__( 'Two-factor authentication with a key file','senpai-software-2fa' )); ?></h3>
        <p><?php echo esc_html(__( 'You can select any file on your computer and use it as a secret key to log into the admin area. Your file will not be uploaded to the site.','senpai-software-2fa' )); ?></p>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo esc_html(__( 'Status','senpai-software-2fa' )); ?></th>
                <td>
                    <input type="radio" <?php echo esc_html($checked_disable); ?> id="senpai_software_2fa_status_disable"
                           name="senpai_software_2fa_status" value="disable">
                    <label for="senpai_software_2fa_status_disable"><?php echo esc_html(__( 'Disable','senpai-software-2fa' )); ?></label>
                    <input type="radio" <?php echo esc_html($checked_enable); ?> id="senpai_software_2fa_status_enable"
                           name="senpai_software_2fa_status" value="enable">
                    <label for="senpai_software_2fa_status_enable"><?php echo esc_html(__( 'Enable','senpai-software-2fa' )); ?></label>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html($file_hash_text); ?></th>
                <td>
                    <div id="senpai_software_2fa_block">
                        <p>
                            <label>
                                <span class="dashicons dashicons-admin-network"></span> <?php echo esc_html(__( 'Select key file (max file size 1 GB)','senpai-software-2fa' )); ?>
                                <input type="file" id="senpai_software_2fa_file" onchange="senpai_software_2fa_upload();">
                            </label>
                            <input type="hidden" id="senpai_software_2fa_hash" name="senpai_software_2fa_hash">
                        </p>
                        <p id="senpai_software_2fa_name"></p>
                        <p id="senpai_software_2fa_progress"></p>
                        <p id="senpai_software_2fa_error"><?php echo esc_html(__( 'File is too big','senpai-software-2fa' )); ?></p>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <p><span class="dashicons dashicons-warning"></span> <?php echo esc_html(__('Do not change the contents of the file that is used as the key.','senpai-software-2fa')); ?></p>
    </div>
    <?php
}
add_action('show_user_profile', __NAMESPACE__.'\profile_fields');
add_action('edit_user_profile', __NAMESPACE__.'\profile_fields');

/**
 * Update user profile fields.
 */
function profile_fields_save($user_id){

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if(isset($_POST['senpai_software_2fa_status'])) {

        $status = sanitize_text_field( $_POST['senpai_software_2fa_status'] ); // Sanitized

        if ($status == 'enable' || $status == 'disable'){ // Validated
            update_user_meta($user_id, 'senpai_software_2fa_status', $status);
        }
    }

    if (isset($_POST['senpai_software_2fa_hash'])) {

        $file_hash = sanitize_text_field( $_POST['senpai_software_2fa_hash'] ); // Sanitized

        if ( preg_match( '/^[a-f0-9]{40}$/', $file_hash ) ) { // Validated
            $file_hash=sha1($file_hash);
            update_user_meta($user_id, 'senpai_software_2fa_hash', $file_hash);
        }
    }
}
add_action('personal_options_update', __NAMESPACE__.'\profile_fields_save');
add_action('edit_user_profile_update', __NAMESPACE__.'\profile_fields_save');

/**
 * Add plugin page
 */
function menu() {

    add_options_page(
        esc_html(__( '2FA Settings','senpai-software-2fa' )),
        esc_html(__( '2FA Settings','senpai-software-2fa' )),
        'manage_options',
        'snp_2fa',
        __NAMESPACE__.'\set',
        99
    );
}

add_action('admin_menu', __NAMESPACE__.'\menu');

function set() {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $xmlrpc = sanitize_text_field( $_POST['snp_2fa_xmlrpc'] );
        $hints = sanitize_text_field( $_POST['snp_2fa_hint'] );
        $attempts = sanitize_text_field( $_POST['snp_2fa_attempts'] );
        $block_period = sanitize_text_field( $_POST['snp_2fa_block_period'] );

        update_option( 'snp_2fa_xmlrpc', $xmlrpc );
        update_option( 'snp_2fa_hint', $hints );
        update_option( 'snp_2fa_attempts', $attempts );
        update_option( 'snp_2fa_block_period', $block_period );

        if ( get_option( 'snp_2fa_attempts' ) !== null ) {

            global $wpdb;
            $table_name = $wpdb->prefix . 'snp_2fa_ip';

            if ( $wpdb->get_var("show tables like '".$table_name."'") != $table_name ) {

                $charset_collate = $wpdb->get_charset_collate();

                $sql = "CREATE TABLE $table_name (
                  id int(10) NOT NULL AUTO_INCREMENT,
                  ip varchar(40) NOT NULL,
                  counter int(10) NOT NULL,
                  blockdate varchar(30) NOT NULL,
                  PRIMARY KEY  (id)
                ) $charset_collate;";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );

            }
        }

        add_settings_error(
            'snp-2fa-settings',
            'settings-saved',
            __('Settings saved.', 'default'),
            'updated'
        );
        settings_errors('snp-2fa-settings');

    }

    $xmlrpc_disable=null;
    $xmlrpc_enable=null;

    $xmlrpc=get_option( 'snp_2fa_xmlrpc' );

    if($xmlrpc==1){
        $xmlrpc_disable="checked";
    } else {
        $xmlrpc_enable="checked";
    }

    $hint=null;
    $hint=get_option( 'snp_2fa_hint' );

    $attempts=null;
    $attempts=get_option( 'snp_2fa_attempts' );

    $block_period=null;
    $block_period=get_option( 'snp_2fa_block_period' );
    ?>

    <div class="wrap">
        <h1><?php echo esc_html(__( '2FA Settings','senpai-software-2fa' )); ?></h1>
        <form method="post">
            <table class="form-table">
                <tbody>
                <tr>
                    <th>XML-RPC</th>
                    <td>
                        <input type="radio" <?php echo esc_html($xmlrpc_disable); ?> id="snp_2fa_xmlrpc_disable" name="snp_2fa_xmlrpc" value="1">
                        <label for="snp_2fa_xmlrpc_disable"><?php echo esc_html(__( 'Disable','senpai-software-2fa' )); ?></label>

                        <input type="radio" <?php echo esc_html($xmlrpc_enable); ?> id="snp_2fa_xmlrpc_enable" name="snp_2fa_xmlrpc" value="0">
                        <label for="snp_2fa_xmlrpc_enable"><?php echo esc_html(__( 'Enable','senpai-software-2fa' )); ?></label>
                        <p class="description">
                            <?php echo esc_html(__( 'XML-RPC creates serious vulnerabilities for the site. For full protection, it must be disabled.','senpai-software-2fa' )); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html(__( 'Login hints','senpai-software-2fa' )); ?></th>
                    <td><textarea class="regular-text" name="snp_2fa_hint"><?php echo sanitize_text_field($hint); ?></textarea>
                        <p class="description">
                            <?php echo esc_html(__( ' Default hints help hackers crack your credentials. Replace hints with neutral text, such as "Invalid data".','senpai-software-2fa' )); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html(__( 'Limiting login attempts','senpai-software-2fa' )); ?></th>
                    <td>
                        <input type="number" min="1" placeholder="For example: 3" name="snp_2fa_attempts" value="<?php echo sanitize_text_field($attempts); ?>">
                        <p class="description">
                            <?php echo esc_html(__( 'The number of failed login attempts after which the IP will be blocked. To remove restrictions, leave the field blank.','senpai-software-2fa' )); ?>
                        </p>
                        <br/>
                        <input type="number" min="1" placeholder="For example: 15" name="snp_2fa_block_period" value="<?php echo sanitize_text_field($block_period); ?>">
                        <p class="description">
                            <?php echo esc_html(__( 'The period for which the IP will be blocked (in minutes).','senpai-software-2fa' )); ?>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
            <p><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Save', 'default'); ?>"></p>
        </form>
    </div>

    <?php
}