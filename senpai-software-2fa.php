<?php
/**
 * Plugin Name:       Senpai Software - Two-factor authentication (2FA) with a key file
 * Plugin URI:        https://senpai.software/wp-plugins/2fa/
 * Description:       Unique method two-factor auth (2FA). Limit Login Attempts. Disable XML-RPC. Protection against brute force attacks.
 * Version:           2.0.1
 * Author:            Senpai Software
 * Author URI:        https://senpai.software
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       senpai-software-2fa
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      5.6
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

namespace SenpaiSoftware2FA;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * The code that runs during plugin activation.
 */
function activation(){
    global $wpdb;
    $users = get_users();
    foreach ( $users as $user ) {
        add_user_meta($user->ID, 'senpai_software_2fa_hash', '');
        add_user_meta($user->ID, 'senpai_software_2fa_status', 'disable');
    }
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivation(){
    global $wpdb;
    $users = get_users();
    foreach ( $users as $user ) {
        delete_user_meta($user->ID, 'senpai_software_2fa_hash');
        delete_user_meta($user->ID, 'senpai_software_2fa_status');
    }
}

/**
 * The code that runs during plugin uninstall.
 */
function uninstall(){
    global $wpdb;
    $users = get_users();
    foreach ( $users as $user ) {
        delete_user_meta($user->ID, 'senpai_software_2fa_hash');
        delete_user_meta($user->ID, 'senpai_software_2fa_status');
    }

    delete_option( 'snp_2fa_xmlrpc');
    delete_option( 'snp_2fa_hint');
    delete_option( 'snp_2fa_attempts');
    delete_option( 'snp_2fa_block_period');

    $table_name = $wpdb->prefix . 'snp_2fa_ip';
    $query = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($query);
}

register_activation_hook(__FILE__, __NAMESPACE__.'\activation');
register_deactivation_hook(__FILE__, __NAMESPACE__.'\deactivation');
register_uninstall_hook(__FILE__, __NAMESPACE__.'\uninstall');

/**
 * Languages
 */
function load_textdomain() {
    load_plugin_textdomain( 'senpai-software-2fa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', __NAMESPACE__.'\load_textdomain' );

/**
 * The core
 */
require plugin_dir_path( __FILE__ ) . 'senpai-software-2fa-core.php';

/**
 * Admin part
 */
if( is_admin() ){
    require plugin_dir_path( __FILE__ ) . 'admin/senpai-software-2fa-admin.php';
}