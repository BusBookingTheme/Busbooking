<?php
if (is_admin()) {


    function wbtm_generate_order_key()
    {
        return 'wc_' . apply_filters('woocommerce_generate_order_key', 'order_' . wp_generate_password(13, false));
    }


    add_action('admin_menu', 'wbtm_admin_purchase_ticket_menu');
    function wbtm_admin_purchase_ticket_menu()
    {
        $menu_label = mage_bus_label('wbtm_menu_translate_purchase_ticket', __('Purchase Ticket', 'addon-bus--ticket-booking-with-seat-pro'), true);

        add_submenu_page('edit.php?post_type=wbtm_bus', $menu_label, $menu_label, 'manage_woocommerce', 'admin_purchase_ticket', 'wbtm_admin_purchase_ticket');
    }

    function wbtm_admin_purchase_ticket()
    {
        global $wpdb, $magepdf, $wbtmmain, $wbtmpublic;

        $date = isset($_GET['j_date']) ? strip_tags($_GET['j_date']) : date('Y-m-d');
        $return = false;

        $bus_return_show = mage_bus_setting_value('bus_return_show', 'enable');

        $bus_stops = get_terms('wbtm_bus_stops', array(
            'hide_empty' => false,
        ));

        $exit = false;

        if (isset($_POST['add-to-cart-admin'])) {

//             echo '<pre>';print_r($_POST);die;

            $wbtm_anydate_return = sanitize_text_field($_POST['wbtm_anydate_return']);
            $wbtm_anydate_return_price = sanitize_text_field($_POST['wbtm_anydate_return_price']);

            $wbtm_order_seat_plan = sanitize_text_field($_POST['wbtm_order_seat_plan']);
            $start = isset($_POST['start_stops']) ? $_POST['start_stops'] : '';
            $end = isset($_POST['end_stops']) ? $_POST['end_stops'] : '';
            $j_date = sanitize_text_field($_POST['journey_date']);
            $seat_name = isset($_POST['seat_name']) ? $_POST['seat_name'] : array();
            if ($wbtm_order_seat_plan == 'yes' && !empty($seat_name)) {
                $count_seat = count($seat_name);
                $bus_fare = (sanitize_text_field($_POST['bus_fare']) * $count_seat);
                $seats = implode(",", $seat_name);
                $fare = $wbtmmain->wbtm_array_strip($_POST['seat_fare']);
                $fare = $fare ? array_reverse($fare) : array();
                $passenger_label = $wbtmmain->wbtm_array_strip($_POST['passenger_label']);
            }
            $bus_id = sanitize_text_field($_POST['bus_id']);

            // If seat is already booked then request will be canceled
            if ($seat_name) {
                if (is_array($seat_name) && !empty($seat_name)) {
                    $booked_seats_html = '';
                    $seat_name = array_reverse($seat_name);
                    foreach ($seat_name as $seat) {
                        $check_before_order = (int)$wbtmmain->wbtm_get_seat_status($seat, $j_date, $bus_id, $start, $end);
                        if (($check_before_order == 1 || $check_before_order == 2)) {
                            $booked_seats_html .= '<span>' . $seat . '</span>';
                            $exit = true;
                        }
                    }
                    echo $booked_seats_html ? '<p class="wbtm-admin-seat-notification">' . $booked_seats_html . ' ' . __("Already Booked By Another! This request was not accepted.", "addon-bus--ticket-booking-with-seat-pro") . '</p>' : null;

                }

            }

            if (!$exit) {
                $b_time = isset($_POST['user_start_time']) ? $_POST['user_start_time'] : '';
                $j_time = isset($_POST['bus_start_time']) ? $_POST['bus_start_time'] : '';
                $add_datetime = date("Y-m-d h:i:s");
                $uname = 'Order &ndash; ' . $add_datetime;
                $usr_inf = array();
                $custom_reg_additional = array();

                $extra_bag_price = get_post_meta($bus_id, 'wbtm_extra_bag_price', true) ? get_post_meta($bus_id, 'wbtm_extra_bag_price', true) : 0;

                // $passenger_type_num = $wbtmmain->wbtm_array_strip($_POST['passenger_type']);

                $seat_qty = isset($_POST['seat_qty']) ? $_POST['seat_qty'] : null;
                $seat_qty_count = 0;
                if ($seat_qty) {
                    foreach ($seat_qty as $sq) {
                        if ($sq) {
                            $seat_qty_count += $sq;
                        }
                    }
                }

                $passenger_type = isset($_POST['passenger_type']) ? $_POST['passenger_type'] : null;

                $bus_type = $_POST['wbtm_bus_type'];

                // pickup point
                $wbtm_pickpoint = isset($_POST['wbtm_pickpoint']) ? $_POST['wbtm_pickpoint'] : '';

                $wbtm_billing_type = isset($_POST['mtsa_billing_type']) ? $_POST['mtsa_billing_type'] : '';
                $city_zone = isset($_POST['city_zone']) ? $_POST['city_zone'] : '';

                // Extra Service
                $extra_services = array();
                $total_extra_price = 0;
                $extra_service_qty = isset($_POST['extra_service_qty']) ? $_POST['extra_service_qty'] : array();
                $extra_service_name = isset($_POST['extra_service_name']) ? $_POST['extra_service_name'] : array();
                $extra_service_price = isset($_POST['extra_service_price']) ? $_POST['extra_service_price'] : array();

                if ($extra_service_qty) {
                    $extra_service_i = 0;
                    foreach ($extra_service_qty as $extra_item) {
                        if ($extra_item > 0) {
                            $extra_services[] = array(
                                'name' => isset($extra_service_name[$extra_service_i]) ? $extra_service_name[$extra_service_i] : '',
                                'qty' => $extra_item,
                                'price' => isset($extra_service_price[$extra_service_i]) ? $extra_service_price[$extra_service_i] : 0,
                            );

                            $total_extra_price += $extra_services[$extra_service_i]['qty'] * $extra_services[$extra_service_i]['price'];
                        }

                        $extra_service_i++;
                    }
                }
                // Extra Service END

                $passenger_type_num = array(
                    'Adult' => 0,
                    'Child' => 1,
                    'Infant' => 2,
                );

                // ADD THE FORM INPUT TO $new_post ARRAY
                $new_post = array(
                    'post_title' => $uname,
                    'post_content' => '',
                    'post_category' => array(),
                    'tags_input' => array(),
                    'post_status' => 'wc-completed',
                    'post_type' => 'shop_order'
                );

                //SAVE THE POST
                $pid = wp_insert_post($new_post);
                update_post_meta($pid, '_order_key', wbtm_generate_order_key());
                update_post_meta($pid, '_created_via', 'admin');
                update_post_meta($pid, '_customer_user', get_current_user_id());
                update_post_meta($pid, '_payment_method', 'cod');
                update_post_meta($pid, '_payment_method_title', 'Cash on delivery');
                update_post_meta($pid, '_created_via', 'Admin Dashboard');
                update_post_meta($pid, '_completed_date', $add_datetime);
                update_post_meta($pid, '_recorded_sales', 'yes');
                update_post_meta($pid, '_recorded_coupon_usage_counts', 'yes');
                update_post_meta($pid, '_order_stock_reduced', 'yes');
                update_post_meta($pid, '_extra_services', $extra_services);

                update_post_meta($pid, '_wbtm_anydate_return', $wbtm_anydate_return);
                update_post_meta($pid, '_wbtm_anydate_return_price', $wbtm_anydate_return_price);
                update_post_meta($pid, 'Anydate Return', $wbtm_anydate_return);
                update_post_meta($pid, 'Anydate Return Price', $wbtm_anydate_return_price);
                update_post_meta($pid, 'Journey Date', $j_date);

                $order_id = $pid;
                $user_id = get_current_user_id();
                $counter = 0;

                $next_stops = maybe_serialize($wbtmmain->wbtm_get_all_stops_after_this($bus_id, $start, $end));

                if (empty($seat_name) && $seat_qty_count == 0) { // only extra service
                    $extra_bag_price_total = 0;
                    $j = 0;
                    // Custom Reg user
                    $usr_inf[$counter]['wbtm_user_name'] = $user_name = isset($_POST['wbtm_user_name'][$counter]) ? $_POST['wbtm_user_name'][$counter] : '';
                    $usr_inf[$counter]['wbtm_user_email'] = $user_email = isset($_POST['wbtm_user_email'][$counter]) ? $_POST['wbtm_user_email'][$counter] : '';
                    $usr_inf[$counter]['wbtm_user_phone'] = $user_phone = isset($_POST['wbtm_user_phone'][$counter]) ? $_POST['wbtm_user_phone'][$counter] : '';
                    $usr_inf[$counter]['wbtm_user_address'] = $user_address = isset($_POST['wbtm_user_address'][$counter]) ? $_POST['wbtm_user_address'][$counter] : '';
                    $usr_inf[$counter]['wbtm_user_gender'] = $user_gender = isset($_POST['wbtm_user_gender'][$counter]) ? $_POST['wbtm_user_gender'][$counter] : '';
                    $usr_inf[$counter]['wbtm_extra_bag_qty'] = $wbtm_extra_bag_qty = isset($_POST['wbtm_extra_bag_qty'][$counter]) ? $_POST['wbtm_extra_bag_qty'][$counter] : 0;

                    $extra_bag_price_total = $extra_bag_price_total + ($wbtm_extra_bag_qty * $extra_bag_price);

                    // Additional reg builder field
                    $reg_form_arr = maybe_unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
                    $k = 0;
                    if (is_array($reg_form_arr) && sizeof($reg_form_arr) > 0) {
                        foreach ($reg_form_arr as $builder) {
                            $custom_reg_additional[$k] = array(
                                'name' => $builder['field_label'],
                                'value' => (isset($_POST[$builder['field_id']][$j]) ? $_POST[$builder['field_id']][$j] : ''),
                            );

                            $k++;
                        }
                    }

                    $wbtmmain->create_bus_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, $b_time, $j_time, null, $total_extra_price, $total_extra_price, $j_date, $add_datetime, $user_name, $user_email, null, null, $user_phone, $user_gender, $user_address, $wbtm_extra_bag_qty, $extra_bag_price_total, $usr_inf, $counter, 2, null, $wbtm_billing_type, $city_zone, $wbtm_pickpoint, $extra_services, $custom_reg_additional);
                    $download_url = $magepdf->get_invoice_ajax_url(array('order_id' => $order_id));
                    ?>
                    <div id="message" class="updated notice notice-success is-dismissible">
                        <p><?php _e('Seat Booked Successfully', 'addon-bus--ticket-booking-with-seat-pro') ?>
                            . <a
                                    class="wbtm-button" href="<?php echo $download_url; ?>"
                                    style="background: green;color: #fff;padding: 5px 20px;font-size: 14px;font-style: normal;text-decoration: none;margin: 0 auto;width: 200px;display: inline;text-align: center;"><?php _e('Download Ticket', 'addon-bus--ticket-booking-with-seat-pro'); ?></a>
                        </p>
                        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>

                    <?php
                } else { // Seat Plan
                    if ($wbtm_order_seat_plan == 'yes') {
                        $extra_bag_price_total = 0;
                        $j = 0;
                        foreach ($seat_name as $_seats) {
                            $custom_reg_additional = array();
                            if (!empty($_seats)) {

                                // Custom Reg user
                                $usr_inf[$counter]['wbtm_user_name'] = $user_name = isset($_POST['wbtm_user_name'][$counter]) ? $_POST['wbtm_user_name'][$counter] : '';
                                $usr_inf[$counter]['wbtm_user_email'] = $user_email = isset($_POST['wbtm_user_email'][$counter]) ? $_POST['wbtm_user_email'][$counter] : '';
                                $usr_inf[$counter]['wbtm_user_phone'] = $user_phone = isset($_POST['wbtm_user_phone'][$counter]) ? $_POST['wbtm_user_phone'][$counter] : '';
                                $usr_inf[$counter]['wbtm_user_address'] = $user_address = isset($_POST['wbtm_user_address'][$counter]) ? $_POST['wbtm_user_address'][$counter] : '';
                                $usr_inf[$counter]['wbtm_user_gender'] = $user_gender = isset($_POST['wbtm_user_gender'][$counter]) ? $_POST['wbtm_user_gender'][$counter] : '';
                                $usr_inf[$counter]['wbtm_extra_bag_qty'] = $wbtm_extra_bag_qty = isset($_POST['extra_bag_quantity'][$counter]) ? $_POST['extra_bag_quantity'][$counter] : 0;

                                $extra_bag_price_total = $extra_bag_price_total + ($wbtm_extra_bag_qty * $extra_bag_price);
                                // Additional reg builder field
                                $reg_form_arr = maybe_unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));

                                $k = 0;
                                if (is_array($reg_form_arr) && sizeof($reg_form_arr) > 0) {
                                    foreach ($reg_form_arr as $builder) {
                                        $custom_reg_additional[$k] = array(
                                            'name' => $builder['field_label'],
                                            'value' => (isset($_POST[$builder['field_id']][$j]) ? $_POST[$builder['field_id']][$j] : ''),
                                        );

                                        $k++;
                                    }
                                }


                                // New
                                if (isset($passenger_label[$counter])) {
                                    $passenger_label_s = $passenger_label[$counter];
                                } else {
                                    $passenger_label_s = "";
                                }

                                if (isset($passenger_type_num[$counter])) {
                                    $passenger_type_num_s = $passenger_type_num[$counter];
                                } else {
                                    $passenger_type_num_s = "";
                                }

                                if (isset($fare[$counter])) {
                                    $fare_s = $fare[$counter];
                                } else {
                                    $fare_s = "";
                                }

                                 $fare_s = $fare_s + $extra_bag_price_total;


                                $wbtmmain->create_bus_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, $b_time, $j_time, $_seats, $fare_s, $j_date, $add_datetime, $user_name, $user_email, $passenger_label_s, $passenger_type_num_s, $user_phone, $user_gender, $user_address, $wbtm_extra_bag_qty, $extra_bag_price_total, $usr_inf, $counter, 2, null, $wbtm_billing_type, $city_zone, $wbtm_pickpoint, $extra_services, $custom_reg_additional);
                                $download_url = $magepdf->get_invoice_ajax_url(array('order_id' => $order_id));

                                if ((count($seat_name) - 1) == $counter) :
                                    ?>
                                    <div id="message" class="updated notice notice-success is-dismissible">
                                        <p><?php _e('Seat Booked Successfully', 'addon-bus--ticket-booking-with-seat-pro') ?>
                                            . <a
                                                    class="wbtm-button" href="<?php echo $download_url; ?>"
                                                    style="background: green;color: #fff;padding: 5px 20px;font-size: 14px;font-style: normal;text-decoration: none;margin: 0 auto;width: 200px;display: inline;text-align: center;"><?php _e('Download Ticket', 'addon-bus--ticket-booking-with-seat-pro'); ?></a>
                                        </p>
                                        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span>
                                        </button>
                                    </div>
                                <?php
                                endif;
                            }
                            $counter++;
                            $j++;
                        }
                        $bus_fare = array_sum($fare) + $extra_bag_price_total + $total_extra_price;
                        update_post_meta($pid, '_order_total', $bus_fare);
                    } elseif ($wbtm_order_seat_plan == 'no') { // Without seat plan
                        $no_seat_total = 0;
                        $extra_bag_price_total = 0;
                        if ($seat_qty) {

                            $j = 0;
                            foreach ($seat_qty as $key => $qty) {
                                $custom_reg_additional = array();
                                $counter = $j;
                                if ($qty > 0) {
                                    for ($i = 0; $i < (int)$qty; $i++) {
                                        // Seat
                                        $_seats = $passenger_type[$key] . '(1)';
                                        $passenger_label_s = $passenger_type[$key];
                                        $passenger_type_num_s = $passenger_type_num[$passenger_type[$key]];

                                        // Price
                                        if ($bus_type === 'sub') { // Subscription Bus
                                            $fare_s = mtsa_bus_price_get($bus_id, $start, $end, $wbtm_billing_type, $passenger_type[$key], $city_zone);
                                            $no_seat_total = $no_seat_total + $fare_s;

                                            $is_return = false;
                                        } else { // General Bus
                                            $fare_s = mage_bus_seat_price($bus_id, $start, $end, false, $passenger_type_num[$passenger_type[$key]]);
                                            $no_seat_total = $no_seat_total + $fare_s;
                                        }
                                        // $fare_s = $fare_s + $total_extra_price;

                                        // Custom reg user yes
                                        $usr_inf[$j]['wbtm_user_name'] = $user_name = isset($_POST['wbtm_user_name'][$j]) ? $_POST['wbtm_user_name'][$j] : '';
                                        $usr_inf[$j]['wbtm_user_email'] = $user_email = isset($_POST['wbtm_user_email'][$j]) ? $_POST['wbtm_user_email'][$j] : '';
                                        $usr_inf[$j]['wbtm_user_phone'] = $user_phone = isset($_POST['wbtm_user_phone'][$j]) ? $_POST['wbtm_user_phone'][$j] : '';
                                        $usr_inf[$j]['wbtm_user_address'] = $user_address = isset($_POST['wbtm_user_address'][$j]) ? $_POST['wbtm_user_address'][$j] : '';
                                        $usr_inf[$j]['wbtm_user_gender'] = $user_gender = isset($_POST['wbtm_user_gender'][$j]) ? $_POST['wbtm_user_gender'][$j] : '';
                                        $usr_inf[$j]['wbtm_extra_bag_qty'] = $wbtm_extra_bag_qty = isset($_POST['extra_bag_quantity'][$j]) ? $_POST['extra_bag_quantity'][$j] : 0;

                                        $extra_bag_price_total = $extra_bag_price_total + ($wbtm_extra_bag_qty * $extra_bag_price);
                                        // Additional reg builder field
                                        $reg_form_arr = maybe_unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
                                        $k = 0;
                                        if (is_array($reg_form_arr) && sizeof($reg_form_arr) > 0) {
                                            foreach ($reg_form_arr as $builder) {
                                                $custom_reg_additional[$k] = array(
                                                    'name' => $builder['field_label'],
                                                    'value' => (isset($_POST[$builder['field_id']][$j]) ? $_POST[$builder['field_id']][$j] : ''),
                                                );

                                                $k++;
                                            }
                                        }

                                        $fare_s = $fare_s + $extra_bag_price_total;

                                        $wbtmmain->create_bus_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, $b_time, $j_time, $_seats, $fare_s, $j_date, $add_datetime, $user_name, $user_email, $passenger_label_s, $passenger_type_num_s, $user_phone, $user_gender, $user_address, $wbtm_extra_bag_qty, $extra_bag_price_total, $usr_inf = array(), $counter, 2, null, $wbtm_billing_type, $city_zone, $wbtm_pickpoint, $extra_services, $custom_reg_additional);
                                        $download_url = $magepdf->get_invoice_ajax_url(array('order_id' => $order_id));
                                        ?>
                                        <div id="message" class="updated notice notice-success is-dismissible">
                                            <p><?php _e('Seat Booked Successfully', 'addon-bus--ticket-booking-with-seat-pro') ?>
                                                . <a
                                                        class="wbtm-button" href="<?php echo $download_url; ?>"
                                                        style="background: green;color: #fff;padding: 5px 20px;font-size: 14px;font-style: normal;text-decoration: none;margin: 0 auto;width: 200px;display: inline;text-align: center;"><?php _e('Download Ticket', 'addon-bus--ticket-booking-with-seat-pro'); ?></a>
                                            </p>
                                            <button type="button" class="notice-dismiss"><span
                                                        class="screen-reader-text">Dismiss this notice.</span>
                                            </button>
                                        </div>
                                        <?php
                                    }
                                }
                            }
                            $j++;
                            $no_seat_total = $no_seat_total + $extra_bag_price_total + $total_extra_price;
                            update_post_meta($pid, '_order_total', $no_seat_total);
                        }
                    }
                }
            }

            echo `<script>;
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
            </script>`;
        }

        $bus_start_route = isset($_GET['bus_start_route']) ? $_GET['bus_start_route'] : '';
        $bus_end_route = isset($_GET['bus_end_route']) ? $_GET['bus_end_route'] : '';
        $j_date = isset($_GET['j_date']) ? $_GET['j_date'] : '';
        $r_date = isset($_GET['r_date']) ? $_GET['r_date'] : '';

        ob_start();
        ?>
        <!--
        <div class="wbtm-page-wrap">
            <div class="wbtm-page-top">
                <div class="wbtm-page-top-inner">
                    <h3>
                        <?php if (has_action('wbtm_admin_purchase_ticket_subscription_tab')) :
                            _e('Select Bus type', 'addon-bus--ticket-booking-with-seat-pro');
                        else :
                            _e('Bus type', 'addon-bus--ticket-booking-with-seat-pro');
                        endif; ?>
                    </h3>
                    
                    <ul class="wbtm_tab_link_wrap">
                        <li class="clickme">
                            <button data-tag="general"
                                    class="wbtm_tab_link wbtm_btn_primary wbtm_tab_active"><?php _e('General Bus', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
                        </li>
                        <?php do_action('wbtm_admin_purchase_ticket_subscription_tab') ?>
                    </ul>
                    
                </div>
            </div>
            -->
            <div style="clear: both;"></div>
            <div id="container" class="wrap">
                <div class="wbtm_content_item" id="general">
                    <div class="wbtm-search-form-sec the_select">
                        <form action="" method="GET">
                            <input type="hidden" name="post_type" value="wbtm_bus">
                            <input type="hidden" name="page" value="admin_purchase_ticket">
                            <div class="wbtm_search_form_inner">
                                <div class="wbtm_field_group">
                                    <label for="wbtm_start"><?php _e('Boarding Point', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                    <select name="bus_start_route" id="wbtm_start">
                                        <?php foreach ($bus_stops as $route) : ?>
                                            <option value="<?php echo $route->name ?>" <?php echo(($route->name == $bus_start_route) ? 'selected' : ''); ?>><?php echo $route->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="wbtm_field_group">
                                    <label for="wbtm_end"><?php _e('Dropping Point', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                    <select name="bus_end_route" id="wbtm_end">
                                        <?php foreach ($bus_stops as $route) : ?>
                                            <option value="<?php echo $route->name ?>" <?php echo(($route->name == $bus_end_route) ? 'selected' : ''); ?>><?php echo $route->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="wbtm_field_group">
                                    <label for="j_date"><?php _e('Journey date', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                    <input type="text" id="j_date" name="j_date" value="<?php echo $j_date; ?>">
                                </div>
                                <?php if($bus_return_show === 'enable') : ?>
                                <div class="wbtm_field_group">
                                    <label for="rr_date"><?php _e('Return date', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                    <input type="text" id="rr_date" name="r_date" value="<?php echo $r_date; ?>">
                                </div>
                                <?php endif; ?>
                              
                                <div class="wbtm_field_group" style="width:100px;">
                                    <input type="submit" name="wbtm_admin_bus_search" class="wbtm_btn_primary"
                                           value="<?php _e('Search', 'addon-bus--ticket-booking-with-seat-pro'); ?>"/>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="bus_list_header do-flex">
                        <div class="bus_img do-flex">
                            <div><p><?php _e('Image', 'addon-bus--ticket-booking-with-seat-pro'); ?></p></div>
                        </div>
                        <div class="left do-flex">
                            <div><p><?php echo mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'); ?></p></div>
                            <div><p><?php _e('Schedule', 'addon-bus--ticket-booking-with-seat-pro'); ?></p></div>
                        </div>
                        <div class="right do-flex">
                            <div><p>Type<?php _e('', 'addon-bus--ticket-booking-with-seat-pro'); ?></p></div>
                            <div><p><?php _e('Seats Available', 'addon-bus--ticket-booking-with-seat-pro'); ?></p></div>
                            <div><p><?php _e('View', 'addon-bus--ticket-booking-with-seat-pro'); ?></p></div>
                        </div>
                    </div>
                    <ul class="wbtm-admin-bus-list">
                        <?php
                        if (isset($_GET['bus_start_route']) && ($_GET['bus_end_route']) && ($_GET['j_date'])) {
                            $start = $_GET['bus_start_route'];
                            $end = $_GET['bus_end_route'];
                            $j_date = $_GET['j_date'];
                            $j_date = mage_wp_date($j_date, 'Y-m-d');
                            $args_search_qqq = array(
                                'post_type' => array('wbtm_bus'),
                                'posts_per_page' => -1,
                                'order' => 'ASC',
                                'orderby' => 'meta_value',
                                // 'meta_key' => 'wbtm_bus_start_time',
                                'meta_query' => array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'wbtm_bus_bp_stops',
                                        'value' => $start,
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'wbtm_bus_next_stops',
                                        'value' => $end,
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'relation' => 'OR',
                                        array(
                                            'key' => 'wbtm_seat_type_conf',
                                            'compare' => 'NOT EXISTS',
                                        ),
                                        array(
                                            'key' => 'wbtm_seat_type_conf',
                                            'value' => 'wbtm_seat_plan',
                                            'compare' => '=',
                                        ),
                                        array(
                                            'key' => 'wbtm_seat_type_conf',
                                            'value' => 'wbtm_without_seat_plan',
                                            'compare' => '=',
                                        ),
                                    )
                                )

                            );


                            $loop = new WP_Query($args_search_qqq);
                            $result_empty = true;
                            while ($loop->have_posts()) {
                                $loop->the_post();
                                $id = get_the_ID();
                                $has_bus = false;

                                $seat_price = mage_bus_seat_price($id, $start, $end, false);
                                if (!$seat_price) { // if price not set skip the result
                                    continue;
                                }

                                // ****************8
                                $bus_bp_array = get_post_meta($id, 'wbtm_bus_bp_stops', true) ? get_post_meta($id, 'wbtm_bus_bp_stops', true) : [];
                                $bus_bp_array = maybe_unserialize($bus_bp_array);

                                if ($bus_bp_array) {
                                    $bus_next_stops_array = get_post_meta($id, 'wbtm_bus_next_stops', true) ? get_post_meta($id, 'wbtm_bus_next_stops', true) : [];
                                    $bus_next_stops_array = maybe_unserialize($bus_next_stops_array);

                                    // Intermidiate Route
                                    $o_1 = mage_bus_end_has_prev($start, $end, $bus_bp_array);
                                    $o_2 = mage_bus_start_has_next($start, $end, $bus_next_stops_array);

                                    if ($o_1 && $o_2) {
                                        continue;
                                    }
                                    // Intermidiate Route END

                                    // Buffer Time Calculation
                                    $bp_time = $wbtmmain->wbtm_get_bus_start_time($start, $bus_bp_array);
                                    $is_buffer = $wbtmmain->wbtm_buffer_time_check($bp_time, $j_date);
                                    // Buffer Time Calculation END

                                    if ($is_buffer == 'yes') {
                                        // Operational on day
                                        $is_on_date = false;
                                        $bus_on_dates = array();
                                        $bus_on_date = mage_determine_ondate($id, $return, $start, $end);
                                        if ($bus_on_date != null) {
                                            $bus_on_dates = explode(', ', $bus_on_date);
                                            $is_on_date = true;
                                        }

                                        if ($is_on_date) {
                                            if (in_array($j_date, $bus_on_dates)) {
                                                $has_bus = true;
                                            }
                                        } else {

                                            // Offday schedule check
                                            // $bus_stops_times = get_post_meta($id, 'wbtm_bus_bp_stops', true);
                                            // $bus_offday_schedules = get_post_meta($id, 'wbtm_offday_schedule', true);
                                            $bus_offday_schedules = mage_determine_offdate($id, $return, $start, $end);

                                            // Get Bus Start Time
                                            $start_time = '';
                                            foreach ($bus_bp_array as $stop) {
                                                if ($stop['wbtm_bus_bp_stops_name'] == $start) {
                                                    $start_time = $stop['wbtm_bus_bp_start_time'];
                                                    break;
                                                }
                                            }

                                            $start_time = mage_wp_time($start_time); // Time convert 24 to 12

                                            $offday_current_bus = false;
                                            if (!empty($bus_offday_schedules)) {
                                                //$s_datetime = new DateTime($j_date . ' ' . $start_time);
                                                $s_datetime = date('Y-m-d H:i:s', strtotime($j_date . ' ' . $start_time));

                                                foreach ($bus_offday_schedules as $item) {

                                                    $c_iterate_date_from = $item['from_date'];
                                                    //$c_iterate_datetime_from = new DateTime($c_iterate_date_from . ' ' . $item['from_time']);
                                                    $c_iterate_datetime_from = date('Y-m-d H:i:s', strtotime($c_iterate_date_from . ' ' . $item['from_time']));

                                                    $c_iterate_date_to = $item['to_date'];
                                                    // $c_iterate_datetime_to = new DateTime($c_iterate_date_to . ' ' . $item['to_time']);
                                                    $c_iterate_datetime_to = date('Y-m-d H:i:s', strtotime($c_iterate_date_to . ' ' . $item['to_time']));

                                                    if (($s_datetime >= $c_iterate_datetime_from) && ($s_datetime <= $c_iterate_datetime_to)) {
                                                        $offday_current_bus = true;
                                                        break;
                                                    }
                                                }
                                            }

                                            // Check Offday and date
                                            if (!$offday_current_bus && !mage_check_search_day_off_new($id, $j_date)) {
                                                $has_bus = true;
                                            }
                                        }

                                    }
                                }
                                // ****************8

                                // Bus list item
                                if($has_bus) {
                                    wbtm_admin_bus_list_item($id, $start, $end, $date, $seat_price, false);
                                    $result_empty = false;
                                }
                                // Bus list item END
                            }

                            wp_reset_query();

                            echo ($result_empty ? '<li style="color:#d63638;text-align:center">'.__("Sorry! No Bus Found", "addon-bus--ticket-booking-with-seat-pro").'</li>' : null);
                        }
                        ?>
                        
                    </ul>
                    <div class="wbtm-admin-return-route-title"><?php echo __('Return', 'addon-bus--ticket-booking-with-seat-pro').' '.mage_bus_setting_value('bus_menu_label', 'Bus'); ?></div>
                    <ul class="wbtm-admin-bus-list">
                        <?php
                        if (isset($_GET['bus_start_route']) && ($_GET['bus_end_route']) && ($_GET['r_date'])) {
                            $start = $_GET['bus_end_route'];
                            $end = $_GET['bus_start_route'];
                            $j_date = $_GET['r_date'];
                            $j_date = mage_wp_date($j_date, 'Y-m-d');
                            $date = isset($_GET['r_date']) ? strip_tags($_GET['r_date']) : date('Y-m-d');
                            $args_search_qqq = array(
                                'post_type' => array('wbtm_bus'),
                                'posts_per_page' => -1,
                                'order' => 'ASC',
                                'orderby' => 'meta_value',
                                // 'meta_key' => 'wbtm_bus_start_time',
                                'meta_query' => array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'wbtm_bus_bp_stops_return',
                                        'value' => $start,
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'key' => 'wbtm_bus_next_stops_return',
                                        'value' => $end,
                                        'compare' => 'LIKE',
                                    ),
                                    array(
                                        'relation' => 'OR',
                                        array(
                                            'key' => 'wbtm_seat_type_conf',
                                            'compare' => 'NOT EXISTS',
                                        ),
                                        array(
                                            'key' => 'wbtm_seat_type_conf',
                                            'value' => 'wbtm_seat_plan',
                                            'compare' => '=',
                                        ),
                                        array(
                                            'key' => 'wbtm_seat_type_conf',
                                            'value' => 'wbtm_without_seat_plan',
                                            'compare' => '=',
                                        ),
                                    )
                                )

                            );


                            $loop = new WP_Query($args_search_qqq);
                            $result_empty = true;
                            while ($loop->have_posts()) {
                                $loop->the_post();
                                $id = get_the_ID();
                                $has_bus = false;

                                $seat_price = mage_bus_seat_price($id, $start, $end, false);
                                if (!$seat_price) { // if price not set skip the result
                                    continue;
                                }

                                // ****************8 
                                $bus_bp_array = get_post_meta($id, 'wbtm_bus_bp_stops_return', true) ? get_post_meta($id, 'wbtm_bus_bp_stops_return', true) : [];
                                $bus_bp_array = maybe_unserialize($bus_bp_array);

                                if ($bus_bp_array) {
                                    $bus_next_stops_array = get_post_meta($id, 'wbtm_bus_next_stops_return', true) ? get_post_meta($id, 'wbtm_bus_next_stops_return', true) : [];
                                    $bus_next_stops_array = maybe_unserialize($bus_next_stops_array);

                                    // Intermidiate Route
                                    $o_1 = mage_bus_end_has_prev($start, $end, $bus_bp_array);
                                    $o_2 = mage_bus_start_has_next($start, $end, $bus_next_stops_array);

                                    if ($o_1 && $o_2) {
                                        continue;
                                    }
                                    // Intermidiate Route END

                                    // Buffer Time Calculation
                                    $bp_time = $wbtmmain->wbtm_get_bus_start_time($start, $bus_bp_array);
                                    $is_buffer = $wbtmmain->wbtm_buffer_time_check($bp_time, $j_date);
                                    // Buffer Time Calculation END

                                    if ($is_buffer == 'yes') {
                                        // Operational on day
                                        $is_on_date = false;
                                        $bus_on_dates = array();
                                        $bus_on_date = mage_determine_ondate($id, $return, $start, $end);
                                        if ($bus_on_date != null) {
                                            $bus_on_dates = explode(', ', $bus_on_date);
                                            $is_on_date = true;
                                        }

                                        if ($is_on_date) {
                                            if (in_array($j_date, $bus_on_dates)) {
                                                $has_bus = true;
                                            }
                                        } else {

                                            // Offday schedule check
                                            // $bus_stops_times = get_post_meta($id, 'wbtm_bus_bp_stops', true);
                                            // $bus_offday_schedules = get_post_meta($id, 'wbtm_offday_schedule', true);
                                            $bus_offday_schedules = mage_determine_offdate($id, $return, $start, $end);

                                            // Get Bus Start Time
                                            $start_time = '';
                                            foreach ($bus_bp_array as $stop) {
                                                if ($stop['wbtm_bus_bp_stops_name'] == $start) {
                                                    $start_time = $stop['wbtm_bus_bp_start_time'];
                                                    break;
                                                }
                                            }

                                            $start_time = mage_wp_time($start_time); // Time convert 24 to 12

                                            $offday_current_bus = false;
                                            if (!empty($bus_offday_schedules)) {
                                                //$s_datetime = new DateTime($j_date . ' ' . $start_time);
                                                $s_datetime = date('Y-m-d H:i:s', strtotime($j_date . ' ' . $start_time));

                                                foreach ($bus_offday_schedules as $item) {

                                                    $c_iterate_date_from = $item['from_date'];
                                                    //$c_iterate_datetime_from = new DateTime($c_iterate_date_from . ' ' . $item['from_time']);
                                                    $c_iterate_datetime_from = date('Y-m-d H:i:s', strtotime($c_iterate_date_from . ' ' . $item['from_time']));

                                                    $c_iterate_date_to = $item['to_date'];
                                                    // $c_iterate_datetime_to = new DateTime($c_iterate_date_to . ' ' . $item['to_time']);
                                                    $c_iterate_datetime_to = date('Y-m-d H:i:s', strtotime($c_iterate_date_to . ' ' . $item['to_time']));

                                                    if (($s_datetime >= $c_iterate_datetime_from) && ($s_datetime <= $c_iterate_datetime_to)) {
                                                        $offday_current_bus = true;
                                                        break;
                                                    }
                                                }
                                            }

                                            // Check Offday and date
                                            if (!$offday_current_bus && !mage_check_search_day_off_new($id, $j_date, true)) {
                                                $has_bus = true;
                                            }
                                        }

                                    }
                                }
                                // ****************8

                                // Bus list item
                                if($has_bus) {
                                    wbtm_admin_bus_list_item_return($id, $start, $end, $date, $seat_price, false);
                                    $result_empty = false;
                                }
                                // Bus list item END
                            }

                            wp_reset_query();

                            echo ($result_empty ? '<li style="color:#d63638;text-align:center">'.__("Sorry! No Bus Found", "addon-bus--ticket-booking-with-seat-pro").'</li>' : null);
                        }
                        ?>
                        
                    </ul>
                </div>
                <div class="wbtm_content_item hide" id="subscription">
                    <div class="wbtm_subscription_inner">
                        <?php if (has_action('subscription_admin_order')) {
                            do_action('subscription_admin_order');
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <style>#wpfooter {position: inherit;}</style>
        <?php
    }

    function wbtm_admin_bus_list_item($id, $start, $end, $date, $seat_price, $dd)
    {
        global $wbtmmain;

        $values = get_post_custom(get_the_id());
        $total_seat = mage_bus_total_seat_new();
        // $sold_seat = $wbtmmain->wbtm_get_available_seat(get_the_id(), $date);
        // $available_seat = ($total_seat - $sold_seat);
        $available_seat = $total_seat - mage_partial_seat_booked_count(false);
        $bus_bp_array = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_bp_stops', true));
        $bus_dp_array = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_next_stops', true));
        $bp_time = $wbtmmain->wbtm_get_bus_start_time($start, $bus_bp_array);
        $dp_time = $wbtmmain->wbtm_get_bus_end_time($end, $bus_dp_array);

        // Pickpoint
        $pickpoints = mage_determine_pickuppoint($id, false, $start, $end);
        if ($pickpoints != '') {
            $pickpoints = maybe_unserialize($pickpoints);
        }

        $bus_bp_array = get_post_meta($id, 'wbtm_bus_bp_stops', true) ? get_post_meta($id, 'wbtm_bus_bp_stops', true) : [];
        $bus_bp_array = maybe_unserialize($bus_bp_array);
        if(mage_bus_is_midnight_trip($bus_bp_array, $start)) { // If Boarding point is after 11:59 pm then journey date will be search date +1 day
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
        }

        $seat_panel_settings = get_option('wbtm_bus_settings');
        $any_date_return_switch = !empty($seat_panel_settings['any_day_return']) ? $seat_panel_settings['any_day_return'] : 'off';
        ?>

        <li>
            <div class="admin-bus-list">
                <div class="admin-bus-list-item one bus-img">
                    <?php the_post_thumbnail('thumb'); ?>
                </div>
                <div class="admin-bus-list-item two">
                    <div class="">
                        <strong><?php the_title(); ?></strong>
                        <?php echo $values['wbtm_bus_no'][0]; ?>
                    </div>
                    <div class="">
                        <strong><?php echo date('h:i A', strtotime($bp_time)); ?></strong>
                    </div>
                </div>
                <div class="admin-bus-list-item three">
                    <div class="">
                        <strong><?php echo mage_bus_type($id); ?></strong>
                    </div>
                    <div class="">
                        <span class='seat-left'><?php echo $available_seat.' / '.mage_bus_total_seat_new().' '.__("Seat left", "bus-ticket-booking-with-seat-reservation"); ?></span>
                    </div>
                    <div class="" style="text-align: center">
                        <button class="wbtm_btn_primary admin-general-bus-detail-toggle">
                            View Seats
                        </button>
                    </div>
                </div>
            </div> <!-- Bus list header end -->
            <!-- Bus Detail -->
            <div class="admin-bus-details" data-bus-id="<?php echo $id; ?>"
                 id="admin-bus-details<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>">

                <form action="" method='post'>
                    <input type="hidden" name='journey_date' class="text"
                           value='<?php echo $date; ?>'/>
                    <input type="hidden" name='start_stops'
                           value="<?php echo $start; ?>"
                           class="hidden"/>
                    <input type='hidden' value='<?php echo $end; ?>'
                           name='end_stops'/>
                    <input type='hidden' value='<?php the_title(); ?>'
                           name='bus_name'/>
                    <input type="hidden" name="bus_id"
                           value="<?php echo get_the_id(); ?>">
                    <input type="hidden" name="wbtm_bus_type" value="general">
                    <input type="hidden" name="wbtm_anydate_return_price" id="wbtm_anydate_return_price" value=""/>
                    <?php
                    // Bus Seat Type
                    $bus_seat_type_conf = get_post_meta($id, 'wbtm_seat_type_conf', true);
                    if ($bus_seat_type_conf === 'wbtm_without_seat_plan') : // Without Seat Plan
                        // Price
                        $seatPrices = get_post_meta($id, 'wbtm_bus_prices', true);
                        $available_seat_type = array();
                        if ($seatPrices) {
                            $i = 0;
                            foreach ($seatPrices as $price) {
                                if (strtolower($price['wbtm_bus_bp_price_stop']) == strtolower($start) && strtolower($price['wbtm_bus_dp_price_stop']) == strtolower($end)) {
                                    if ((float)$price['wbtm_bus_price'] > 0) {
                                        $available_seat_type[$i]['type'] = 'Adult';
                                        $available_seat_type[$i]['price'] = $price['wbtm_bus_price'];
                                        $i++;
                                    }
                                    if ((float)$price['wbtm_bus_child_price'] > 0) {
                                        $available_seat_type[$i]['type'] = 'Child';
                                        $available_seat_type[$i]['price'] = $price['wbtm_bus_child_price'];
                                        $i++;
                                    }
                                    if ((float)$price['wbtm_bus_infant_price'] > 0) {
                                        $available_seat_type[$i]['type'] = 'Infant';
                                        $available_seat_type[$i]['price'] = $price['wbtm_bus_infant_price'];
                                        $i++;
                                    }
                                    break;
                                }  // end foreach
                            }
                        } // end if
                        ?>
                        <input type="hidden" name="wbtm_order_seat_plan" value="no">
                        <input type="hidden"
                               value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                               name="user_start_time"
                               id='user_start_time<?php echo get_the_id(); ?>'>
                        <input type="hidden" name="bus_start_time"
                               value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                               id='bus_start_time'>
                        <div class="mage-no-seat">
                            <div class="mage-no-seat-inner">
                                <div class="mage-no-seat-left">
                                    <table class="mage-seat-table mage-bus-short-info">
                                        <tr>
                                            <th><i class="fas fa-map-marker"></i>
                                                <?php mage_bus_label('wbtm_boarding_points_text', __('Boarding', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo $start; ?> ( <?php echo $bp_time; ?>
                                                )
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-map-marker"></i>
                                                <?php mage_bus_label('wbtm_dropping_points_text', __('Dropping', 'addon-bus--ticket-booking-with-seat-pro')) ?>
                                            </th>
                                            <td><?php echo $end; ?> ( <?php echo $dp_time; ?>
                                                )
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-bus" aria-hidden="true"></i>
                                                <?php mage_bus_label('wbtm_type_text', __('Coach Type:', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo mage_bus_type(); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-calendar"
                                                   aria-hidden="true"></i>
                                                <?php mage_bus_label('wbtm_date_text', __('Date:', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo get_wbtm_datetime($date, 'date'); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-clock" aria-hidden="true"></i>
                                                <?php mage_bus_label('wbtm_start_time_text', __('Start Time:', 'addon-bus--ticket-booking-with-seat-pro')) ?>
                                            </th>
                                            <td><?php echo $bp_time; ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-money-alt"></i>
                                                <?php mage_bus_label('wbtm_fare_text', __('Fare:', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo wc_price($seat_price); ?> /
                                                <?php mage_bus_label('wbtm_seat_text', __('Seat', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </td>
                                        </tr>
                                    </table>
                                    <img style="position: absolute;bottom: 6px;left: 6px;"
                                         class="wbtm-form-builder-loading"
                                         src="<?php echo WBTMPRO_PLUGIN_URL . '/public/images/loading.gif'; ?>"
                                         alt="">
                                </div>
                                <div class="mage-no-seat-right">
                                    <table class="mage-seat-table">
                                        <thead>
                                        <tr>
                                            <th><?php _e('Type', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                            <th><?php _e('Quantity', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                            <th><?php _e('Price', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                            <th><?php _e('SubTotal', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($available_seat_type as $type) : ?>
                                            <tr>
                                                <td><?php echo $type['type'] ?></td>
                                                <td class="mage-seat-qty">
                                                    <button class="wbtm-qty-change wbtm-qty-dec"
                                                            data-qty-change="dec">-
                                                    </button>
                                                    <input class="qty-input" type="text"
                                                           data-seat-type="<?php echo strtolower($type['type']); ?>"
                                                           data-price="<?php echo $type['price']; ?>"
                                                           name="seat_qty[]"/>
                                                    <button class="wbtm-qty-change wbtm-qty-inc"
                                                            data-qty-change="inc">+
                                                    </button>
                                                    <input type="hidden" name="passenger_type[]"
                                                           value="<?php echo $type['type'] ?>">
                                                    <input type="hidden" name="bus_dd[]"
                                                           value="no">
                                                </td>
                                                <td><?php echo get_woocommerce_currency_symbol() . $type['price'] . '<sub> / seat</sub>'; ?>
                                                </td>
                                                <td class="mage-seat-price">
                                                    <?php echo get_woocommerce_currency_symbol() . '<span class="price-figure">0.00</span>' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="4"></td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <strong><?php _e('Total', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                                                    :</strong></td>
                                            <td class="mage-price-total">
                                                <strong><?php echo get_woocommerce_currency_symbol(); ?>
                                                    <span
                                                            class="price-figure">0.00</span></strong>
                                            </td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                    <?php if($any_date_return_switch == 'on'): ?>
                                    <div class="wbtm_anydate_return_wrap">
                                        <div class="wbtm_anydate_return_col">
                                            <?php _e('Any Date Return:', 'bus-ticket-booking-with-seat-reservation') ?><br>
                                            <small><?php mage_bus_label('wbtm_anydate_return_desc_text', __('Same ticket will be valid for return up to next 15 days', 'bus-ticket-booking-with-seat-reservation')); ?></small>
                                        </div>
                                        <div class="wbtm_anydate_return_col">
                                            <div  class="wbtm_anydate_return_switch">
                                                <label for="wbtm_anydate_return_off"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="off" id="wbtm_anydate_return_off"> <span>off</span></label><label for="wbtm_anydate_return_on"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="on" id="wbtm_anydate_return_on"> <span>on</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($pickpoints) : ?>
                                        <div class="wbtm-pickpoint-wrap">
                                            <label for="wbtm-pickpoint-no-seat"
                                                   style="font-weight:700"><?php _e('Pickup Point:', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                            <select name="wbtm_pickpoint"
                                                    id="wbtm-pickpoint-no-seat" required>
                                                <option value=""><?php _e('Select Pickup Point', 'addon-bus--ticket-booking-with-seat-pro') ?></option>
                                                <?php foreach ($pickpoints as $point) :
                                                    $d = ucfirst($point['pickpoint']) . ' [' . $point['time'] . ']';
                                                    ?>
                                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Extra Services -->
                                    <?php
                                    if (function_exists('wbtm_extra_services_section')) {
                                        wbtm_extra_services_section($id);
                                    }
                                    ?>
                                    <!-- Extra Services END -->

                                    <div class="mage-grand-total" style="padding-right: 55px;">
                                        <p>
                                            <strong><?php _e('Grand Total', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                                                :</strong> <span
                                                    class="mage-price-figure">0.00</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="wbtm-form-builder">
                                <!-- <img class="wbtm-form-builder-loading" src="<?php //echo WBTMPRO_PLUGIN_URL.'/public/images/loading.gif';
                                ?>" alt=""> -->
                                <div class="wbtm-form-builder-adult wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                                <div class="wbtm-form-builder-child wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                                <div class="wbtm-form-builder-infant wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                                <div class="wbtm-form-builder-es wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                            </div>
                            <?php if($available_seat > 0) : ?>
                            <button class="mage_button no-seat-submit-btn wbtm_btn_primary"
                                    disabled
                                    type="submit" name="add-to-cart-admin"
                                    value="<?php echo get_post_meta($id, 'link_wc_product', true); ?>"
                                    class="single_add_to_cart_button">
                                <?php mage_bus_label('wbtm_book_now_text', __('Book Now', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php else : // With Seat Plan ?>
                        <input type="hidden" name="wbtm_order_seat_plan" value="yes">
                        <div style="display:flex;justify-content:space-between">
                            <?php $wbtmmain->wbtm_bus_seat_plan($wbtmmain->wbtm_get_this_bus_seat_plan(), $start, $date, false); ?>
                            <div class="bus-info-sec">
                                <?php
                                $price_arr = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_prices', true));
                                $fare = $wbtmmain->wbtm_get_bus_price($start, $end, $price_arr);
                                ?>

                                <div class="top-search-section">
                                    <div class="leaving-list">
                                        <h6>Route</h6>
                                        <div class="selected_route">
                                            <?php printf('<span>%s <i class="fa fa-long-arrow-right"></i> %s<span>', $start, $end); ?>
                                            (<?php echo get_woocommerce_currency_symbol(); ?><?php echo $wbtmmain->wbtm_get_bus_price($start, $end, $price_arr); ?>
                                            )
                                            <input type="hidden" name="bus_fare"
                                                   value="<?php echo $wbtmmain->wbtm_get_bus_price($start, $end, $price_arr); ?>">
                                        </div>
                                    </div>
                                    <div class="leaving-list">
                                        <h6>Date</h6>
                                        <div class="selected_date">
                                            <?php printf('<span>%s</span>', date('jS F, Y', strtotime($date))); ?>
                                        </div>
                                    </div>
                                    <div class="leaving-list">
                                        <h6>Start & Arrival Time</h6>
                                        <div class="selected_date">
                                            <?php
                                            // $bus_bp_array = get_post_meta(get_the_id(),'wbtm_bus_bp_stops',true);
                                            // $bus_dp_array = get_post_meta(get_the_id(),'wbtm_bus_next_stops',true);
                                            // $bp_time = $wbtmmain->wbtm_get_bus_start_time($start, $bus_bp_array);
                                            // $dp_time = $wbtmmain->wbtm_get_bus_end_time($end, $bus_dp_array);
                                            echo date('h:i A', strtotime($bp_time)) . ' <i class="fa fa-long-arrow-right"></i> ' . date('h:i A', strtotime($dp_time));
                                            ?>
                                            <input type="hidden"
                                                   value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                                                   name="user_start_time"
                                                   id='user_start_time<?php echo get_the_id(); ?>'>
                                            <input type="hidden" name="bus_start_time"
                                                   value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                                                   id='bus_start_time'>
                                        </div>
                                    </div>
                                </div>
                                <div class="seat-selected-list-fare">
                                    <table class="selected-seat-list<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?> selected-seat-table">
 
                                        <tr class='list_head<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>'>
                                            <th>Seat No</th>
                                            <th>Type</th>
                                            <th>Fare</th>
                                            <th>Remove</th>
                                        </tr>
                                        <tr>
                                            <td align="center">Total <span
                                                        id='total_seat<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>_booked'></span><input
                                                        type="hidden" value=""
                                                        id="tq<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"
                                                        name='total_seat' class="number"/></td>
                                            <td></td>
                                            <td align="center"><input type="hidden" value=""
                                                                      id="tfi<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"
                                                                      class="number"/>
                                                <div class="mage-price-total"><span
                                                            id="totalFare<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"></span>
                                                </div>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </table>
                                    <?php if($any_date_return_switch == 'on'): ?>
                                    <div class="wbtm_anydate_return_wrap">
                                        <div class="wbtm_anydate_return_col">
                                            <?php _e('Any Date Return:', 'bus-ticket-booking-with-seat-reservation') ?><br>
                                            <small><?php mage_bus_label('wbtm_anydate_return_desc_text', __('Same ticket will be valid for return up to next 15 days', 'bus-ticket-booking-with-seat-reservation')); ?></small>
                                        </div>
                                        <div class="wbtm_anydate_return_col">
                                            <div  class="wbtm_anydate_return_switch">
                                                <label for="wbtm_anydate_return_off"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="off" id="wbtm_anydate_return_off"> <span>off</span></label><label for="wbtm_anydate_return_on"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="on" id="wbtm_anydate_return_on"> <span>on</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>         
                                    <?php if ($pickpoints) : ?>
                                        <div class="wbtm-pickpoint-wrap">
                                            <label for="wbtm-pickpoint-no-seat"><?php _e('Pickup Point:', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                            <select name="wbtm_pickpoint"
                                                    id="wbtm-pickpoint-no-seat" required>
                                                <option value=""><?php _e('Select Pickup Point', 'addon-bus--ticket-booking-with-seat-pro') ?></option>
                                                <?php foreach ($pickpoints as $point) :
                                                    $d = ucfirst($point['pickpoint']) . ' [' . $point['time'] . ']';
                                                    ?>
                                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Extra Services -->
                                    <?php
                                    if (function_exists('wbtm_extra_services_section')) {
                                        wbtm_extra_services_section($id);
                                    }
                                    ?>
                                    <!-- Extra Services END -->

                                    <div class="mage-grand-total">
                                        <p>
                                            <strong><?php _e('Grand Total', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                                                :</strong> <span
                                                    class="mage-price-figure">0.00</span></p>
                                        <img style="position: absolute;top: -18px;left: 0;width:50px"
                                             class="wbtm-form-builder-loading"
                                             src="<?php echo WBTMPRO_PLUGIN_URL . '/public/images/loading.gif'; ?>"
                                             alt="">
                                    </div>
                                    <div class="mage_customer_info_area"></div>

                                    <div id="divParent<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"></div>
                                    <button id='bus-booking-btn<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>'
                                            type="submit" name="add-to-cart-admin"
                                            value="<?php echo esc_attr(get_the_id()); ?>"
                                            class="single_add_to_cart_button button alt btn-mep-event-cart wbtm_btn_primary">
                                        Book Now
                                    </button>
                                </div>

                            </div>
                        </div>

                        <?php
                        $uid = get_the_id() . $wbtmmain->wbtm_make_id($date);

                        $wbtmmain->wbtm_seat_booking_js($uid, $fare);
                    endif; ?>
                </form>
            </div>

            <?php

            ?>
        </li>

        <?php
    }

    
    function wbtm_admin_bus_list_item_return($id, $start, $end, $date, $seat_price, $dd)
    {
        global $wbtmmain;
        $values = get_post_custom(get_the_id());
        $total_seat = mage_bus_total_seat_new();
        // $sold_seat = $wbtmmain->wbtm_get_available_seat(get_the_id(), $date);
        // $available_seat = ($total_seat - $sold_seat);
        $available_seat = $total_seat - mage_partial_seat_booked_count(false);
        $bus_bp_array = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_bp_stops_return', true));
        $bus_dp_array = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_next_stops_return', true));
        $bp_time = $wbtmmain->wbtm_get_bus_start_time($start, $bus_bp_array);
        $dp_time = $wbtmmain->wbtm_get_bus_end_time($end, $bus_dp_array);

        // Pickpoint
        $pickpoints = mage_determine_pickuppoint($id, false, $start, $end);
        if ($pickpoints != '') {
            $pickpoints = maybe_unserialize($pickpoints);
        }

        $bus_bp_array = get_post_meta($id, 'wbtm_bus_bp_stops_return', true) ? get_post_meta($id, 'wbtm_bus_bp_stops_return', true) : [];
        $bus_bp_array = maybe_unserialize($bus_bp_array);
        if(mage_bus_is_midnight_trip($bus_bp_array, $start)) { // If Boarding point is after 11:59 pm then journey date will be search date +1 day
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
        }
        $seat_panel_settings = get_option('wbtm_bus_settings');
        $any_date_return_switch = !empty($seat_panel_settings['any_day_return']) ? $seat_panel_settings['any_day_return'] : 'off';
        ?>

        <li>
            <div class="admin-bus-list">
                <div class="admin-bus-list-item one bus-img">
                    <?php the_post_thumbnail('thumb'); ?>
                </div>
                <div class="admin-bus-list-item two">
                    <div class="">
                        <strong><?php the_title(); ?></strong>
                        <?php echo $values['wbtm_bus_no'][0]; ?>
                    </div>
                    <div class="">
                        <strong><?php echo date('h:i A', strtotime($bp_time)); ?></strong>
                    </div>
                </div>
                <div class="admin-bus-list-item three">
                    <div class="">
                        <strong><?php echo mage_bus_type($id); ?></strong>
                    </div>
                    <div class="">
                        <span class='seat-left'><?php echo $available_seat.' / '.mage_bus_total_seat_new().' '.__("Seat left", "bus-ticket-booking-with-seat-reservation"); ?></span>
                    </div>
                    <div class="" style="text-align: center">
                        <button class="wbtm_btn_primary admin-general-bus-detail-toggle">
                            View Seats
                        </button>
                    </div>
                </div>
            </div> <!-- Bus list header end -->
            <!-- Bus Detail -->
            <div class="admin-bus-details" data-bus-id="<?php echo $id; ?>"
                 id="admin-bus-details<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>">

                <form action="" method='post'>
                    <input type="hidden" name='journey_date' class="text"
                           value='<?php echo $date; ?>'/>
                    <input type="hidden" name='start_stops'
                           value="<?php echo $start; ?>"
                           class="hidden"/>
                    <input type='hidden' value='<?php echo $end; ?>'
                           name='end_stops'/>
                    <input type='hidden' value='<?php the_title(); ?>'
                           name='bus_name'/>
                    <input type="hidden" name="bus_id"
                           value="<?php echo get_the_id(); ?>">
                    <input type="hidden" name="wbtm_bus_type" value="general">
                    <input type="hidden" name="wbtm_anydate_return_price" id="wbtm_anydate_return_price" value=""/>
                    <?php
                    // Bus Seat Type
                    $bus_seat_type_conf = get_post_meta($id, 'wbtm_seat_type_conf', true);
                    if ($bus_seat_type_conf === 'wbtm_without_seat_plan') : // Without Seat Plan
                        // Price
                        $seatPrices = get_post_meta($id, 'wbtm_bus_prices_return', true);
                        $available_seat_type = array();
                        if ($seatPrices) {
                            $i = 0;
                            foreach ($seatPrices as $price) {
                                if (strtolower($price['wbtm_bus_bp_price_stop']) == strtolower($start) && strtolower($price['wbtm_bus_dp_price_stop']) == strtolower($end)) {
                                    if ((float)$price['wbtm_bus_price'] > 0) {
                                        $available_seat_type[$i]['type'] = 'Adult';
                                        $available_seat_type[$i]['price'] = $price['wbtm_bus_price'];
                                        $i++;
                                    }
                                    if ((float)$price['wbtm_bus_child_price'] > 0) {
                                        $available_seat_type[$i]['type'] = 'Child';
                                        $available_seat_type[$i]['price'] = $price['wbtm_bus_child_price'];
                                        $i++;
                                    }
                                    if ((float)$price['wbtm_bus_infant_price'] > 0) {
                                        $available_seat_type[$i]['type'] = 'Infant';
                                        $available_seat_type[$i]['price'] = $price['wbtm_bus_infant_price'];
                                        $i++;
                                    }
                                    break;
                                }  // end foreach
                            }
                        } // end if
                        ?>
                        <input type="hidden" name="wbtm_order_seat_plan" value="no">
                        <input type="hidden"
                               value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                               name="user_start_time"
                               id='user_start_time<?php echo get_the_id(); ?>'>
                        <input type="hidden" name="bus_start_time"
                               value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                               id='bus_start_time'>
                        <div class="mage-no-seat">
                            <div class="mage-no-seat-inner">
                                <div class="mage-no-seat-left">
                                    <table class="mage-seat-table mage-bus-short-info">
                                        <tr>
                                            <th><i class="fas fa-map-marker"></i>
                                                <?php mage_bus_label('wbtm_boarding_points_text', __('Boarding', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo $start; ?> ( <?php echo $bp_time; ?>
                                                )
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-map-marker"></i>
                                                <?php mage_bus_label('wbtm_dropping_points_text', __('Dropping', 'addon-bus--ticket-booking-with-seat-pro')) ?>
                                            </th>
                                            <td><?php echo $end; ?> ( <?php echo $dp_time; ?>
                                                )
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-bus" aria-hidden="true"></i>
                                                <?php mage_bus_label('wbtm_type_text', __('Coach Type:', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo mage_bus_type(); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-calendar"
                                                   aria-hidden="true"></i>
                                                <?php mage_bus_label('wbtm_date_text', __('Date:', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo get_wbtm_datetime($date, 'date'); ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-clock" aria-hidden="true"></i>
                                                <?php mage_bus_label('wbtm_start_time_text', __('Start Time:', 'addon-bus--ticket-booking-with-seat-pro')) ?>
                                            </th>
                                            <td><?php echo $bp_time; ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fa fa-money-alt"></i>
                                                <?php mage_bus_label('wbtm_fare_text', __('Fare:', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </th>
                                            <td><?php echo wc_price($seat_price); ?> /
                                                <?php mage_bus_label('wbtm_seat_text', __('Seat', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                                            </td>
                                        </tr>
                                    </table>
                                    <img style="position: absolute;bottom: 6px;left: 6px;"
                                         class="wbtm-form-builder-loading"
                                         src="<?php echo WBTMPRO_PLUGIN_URL . '/public/images/loading.gif'; ?>"
                                         alt="">
                                </div>
                                <div class="mage-no-seat-right">
                                    <table class="mage-seat-table">
                                        <thead>
                                        <tr>
                                            <th><?php _e('Type', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                            <th><?php _e('Quantity', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                            <th><?php _e('Price', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                            <th><?php _e('SubTotal', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($available_seat_type as $type) : ?>
                                            <tr>
                                                <td><?php echo $type['type'] ?></td>
                                                <td class="mage-seat-qty">
                                                    <button class="wbtm-qty-change wbtm-qty-dec"
                                                            data-qty-change="dec">-
                                                    </button>
                                                    <input class="qty-input" type="text"
                                                           data-seat-type="<?php echo strtolower($type['type']); ?>"
                                                           data-price="<?php echo $type['price']; ?>"
                                                           name="seat_qty[]"/>
                                                    <button class="wbtm-qty-change wbtm-qty-inc"
                                                            data-qty-change="inc">+
                                                    </button>
                                                    <input type="hidden" name="passenger_type[]"
                                                           value="<?php echo $type['type'] ?>">
                                                    <input type="hidden" name="bus_dd[]"
                                                           value="no">
                                                </td>
                                                <td><?php echo get_woocommerce_currency_symbol() . $type['price'] . '<sub> / seat</sub>'; ?>
                                                </td>
                                                <td class="mage-seat-price">
                                                    <?php echo get_woocommerce_currency_symbol() . '<span class="price-figure">0.00</span>' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="4"></td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <strong><?php _e('Total', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                                                    :</strong></td>
                                            <td class="mage-price-total">
                                                <strong><?php echo get_woocommerce_currency_symbol(); ?>
                                                    <span
                                                            class="price-figure">0.00</span></strong>
                                            </td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                    <?php if($any_date_return_switch == 'on'): ?>
                                    <div class="wbtm_anydate_return_wrap">
                                        <div class="wbtm_anydate_return_col">
                                            <?php _e('Any Date Return:', 'bus-ticket-booking-with-seat-reservation') ?><br>
                                            <small><?php mage_bus_label('wbtm_anydate_return_desc_text', __('Same ticket will be valid for return up to next 15 days', 'bus-ticket-booking-with-seat-reservation')); ?></small>
                                        </div>
                                        <div class="wbtm_anydate_return_col">
                                            <div  class="wbtm_anydate_return_switch">
                                                <label for="wbtm_anydate_return_off"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="off" id="wbtm_anydate_return_off"> <span>off</span></label><label for="wbtm_anydate_return_on"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="on" id="wbtm_anydate_return_on"> <span>on</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?> 
                                    <?php if ($pickpoints) : ?>
                                        <div class="wbtm-pickpoint-wrap">
                                            <label for="wbtm-pickpoint-no-seat"
                                                   style="font-weight:700"><?php _e('Pickup Point:', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                            <select name="wbtm_pickpoint"
                                                    id="wbtm-pickpoint-no-seat" required>
                                                <option value=""><?php _e('Select Pickup Point', 'addon-bus--ticket-booking-with-seat-pro') ?></option>
                                                <?php foreach ($pickpoints as $point) :
                                                    $d = ucfirst($point['pickpoint']) . ' [' . $point['time'] . ']';
                                                    ?>
                                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Extra Services -->
                                    <?php
                                    if (function_exists('wbtm_extra_services_section')) {
                                        wbtm_extra_services_section($id);
                                    }
                                    ?>
                                    <!-- Extra Services END -->

                                    <div class="mage-grand-total" style="padding-right: 55px;">
                                        <p>
                                            <strong><?php _e('Grand Total', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                                                :</strong> <span
                                                    class="mage-price-figure">0.00</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="wbtm-form-builder">
                                <!-- <img class="wbtm-form-builder-loading" src="<?php //echo WBTMPRO_PLUGIN_URL.'/public/images/loading.gif';
                                ?>" alt=""> -->
                                <div class="wbtm-form-builder-adult wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                                <div class="wbtm-form-builder-child wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                                <div class="wbtm-form-builder-infant wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                                <div class="wbtm-form-builder-es wbtm-form-builder-type-wrapper mage_customer_info_area"></div>
                            </div>
                            <?php if($available_seat > 0) : ?>
                            <button class="mage_button no-seat-submit-btn wbtm_btn_primary"
                                    disabled
                                    type="submit" name="add-to-cart-admin"
                                    value="<?php echo get_post_meta($id, 'link_wc_product', true); ?>"
                                    class="single_add_to_cart_button">
                                <?php mage_bus_label('wbtm_book_now_text', __('Book Now', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php else : // With Seat Plan ?>
                        <input type="hidden" name="wbtm_order_seat_plan" value="yes">
                        <div style="display:flex;justify-content:space-between">
                            <?php $wbtmmain->wbtm_bus_seat_plan($wbtmmain->wbtm_get_this_bus_seat_plan(), $start, $date, true); ?>
                            <div class="bus-info-sec">
                                <?php
                                $price_arr = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_prices_return', true));
                                $fare = $wbtmmain->wbtm_get_bus_price($start, $end, $price_arr);
                                ?>

                                <div class="top-search-section">
                                    <div class="leaving-list">
                                        <h6>Route</h6>
                                        <div class="selected_route">
                                            <?php printf('<span>%s <i class="fa fa-long-arrow-right"></i> %s<span>', $start, $end); ?>
                                            (<?php echo get_woocommerce_currency_symbol(); ?><?php echo $wbtmmain->wbtm_get_bus_price($start, $end, $price_arr); ?>
                                            )
                                            <input type="hidden" name="bus_fare"
                                                   value="<?php echo $wbtmmain->wbtm_get_bus_price($start, $end, $price_arr); ?>">
                                        </div>
                                    </div>
                                    <div class="leaving-list">
                                        <h6>Date</h6>
                                        <div class="selected_date">
                                            <?php printf('<span>%s</span>', date('jS F, Y', strtotime($date))); ?>
                                        </div>
                                    </div>
                                    <div class="leaving-list">
                                        <h6>Start & Arrival Time</h6>
                                        <div class="selected_date">
                                            <?php
                                            // $bus_bp_array = get_post_meta(get_the_id(),'wbtm_bus_bp_stops',true);
                                            // $bus_dp_array = get_post_meta(get_the_id(),'wbtm_bus_next_stops',true);
                                            // $bp_time = $wbtmmain->wbtm_get_bus_start_time($start, $bus_bp_array);
                                            // $dp_time = $wbtmmain->wbtm_get_bus_end_time($end, $bus_dp_array);
                                            echo date('h:i A', strtotime($bp_time)) . ' <i class="fa-solid fa-arrow-right"></i> ' . date('h:i A', strtotime($dp_time));
                                            ?>
                                            <input type="hidden"
                                                   value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                                                   name="user_start_time"
                                                   id='user_start_time<?php echo get_the_id(); ?>'>
                                            <input type="hidden" name="bus_start_time"
                                                   value="<?php echo date('h:i A', strtotime($bp_time)); ?>"
                                                   id='bus_start_time'>
                                        </div>
                                    </div>
                                </div>
                                <div class="seat-selected-list-fare">
                                    <table class="selected-seat-list<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?> selected-seat-table">
                                        <tr class='list_head<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>'>
                                            <th>Seat No</th>
                                            <th>Type</th>
                                            <th>Fare</th>
                                            <th>Remove</th>
                                        </tr>
                                        <tr>
                                            <td align="center">Total <span
                                                        id='total_seat<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>_booked'></span><input
                                                        type="hidden" value=""
                                                        id="tq<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"
                                                        name='total_seat' class="number"/></td>
                                            <td></td>
                                            <td align="center"><input type="hidden" value=""
                                                                      id="tfi<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"
                                                                      class="number"/>
                                                <div class="mage-price-total"><span
                                                            id="totalFare<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"></span>
                                                </div>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </table>
                                    <?php if($any_date_return_switch == 'on'): ?>
                                    <div class="wbtm_anydate_return_wrap">
                                        <div class="wbtm_anydate_return_col">
                                            <?php _e('Any Date Return:', 'bus-ticket-booking-with-seat-reservation') ?><br>
                                            <small><?php mage_bus_label('wbtm_anydate_return_desc_text', __('Same ticket will be valid for return up to next 15 days', 'bus-ticket-booking-with-seat-reservation')); ?></small>
                                        </div>
                                        <div class="wbtm_anydate_return_col">
                                            <div  class="wbtm_anydate_return_switch">
                                                <label for="wbtm_anydate_return_off"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="off" id="wbtm_anydate_return_off"> <span>off</span></label><label for="wbtm_anydate_return_on"><input type="radio" name="wbtm_anydate_return" class="wbtm_anydate_return" value="on" id="wbtm_anydate_return_on"> <span>on</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>                                    
                                    <?php if ($pickpoints) : ?>
                                        <div class="wbtm-pickpoint-wrap">
                                            <label for="wbtm-pickpoint-no-seat"><?php _e('Pickup Point:', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                            <select name="wbtm_pickpoint"
                                                    id="wbtm-pickpoint-no-seat" required>
                                                <option value=""><?php _e('Select Pickup Point', 'addon-bus--ticket-booking-with-seat-pro') ?></option>
                                                <?php foreach ($pickpoints as $point) :
                                                    $d = ucfirst($point['pickpoint']) . ' [' . $point['time'] . ']';
                                                    ?>
                                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Extra Services -->
                                    <?php
                                    if (function_exists('wbtm_extra_services_section')) {
                                        wbtm_extra_services_section($id);
                                    }
                                    ?>
                                    <!-- Extra Services END -->

                                    <div class="mage-grand-total">
                                        <p>
                                            <strong><?php _e('Grand Total', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                                                :</strong> <span
                                                    class="mage-price-figure">0.00</span></p>
                                        <img style="position: absolute;top: -18px;left: 0;width:50px"
                                             class="wbtm-form-builder-loading"
                                             src="<?php echo WBTMPRO_PLUGIN_URL . '/public/images/loading.gif'; ?>"
                                             alt="">
                                    </div>
                                    <div class="mage_customer_info_area"></div>

                                    <div id="divParent<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"></div>
                                    <button id='bus-booking-btn<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>'
                                            type="submit" name="add-to-cart-admin"
                                            value="<?php echo esc_attr(get_the_id()); ?>"
                                            class="single_add_to_cart_button button alt btn-mep-event-cart wbtm_btn_primary">
                                        Book Now
                                    </button>
                                </div>

                            </div>
                        </div>

                        <?php
                        $uid = get_the_id() . $wbtmmain->wbtm_make_id($date);
                        $wbtmmain->wbtm_seat_booking_js($uid, $fare);
                    endif; ?>
                </form>
            </div>

            <?php

            ?>
        </li>

        <?php
    }

    add_action('woocommerce_order_status_changed', 'wbtm_pro_bus_ticket_seat_management', 10, 99);
    function wbtm_pro_bus_ticket_seat_management($order_id, $from_status, $to_status, $order)
    {
        global $wpdb;
        // Getting an instance of the order object
        $order = wc_get_order($order_id);
        $order_meta = get_post_meta($order_id);
        $created_by = get_post_meta($order_id, '_created_via', true);
        $fare = $order->get_total();

        if ($order->has_status('processing') || $order->has_status('pending') || $order->has_status('on-hold')) {
            if ($created_by == 'Admin Dashboard') {
                $status = 1;
                $table_name = $wpdb->prefix . 'wbtm_bus_booking_list';
                $wpdb->query($wpdb->prepare("UPDATE $table_name 
                SET status = %d 
             WHERE order_id = %d", $status, $order_id)
                );
                // $fare = wbtm_get_total_of_order($order_id);
                update_post_meta($order_id, '_order_total', $fare);
            }
        }

        if ($order->has_status('cancelled')) {
            if ($created_by == 'Admin Dashboard') {
                $status = 3;
                $table_name = $wpdb->prefix . 'wbtm_bus_booking_list';
                $wpdb->query($wpdb->prepare("UPDATE $table_name 
                SET status = %d 
             WHERE order_id = %d", $status, $order_id)
                );
                // $fare = wbtm_get_total_of_order($order_id);
                update_post_meta($order_id, '_order_total', $fare);
            }
        }

        if ($order->has_status('completed')) {
            if ($created_by == 'Admin Dashboard') {
                $status = 2;
                $table_name = $wpdb->prefix . 'wbtm_bus_booking_list';
                $wpdb->query($wpdb->prepare("UPDATE $table_name 
                SET status = %d 
             WHERE order_id = %d", $status, $order_id)
                );
                // $fare = wbtm_get_total_of_order($order_id);
                update_post_meta($order_id, '_order_total', $fare);
            }
        }

    }

}