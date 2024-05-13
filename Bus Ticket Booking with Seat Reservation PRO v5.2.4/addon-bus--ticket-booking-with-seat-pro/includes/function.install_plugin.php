<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

function wbtm_pro_install_mage_pdf(){
    include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
    include_once( ABSPATH . 'wp-admin/includes/file.php' );
    include_once( ABSPATH . 'wp-admin/includes/misc.php' );
    include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
    $upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin() );
    $upgrader->install('https://github.com/magepeopleteam/magepeople-pdf-support/archive/master.zip');
}

add_action('admin_menu', 'mep_event_welcome_admin_menu');
function mep_event_welcome_admin_menu()
{
    add_submenu_page('edit.php?post_type=wbtm_bus', __('Install Plugin', 'mage-eventpress'), __('<span style="color:red">Install Plugin</span>', 'mage-eventpress'), 'manage_options', 'wbtm_install_plugin_page',  'wbtm_install_plugin_page');
}

function wbtm_install_plugin_page()
{
    $plugin_dir         = ABSPATH . 'wp-content/plugins/magepeople-pdf-support-master';
    $thedir             = is_dir($plugin_dir);
    if ( $thedir ) {    
        ?>
            <style>
            body.wbtm_bus_page_wbtm_install_plugin_page .notice.notice-error {
                display: none;
            }
            </style>
              <div id="message" class="updated notice notice-success is-dismissible">
                <p><?php _e('Mage PDF Support Plugin is already installed in your website. Please Go to plugin list and active the plugin.', 'addon-bus--ticket-booking-with-seat-pro'); ?></p>
                <!-- <button type="button" class="notice-dismiss"><span
                            class="screen-reader-text"><?php _e('Dismiss this notice.', 'addon-bus--ticket-booking-with-seat-pro'); ?></span>
                </button> -->
            </div>
        <?php
    }else{
        wbtm_pro_install_mage_pdf();
    }
}