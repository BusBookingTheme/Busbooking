<?php

if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

add_shortcode('view-ticket', 'wbtm_pro_view_tickets');
function wbtm_pro_view_tickets($atts, $content = null)
{
    global $wpdb;
    if (isset($_GET['pin'])) {
        $pin = strip_tags($_GET['pin']);
    } else {
        $pin = "";
    }
?>
    <div class="ticket-search">
        <form action="<?php echo get_site_url(); ?>/view-ticket" method="get">
            <h2>Search Ticket</h2>
            <input required type="text" placeholder="Enter Ticket PIN" value='<?php echo $pin; ?>' name='pin' class='ticket-input' />
            <button type="submit" class="ticket-btn"><?php _e('Search Ticket','addon-bus--ticket-booking-with-seat-pro') ?></button>
        </form>
    </div>
    <?php
    if (is_user_logged_in()) {
        if (isset($_GET['pin'])) {
            $pin = strip_tags($_GET['pin']);
            $pin_arr = explode('-', $pin);
            $order_id = $pin_arr[0];
            $booking_id = $pin_arr[1];
            $user_id = $pin_arr[2];
            $bus_id = $pin_arr[3];
            $user_id = get_current_user_id();

            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'post__in' => array($booking_id),
                'meta_query'  => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'wbtm_bus_id',
                            'value' => $bus_id,
                            'compare' => '='
                        ),
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'wbtm_status',
                            'value'   => 1,
                            'compare' => '='
                        ),
                        array(
                            'key'     => 'wbtm_status',
                            'value' => 2,
                            'compare' => '='
                        ),
                    )
                )
            );
            $passenger = new WP_Query($args);


            foreach ($passenger->posts as $_ticket) {
                $ticket = $_ticket->ID;
                $order_id       = get_post_meta($ticket, 'wbtm_order_id', true);
                $user_id        = get_post_meta($ticket, 'wbtm_user_id', true);
                $bus_id         = get_post_meta($ticket, 'wbtm_bus_id', true);
                $boarding       = get_post_meta($ticket, 'wbtm_boarding_point', true);
                $dropping       = get_post_meta($ticket, 'wbtm_droping_point', true);
                $start_time     = get_post_meta($ticket, 'wbtm_bus_start', true);
                $seat           = get_post_meta($ticket, 'wbtm_seat', true);
                $fare           = get_post_meta($ticket, 'wbtm_bus_fare', true);
                $journey_date   = get_post_meta($ticket, 'wbtm_journey_date', true);
                $booking_date   = get_post_meta($ticket, 'wbtm_booking_date', true);
                $status         = get_post_meta($ticket, 'wbtm_status', true);
                $name           = get_post_meta($ticket, 'wbtm_user_name', true);
                $email          = get_post_meta($ticket, 'wbtm_user_email', true);
                $phone          = get_post_meta($ticket, 'wbtm_user_phone', true);
                $gender         = get_post_meta($ticket, 'wbtm_user_gender', true);
                $address        = get_post_meta($ticket, 'wbtm_user_address', true);
                $extra_bag      = get_post_meta($ticket, 'wbtm_user_extra_bag', true);
                $pin            = $order_id . "-" . $ticket . "-" . $user_id . "-" . $bus_id;
                $order          = wc_get_order($order_id);
    ?>
                <div class="wbtm-ticket-body wbtm-view-ticket">

                    <div class="wbtm-ticket">
                        <div class="wbtm-ticket-qr-code">
                            <div class="wbtm-ticket-qr-code">
                                <?php do_action('before_wbtm_qr_display', $pin); ?>
                                <div class='qr-code'>
                                    <?php do_action('wbtm_qr_display', $pin); ?>
                                </div>
                                <?php do_action('after_wbtm_qr_display', $pin); ?>
                            </div>
                        </div>
                        <div class="wbtm-ticket-single">
                            <div class="wbtm-ticket-inline wbtm-bus-title">

                                <strong><?php _e('Bus', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo get_the_title($bus_id); ?> </h3>
                            </div>
                            <div class="wbtm-ticket-inline"><strong><?php _e('PIN', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $pin; ?> </div>
                        </div>

                        <div class="wbtm-ticket-single">
                            <?php if ($name) { ?>
                                <div class="wbtm-ticket-inline"><strong><?php _e('Name', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $name; ?></div>
                            <?php } elseif ($phone) { ?>
                                <div class="wbtm-ticket-inline"><strong><?php _e('Phone', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $phone; ?></div>
                            <?php } else { ?><div class="wbtm-ticket-inline"></div> <?php } ?>
                        </div>

                        <div class="wbtm-ticket-single">
                            <?php if ($gender) { ?>
                                <div class="wbtm-ticket-inline"><strong><?php _e('Gender', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $gender; ?></div>
                            <?php } elseif ($email) { ?>
                                <div class="wbtm-ticket-inline"><strong><?php _e('Email', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $email; ?></div>
                            <?php } else { ?><div class="wbtm-ticket-inline"></div> <?php } ?>
                        </div>

                        <?php if ($address) { ?>
                            <div class="wbtm-ticket-single">
                                <div class="wbtm-ticket-inline"><strong><?php _e('Address', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $address; ?></div>
                            </div>
                        <?php } ?>

                        <div class="wbtm-ticket-single">
                            <div class="wbtm-ticket-inline"><strong><?php _e('Journey Date', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo get_wbtm_datetime($journey_date, 'date-time'); ?></div>
                            <div class="wbtm-ticket-inline"><strong><?php _e('Time', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo get_wbtm_datetime($start_time, 'time'); ?></div>
                        </div>

                        <div class="wbtm-ticket-single">
                            <div class="wbtm-ticket-inline"><strong><?php _e('Boarding', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $boarding; ?></div>
                            <div class="wbtm-ticket-inline"><strong><?php _e('Dropping', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $dropping; ?></div>
                        </div>

                        <div class="wbtm-ticket-single">
                            <div class="wbtm-ticket-inline"><strong><?php _e('Seat', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo $seat; ?></div>
                            <div class="wbtm-ticket-inline"><strong><?php _e('Bus No', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo get_post_meta($bus_id, 'wbtm_bus_no', true); ?></div>
                        </div>

                        <div class="wbtm-ticket-single">
                            <div class="wbtm-ticket-inline"><strong><?php _e('Price', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo wc_price($fare);; ?></div>
                            <div class="wbtm-ticket-inline"><strong><?php _e('Purchased on', 'addon-bus--ticket-booking-with-seat-pro'); ?></strong>: <?php echo get_wbtm_datetime($booking_date, 'date-time-text'); ?></div>
                        </div>
                    </div>
                    <div style="text-align:center;margin:20px 0;">
                        <?php echo do_shortcode('[wbtm-ticket-link order-id="'.$order_id.'"]'); ?>
                    </div>
                </div>

        <?php
            }
        }
    } else {
        ?>
        <h3 align=center><?php _e('You need to login your account to view the ticket.', 'addon-bus--ticket-booking-with-seat-pro'); ?> </h3>
<?php
    }
}

add_shortcode('wbtm-ticket-link', 'wbtm_pro_ticket_link');
function wbtm_pro_ticket_link($atts) {
    global $magepdf;

    $prop = shortcode_atts( array(
        'order-id' => null
    ), $atts );

    $order_id = $prop['order-id'];

    if($order_id) {
        $link = get_site_url().'/?action=download_pdf_ticket&order_id='.$order_id;
        return '<a class="wbtm-download-ticket" href="'.$link.'">'.__("Download Ticket", "addon-bus--ticket-booking-with-seat-pro").'</a>';
    } else {
        return 'Order id not found!';
    }

}
