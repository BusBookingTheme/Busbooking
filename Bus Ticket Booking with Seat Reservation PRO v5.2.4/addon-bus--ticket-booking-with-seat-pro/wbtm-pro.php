<?php
/**
* Plugin Name: Bus Ticket Booking with Seat Reservation PRO
* Plugin URI: http://mage-people.com
* Description: Pro version of Woocommerce Bus Tickets Manager, A Complete Bus Ticketig System for WordPress & WooCommerce
* Version: 5.0.4
* Author: MagePeople Team
* Author URI: http://www.mage-people.com/
* Text Domain: addon-bus--ticket-booking-with-seat-pro
* Domain Path: /languages/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



add_action( 'init', 'WbtmPro_language_load');
function WbtmPro_language_load(){
    $plugin_dir = basename(dirname(__FILE__))."/languages/";
    load_plugin_textdomain( 'addon-bus--ticket-booking-with-seat-pro', false, $plugin_dir );
}

class WbtmPro_Base{
	public function __construct(){
		$this->define_constants();

		add_action( 'admin_enqueue_scripts',array($this,'enqueue_styles' ));
	}
	public function define_constants() {

		define( 'WBTMPRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'WBTMPRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WBTMPRO_PLUGIN_FILE', plugin_basename( __FILE__ ) );




        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


        if ( $this->check_woocommerce() && $this->check_bus_reservation() && $this->check_pdf_support() ) {

            $this->load_main_class();

            if (!defined('WBTM_STORE_URL')) {
                define('WBTM_STORE_URL', 'https://mage-people.com/');
            }
            define('WBTM_PRO_ID', 85351);
            define('WBTM_PRO_NAME', 'Bus Ticket Booking with Seat Reservation PRO');

            if (!class_exists('EDD_SL_Plugin_Updater')) {
                include(dirname(__FILE__) . '/license/EDD_SL_Plugin_Updater.php');
            }
            include(dirname(__FILE__) . '/license/main.php');
            $license_key      	= trim(get_option('wtm_pro_license_key'));
            $edd_updater 		= new EDD_SL_Plugin_Updater(WBTM_STORE_URL, __FILE__, array(
                'version'     		=> '5.0.4',
                'license'     		=> $license_key,
                'item_name'   		=> WBTM_PRO_NAME,
                'item_id'     		=> WBTM_PRO_ID,
                'author'      		=> 'MagePeople Team',
                'url'         		=> home_url(),
                'beta'        		=> false
            ));
            require_once(dirname(__FILE__) . "/includes/AddonBusTicketBookingWithSeatProClass.php");

        }else{
            require WBTMPRO_PLUGIN_DIR . 'includes/function.install_plugin.php';
            add_action( 'admin_notices', array($this,'wbtm_wc_bus_pdf_not_activate') );
        }

    }


    function wbtm_wc_bus_pdf_not_activate() {

        $class = 'notice notice-error';

        $message = 'Bus Ticket Booking with Seat Reservation PRO Dependent on Plugin ';

        $bus_install_url = get_admin_url().'plugin-install.php?s=bus-ticket-booking-with-seat-reservation&tab=search&type=term';
        $wc_install_url = get_admin_url().'plugin-install.php?s=woocommerce&tab=search&type=term';
        $mpdf_install_url = get_admin_url().'edit.php?post_type=wbtm_bus&page=wbtm_install_plugin_page';

        if(!$this->check_woocommerce()){
            $message .= __( 'Woocommerce <a class="btn button" href='.$wc_install_url.'>Click Here to Install WooCommerce</a> You need to install and activete.', 'addon-bus--ticket-booking-with-seat-pro' );
        }

        if(!$this->check_bus_reservation()){
            $message .= __( 'Bus Ticket Booking with Seat Reservation. <a class="btn button" href='.$bus_install_url.'>Click Here to Install Bus</a>  You need to install and activete. ', 'addon-bus--ticket-booking-with-seat-pro' );
        }

        if(!$this->check_pdf_support()){
            $message .= __( 'MagePeople PDF Support <a class="btn button" href='.$mpdf_install_url.'>Click Here to Install PDF Support</a>. You need to install and activete ', 'addon-bus--ticket-booking-with-seat-pro' );
        }


        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ),  $message  );





    }




	public function load_main_class(){		
		require WBTMPRO_PLUGIN_DIR . 'includes/class-plugin.php';
	}
	public function enqueue_styles() {


        
        wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all' );
        wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_style( 'select2css' );
        wp_enqueue_script( 'select2' );

		wp_enqueue_style('bus-admin-style',WBTMPRO_PLUGIN_URL.'css/bus-admin.css',array(),'4.0');
		
		wp_enqueue_script('bus-admin-script',WBTMPRO_PLUGIN_URL.'js/bus-admin.js',array( 'jquery' ), time(), true);
		if ( class_exists( 'WooCommerce' ) ) {
        wp_localize_script( 'bus-admin-script', 'php_vars', array('currency_symbol' => get_woocommerce_currency_symbol()) );
        }


		wp_enqueue_style( 'fontawesome.v6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css', false, '6.1.1', 'all' );
	}


    public  function check_woocommerce() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin_dir = ABSPATH . 'wp-content/plugins/woocommerce';
        if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return 'yes';
        }  else {
            return 0;
        }
    }

    public  function check_bus_reservation() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin_dir = ABSPATH . 'wp-content/plugins/woocommerce';
        if ( is_plugin_active( 'bus-ticket-booking-with-seat-reservation/woocommerce-bus.php' ) ) {
            return 'yes';
        }  else {
            return 0;
        }
    }

    public  function check_pdf_support() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin_dir = ABSPATH . 'wp-content/plugins/woocommerce';
        if ( is_plugin_active( 'magepeople-pdf-support-master/mage-pdf.php' ) ) {
            return 'yes';
        }  else {
            return 0;
        }
    }
}


new WbtmPro_Base();


