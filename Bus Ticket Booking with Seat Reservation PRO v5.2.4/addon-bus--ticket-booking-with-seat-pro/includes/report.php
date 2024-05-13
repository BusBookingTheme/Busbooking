<?php

add_action('admin_menu', 'wbtm_bus_report_menu');

function wbtm_bus_report_menu()
{
    add_submenu_page(null, __('Report', 'addon-bus--ticket-booking-with-seat-pro'), __('Report', 'addon-bus--ticket-booking-with-seat-pro'), 'manage_options', 'bus_report', 'wbtm_bus_report');
}

function wbtm_bus_report() {
    
    $seats_rows = array();
    $seat_col = array();
    $seats_rows_upper = array();
    $seat_col_upper = array();
    $bus_id = (isset($_GET['bus_id']) ? $_GET['bus_id'] : null);
    $j_date = (isset($_GET['j_date']) ? $_GET['j_date'] : null);
    if($bus_id) {
        $seats_rows = get_post_meta($bus_id, 'wbtm_bus_seats_info', true);
        $seat_col = get_post_meta($bus_id, 'wbtm_seat_cols', true);

        $seats_rows_upper = get_post_meta($bus_id, 'wbtm_bus_seats_info_dd', true);
        $seat_col_upper = get_post_meta($bus_id, 'wbtm_seat_cols_dd', true);
    }
    
    $seat_html = '';
    $seat_html_upper = '';
    ?>

    <div class="wrap">
        <div class="wbtm-page-top">
            <div class="wbtm-page-top-inner">
                <h3>
                    <?php _e('Select Report', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                </h3>
                <ul class="wbtm_tab_link_wrap">
                    <li class="clickme">
                        <a href="<?php echo get_admin_url().'edit.php?post_type=wbtm_bus&page=wbtm-reports'; ?>" class="wbtm_tab_link wbtm_btn_primary"><?php _e('Sells details Report', 'addon-bus--ticket-booking-with-seat-pro') ?></a>
                    </li>
                    <li class="clickme">
                        <a href="javascript: void(0)" class="wbtm_tab_link wbtm_btn_primary"><?php _e('Quick book status report', 'addon-bus--ticket-booking-with-seat-pro') ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <h2>Quick book status report</h2>
        <form action="<?php echo get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=bus_report" method="GET">
            <input type="hidden" name="post_type" value="wbtm_bus">
            <input type="hidden" name="page" value="bus_report">
            <div class="mage-custom-filter-area">

                <div class="mage-custom-filter-item">
                    <span class="mage-custom-filter-text"><?php _e('Filter list', 'addon-bus--ticket-booking-with-seat-pro') ?></span>
                    <select name="bus_id" id="select2" class="select2">
                        <option value=""><?php _e('By Bus', 'addon-bus--ticket-booking-with-seat-pro'); ?></option>
                        <?php
                        $args = array(
                            'post_type' => 'wbtm_bus',
                            'posts_per_page' => -1
                        );
                        $loop = new WP_Query($args);
                        while ($loop->have_posts()) {
                            $loop->the_post();
                            $start = get_post_meta(get_the_id(), 'wbtm_bus_no', true);
                            // $busit = get_the_id() . "-" . $start;
                            $busid = get_the_id();
                            ?>
                            <option value="<?php echo $busid; ?>" <?php echo ((isset($_GET['bus_id']) && $_GET['bus_id'] == $busid) ? 'selected' : '') ?>><?php the_title(); ?> - <?php echo $start; ?></option>
                            <?php
                        }
                        wp_reset_postdata();
                        ?>
                    </select>
                </div>

                <div class="mage-custom-filter-item">
                    <input type="text" id="ja_date" name="j_date" placeholder="<?php _e('By Journey Date', 'addon-bus--ticket-booking-with-seat-pro') ?>" value="<?php echo (isset($_GET['j_date']) ? $_GET['j_date'] : '') ?>">
                </div>

                <div class="mage-custom-filter-item">
                    <button type="submit"><?php _e('Filter', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
                </div>

            </div>
            
        </form>
    </div>
    
    <?php if( $bus_id && !empty($seats_rows) && !empty($seat_col) ) : ?>
    <div class="wbtm_seat_status_hint">
        <div class="wbtm_seat_hint_item wbtm_seat_free">
            <span></span>
            <span><?php _e('Available', 'addon-bus--ticket-booking-with-seat-pro') ?></span>
        </div>
        <div class="wbtm_seat_hint_item wbtm_seat_booked">
            <span></span>
            <span><?php _e('Booked', 'addon-bus--ticket-booking-with-seat-pro') ?></span>
        </div>
        <div class="wbtm_seat_hint_item wbtm_seat_sold">
            <span></span>
            <span><?php _e('Sold', 'addon-bus--ticket-booking-with-seat-pro') ?></span>
        </div>
    </div>
    <?php endif; ?>
    <div class="wbtm_bus_ticket_wrap">
        <?php if( $bus_id && !empty($seats_rows) && !empty($seat_col) ) : ?>
        <div class="bus-detail-status">
            <div class="bus_info">
                <?php echo '<img src="'.get_the_post_thumbnail_url($bus_id).'" >' ?>
                <div>
                    <span style="display:block"><?php echo '<a href="'.get_post_permalink($bus_id).'">'.get_the_title($bus_id).'</a>' ?></span>
                    <span><?php echo get_post_meta($bus_id, 'wbtm_bus_no', true) ?></span>
                </div>
            </div>
            <div>
                <strong><?php _e('Type', 'addon-bus--ticket-booking-with-seat-pro') ?></strong> : <span><?php echo get_the_terms($bus_id, 'wbtm_bus_cat') ? get_the_terms($bus_id, 'wbtm_bus_cat')[0]->name : '' ?></span>
            </div>
            <!-- <div>
                <strong>Seat Available</strong> : <span><?php //echo wbtm_bus_sold_seat($bus_id, false) . ' / ' . mage_bus_total_seat(); ?></span>
            </div> -->
            <div>
                <strong><?php _e('Driver Position', 'addon-bus--ticket-booking-with-seat-pro') ?></strong> : <span><?php echo get_post_meta($bus_id, 'driver_seat_position', true) ?></span>
            </div>
        </div>
        <div class="wbtm_bus_lower_deck">
            <div class="wbtm_bus_ticket_inner">
                <div class="mage_seat_list_wrapper">
                    <div class="mage_seat_list" data-bus-id="<?php echo $bus_id; ?>" data-j-date="<?php echo $j_date; ?>">
                        <p class="wbtm_deck_title">Lower Deck</p>
                        <?php
                            $action_class = ($j_date ? 'mage_see_seat_detail' : null);
                            foreach ($seats_rows as $seat) {
                                $seat_html .= '<div class="flexEqual mage_bus_seat">';
                                for ($i = 1; $i <= $seat_col; $i++) {
                                    $seat_name = $seat["seat" . $i];
                                    $seat_status = wbtm_seat_status($bus_id, $j_date, $seat_name);
                                    $passenger_name = ($seat_status) ? get_post_meta($seat_status['booking_id'], 'wbtm_user_name', true) : '';
                                    if($seat_name) {
                                        $seat_html .= '<div class="flex_justifyCenter mage_bus_seat_item"><span class="mage_bus_seat_icon wbtm_'.$seat_status['status'].' '.$action_class.'" data-seat="'.$seat_name.'">'.$passenger_name.'<span class="bus_handle"e>'.$seat_name.'</span></span></div>';
                                    } else {
                                        $seat_html .= '<div class="flex_justifyCenter mage_bus_seat_item wbtm_seat_gap"></div>';
                                    }
                                    
                                }
                                $seat_html .= '</div>';
                            }
                            echo $seat_html;
                            
                        ?>
                    </div>
                    <?php if(!empty($seats_rows_upper)) : ?>
                        <div class="mage_seat_list" data-bus-id="<?php echo $bus_id; ?>" data-j-date="<?php echo $j_date; ?>">
                            <p class="wbtm_deck_title"><?php _e('Upper Deck', 'addon-bus--ticket-booking-with-seat-pro') ?></p>
                            <?php
                                $action_class = ($j_date ? 'mage_see_seat_detail' : null);
                                foreach ($seats_rows_upper as $seat) {
                                    $seat_html_upper .= '<div class="flexEqual mage_bus_seat">';
                                    for ($i = 1; $i <= $seat_col_upper; $i++) {
                                        $seat_name = $seat["dd_seat" . $i];
                                        $seat_status = wbtm_seat_status($bus_id, $j_date, $seat_name);
                                        $passenger_name = ($seat_status) ? get_post_meta($seat_status['booking_id'], 'wbtm_user_name', true) : '';
                                        if($seat_name) {
                                            $seat_html_upper .= '<div class="flex_justifyCenter mage_bus_seat_item"><span class="mage_bus_seat_icon wbtm_'.$seat_status['status'].' '.$action_class.'" data-seat="'.$seat_name.'">'.$passenger_name.'<span class="bus_handle">'.$seat_name.'</span></span></div>';
                                        } else {
                                            $seat_html_upper .= '<div class="flex_justifyCenter mage_bus_seat_item wbtm_seat_gap"></div>';
                                        }
                                        
                                    }
                                    $seat_html_upper .= '</div>';
                                }
                                echo $seat_html_upper;
                                
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mage_seat_detail"><div class="inner"><p class="mage_bus_info"><?php _e('Please, select a seat first', 'addon-bus--ticket-booking-with-seat-pro') ?></p></div></div>
            </div>
        </div>
        <?php elseif( $bus_id && empty($seats_rows) ) : echo '<p class="mage_bus_info">'.__('This bus has no data!', 'addon-bus--ticket-booking-with-seat-pro').'</p>'; ?>
        <?php else : echo '<p class="mage_bus_info">'.__('Please select a bus!', 'addon-bus--ticket-booking-with-seat-pro').'</p>'; ?>
        <?php endif; ?>
        <div class="wbtm_bus_upper_deck"></div>
    </div>

    <script>
        jQuery(document).ready(function() {
            jQuery('body').on('click', '.mage_see_seat_detail', function() {
                let bus_id = jQuery(this).parents('.mage_seat_list').attr('data-bus-id');
                let j_date = jQuery(this).parents('.mage_seat_list').attr('data-j-date');
                let seat = jQuery(this).attr('data-seat');
                let placeholder = jQuery('.mage_seat_detail .inner');
                let loader_img_url = '<?php echo WBTMPRO_PLUGIN_URL.'public/images/loading.gif' ?>';
                let loader = '<img src="'+loader_img_url+'" />';
                
                if( !jQuery(this).hasClass('wbtm_free') ) {
                    if(bus_id && seat) {
                        jQuery.ajax({
                            url: wbtm_ajaxurl,
                            type: 'POST',
                            dataType: 'html',
                            data: { action: 'mage_admin_ticket_detail', bus_id: bus_id, j_date: j_date, seat: seat },
                            beforeSend: function() {
                                placeholder.html(loader);
                            },
                            success: function(data) {
                                if(data) {
                                    placeholder.html(data);

                                }
                            }
                        })
                    }
                } else {
                    placeholder.html('<p class="mage_bus_info">This seat is not sale yet!</p>');
                }
            });
        });
    </script>

    <?php

}

add_action("wp_ajax_mage_admin_ticket_detail", "mage_admin_ticket_detail");
add_action("wp_ajax_nopriv_mage_admin_ticket_detail", "mage_admin_ticket_detail");

function mage_admin_ticket_detail() {
    $bus_id = $_REQUEST['bus_id'];
    $j_date = $_REQUEST['j_date'];
    $seat = $_REQUEST['seat'];

    $args = array(
        'post_type' => 'wbtm_bus_booking',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'wbtm_bus_id',
                'value' => $bus_id,
                'compare' => '='
            ),
            array(
                'key' => 'wbtm_journey_date',
                'value' => $j_date,
                'compare' => '='
            ),
            array(
                'key' => 'wbtm_seat',
                'value' => $seat,
                'compare' => '='
            )

        )
    );

    $seat_data = new WP_Query($args);
    $booking_id = (isset($seat_data->posts[0]) ? $seat_data->posts[0]->ID : null);
    wp_reset_postdata();
    // ob_start();
    ?>
        <table class="wp-list-table widefat striped posts mage-bus-report-table" style="width: 100%">
            <tr>
                <th>Order No</th>
                <td>: <?php echo '<a href="'.get_admin_url().'post.php?post='.get_post_meta($booking_id, 'wbtm_order_id', true).'&action=edit">'.get_post_meta($booking_id, 'wbtm_order_id', true).'</a>' ?></td>
            </tr>
            <tr>
                <th>Name</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_user_name', true) ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_user_email', true) ?></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_user_phone', true) ?></td>
            </tr>
            <tr>
                <th>Booking</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_booking_date', true) ?></td>
            </tr>
            <tr>
                <th>Journey</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_journey_date', true) ?></td>
            </tr>
            <tr>
                <th>Boarding</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_boarding_point', true) ?></td>
            </tr>
            <tr>
                <th>Dropping</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_droping_point', true) ?></td>
            </tr>
            <tr>
                <th>Fare</th>
                <td>: <?php echo wc_price(get_post_meta($booking_id, 'wbtm_bus_fare', true)) ?></td>
            </tr>
            <tr>
                <th>Seat Type</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_passenger_type', true) ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>: <?php echo get_post_meta($booking_id, 'wbtm_status', true) ?></td>
            </tr>
        </table>
    <?php
    // $html = ob_get_contents();
    // echo $html;
    die();
}

function wbtm_bus_sold_seat($bus_id, $return) {
    $date = $return ? wbtm_convert_date_to_php(mage_bus_isset('r_date')) : wbtm_convert_date_to_php(mage_bus_isset('j_date'));
    $args = array(
        'post_type' => 'wbtm_bus_booking',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_journey_date',
                    'value' => $date,
                    'compare' => '='
                ),
                array(
                    'key' => 'wbtm_bus_id',
                    'value' => $bus_id,
                    'compare' => '='
                )
            ),
            array(
                'relation' => 'OR',
                array(
                    'key' => 'wbtm_status',
                    'value' => 1,
                    'compare' => '='
                ),
                array(
                    'key' => 'wbtm_status',
                    'value' => 2,
                    'compare' => '='
                )
            )
        )
    );
    $q = new WP_Query($args);
    return $q->post_count > 0 ? $q->post_count : 0;
}

function wbtm_seat_status($bus_id, $j_date, $seat_name) {
    $status = null;

    $args = array(
        'post_type' => 'wbtm_bus_booking',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'wbtm_bus_id',
                'value' => $bus_id,
                'compare' => '='
            ),
            array(
                'key' => 'wbtm_journey_date',
                'value' => $j_date,
                'compare' => '='
            ),
            array(
                'key' => 'wbtm_seat',
                'value' => $seat_name,
                'compare' => '='
            )

        )
    );
    
    $seat_data = new WP_Query($args);
    $booking_id = (isset($seat_data->posts[0]) ? $seat_data->posts[0]->ID : null);
    $data = array();

    if($booking_id) {
        $seat_status = get_post_meta($booking_id, 'wbtm_status', true);

        if($seat_status == 1) {
            $status = 'booked';
        } elseif($seat_status == 2) {
            $status = 'sold';
        } else {
            $status = 'free';
        }

    } else {
        $status = 'free';
    }

    $data['booking_id'] = $booking_id;
    $data['status'] = $status;

    return $data;
}