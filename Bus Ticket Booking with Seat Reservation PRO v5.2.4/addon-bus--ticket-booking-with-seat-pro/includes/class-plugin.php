<?php
if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
/**
 * @since      1.0.0
 * @package    WBTM_Plugin
 * @subpackage WBTM_Plugin/includes
 * @author     MagePeople team <magepeopleteam@gmail.com>
 */

class WbtmPro_Plugin {

	public function __construct() {
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once WBTMPRO_PLUGIN_DIR . 'lib/classes/class-pdf.php';			
		require_once WBTMPRO_PLUGIN_DIR . 'includes/class-cpt.php';		
		require_once WBTMPRO_PLUGIN_DIR . 'includes/class-settings.php';		
		require_once WBTMPRO_PLUGIN_DIR . 'includes/class-metabox.php';
		require_once WBTMPRO_PLUGIN_DIR . 'includes/class-function.php';
		//require_once WBTMPRO_PLUGIN_DIR . 'includes/passenger_list.php';
		require_once WBTMPRO_PLUGIN_DIR . 'includes/extra_services.php';
		require_once WBTMPRO_PLUGIN_DIR . 'includes/report.php';
		require_once WBTMPRO_PLUGIN_DIR . 'includes/class_wbbm_report.php';
		require_once WBTMPRO_PLUGIN_DIR . 'includes/class-csv-export.php';
		require_once WBTMPRO_PLUGIN_DIR . 'includes/class-shortcode.php';
		require_once WBTMPRO_PLUGIN_DIR . 'includes/wbtm_admin_ticket_purchase.php';
	}
}
new WbtmPro_Plugin();