<?php
if (is_admin()) {


    function wbtm_generate_order_key()
    {
        return 'wc_' . apply_filters('woocommerce_generate_order_key', 'order_' . wp_generate_password(13, false));
    }


    add_action('admin_menu', 'wbtm_admin_purchase_ticket_menu');
    function wbtm_admin_purchase_ticket_menu()
    {
        add_submenu_page('edit.php?post_type=wbtm_bus', __('Purchase Ticket', 'addon-bus--ticket-booking-with-seat-pro'), __('Purchase Ticket', 'addon-bus--ticket-booking-with-seat-pro'), 'manage_options', 'admin_purchase_ticket', 'wbtm_admin_purchase_ticket');
    }

    function wbtm_admin_purchase_ticket()
    {
        global $wpdb, $magepdf, $wbtmmain, $wbtmpublic;


        $start = isset($_GET['bus_start_route']) ? strip_tags($_GET['bus_start_route']) : '';
        $end = isset($_GET['bus_end_route']) ? strip_tags($_GET['bus_end_route']) : '';
        $date = isset($_GET['j_date']) ? strip_tags($_GET['j_date']) : date('Y-m-d');
        $rdate = isset($_GET['r_date']) ? strip_tags($_GET['j_date']) : date('Y-m-d');


        if (isset($_POST['add-to-cart-admin'])) {
            
            $start = sanitize_text_field($_POST['start_stops']);
            $end = sanitize_text_field($_POST['end_stops']);
            $j_date = sanitize_text_field($_POST['journey_date']);
            $seat_name = $wbtmmain->wbtm_array_strip($_POST['seat_name']);
            $count_seat = count($seat_name);
            $b_time = sanitize_text_field($_POST['user_start_time']);
            $j_time = sanitize_text_field($_POST['bus_start_time']);
            $bus_id = sanitize_text_field($_POST['bus_id']);
            $bus_fare = (sanitize_text_field($_POST['bus_fare']) * $count_seat);
            $add_datetime = date("Y-m-d h:i:s");
            $uname = 'Order &ndash; ' . $add_datetime;
            $seats = implode(",", $seat_name);
            $bus_name = sanitize_text_field($_POST['bus_name']);
            $fare = $wbtmmain->wbtm_array_strip($_POST['seat_fare']);
            $extra_bag_price = get_post_meta($bus_id, 'wbtm_extra_bag_price', true) ? get_post_meta($bus_id, 'wbtm_extra_bag_price', true) : 0;

            $passenger_label = $wbtmmain->wbtm_array_strip($_POST['passenger_label']);
            $passenger_type_num = $wbtmmain->wbtm_array_strip($_POST['passenger_type']);


// wbtm_add_order_item_lines($pid,$bus_name,$seats,$start,$end,$j_date,$j_time,$bus_id);
            $ext_bag = 0;

            if (isset($_POST['wbtm_user_name']) || ($_POST['wbtm_user_email']) || ($_POST['wbtm_user_phone']) || ($_POST['wbtm_user_address']) || ($_POST['wbtm_user_gender'])) {

                $wbtm_user_name = $_POST['wbtm_user_name'];
                $wbtm_user_email = $_POST['wbtm_user_email'];
                $wbtm_user_phone = $_POST['wbtm_user_phone'];
                $wbtm_user_address = $_POST['wbtm_user_address'];
                $wbtm_user_gender = $_POST['wbtm_user_gender'];
                $extra_bag_quantity = $_POST['extra_bag_quantity'];


                $count_user = count($wbtm_user_name);
                for ($iu = 0; $iu < $count_user; $iu++) {

                    if ($wbtm_user_name[$iu] != '') :
                        $user[$iu]['wbtm_user_name'] = stripslashes(strip_tags($wbtm_user_name[$iu]));
                    endif;

                    if ($wbtm_user_email[$iu] != '') :
                        $user[$iu]['wbtm_user_email'] = stripslashes(strip_tags($wbtm_user_email[$iu]));
                    endif;

                    if ($wbtm_user_phone[$iu] != '') :
                        $user[$iu]['wbtm_user_phone'] = stripslashes(strip_tags($wbtm_user_phone[$iu]));
                    endif;

                    if ($wbtm_user_address[$iu] != '') :
                        $user[$iu]['wbtm_user_address'] = stripslashes(strip_tags($wbtm_user_address[$iu]));
                    endif;

                    if ($wbtm_user_gender[$iu] != '') :
                        $user[$iu]['wbtm_user_gender'] = stripslashes(strip_tags($wbtm_user_gender[$iu]));
                    endif;

                    if ($extra_bag_quantity[$iu] != '') :
                        $user[$iu]['wbtm_extra_bag_qty'] = stripslashes(strip_tags($extra_bag_quantity[$iu]));
                        $ext_bag = $ext_bag + $extra_bag_quantity[$iu];
                    endif;


                    $wbtm_form_builder_data = get_post_meta($bus_id, 'wbtm_form_builder_data', true);
                    if ($wbtm_form_builder_data) {
                        foreach ($wbtm_form_builder_data as $_field) {
                            if ($wbtm_user_ticket_type[$iu] != '') :
                                $user[$iu][$_field['wbtm_fbc_id']] = stripslashes(strip_tags($_POST[$_field['wbtm_fbc_id']][$iu]));
                            endif;
                        }
                    }

                }
            } else {
                $user = "";
            }

            $extra_bag_price = ($extra_bag_price * $ext_bag);
            $bus_fare = array_sum($fare) + $extra_bag_price;


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
            update_post_meta($pid, '_order_total', $bus_fare);
            update_post_meta($pid, '_customer_user', get_current_user_id());
            update_post_meta($pid, '_payment_method', 'cod');
            update_post_meta($pid, '_payment_method_title', 'Cash on delivery');
            update_post_meta($pid, '_created_via', 'Admin Dashboard');
            update_post_meta($pid, '_completed_date', $add_datetime);
            update_post_meta($pid, '_recorded_sales', 'yes');
            update_post_meta($pid, '_recorded_coupon_usage_counts', 'yes');
            update_post_meta($pid, '_order_stock_reduced', 'yes');


            $order_id = $pid;
            $user_id = get_current_user_id();
            $usr_inf = $user;
            $counter = 0;


            $next_stops = maybe_serialize($wbtmmain->wbtm_get_all_stops_after_this($bus_id, $start, $end));
            // echo '<pre>';
            // print_r($passenger_label);
            // print_r($passenger_type_num);
            // print_r($fare);
            // die;
            foreach ($seat_name as $_seats) {
                // echo $counter;
                if (!empty($_seats)) {

// if(isset($_POST['custom_reg_user']) && ($_POST['custom_reg_user'])=='yes'){
                    if (isset($usr_inf[$counter]['wbtm_user_name'])) {
                        $user_name = $usr_inf[$counter]['wbtm_user_name'];
                    } else {
                        $user_name = "";
                    }
                    if (isset($usr_inf[$counter]['wbtm_user_email'])) {
                        $user_email = $usr_inf[$counter]['wbtm_user_email'];
                    } else {
                        $user_email = "";
                    }
                    if (isset($usr_inf[$counter]['wbtm_user_phone'])) {
                        $user_phone = $usr_inf[$counter]['wbtm_user_phone'];
                    } else {
                        $user_phone = "";
                    }
                    if (isset($usr_inf[$counter]['wbtm_user_address'])) {
                        $user_address = $usr_inf[$counter]['wbtm_user_address'];
                    } else {
                        $user_address = "";
                    }


                    if (isset($usr_inf[$counter]['wbtm_user_gender'])) {
                        $user_gender = $usr_inf[$counter]['wbtm_user_gender'];
                    } else {
                        $user_gender = "";
                    }


                    if (isset($usr_inf[$counter]['wbtm_extra_bag_qty'])) {
                        $wbtm_extra_bag_qty = $usr_inf[$counter]['wbtm_extra_bag_qty'];
                    } else {
                        $wbtm_extra_bag_qty = 0;
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
                    
                    

// }


// $check_before_add = wbtm_get_order_seat_check($bus_id,$order_id,$_seats,$b_time,$j_date);


// if($check_before_add==0){

                    $add_ticket = $wbtmmain->create_bus_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, $b_time, $j_time, $_seats, $fare_s, $j_date, $add_datetime, $user_name, $user_email, $passenger_label_s, $passenger_type_num_s, $user_phone, $user_gender, $user_address, $wbtm_extra_bag_qty, $usr_inf, $counter, 2);

// if($add_ticket){
                    $download_url = $magepdf->get_invoice_ajax_url(array('order_id' => $order_id));
                    ?>
                    <div id="message" class="updated notice notice-success is-dismissible">
                        <p><?php _e('Seat Booked Successfully', 'addon-bus--ticket-booking-with-seat-pro') ?>. <a
                                    class="wbtm-button" href="<?php echo $download_url; ?>"
                                    style="background: green;color: #fff;padding: 5px 20px;font-size: 14px;font-style: normal;text-decoration: none;margin: 0 auto;width: 200px;display: inline;text-align: center;"><?php _e('Download Ticket', 'addon-bus--ticket-booking-with-seat-pro'); ?></a>
                        </p>
                        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>
                    <?php
                    // }
                    // }
                }
                $counter++;
            }


        }


        ob_start();
        ?>
        <style type="text/css">
            .wp-admin .search-radio-sec {
                display: none;
            }
        </style>
        <div class="wbtm-search-form-sec the_select">
            <form action="" method="get">
                <input type="hidden" name="post_type" value="wbtm_bus">
                <input type="hidden" name="page" value="admin_purchase_ticket">
                <?php $wbtmmain->wbtm_bus_search_fileds($start, $end, $date, $rdate); //do_action('wbtm_search_fields');
                ?>
            </form>
        </div>

        <ul class="admin-bus-list">
            <?php
            if (isset($_GET['bus_start_route']) && ($_GET['bus_end_route']) && ($_GET['j_date'])) {
                $args_search_qqq = array(
                    'post_type' => array('wbtm_bus'),
                    'posts_per_page' => -1,
                    'order' => 'ASC',
                    'orderby' => 'meta_value',
                    'meta_key' => 'wbtm_bus_start_time',
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
                    )

                );


                $loop = new WP_Query($args_search_qqq);
                while ($loop->have_posts()) {
                    $loop->the_post();
                    $values = get_post_custom(get_the_id());
                    $term = get_the_terms(get_the_id(), 'wbtm_bus_cat');

                    $total_seat = $values['wbtm_total_seat'][0];
                    $sold_seat = $wbtmmain->wbtm_get_available_seat(get_the_id(), $date);
                    $available_seat = ($total_seat - $sold_seat);

                    $price_arr = get_post_meta(get_the_id(), 'wbtm_bus_prices', true);
                    $bus_bp_array = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_bp_stops', true));
                    $bus_dp_array = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_next_stops', true));
                    $bp_time = $wbtmmain->wbtm_get_bus_start_time($start, $bus_bp_array);
                    $dp_time = $wbtmmain->wbtm_get_bus_end_time($end, $bus_dp_array);

                    $bus_meta = get_post_custom(get_the_id());
                    $next_stops_arr = get_post_meta(get_the_id(), 'wbtm_bus_next_stops', true);
                    $wbtm_bus_bp_stops = get_post_meta(get_the_id(), 'wbtm_bus_bp_stops', true);
                    $count = 1;
                    $term = get_the_terms(get_the_id(), 'wbtm_bus_cat');
                    $price_arr = get_post_meta(get_the_id(), 'wbtm_bus_prices', true);


                    ?>
                    <li>
                        <div class="admin-bus-list">
                            <div class="bus-name">
                                <strong><?php the_title(); ?></strong>
                            </div>
                            <div class="bus-name">
                                <strong><?php echo $values['wbtm_bus_no'][0]; ?></strong>
                            </div>
                            <div class="bus-name">
                                <strong><?php echo date('h:i A', strtotime($bp_time)); ?></strong>
                            </div>
                            <div class="bus-name" style="text-align: center">
                                <span class='seat-left'><?php echo $available_seat; ?> Seat Left</span>
                            </div>
                            <div class="bus-name" style="text-align: right">
                                <button id="view_panel_<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>">
                                    View Seats
                                </button>
                            </div>
                        </div>
                        <div style='display: none;' class="admin-bus-details"
                             id="admin-bus-details<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>">


                            <?php
                            $current_plan = get_post_meta(get_the_id(), 'seat_plan', true);
                            if ($current_plan) {
                                $current_seat_plan = $current_plan;
                            } else {
                                $current_seat_plan = 'seat_plan_1';
                            }
                            $wbtmmain->wbtm_bus_seat_plan($wbtmmain->wbtm_get_this_bus_seat_plan(), $start, $date);
                            ?>
                            <div class="bus-info-sec">
                                <?php
                                $price_arr = maybe_unserialize(get_post_meta(get_the_id(), 'wbtm_bus_prices', true));
                                $fare = $wbtmmain->wbtm_get_bus_price($start, $end, $price_arr);
                                ?>
                                <form action="" method='post'>
                                    <div class="top-search-section">
                                        <div class="leaving-list">
                                            <input type="hidden" name='journey_date' class="text"
                                                   value='<?php echo $date; ?>'/>
                                            <input type="hidden" name='start_stops' value="<?php echo $start; ?>"
                                                   class="hidden"/>
                                            <input type='hidden' value='<?php echo $end; ?>' name='end_stops'/>
                                            <input type='hidden' value='<?php the_title(); ?>' name='bus_name'/>
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
                                                                          class="number"/><span
                                                            id="totalFare<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"></span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </table>
                                        <div id="divParent<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>"></div>
                                        <input type="hidden" name="bus_id" value="<?php echo get_the_id(); ?>">
                                        <button id='bus-booking-btn<?php echo get_the_id() . $wbtmmain->wbtm_make_id($date); ?>'
                                                type="submit" name="add-to-cart-admin"
                                                value="<?php echo esc_attr(get_the_id()); ?>"
                                                class="single_add_to_cart_button button alt btn-mep-event-cart">Book Now
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php
                        $uid = get_the_id() . $wbtmmain->wbtm_make_id($date);
                        $wbtmmain->wbtm_seat_booking_js($uid, $fare);
                        ?>
                    </li>
                    <?php
                }
            }
            ?>
        </ul>
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