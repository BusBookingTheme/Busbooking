<?php
if (!defined('ABSPATH')) exit;  // if direct access

class AddonBusTicketBookingWithSeatProClass
{
    public function __construct()
    {
        $this->load_dependencies();
        $this->define_all_hooks();
        $this->define_all_filters();

    }


    private function load_dependencies() {
        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/CommonClass.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/AdminPassengerList.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ExportToCsvClass.php';

    }

    private function define_all_hooks() {
        $AdminPassengerList = new AdminPassengerList;
        $ExportToCsvClass = new ExportToCsvClass;

        add_action('admin_init', array($AdminPassengerList,'passenger_list_init'), 99, 3);
        add_action('admin_menu', array($AdminPassengerList,'wbtm_passenger_list_menu'), 99, 3);

        add_action('wp_ajax_wbbm_custom_field_for_single_bus', [$AdminPassengerList, 'wbbm_custom_field_for_single_bus']);
        add_action('wp_ajax_nopriv_wbbm_custom_field_for_single_bus', [$AdminPassengerList, 'wbbm_custom_field_for_single_bus']);

        add_action('admin_init', [$ExportToCsvClass,'wpmsems_export_default_form']);

    }

    private function define_all_filters() {
       // $FilterClass = new FilterClass();
        //add_filter('single_template', array($FilterClass,'WBTM_register_custom_single_template'), 10);
        //add_action('mage_next_date', array($NextDateClass,'mage_next_date_suggestion_single'), 99, 3);
    }
}

new AddonBusTicketBookingWithSeatProClass();

