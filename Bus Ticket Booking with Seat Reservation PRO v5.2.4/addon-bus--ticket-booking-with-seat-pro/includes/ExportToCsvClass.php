<?php
if (!defined('ABSPATH')) exit;  // if direct access

class ExportToCsvClass extends AdminPassengerList
{
    public function __construct()
    {


    }


    function wpmsems_export_default_form()
    {

        if (isset($_GET['action']) && $_GET['action'] == 'export_passenger_list') {

            // Check for current user privileges
            if (!current_user_can('manage_options') && !current_user_can('my_sell_wbtm_bus')) {
                return false;
            }

            ob_start();

            $domain = $_SERVER['SERVER_NAME'];
            // Bus Type
            $bus_type = (isset($_GET['bus_type']) ? $_GET['bus_type'] : 'general');
            // Route Type
            $route_type = isset($_GET['route_type']) ? $_GET['route_type'] : '';
            $filename = 'Passenger_list' . $domain . '_' . time() . '.csv';

            $bus_id = (isset($_GET['bus_id']) ? $_GET['bus_id'] : null);
            $mage_meta = get_post_custom($bus_id);

            $data_rows = array();

            $filtering_data = $this->filtering_query($bus_id,$bus_type,$route_type);

            $meta_query = $filtering_data[0];

            $header_row = $this->wbtm_csv_head_row('', $bus_type, $route_type, $mage_meta, $bus_id);


            // -------------------------------------------------
            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'meta_query' => $meta_query,
            );
            $passenger = new WP_Query($args);

            $passger_query = $passenger->posts;
            foreach ($passger_query as $_passger) {
                $passenger_id = $_passger->ID;
                if (get_post_type($passenger_id) == 'wbtm_bus_booking') {
                    $row = $this->wbtm_csv_passenger_data($passenger_id, $bus_type, $route_type,$bus_id,$mage_meta);
                }
                $data_rows[] = $row;
            }
            // echo '<pre>';
            // print_r($data_rows);
            // die;
            wp_reset_postdata();
            $fh = @fopen('php://output', 'w');
            fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Description: File Transfer');
            header('Content-type: text/csv');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Expires: 0');
            header('Pragma: public');
            fputcsv($fh, $header_row);
            foreach ($data_rows as $data_row) {
                fputcsv($fh, $data_row);
            }
            fclose($fh);
            ob_end_flush();
            die();
        }
    }


    function wbtm_csv_passenger_data($post_id = '', $bus_type = 'general', $route_type = '',$bus_id,$mage_meta)
    {
        global $wbtmmain;
        $order_id = get_post_meta($post_id, 'wbtm_order_id', true);
        $order = wc_get_order($order_id);
        $billing = array();
        if($order) {
            $billing = $order->get_address('billing');
        }

        $status = (is_object($order) ? $order->get_status() : null);
        $billing_type_str = get_post_meta($post_id, 'wbtm_billing_type', true);
        $j_date = get_post_meta($post_id, 'wbtm_journey_date', true);
        $valid_till = '';
        $zone_text = '';

        $show_price = $original_price = get_post_meta($post_id, 'wbtm_bus_fare', true);
        $discount_price = get_post_meta($post_id, '_wbtm_tp', true) ?? 0 ;
        if($discount_price && $original_price > $discount_price) {
            $show_price = $discount_price;
        }

        if ($billing_type_str) {
            $valid_till = mtsa_calculate_valid_date($j_date, $billing_type_str);

            $zone = get_post_meta($post_id, 'wbtm_city_zone', true);
            if($zone) {
                $zone = get_term($zone);
                $zone_text = $zone->name;
            }

        }



        if ($bus_type == 'sub' && $route_type != 'city_zone') {

            $passenger_data = array(
                get_post_meta($post_id, 'wbtm_seat', true),
                get_the_title(get_post_meta($post_id, 'wbtm_bus_id', true)) . "-" . get_post_meta(get_post_meta($post_id, 'wbtm_bus_id', true), 'wbtm_bus_no', true),
                mage_wp_date(get_post_meta($post_id, 'wbtm_booking_date', true)),
                mage_wp_date(get_post_meta($post_id, 'wbtm_journey_date', true)) . ' ' . get_post_meta($post_id, 'wbtm_bus_start', true),
                mage_wp_date($valid_till),
                $billing_type_str,
                $zone_text,
                get_post_meta($post_id, 'wbtm_boarding_point', true),
                get_post_meta($post_id, 'wbtm_droping_point', true),
                get_post_meta($post_id, 'wbtm_order_id', true),
                $status
            );
        } elseif ($bus_type == 'sub' && $route_type == 'city_zone') {

            $passenger_data = array(
                get_post_meta($post_id, 'wbtm_seat', true),
                get_the_title(get_post_meta($post_id, 'wbtm_bus_id', true)) . "-" . get_post_meta(get_post_meta($post_id, 'wbtm_bus_id', true), 'wbtm_bus_no', true),
                mage_wp_date(get_post_meta($post_id, 'wbtm_booking_date', true)),
                mage_wp_date(get_post_meta($post_id, 'wbtm_journey_date', true)) . ' ' . get_post_meta($post_id, 'wbtm_bus_start', true),
                $valid_till,
                $billing_type_str,
                $zone_text,
                get_post_meta($post_id, 'wbtm_order_id', true),
                $status
            );
        } else {



            $billing_default_value_custom_export = $this->billing_default_value_custom_export($wbtmmain,$post_id,$billing,$order_id);
            $billing_custom_fields_value_export = $this->billing_custom_fields_value_export($order_id);
            $four_register_form_value_export = $this->four_register_form_value_export($post_id,$bus_id,$mage_meta);
            $form_builder_custom_value_export = $this->form_builder_custom_value_export($post_id,$bus_id);


            $passenger_data = array(
                get_post_meta($post_id, 'wbtm_seat', true),
                get_the_title(get_post_meta($post_id, 'wbtm_bus_id', true)) . "-" . get_post_meta(get_post_meta($post_id, 'wbtm_bus_id', true), 'wbtm_bus_no', true),
                mage_wp_date(get_post_meta($post_id, 'wbtm_booking_date', true)),
                mage_wp_date(get_post_meta($post_id, 'wbtm_journey_date', true)) . ' ' . get_post_meta($post_id, 'wbtm_bus_start', true),
                get_post_meta($post_id, 'wbtm_boarding_point', true),
                get_post_meta($post_id, 'wbtm_droping_point', true),
                get_post_meta($post_id, 'wbtm_pickpoint', true),
                get_post_meta($post_id, 'wbtm_order_id', true),
                $original_price,
                ($original_price > $show_price ? $show_price : ''),
                $status
            );

        }

        if (count($form_builder_custom_value_export) > 0) {
            $passenger_data = array_merge($form_builder_custom_value_export,$passenger_data);
        }
        if (count($four_register_form_value_export) > 0) {
            $passenger_data = array_merge($four_register_form_value_export,$passenger_data);
        }
        if (count($billing_custom_fields_value_export) > 0) {
            $passenger_data = array_merge($billing_custom_fields_value_export,$passenger_data);
        }
        if (count($billing_default_value_custom_export) > 0) {
            $passenger_data = array_merge($billing_default_value_custom_export,$passenger_data);
        }

        return $passenger_data;
    }

    function billing_default_value_custom_export($wbtmmain,$post_id,$billing,$order_id){

        $order_meta_data = get_post_meta($order_id);
        $billing_default_fields_setting = $wbtmmain->bus_get_option('default_billing_fields_setting', 'ticket_manager_settings', array());

        $billing_default_heading_array = array();

        if (in_array('p_name', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = get_post_meta($post_id, 'wbtm_user_name', true);
        }
        if (in_array('p_phone', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = get_post_meta($post_id, 'wbtm_user_phone', true);
        }
        if (in_array('p_email', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = get_post_meta($post_id, 'wbtm_user_email', true);
        }
        if (in_array('p_company', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = ($billing ? $billing['company'] : '');
        }
        if (in_array('p_address', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = ($billing ? $billing['address_1'] : '');
        }
        if (in_array('p_city', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = ($billing ? $billing['city'] : '');
        }
        if (in_array('p_state', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = ($billing ? $billing['state'] : '');
        }
        if (in_array('p_postcode', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = ($billing ? $billing['postcode'] : '');
        }
        if (in_array('p_country', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = ($billing ? $billing['country'] : '');
        }
        if (in_array('p_total_paid', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = get_post_meta($post_id, 'wbtm_bus_fare', true);
        }
        if (in_array('p_payment_method', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = isset($order_meta_data['_payment_method_title'][0]) ? $order_meta_data['_payment_method_title'][0] : null;
        }

        return $billing_default_heading_array;


    }
    function billing_custom_fields_value_export($order_id){
        $get_settings = get_option('wbtm_bus_settings');
        $get_val = isset($get_settings['custom_fields']) ? $get_settings['custom_fields'] : '';
        $custom_fields = $get_val ? $get_val : null;
        $custom_tbody_html = [];
        if ($custom_fields) {
            $custom_fields_arr = explode(',', $custom_fields);
            if ($custom_fields_arr) {
                foreach ($custom_fields_arr as $item) {
                    $item = trim($item);
                    $custom_tbody_html[] =  get_post_meta($order_id, 'wbtm_custom_field_' . $item, true) ;
                }
            }
        }
        return $custom_tbody_html;
    }

    function four_register_form_value_export($passenger_id,$bus_id,$mage_meta){
        $four_register_form_value = [];
        if($bus_id && $mage_meta){

            $gender = array_key_exists('wbtm_user_gender', $mage_meta) ? strip_tags($mage_meta['wbtm_user_gender'][0]) : false;
            if($gender){
                $four_register_form_value[] =  get_post_meta($passenger_id, 'wbtm_user_gender', true);
            }
            if (array_key_exists('wbtm_user_extra_bag', $mage_meta)) {
                $extra_bag = strip_tags($mage_meta['wbtm_user_extra_bag'][0]);
                $extra_bag_qty = isset($mage_meta['wbtm_extra_bag_price']) ? strip_tags($mage_meta['wbtm_extra_bag_price'][0]) : 0;
                if($extra_bag && $extra_bag_qty){
                    $four_register_form_value[] =  get_post_meta($passenger_id, 'wbtm_user_extra_bag', true) ;
                }
            }
        }
        return $four_register_form_value;
    }

    function form_builder_custom_value_export($passenger_id,$bus_id){

        $form_builder_custom_value = [];

        if($bus_id){
            /*form_builder_custom_form use wbtm_user_additional for name and value */
            $extra_p_fields_values = maybe_unserialize(get_post_meta($passenger_id, 'wbtm_user_additional', true));

            if(!$extra_p_fields_values){
                $extra_p_fields_values = [];
            }
            $field_array = count($extra_p_fields_values);
            $index = 0;
            /*form_builder_custom_form use attendee_reg_form not value */
            $custom_field = unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
            if($custom_field) {
                foreach($custom_field as $epv) {
                    if($field_array > $index){
                        $form_builder_custom_value[] = $extra_p_fields_values[$index]['value'];
                    }else{
                        $form_builder_custom_value[] = '-';
                    }
                    $index++;
                }
            }
        }else{
            // Extra Passenger info fields value
            $extra_p_fields_values = maybe_unserialize(get_post_meta($passenger_id, 'wbtm_user_additional', true));
            $extra_p_fields_value_str = '';
            $epv_i = 0;
            if($extra_p_fields_values) {
                foreach($extra_p_fields_values as $epv) {
                    $extra_p_fields_value_str .= sprintf("%s = %s", $epv['name'], $epv['value']) . ((count($extra_p_fields_values) - 1 == $epv_i) ? "" : " + ");
                    $epv_i++;
                }
            }
            $form_builder_custom_value[] = ($extra_p_fields_value_str ? $extra_p_fields_value_str : "-");
        }

        return $form_builder_custom_value;
    }

    function wbtm_csv_head_row($post_id = '', $bus_type = 'general', $route_type = '',$mage_meta='',$bus_id='')
    {

        if ($bus_type == 'sub' && $route_type != 'city_zone') {
            $head_row = array(
                __('Seat','addon-bus--ticket-booking-with-seat-pro'),
                mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'),
                __('Booking Date','addon-bus--ticket-booking-with-seat-pro'),
                __('Start Date','addon-bus--ticket-booking-with-seat-pro'),
                __('Valid Till','addon-bus--ticket-booking-with-seat-pro'),
                __('Billing Type','addon-bus--ticket-booking-with-seat-pro'),
                __('Zone','addon-bus--ticket-booking-with-seat-pro'),
                __('From','addon-bus--ticket-booking-with-seat-pro'),
                __('To','addon-bus--ticket-booking-with-seat-pro'),
                __('Order','addon-bus--ticket-booking-with-seat-pro'),
                __('Order Status','addon-bus--ticket-booking-with-seat-pro')
            );
        } elseif ($bus_type == 'sub' && $route_type == 'city_zone') {
            $head_row = array(
                __('Seat','addon-bus--ticket-booking-with-seat-pro'),
                mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'),
                __('Booking Date','addon-bus--ticket-booking-with-seat-pro'),
                __('Start Date','addon-bus--ticket-booking-with-seat-pro'),
                __('Valid Till','addon-bus--ticket-booking-with-seat-pro'),
                __('Billing Type','addon-bus--ticket-booking-with-seat-pro'),
                __('Zone','addon-bus--ticket-booking-with-seat-pro'),
                __('Order','addon-bus--ticket-booking-with-seat-pro'),
                __('Order Status','addon-bus--ticket-booking-with-seat-pro')

            );
        } else {
            $head_row = array(
                __('Seat','addon-bus--ticket-booking-with-seat-pro'),
                mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'),
                __('Booking Date','addon-bus--ticket-booking-with-seat-pro'),
                __('Journey Date','addon-bus--ticket-booking-with-seat-pro'),
                __('From','addon-bus--ticket-booking-with-seat-pro'),
                __('To','addon-bus--ticket-booking-with-seat-pro'),
                __('Pickup Point','addon-bus--ticket-booking-with-seat-pro'),
                __('Order','addon-bus--ticket-booking-with-seat-pro'),
                __('Price','addon-bus--ticket-booking-with-seat-pro'),
                __('Discounted Price','addon-bus--ticket-booking-with-seat-pro'),
                __('Order Status','addon-bus--ticket-booking-with-seat-pro')
            );
        }

        $billing_default_heading_custom_export = $this->billing_default_heading_custom_export();
        $custom_heading_html_export = $this->custom_heading_html_export();
        $four_register_form_heading_export = $this->four_register_form_heading_export($mage_meta,$bus_id);
        $form_builder_custom_heading_export = $this->form_builder_custom_heading_export($bus_id);

        if (count($form_builder_custom_heading_export) > 0) {
            $head_row = array_merge($form_builder_custom_heading_export,$head_row);
        }
        if (count($four_register_form_heading_export) > 0) {
            $head_row = array_merge($four_register_form_heading_export,$head_row);
        }
        if (count($custom_heading_html_export) > 0) {
            $head_row = array_merge($custom_heading_html_export,$head_row);
        }
        if (count($billing_default_heading_custom_export) > 0) {
            $head_row = array_merge($billing_default_heading_custom_export,$head_row);
        }

        // Extra Checkout field
        $get_settings = get_option('wbtm_bus_settings');
        $get_val = isset($get_settings['custom_fields']) ? $get_settings['custom_fields'] : '';
        $output = $get_val ? $get_val : null;
        if($output) {
            $head_row = array_merge($head_row, array('Extra Checkout field'));
        }

        return $head_row;
    }





    function billing_default_heading_custom_export(){

        global $wbtmmain;
        $billing_default_fields_setting = $wbtmmain->bus_get_option('default_billing_fields_setting', 'ticket_manager_settings', array());

        $billing_default_heading_array = array();

        if (in_array('p_name', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Name';
        }
        if (in_array('p_phone', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Phone';
        }
        if (in_array('p_email', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Email';
        }
        if (in_array('p_company', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Company';
        }
        if (in_array('p_address', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Address';
        }
        if (in_array('p_city', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'City';
        }
        if (in_array('p_state', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'State';
        }
        if (in_array('p_postcode', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Postcode';
        }
        if (in_array('p_country', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Country';
        }
        if (in_array('p_total_paid', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Price';
        }
        if (in_array('p_payment_method', $billing_default_fields_setting)) {
            $billing_default_heading_array[] = 'Payment Method';
        }

        return $billing_default_heading_array;
    }

    function custom_heading_html_export(){


        // Custom FIeld
        $get_settings = get_option('wbtm_bus_settings');
        $get_val = isset($get_settings['custom_fields']) ? $get_settings['custom_fields'] : '';
        $custom_fields = $get_val ? $get_val : null;
        $custom_heading_html = [];

        if ($custom_fields) {
            $custom_fields_arr = explode(',', $custom_fields);
            if ($custom_fields_arr) {
                foreach ($custom_fields_arr as $item) {
                    $custom_heading_html[] =  (($item) ? ucfirst(str_replace('_', ' ', $item)) : "") ;
                }
            }
        }

        return $custom_heading_html;
    }

    function four_register_form_heading_export($mage_meta,$bus_id){
        $four_register_form_heading = [];
        if($bus_id){
            if ($mage_meta) {
                $gender = array_key_exists('wbtm_user_gender', $mage_meta) ? strip_tags($mage_meta['wbtm_user_gender'][0]) : false;
                if($gender){
                    $four_register_form_heading[]= 'Gender';
                }
                if (array_key_exists('wbtm_user_extra_bag', $mage_meta)) {
                    $extra_bag = strip_tags($mage_meta['wbtm_user_extra_bag'][0]);
                    $extra_bag_qty = isset($mage_meta['wbtm_extra_bag_price']) ? strip_tags($mage_meta['wbtm_extra_bag_price'][0]) : 0;
                    if($extra_bag && $extra_bag_qty){
                        $four_register_form_heading[] = 'Extra Bag';
                    }
                }
            }
        }
        return $four_register_form_heading;
    }

    function form_builder_custom_heading_export($bus_id){
        $form_builder_custom_heading = [];
        if($bus_id){
            $custom_field = unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
            if($custom_field) {
                foreach($custom_field as $epv) {
                    $form_builder_custom_heading[]= $epv['field_label'];
                }
            }
        }else{
            $form_builder_custom_heading[] =  __('Extra Info', 'addon-bus--ticket-booking-with-seat-pro');
        }
        return $form_builder_custom_heading;
    }

    function custom_field(){
        $bus_id = explode('-',$_GET['bus_id']);
        return unserialize(get_post_meta($bus_id[0], 'attendee_reg_form', true));
    }


}

