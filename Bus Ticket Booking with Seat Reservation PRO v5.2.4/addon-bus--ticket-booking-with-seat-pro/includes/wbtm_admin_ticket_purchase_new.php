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

        $bus_stops = get_terms('wbtm_bus_stops', array(
            'hide_empty' => false,
        ));

        if (isset($_GET['wbtm_admin_bus_search'])) {
            $start = isset($_GET['wbtm_start']) ? strip_tags($_GET['wbtm_start']) : '';
            $end = isset($_GET['wbtm_end']) ? strip_tags($_GET['wbtm_end']) : '';
            $date = isset($_GET['j_date']) ? strip_tags($_GET['j_date']) : date('Y-m-d');
            $rdate = isset($_GET['r_date']) ? strip_tags($_GET['j_date']) : date('Y-m-d');

            $args = array(
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

            $loop = new WP_Query($args);
        }
        ?>

        <div class="wbtm-page-wrap">
            <ul class="wbtm_tab_link_wrap">
                <li class="clickme">
                    <button data-tag="general"
                            class="wbtm_tab_link wbtm_btn_primary wbtm_tab_active"><?php _e('General', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
                </li>
                <li class="clickme">
                    <button data-tag="subscription"
                            class="wbtm_tab_link wbtm_btn_primary"><?php _e('Subscription', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
                </li>
            </ul>
            <div style="clear: both;"></div>
            <div id="container">
                <div class="wbtm_content_item" id="general">
                    <div class="wbtm_content_item_wrap">
                        <?php if (!empty($bus_stops)) : ?>
                            <form action="" method="GET">
                                <input type="hidden" name="post_type" value="wbtm_bus">
                                <input type="hidden" name="page" value="admin_purchase_ticket">
                                <div class="wbtm_search_form_inner">
                                    <div class="wbtm_field_group">
                                        <label for="wbtm_start"><?php _e('Boarding Point', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                        <select name="wbtm_start" id="wbtm_start">
                                            <?php foreach ($bus_stops as $route) : ?>
                                                <option value="<?php echo $route->name ?>"><?php echo $route->name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="wbtm_field_group">
                                        <label for="wbtm_end"><?php _e('Dropping Point', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                        <select name="wbtm_end" id="wbtm_end">
                                            <?php foreach ($bus_stops as $route) : ?>
                                                <option value="<?php echo $route->name ?>"><?php echo $route->name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="wbtm_field_group">
                                        <label for="j_date"><?php _e('Journey date', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                                        <input type="text" id="j_date">
                                    </div>
                                    <div class="wbtm_field_group">
                                        <input type="submit" name="wbtm_admin_bus_search" class="wbtm_btn_primary"
                                               value="<?php _e('Search', 'addon-bus--ticket-booking-with-seat-pro'); ?>"/>
                                    </div>
                                </div>
                            </form>
                        <?php else :
                            echo '<p>' . __('No stops found', 'addon-bus--ticket-booking-with-seat-pro') . '</p>';
                        endif; ?>

                        <!--Search result-->
                        <div class="wbtm_bus_list_wrap">
                            <div class="bus_list_header do-flex">
                                <div class="bus_img do-flex">
                                    <div><p>Image</p></div>
                                </div>
                                <div class="left do-flex">
                                    <div><p>Bus Name</p></div>
                                    <div><p>Schedule</p></div>
                                    <div><p>Type</p></div>
                                </div>
                                <div class="right do-flex">
                                    <div><p>Fare</p></div>
                                    <div><p>Seats Available</p></div>
                                    <div><p>View</p></div>
                                </div>
                            </div>

                            <?php
                            if (isset($_GET['wbtm_admin_bus_search'])) :
                                if ($loop->found_posts > 0) :
                                    while ($loop->have_posts()) :
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

                                        <div class="wbtm_bus_list_item">
                                            <div class="wbtm_bus_list_item_inner">
                                                <div class="item_top do-flex">
                                                    <div class="bus_img do-flex">
                                                        <div>
                                                            <img src="http://magebus.local/wp-content/uploads/2020/07/download-200x149.jpeg"
                                                                 alt="">
                                                        </div>
                                                    </div>
                                                    <div class="left do-flex">
                                                        <div><p>Bus Name</p></div>
                                                        <div><p>Schedule</p></div>
                                                        <div><p>Type</p></div>
                                                    </div>
                                                    <div class="right do-flex">
                                                        <div><p>Fare</p></div>
                                                        <div><p>Seats Available</p></div>
                                                        <div>
                                                            <p>
                                                                <button class="wbtm_bus_detail_btn">View</button>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="item_bottom">
                                                    <div class="item_bottom_inner">
                                                        <div class="left">

                                                        </div>
                                                        <div class="middle"></div>
                                                        <div class="right"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php

                                    endwhile;
                                endif;
                            endif;
                            ?>
                        </div>
                        <!--Search result END-->
                    </div>
                </div>
                <div class="wbtm_content_item hide" id="subscription">TWO</div>
            </div>
        </div>

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