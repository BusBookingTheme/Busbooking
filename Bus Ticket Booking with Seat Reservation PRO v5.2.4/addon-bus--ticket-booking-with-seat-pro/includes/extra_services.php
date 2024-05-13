<?php
if (!defined('ABSPATH')) exit;  // if direct access

add_action('admin_init', 'wbtm_extra_service_init');
function wbtm_extra_service_init() {
    if( !session_id() )
        session_start();
}

add_action('admin_menu', 'wbtm_extra_service_menu');
function wbtm_extra_service_menu()
{
    add_submenu_page('edit.php?post_type=wbtm_bus', __('Extra Service', 'addon-bus--ticket-booking-with-seat-pro'), __('Extra Service', 'addon-bus--ticket-booking-with-seat-pro'), 'extra_service_wbtm_bus', 'extra_service', 'wbtm_extra_service_callback');
}

function wbtm_extra_service_callback()
{
    echo '<div style="padding-right: 15px;">';


    // Extra Service Edit page
    $mode = isset($_GET['mode']) ? $_GET['mode'] : null;
    if(isset($_GET['order_id']) && $mode === 'edit') {
        $e_order_id = $_GET['order_id'];

        if(isset($_POST['exs_update'])) {
            $exs_name = isset($_POST['exs_name']) ? $_POST['exs_name'] : '';
            $exs_qty = isset($_POST['exs_qty']) ? $_POST['exs_qty'] : '';
            $exs_price = isset($_POST['exs_price']) ? $_POST['exs_price'] : '';

            $exs_arr = array();

            if(is_array($exs_name) && !empty($exs_name)) {
                $exs_i = 0;
                foreach($exs_name as $exs) {
                    $exs_arr[$exs_i]['name']    = $exs;
                    $exs_arr[$exs_i]['qty']     = $exs_qty[$exs_i];
                    $exs_arr[$exs_i]['price']   = $exs_price[$exs_i];

                    $exs_i++;
                }
            }
            // echo '<pre>'; print_r($exs_arr); die;
            update_post_meta($e_order_id, '_extra_services', $exs_arr);
            
            // $order = wc_get_order($e_order_id);
            // foreach ($order->get_items() as $item_id => $item_obj) {
            //     $has_extra_service = wc_get_order_item_meta($item_id, '_extra_services');
            //     if($has_extra_service) {
            //         wc_update_order_item_meta($item_id, '_extra_services', $exs_arr);
            //     }
            // }

            // update_post_meta($_POST['booking_id'], 'wbtm_extra_services', maybe_serialize($exs_arr));
            $_SESSION['wbtm_exs_update'] = __('Sucessfully updated!', 'addon-bus--ticket-booking-with-seat-pro');
            wp_redirect(admin_url('edit.php?post_type=wbtm_bus&page=extra_service'));

        }


        echo '<h2>'.__('Extra Service Edit', 'addon-bus--ticket-booking-with-seat-pro').'</h2>';

        ?>
        <form action="" method="POST">
            <table class="wp-list-table widefat striped posts extra_service_list_table" style="width: 100%;margin-bottom: 10px;">
                <thead>
                    <tr>
                        <td><?php _e('Name', 'addon-bus--ticket-booking-with-seat-pro') ?></td>
                        <td><?php _e('Qty', 'addon-bus--ticket-booking-with-seat-pro') ?></td>
                        <td><?php _e('Unit Price', 'addon-bus--ticket-booking-with-seat-pro') ?></td>
                        <td><?php _e('Total Price', 'addon-bus--ticket-booking-with-seat-pro') ?></td>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $order = wc_get_order($e_order_id);
                        $extra_services = get_post_meta($e_order_id, '_extra_services', true);
                        if($extra_services) {
                            $extra_services = maybe_unserialize($extra_services);
                            foreach($extra_services as $serv) {
                                echo '<tr>';
                                echo '<td>'.$serv['name'].'<input type="hidden" name="exs_name[]" value="'.$serv['name'].'"</td>';
                                echo '<td class="exs_change_value"><input type="number" name="exs_qty[]" value="'.$serv['qty'].'"></td>';
                                echo '<td class="exs_change_value">'.get_woocommerce_currency_symbol().'<input type="text" name="exs_price[]" value="'.$serv['price'].'"></td>';
                                echo '<td class="exs_total">'.get_woocommerce_currency_symbol().'<span class="price_figure">'.($serv['qty'] * $serv['price']).'</span></td>';
                                echo '</tr>';
                            }
                        }
                    ?>
                </tbody>
            </table>
            <input class="wbtm_btn" type="submit" name="exs_update" value="<?php _e('Update', 'addon-bus--ticket-booking-with-seat-pro'); ?>">
            <a class="wbtm_btn" href="<?php echo admin_url('edit.php?post_type=wbtm_bus&page=extra_service') ?>"><?php _e('Go back', 'addon-bus--ticket-booking-with-seat-pro'); ?></a>
        </form>
        <?php

        return;
    }



    // Extra Service List page
    echo '<div style="display: flex;justify-content: space-between;align-items: center;">';
    echo '<h2>'.__('Extra Service', 'addon-bus--ticket-booking-with-seat-pro').'</h2>';
    if(isset($_SESSION['wbtm_exs_update'])) {
        echo '<div id="wbtm_notification" style="background: #8bc34a;color: #fff;padding: 5px 40px;">'.$_SESSION['wbtm_exs_update'].'</div>';
        unset($_SESSION['wbtm_exs_update']);
        echo '<script>setTimeout(function(){ document.getElementById("wbtm_notification").remove() }, 5000)</script>';
    }
    echo '</div>';

    // Filter
    wbtm_extra_service_menu_filter();

    // Filter query
    $has_filter = false;
    $filter_text ='';

    $current_user_id = get_current_user_id();
    $by_user = array();
    if(!current_user_can('administrator') && $current_user_id) {
        $by_user = array(
            'key' => 'wbtm_user_id',
            'value' => $current_user_id,
            'compare' => '='
        );
    }

    $meta_query = array(
        'relation' => 'AND',
        array(
            'key' => 'wbtm_status',
            'compare' => 'IN',
            'value' => array(1, 2),
        ),
        $by_user
    );

    $filter_query = array(
        'relation' => 'AND',
    );

    // Bus Id
    $bus_id = (isset($_GET['bus_id']) ? $_GET['bus_id'] : null);
    if ($bus_id) {
        $bus_id = explode('-', $bus_id);
        $bus_id = $bus_id[0];

        array_push($filter_query, array(
            'key' => 'wbtm_bus_id',
            'value' => $bus_id,
            'compare' => '='
        ));
        $has_filter = true;
        $filter_text .= ' <span class="mage-filted_item">Bus -> ' . $bus_id . '</span>';
    }
    // Journey Date
    $j_date = (isset($_GET['j_date']) ? $_GET['j_date'] : null);
    if ($j_date) {
        array_push($filter_query, array(
            'key' => 'wbtm_journey_date',
            'value' => $j_date,
            'compare' => '='
        ));
        $has_filter = true;
        $filter_text .= ' <span class="mage-filted_item">Journey Date -> ' . $j_date . '</span>';
    }
    
    // Booking Date
    $filter_booking_date = (isset($_GET['filter_booking_date']) ? $_GET['filter_booking_date'] : null);
    if ($filter_booking_date) {
        array_push($filter_query, array(
            'key' => 'wbtm_booking_date',
            'value' => $filter_booking_date,
            'compare' => 'LIKE'
        ));
        $has_filter = true;
        $filter_text .= ' <span class="mage-filted_item">Booking date -> ' . $filter_booking_date . '</span>';
    }
    // Order ID
    $filter_order_id = (isset($_GET['filter_order_id']) ? $_GET['filter_order_id'] : null);
    if ($filter_order_id) {
        array_push($filter_query, array(
            'key' => 'wbtm_order_id',
            'value' => $filter_order_id,
            'compare' => '='
        ));
        $has_filter = true;
        $filter_text .= ' <span class="mage-filted_item">Order ID -> ' . $filter_order_id . '</span>';
    }

    if ($has_filter) {
        array_push($meta_query, $filter_query);
    }

    $args = array(
        'post_type' => 'wbtm_bus_booking',
        'posts_per_page' => -1,
        'order' => 'DESC',
        'meta_query' => array(
            $meta_query
        )
    );

    $res = new WP_Query($args);

    if(isset($_GET['export_extra_service'])) {
        wbtm_extra_service_export($res);
    }

    ?>
    <div style="text-align: right;padding: 10px 0;">
        <form method='get' action="edit.php">
            <input type="hidden" name='bus_id' value="<?php echo(isset($_GET['bus_id']) ? $_GET['bus_id'] : '') ?>"/>
            <input type="hidden" name='j_date' value="<?php echo(isset($_GET['j_date']) ? $_GET['j_date'] : '') ?>"/>
            <input type="hidden" name="filter_booking_date" value="<?php echo(isset($_GET['filter_booking_date']) ? $_GET['filter_booking_date'] : '') ?>">
            <input type="hidden" name="filter_order_id" value="<?php echo(isset($_GET['filter_order_id']) ? $_GET['filter_order_id'] : '') ?>">
            <input type="hidden" name="post_type" value="wbtm_bus">
            <input type="hidden" name="page" value="extra_service">
            <input type="hidden" name='noheader' value="1"/>
            <input type="hidden" name='export_extra_service' value="export_extra_service"/>
            <input type="submit" name='export' id="csvExport" value="<?php _e('Export to CSV', 'addon-bus--ticket-booking-with-seat-pro'); ?>"/>
        </form>
    </div>

    <table class="wp-list-table widefat striped posts extra_service_list_table" style="width: 100%">
        <thead>
            <tr>
                <th>Sl</th>
                <th><?php _e('Order No', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Billing Name', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Bus Name', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Booking Date', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Journey Date', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Boarding', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Dropping', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Extra Service', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Action', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
            </tr>
        </thead>
        <tbody>
    <?php

    if($res->found_posts > 0) {
        $extra_services = array();
        $j = 0;
        while( $res->have_posts() ) :
            $res->the_post();
            $id = get_the_ID();
            $order_id = get_post_meta($id, 'wbtm_order_id', true);
            $order = wc_get_order($order_id);
            $extra_services = get_post_meta($order_id, '_extra_services', true);

            $current_order = $order_id;
            $prev_order = (isset($prev_order) ? $prev_order : $current_order);

            if($current_order != $prev_order || $j == 0) :
                if($extra_services) : ?>
                    <tr>
                        <td><?php echo $j+1; ?></td>
                        <td>#<?php echo $order_id; ?></td>
                        <td><?php echo ($order ? $order->get_formatted_billing_full_name() : ""); ?></td>
                        <td><?php echo get_the_title(get_post_meta($id, 'wbtm_bus_id', true)); ?></td>
                        <td><?php echo get_post_meta($id, 'wbtm_booking_date', true); ?></td>
                        <td><?php echo get_post_meta($id, 'wbtm_journey_date', true); ?></td>
                        <td><?php echo get_post_meta($id, 'wbtm_boarding_point', true); ?></td>
                        <td><?php echo get_post_meta($id, 'wbtm_droping_point', true); ?></td>
                        <td>
                            <?php 
                            foreach($extra_services as $service) :
                                echo $service['name'].' => '.$service['qty'].' x '.wc_price($service['price']).' = '.wc_price($service['qty'] * $service['price']).'<br>';
                            endforeach; ?>
                        </td>
                        <td>
                            <a class="wbtm_btn" href="<?php echo admin_url('edit.php?post_type=wbtm_bus&page=extra_service&mode=edit&order_id='.$order_id) ?>">Edit</a>
                        </td>
                    </tr>
            <?php endif;
            
            $j++;
            $prev_order = $current_order;
            endif;
            
        endwhile;
        wp_reset_postdata();
    }
    ?>
        </tbody>
    </table>
    </div>

    <?php
}

// Filter
function wbtm_extra_service_menu_filter()
{
    $bus_id = (isset($_GET['bus_id']) ? $_GET['bus_id'] : null);
    ?>

    <form action="<?php echo get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=extra_service" method="get">
        <input type="hidden" name="post_type" value="wbtm_bus">
        <input type="hidden" name="page" value="extra_service">

        <div class="mage-custom-filter-area">
            <span class="mage-custom-filter-text"><?php _e('Filter list', 'addon-bus--ticket-booking-with-seat-pro') ?></span>
            <a href="?post_type=wbtm_bus&page=extra_service"
                class="mage-custom-filter-clear"><?php _e('Clear all filter', 'addon-bus--ticket-booking-with-seat-pro') ?></a>
            <div class="mage-custom-filter-item">
                <label for="mage-f-bus-type"><?php echo mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'); ?></label>
                <select id="mage-f-bus-type" name="bus_id">
                    <option value=""><?php echo __('By', 'addon-bus--ticket-booking-with-seat-pro').' '.mage_bus_setting_value('bus_menu_label', 'Bus'); ?></option>
                    <?php
                    $args = array(
                        'post_type' => 'wbtm_bus',
                        'posts_per_page' => -1
                    );
                    $loop = new WP_Query($args);
                    while ($loop->have_posts()) {
                        $loop->the_post();
                        $start = get_post_meta(get_the_id(), 'wbtm_bus_no', true);
                        $busit = get_the_id() . "-" . $start;
                        ?>
                        <option value="<?php echo $busit; ?>" <?php if ($busit == $bus_id) {
                            echo 'selected';
                        } ?>><?php the_title(); ?> - <?php echo $start; ?></option>
                        <?php
                    }
                    wp_reset_postdata();
                    ?>
                </select>
            </div>

            <div class="mage-custom-filter-item">
                <label for="mage-f-journey-date"><?php _e('Journey Date','addon-bus--ticket-booking-with-seat-pro'); ?></label>
                <input id="mage-f-journey-date" type="text" id="ja_date" name="j_date"
                        placeholder="<?php echo mage_wp_date(date('Y-m-d')); ?>"
                        value="<?php echo(isset($_GET['j_date']) ? $_GET['j_date'] : '') ?>">
            </div>

            <div class="mage-custom-filter-item">
                <label for="filter_booking_date"><?php _e('Booking Date','addon-bus--ticket-booking-with-seat-pro'); ?></label>
                <input type="text" id="filter_booking_date" name="filter_booking_date"
                        placeholder="<?php echo mage_wp_date(date('Y-m-d')); ?>"
                        value="<?php echo(isset($_GET['filter_booking_date']) ? $_GET['filter_booking_date'] : '') ?>">
            </div>

            <div class="mage-custom-filter-item">
                <label for="filter_order_id"><?php _e('Order Id','addon-bus--ticket-booking-with-seat-pro'); ?></label>
                <input type="text" id="filter_order_id" name="filter_order_id"
                        placeholder="0001"
                        value="<?php echo(isset($_GET['filter_order_id']) ? $_GET['filter_order_id'] : '') ?>">
            </div>


            <div class="mage-custom-filter-item" style="flex-basis:20%;margin-bottom:0">
                <button type="submit"><?php _e('Filter', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
            </div>

        </div>

    </form>

    <?php
}

// Export CSV
function wbtm_extra_service_export($res)
{
    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'Passenger_list' . $domain . '_' . time() . '.csv';
    $header = array('Order No', 'Billing Name', mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'), 'Booking Date', 'Journey Date', 'Boarding', 'Dropping', 'Extra Services');
    if($res->found_posts > 0) {
        $data = array();
        $extra_services = array();
        while($res->have_posts()) {
            $extra_services_text = '';
            $res->the_post();
            $id = get_the_ID();
            $order_id = get_post_meta($id, 'wbtm_order_id', true);
            $order = wc_get_order($order_id);
            // $items = $order->get_items();
            // foreach($order->get_items() as $item) {
            //     $extra_services = $item->get_meta('_extra_services');
            // }
            $extra_services = get_post_meta($order_id, '_extra_services', true);
            if($extra_services) {
                $extra_services = maybe_unserialize($extra_services);
                $i = 0;
                foreach($extra_services as $service) :
                    $extra_services_text .= $service['name'].' => '.$service['qty'].' x '.$service['price'].' = '.$service['qty'] * $service['price'].(count($extra_services) - 1 == $i ? '' : ' + ');
                    $i++;
                endforeach;

                $data[] = array(
                    $order_id,
                    ($order ? $order->get_formatted_billing_full_name() : ""),
                    get_the_title(get_post_meta($id, 'wbtm_bus_id', true)),
                    get_post_meta($id, 'wbtm_booking_date', true),
                    get_post_meta($id, 'wbtm_journey_date', true),
                    get_post_meta($id, 'wbtm_boarding_point', true),
                    get_post_meta($id, 'wbtm_droping_point', true),
                    $extra_services_text
                );
            }
        }
        wp_reset_postdata();
        // echo '<pre>'; print_r($data); die;
        $fh = @fopen('php://output', 'w');
        ob_clean();
        fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-type: text/csv');
        header("Content-Disposition: attachment; filename={$filename}");
        header('Expires: 0');
        header('Pragma: public');
        fputcsv($fh, $header);
        foreach ($data as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);
        ob_end_flush();
        die();
    }
}