<?php
/*
* Template Name : Flat
* ----------------------------
* Author        : @MagePeople 
*/

if (!defined('ABSPATH')) exit;  // if direct access
global $wbtmmain, $magepdf;
$logo = wp_get_attachment_url($wbtmmain->bus_get_option('pdf_logo', 'ticket_manager_settings', ''));
$bg = wp_get_attachment_url($wbtmmain->bus_get_option('pdf_bacckground_image', 'ticket_manager_settings', ''));
$bg_color = $wbtmmain->bus_get_option('pdf_backgroud_color', 'ticket_manager_settings', '#fff');
$text_color = $wbtmmain->bus_get_option('pdf_text_color', 'ticket_manager_settings', '#000');
$tc_title = $wbtmmain->bus_get_option('pdf_terms_title', 'ticket_manager_settings', '');
$tc_text = $wbtmmain->bus_get_option('pdf_terms_text', 'ticket_manager_settings', '');
$company_address = $wbtmmain->bus_get_option('pdf_company_address', 'ticket_manager_settings', '');
$company_phone = $wbtmmain->bus_get_option('pdf_company_phone', 'ticket_manager_settings', '');
$company_email = $wbtmmain->bus_get_option('pdf_company_email', 'ticket_manager_settings', '');
$date_format = get_option('date_format');
$time_format = get_option('time_format');
$datetimeformat = $date_format . '  ' . $time_format;
?>
<style>
    .mep_ticket_body {
        background: <?php echo $bg_color; ?>;
        padding: 0;
        position: relative;
        color: <?php echo (($text_color) ? $text_color : '#000') ?>;
    }

    .pdf-header {
        text-align: left;
        border-bottom: 1px solid #666;
        margin-bottom: 30px;
        padding-bottom: 20px;
    }

    .pdf-header img {
        max-width: 200px;
        height: auto;
    }

    .pdf-header p {
        margin: 0;
        padding: 0;
        font-size: 12px
    }

    .wbtm-ticket {
        padding: 10px;
        border: 1px solid #666;
        width: 100%;
    }

    .pdf-header h4,
    .pdf-header h5 {
        padding: 0;
        margin: 10px 0;
        font-size: 14px;
    }

    /*Tickets*/
    .wbtm-ticket-body {
        background: #fff;
        padding: 10px;
        margin: 40px 0 50px;
        width: 100%;
        overflow: hidden;
        display: block;
    }

    .wbtm-ticket-body table {
        width: 100%;
        overflow: hidden;
        display: block;
    }

    .wbtm-ticket-body table tr {
        border: 1px solid #fbfbfb !important;
    }

    .wbtm-ticket-body table tr td {
        padding: 6px;
        font-size: 15px;
        border-bottom: 1px solid #666;
    }

    .wbtm-ticket-body table tr td h3 {
        padding: 10px 0;
        margin: 0;
        font-size: 25px;
    }

    .ticket-search h2 {
        margin: 0;
        padding: 0 0 20px 0;
    }

    .ticket-search input {
        display: block;
        width: 100%;
        padding: 7px;
        margin-bottom: 10px;
    }

    .ticket-search button {
        background: #676fec;
        color: #fff;
        border: 0;
        padding: 10px 20px;
        font-size: 15px;
    }

    span.wbtm-ticket-hold {
        background: #d0d0d0;
        padding: 5px 20px;
    }

    span.wbtm-ticket-confirm {
        background: #02792f;
        padding: 5px 20px;
        color: #fff;
    }

    input.ticket-input {
        padding: 10px !important;
        margin-bottom: 30px !important;
    }

    .wbtm-ticket-single {
        padding: 3px;
    }

    .wbtm-ticket-single .wbtm-ticket-inline {
        /* display: inline-block; */
        float: left;
        width: 48%;
        position: relative;
    }

    .wbtm-bus-title {
        font-size: 18px;
        font-weight: 700;
        color: red;
    }

    .wbtm-ticket-qr-code {
        text-align: center;
    }

    .wbtm-ticket-qr-code img {
        width: 110px;
        border: 1px solid #666;
        margin-bottom: 20px;
    }

    .mep_event_ticket_terms h3 {
        font-size: 15px;
    }

    .mep_event_ticket_terms {
        text-align: center;
        margin: 30px 0;
        /* border-top:1px solid #666; */
        font-size: 12px;
    }

    .page_break {
        page-break-after: always;
    }

    .page_break:last-child {
        page-break-after: auto;
    }

    .bus-ticket-logo img {
        width: 80px;
    }

    .border {
        border-bottom: 1px solid #666;
        padding-bottom: 4px;
    }

    .wbtm_pdf_table {
        border-collapse: separate;
        width: 100%;
    }

    .wbtm_pdf_table th,
    .wbtm_pdf_table td {
        text-align: left;
        border: 1px solid #aaa;
        font-size: 14px;
        padding: 5px;
    }

    .wbtm_pdf_table th {
        background: #ccc;
    }
</style>
<!-- <div class='pdf-ticket-body' > -->
<?php

$args = array(
    'post_type' => 'wbtm_bus_booking',
    'posts_per_page' => -1,
    'meta_query' => array(
        // 'relation' => 'AND',
        array(
            'key' => 'wbtm_order_id',
            'value' => $order_id,
            'compare' => '='
        ),

    ),
);

$ticket_query = new WP_Query($args);
//echo '<div class="wbtm-ticket-body wbtm-tickets">';

// Merge pdf ticket setting
$get_settings = get_option('wbtm_bus_settings');
$get_val = isset($get_settings['merge_pdf_ticket']) ? $get_settings['merge_pdf_ticket'] : '';
$merge_pdf_ticket = $get_val ? $get_val : null;

$ticket_count = count($ticket_query->posts);
$seat_all = '';
$total_fare = 0;
$index = 0;
$name_array = array();
$extra_services = array();
foreach ($ticket_query->posts as $_ticket) {
    $index++;
    $ticket = $_ticket->ID;
    $order_id = get_post_meta($ticket, 'wbtm_order_id', true);
    $user_id = get_post_meta($ticket, 'wbtm_user_id', true);
    $bus_id = get_post_meta($ticket, 'wbtm_bus_id', true);
    $boarding = get_post_meta($ticket, 'wbtm_boarding_point', true);
    $dropping = get_post_meta($ticket, 'wbtm_droping_point', true);
    $start_time = get_post_meta($ticket, 'wbtm_bus_start', true);
    $seat = get_post_meta($ticket, 'wbtm_seat', true);
    $fare = get_post_meta($ticket, 'wbtm_bus_fare', true);
    $journey_date = get_post_meta($ticket, 'wbtm_journey_date', true);
    $booking_date = get_post_meta($ticket, 'wbtm_booking_date', true);
    $status = get_post_meta($ticket, 'wbtm_status', true);
    $name = get_post_meta($ticket, 'wbtm_user_name', true);
    $email = get_post_meta($ticket, 'wbtm_user_email', true);
    $phone = get_post_meta($ticket, 'wbtm_user_phone', true);
    $gender = get_post_meta($ticket, 'wbtm_user_gender', true);
    $address = get_post_meta($ticket, 'wbtm_user_address', true);
    $wbtm_user_extra_bag = get_post_meta($ticket, 'wbtm_user_extra_bag', true);
    $passenger_type = get_post_meta($ticket, 'wbtm_passenger_type', true);
    $original_fare = get_post_meta($ticket, 'wbtm_bus_fare', true);
    $discounted_fare = get_post_meta($ticket, '_wbtm_tp', true);
    if ($discounted_fare && $original_fare > $discounted_fare) {
        $calculated_fare = $discounted_fare;
    } else {
        $calculated_fare = $original_fare;
    }

    $show_dropping_time = get_post_meta($bus_id, 'show_dropping_time', true);
    $show_dropping_time = $show_dropping_time ? $show_dropping_time : 'yes';

    // $extra_bag      = get_post_meta($ticket,'wbtm_user_extra_bag',true);

    $pin = $order_id . "-" . $ticket . "-" . $user_id . "-" . $bus_id;
    $bus_type = get_post_meta($bus_id, 'wbtm_seat_type_conf', true);
    $get_settings = get_option('wbtm_bus_settings');
    $pdf_price_is_show = (isset($get_settings['show_hide_pdf_price']) && $get_settings['show_hide_pdf_price'] === 'hide' ? false : true);
    $order = wc_get_order($order_id);
    // $order_data = $order->get_data();
    // echo '<pre>'; print_r($order_data); die;
    // $price_arr      = get_post_meta($bus_id,'wbtm_bus_prices',true);
    // $fare           = wbtm_get_bus_price($ticket->boarding_point,$ticket->droping_point, $price_arr);

    // Extra Services
    $extra_services = array();
    $extra_services = get_post_meta($order_id, '_extra_services', true);

    // Subscription Bus
    $valid_till = '';
    $zone_text = '';
    $billing_type = get_post_meta($ticket, 'wbtm_billing_type', true);
    $zone = get_post_meta($ticket, 'wbtm_city_zone', true);

    if ($billing_type) {
        $billing_type_str = trim($billing_type);
        $valid_till = mtsa_calculate_valid_date($journey_date, $billing_type);
        if ($zone) {
            $zone = get_term($zone);
            $zone_text = $zone->name;
        }
    }
    // Subscription Bus END

    // Pickup Point
    $pickup_point = get_post_meta($ticket, 'wbtm_pickpoint', true);

    $order  = wc_get_order($order_id);

    if ($merge_pdf_ticket == 'yes') { // If pdf merge YES
        $total_fare += $calculated_fare;
        $name_array[] = $name;

        if ($ticket_count != $index) {
            $seat_all .= $seat . ', ';
            continue;
        } else {
            $seat_all .= $seat;
            // Comma separated Name on merge pdf
            if (count($name_array) > count(array_unique($name_array))) {
                $final_name = $name;
            } else {
                $final_name = implode(', ', $name_array);
            }
            // Comma separated Name on merge pdf END
        }
    } else { // If pdf merge NO
        $total_fare = $calculated_fare;
        $final_name = $name;
    }

    $wbtm_anydate_return = get_post_meta($order_id, '_wbtm_anydate_return', true);
    $wbtm_anydate_return_price = get_post_meta($order_id, '_wbtm_anydate_return_price', true);
    $wbtm_journy_date = get_post_meta($order_id, 'Journey Date', true);
    if (empty($wbtm_journy_date)) {
        $wbtm_journy_date = get_post_meta($ticket, 'wbtm_journey_date', true);
    }
    /*
    $all = get_post_meta($ticket);
    echo '<pre>';
    print_r($all);
    echo '</pre>';
    */
    if (empty($wbtm_anydate_return_price)) {
        foreach ($order->get_items() as $item_id => $item) {

            // Here you get your data
            $wbtm_anydate_return = wc_get_order_item_meta($item_id, '_wbtm_anydate_return', true) ? wc_get_order_item_meta($item_id, '_wbtm_anydate_return', true) : 'off';

            $wbtm_anydate_return_price = wc_get_order_item_meta($item_id, '_wbtm_anydate_return_price', true) ? wc_get_order_item_meta($item_id, '_wbtm_anydate_return_price', true) : 0;
        }
    }



?>
    <div class="wbtm-ticket-body wbtm-tickets">
        <div class="mep_ticket_body" <?php if ($bg) { ?>style="background: url(<?php echo $bg; ?>);" <?php } ?>>
            <div class="wbtm-ticket">
                <div class="pdf-header">
                    <div class="wbtm-ticket-single">
                        <div class="wbtm-ticket-inline">
                            <div class='bus-ticket-logo'>
                                <?php if (!empty($logo)) echo '<img style="width:110px;text-align:left" src="' . $logo . '" alt="logo"/>'; ?>
                            </div>
                        </div>
                        <div class="wbtm-ticket-inline">
                            <p><?php echo $company_address; ?> </p>
                            <p><?php echo $company_email; ?> </p>
                            <p><?php echo $company_phone; ?> </p>
                        </div>
                    </div>
                </div>
                <div class="wbtm-ticket-qr-code">
                    <?php do_action('before_wbtm_qr_display', $pin); ?>
                    <div class='qr-code'>
                        <?php do_action('wbtm_qr_display', $pin); ?>
                    </div>
                    <?php do_action('after_wbtm_qr_display', $pin); ?>
                </div>

                <div class="wbtm-ticket-single">
                    <div class="wbtm-ticket-inline border">
                        <h3><?php echo get_the_title($bus_id); ?> </h3>
                    </div>
                    <div class="wbtm-ticket-inline border">
                        <strong><?php _e('PIN:', 'addon-bus--ticket-booking-with-seat-pro'); ?> </strong> <?php echo $pin; ?>
                    </div>
                </div>

                <div class="wbtm-ticket-single">
                    <?php if ($name) { ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Name:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $final_name; ?>
                        </div>
                    <?php }
                    if ($phone) { ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Phone:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $phone; ?>
                        </div>
                    <?php } else { ?>
                        <div class="wbtm-ticket-inline"></div> <?php } ?>
                </div>

                <div class="wbtm-ticket-single">
                    <?php if ($gender) { ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Gender:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $gender; ?>
                        </div>
                    <?php }
                    if ($email) { ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Email:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $email; ?>
                        </div>
                    <?php } else { ?>
                        <div class="wbtm-ticket-inline"></div> <?php }
                                                                ?>
                </div>

                <?php if ($wbtm_user_extra_bag) { ?>
                    <div class="wbtm-ticket-single">
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Extra Bag:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $wbtm_user_extra_bag; ?>
                        </div>
                    </div>
                    <?php }

                $reg_form_arr = maybe_unserialize(get_post_meta($ticket, 'wbtm_user_additional', true));
                $extra_services = maybe_unserialize(get_post_meta($order_id, '_extra_services', true));

                if (is_array($reg_form_arr) && sizeof($reg_form_arr) > 0) {
                    foreach ($reg_form_arr as $builder) {
                    ?>
                        <div class="wbtm-ticket-single">
                            <div class="wbtm-ticket-inline border">
                                <strong><?php echo $builder['name']; ?>:</strong>
                                <?php echo $builder['value']; ?>
                            </div>
                        </div>

                <?php
                    }
                } ?>

                <div class="wbtm-ticket-single">
                    <?php if ($billing_type) : ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Start Date:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo mage_wp_date($journey_date); ?>
                        </div>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Valid Till:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo mage_wp_date($valid_till); ?>
                        </div>
                    <?php else : ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Journey Date:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo mage_wp_date($wbtm_journy_date); ?>
                        </div>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Time:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo mage_wp_time($start_time); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="wbtm-ticket-single">

                    <?php if ($zone) : ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Zone:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $zone_text; ?>
                        </div>
                    <?php else : ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Boarding:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $boarding; ?>
                        </div>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Dropping:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $dropping; ?>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="wbtm-ticket-single">
                    <?php if ($pickup_point) { ?>
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Pickup Point:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $pickup_point; ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="wbtm-ticket-single">
                    <div class="wbtm-ticket-inline border">
                        <strong><?php _e('Seat:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo ($merge_pdf_ticket == 'yes' ? $seat_all : $seat); ?>
                    </div>
                    <div class="wbtm-ticket-inline border">
                        <strong><?php echo mage_bus_setting_value('bus_menu_label', 'Bus') . ' ' . __('No', 'bus-ticket-booking-with-seat-reservation'); ?></strong> <?php echo get_post_meta($bus_id, 'wbtm_bus_no', true); ?>
                    </div>
                </div>

                <?php if ($passenger_type != '') : ?>
                    <div class="wbtm-ticket-single">
                        <div class="wbtm-ticket-inline border">
                            <strong><?php _e('Passenger Type:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $passenger_type; ?>
                        </div>
                    </div>
                <?php endif; ?>


                <div class="wbtm-ticket-single">
                    <?php if ($pdf_price_is_show) : ?>
                        <div class="wbtm-ticket-inline">
                            <strong><?php _e('Price:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo $original_fare > $total_fare ? '<span style="text-decoration:line-through">' . wc_price($original_fare) . '</span> ' . wc_price($total_fare) : wc_price($total_fare); ?>
                        </div>
                    <?php endif; ?>
                    <div class="wbtm-ticket-inline">
                        <strong><?php _e('Purchase on:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong> <?php echo mage_wp_date($booking_date); ?>
                    </div>
                </div>

                <?php if ($wbtm_anydate_return == 'on') { ?>
                    <div class="wbtm-ticket-single">
                        <div class="wbtm-ticket-inline">
                            <strong><?php _e('Any Date Return Price:', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>

                            <?php echo wc_price($wbtm_anydate_return_price); ?>
                        </div>
                        <small><?php mage_bus_label('wbtm_anydate_return_desc_text', __('Same ticket will be valid for return up to next 15 days', 'addon-bus--ticket-booking-with-seat-pro')); ?></small>
                    </div>
                <?php } ?>

            </div>
        </div>
        <div class="mep_tkt_row">
            <div class="mep_ticket_body_col_12">
                <div class="mep_event_ticket_terms">
                    <?php if ($tc_title) {
                        echo "<h3>" . $tc_title . "</h3>";
                    }
                    if ($tc_text) {
                        echo $tc_text;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php if (($ticket_count) != $index) : ?>
        <div class="page_break"></div>
    <?php endif; ?>

<?php
}

// Extra SErvice
if ($extra_services) :
    $extra_services = maybe_unserialize($extra_services);
?>
    <div class="page_break"></div>
    <h3><?php _e('Extra Service', 'addon-bus--ticket-booking-with-seat-pro'); ?>:</h3>
    <span style="background: #ccc"><?php echo __('Order No', 'addon-bus--ticket-booking-with-seat-pro') . ': ' . $order_id; ?></span>
    <table class="wbtm_pdf_table">
        <thead>
            <tr>
                <th><?php _e('Service Name', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Quantity', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Unit Price', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Total', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php

            foreach ($extra_services as $service) :
                echo '<tr>';
                echo '<td>' . $service['name'] . '</td>';
                echo '<td>' . $service['qty'] . '</td>';
                echo '<td>' . wc_price($service['price']) . '</td>';
                echo '<td>' . wc_price($service['price'] * $service['qty']) . '</td>';
                echo '</tr>';
            endforeach;

            ?>
        </tbody>
    </table>
<?php endif; ?>
<!-- Extra SErvice END -->