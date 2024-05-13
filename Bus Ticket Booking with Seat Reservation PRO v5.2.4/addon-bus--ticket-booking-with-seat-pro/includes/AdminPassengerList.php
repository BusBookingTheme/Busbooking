<?php
if (!defined('ABSPATH')) exit;  // if direct access

class AdminPassengerList
{
    public function __construct()
    {

    }

    function passenger_list_init()
    {
        global $pagination_per_page;
        $pagination_per_page = 50;
        if (!isset($_COOKIE["pagination_limit"])) {
            setcookie("pagination_limit", $pagination_per_page, time() + 30 * 24 * 60 * 60);
        }
    }



    function wbtm_passenger_list_menu()
    {
        add_submenu_page('edit.php?post_type=wbtm_bus', __('Passenger List', 'addon-bus--ticket-booking-with-seat-pro'), __('Passenger List', 'addon-bus--ticket-booking-with-seat-pro'), 'manage_options', 'passenger_list', array( $this, 'wbtm_passenger_list' ));
    }


    function wbtm_passenger_list()
    {
        global $wpdb, $magepdf, $wbtmmain, $pagination_per_page;
        $table_name = $wpdb->prefix . "wbtm_bus_booking_list";
        $limit = 0;

        // Passenger info editing
        if(isset($_GET['mode']) && $_GET['mode'] == 'passenger_info_edit') {
            $booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;
            if($booking_id) {
                $this->wbtm_passenger_info_edit($booking_id);
                return;
            }
        }
        // Passenger info editing END

        if (isset($_POST['pagination_limit'])) {
            $limit = $_POST['pagination_limit'];
            setcookie("pagination_limit", $_POST['pagination_limit'], time() + 30 * 24 * 60 * 60);
        } else {
            if (isset($_COOKIE["pagination_limit"])) {
                $limit = $_COOKIE["pagination_limit"];
            } else {
                $limit = $pagination_per_page;
            }
        }

        //echo $limit;exit;
        if (isset($_GET['action']) && $_GET['action'] == 'delete_seat') {
            // echo 'Yes Did';die;
            $booking_id = strip_tags($_GET['booking_id']);
            $status = 5;
            $del = update_post_meta($booking_id, 'wbtm_status', $status);
            if ($del) {
                ?>
                <div id="message" class="updated notice notice-success is-dismissible">
                    <p><?php _e('Seat Deleted', 'addon-bus--ticket-booking-with-seat-pro'); ?></p>
                    <button type="button" class="notice-dismiss"><span
                            class="screen-reader-text"><?php _e('Dismiss this notice.', 'addon-bus--ticket-booking-with-seat-pro'); ?></span>
                    </button>
                </div>
                <?php
            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'restore_seat') {
            $booking_id = strip_tags($_GET['booking_id']);
            $status = 1;
            $restore = update_post_meta($booking_id, 'wbtm_status', $status);
            if ($restore) {
                ?>
                <div id="message" class="updated notice notice-success is-dismissible">
                    <p><?php _e('Seat restore successfully', 'addon-bus--ticket-booking-with-seat-pro'); ?></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.', 'addon-bus--ticket-booking-with-seat-pro'); ?></span>
                    </button>
                </div>
                <?php
            }
        }

        $bus_id = isset($_GET['bus_id'])?$_GET['bus_id']:'0';
        $isSub = false;
        $isZone = false;
        $mage_meta = get_post_custom($bus_id);

        if (isset($_GET['bus_type'])) $isSub = $_GET['bus_type'] == 'sub' ? true : false;
        if (isset($_GET['route_type'])) $isZone = $_GET['route_type'] == 'city_zone' ? true : false;

        $bus_type = isset($_GET['bus_type']) ? $_GET['bus_type'] : 'general';
        $route_type = isset($_GET['route_type']) ? $_GET['route_type'] : '';

        ?>

        <div class="wrap">

            <h2><?php echo __('Passenger List', 'addon-bus--ticket-booking-with-seat-pro') ?></h2>

            <?php
            if(isset($_GET['action']) && $_GET['action'] == 'deleted_list') :
                $this->deleted_list();
            else :

                $this->filtering_field($isSub, $isZone,$bus_id,$mage_meta);

                $filtering_data = $this->filtering_query($bus_id,$bus_type,$route_type);

                $meta_query = $filtering_data[0];
                $filter_text = $filtering_data[1];
                // Passenger List query and pagination data
                $current_page = (isset($_GET['paged']) ? $_GET['paged'] : 1);
                $offset = ($current_page * $limit) - $limit;
                // Seat booked show policy in search


                ?>



                <div class="wbtm-table-wrap" style="overflow-x: auto">
                    <!-- top header start-->
                    <div style="display: flex;align-items:center;">
                        <div style="flex-grow: 2">
                            <?php echo ($filter_text != null) ? '<p class="mage-filted-text">Search By ->' . $filter_text . '</p>' : ''; ?>
                        </div>
                        <div class="restore-data-list-wrap">
                            <a href="<?php echo get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=passenger_list&action=deleted_list" style="text-decoration: none;padding:5px 5px 5px 14px;color: #f4433c;cursor: pointer;"> <?php _e('Show Removed List', 'addon-bus--ticket-booking-with-seat-pro') ?></a>
                        </div>
                        <div style="margin-left: 10px;">
                            <form method='get' action="edit.php">

                                <?php foreach ($_GET as $key => $value) { ?>
                                    <input type="hidden" name='<?php echo $key ?>' value="<?php echo $value ?>"/>
                                <?php } ?>

                                <input type="hidden" name='noheader' value="1"/>
                                <input type="hidden" name='action' value="export_passenger_list"/>
                                <input style="display:none" type="radio" name='format' id="formatCSV" value="csv" checked="checked"/>
                                <input type="submit" name='export' id="csvExport" value="<?php _e('Export to CSV','addon-bus--ticket-booking-with-seat-pro'); ?>"/>
                            </form>
                        </div>
                        <form action="" method="POST" style="min-width: 197px;margin-right: 0;display: flex;align-items: center;justify-content: flex-end;">
                            <label for="" style="margin-right:5px"><?php _e('Row Per Page', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                            <input type="number" name="pagination_limit" value="<?php echo $limit ?>" style="width:17px;border-radius: 0;border-color: #738b8b;margin: 0;flex-basis: 50%;" title="<?php _e('Type the number and press enter', 'addon-bus--ticket-booking-with-seat-pro'); ?>">
                        </form>
                    </div>
                    <!-- top header end-->

                    <?php
                    $limit = (int)$limit;
                    $args = array(
                        'post_type' => 'wbtm_bus_booking',
                        'posts_per_page' => $limit,
                        'paged' => $current_page,
                        'offset' => $offset,
                        'order' => 'DESC',
                        'meta_query' => $meta_query
                    );
                    $passenger = new WP_Query($args);
                    // echo $passenger->request;
                    $res = $passenger->posts;
                    $total = $passenger->found_posts;
                    $pages = ceil($total / $limit);
                    wp_reset_postdata();

                    $this->main_table($wbtmmain,$res,$offset,$magepdf,$route_type,$bus_type,$bus_id,$mage_meta)

                    ?>
                </div>

                <div class="mage-table-footer">
                    <?php
                    echo '<div class="mage-footer-item">';
                    // Pagination
                    echo ($total > $limit) ? $this->mage_pagination($current_page, $pages) : '';
                    echo '</div>';
                    ?>
                    <div class="mage-footer-item">
                        <?php echo __('Found', 'addon-bus--ticket-booking-with-seat-pro') . ' ' . (($total > 1) ? $total . ' ' . __('items', 'addon-bus--ticket-booking-with-seat-pro') : $total . ' ' . __('item', 'addon-bus--ticket-booking-with-seat-pro')); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php
    }

    function deleted_list(){
        $args = array(
            'post_type' => 'wbtm_bus_booking',
            'posts_per_page' => '-1',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => '=',
                    'value' => '5',
                )
            )
        );
        $passenger_deleted = new WP_Query($args);
        ?>

        <a href="<?php echo get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=passenger_list"><?php _e('Go back', 'addon-bus--ticket-booking-with-seat-pro') ?></a>

        <table class="wbtm_deleted_table">
            <thead>
            <tr>
                <th><?php _e('Sl', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Order no', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Name', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Price', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Bus Name', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Booking Date', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Journey Date', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Boarding', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Dropping', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <th><?php _e('Action', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
            </tr>
            </thead>
            <?php if($passenger_deleted->found_posts > 0) : ?>
                <tbody>
                <?php while($passenger_deleted->have_posts()) {
                    $passenger_deleted->the_post();
                    $d_id = get_the_ID();
                    echo '<tr>';
                    echo '<td>'.($passenger_deleted->current_post + 1).'</td>';
                    echo '<td>'.get_post_meta($d_id, 'wbtm_order_id', true).'</td>';
                    echo '<td>'.get_post_meta($d_id, 'wbtm_user_name', true).'</td>';
                    echo '<td>'.get_post_meta($d_id, 'wbtm_bus_fare', true).'</td>';
                    echo '<td>'.get_the_title(get_post_meta($d_id, 'wbtm_bus_id', true)).'</td>';
                    echo '<td>'.get_post_meta($d_id, 'wbtm_booking_date', true).'</td>';
                    echo '<td>'.get_post_meta($d_id, 'wbtm_journey_date', true).'</td>';
                    echo '<td>'.get_post_meta($d_id, 'wbtm_boarding_point', true).'</td>';
                    echo '<td>'.get_post_meta($d_id, 'wbtm_droping_point', true).'</td>';
                    echo '<td><a href="'.get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=passenger_list&action=restore_seat&booking_id=<?php echo $d_id.'" onclick="javascript:return confirm(\'Are you sure you want to restore this seat?\')">'.__('Restore', 'addon-bus--ticket-booking-with-seat-pro').'</a></td>';
                    echo '</tr>';
                } ?>
                </tbody>
            <?php endif; wp_reset_postdata();?>
        </table>

        <?php
    }


    function filtering_field($isSub, $isZone,$bus_id,$mage_meta){

        ?>

        <form action="<?php echo get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=passenger_list" method="get">
            <input type="hidden" name="post_type" value="wbtm_bus">
            <input type="hidden" name="page" value="passenger_list">

            <div class="mage-custom-filter-area-outer">
                <div class="mage-custom-filter-text">
                    <span><?php _e('Filter list new', 'addon-bus--ticket-booking-with-seat-pro') ?></span>
                </div>
                <span class="wbtm-section-toggle"><i class="fas fa-arrow-circle-up"></i></span>
                <div class="mage-custom-filter-area">
                    <a href="?post_type=wbtm_bus&page=passenger_list" class="mage-custom-filter-clear"><?php _e('Clear all filter', 'addon-bus--ticket-booking-with-seat-pro') ?></a>
                    <?php do_action('mtsa_bus_type_fiter', $isSub, $isZone) ?>

                    <div class="mage-custom-filter-item">
                        <label for="mage-f-bus-type"><?php echo mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'); ?></label>
                        <select id="mage-f-bus-type" name="bus_id">
                            <option value=""><?php echo __('By ', 'addon-bus--ticket-booking-with-seat-pro'). ''. mage_bus_setting_value('bus_menu_label', 'Bus'); ?></option>
                            <?php
                            $args = array(
                                    'post_type' => 'wbtm_bus', 'posts_per_page' => -1
                            );
                            $loop = new WP_Query($args);
                            while ($loop->have_posts()) {
                                $loop->the_post();
                                $start = get_post_meta(get_the_id(), 'wbtm_bus_no', true);
                                $busit = get_the_id();
                                ?>
                                <option value="<?php echo $busit; ?>" <?php echo  ($busit == $bus_id)?'selected':''  ?> >
                                    <?php the_title(); ?> - <?php echo $start; ?>
                                </option>
                                <?php
                            }
                            wp_reset_postdata();
                            ?>
                        </select>
                    </div>

                    <div class="mage-custom-filter-item">
                        <label for="mage-f-journey-date"><?php _e('Journey Date', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                        <input id="mage-f-journey-date" type="text" id="ja_date" name="j_date" placeholder="<?php echo mage_wp_date(date('Y-m-d')) ?>" value="<?php echo(isset($_GET['j_date']) ? $_GET['j_date'] : '') ?>">
                    </div>

                    <div class="mage-custom-filter-item">
                        <label for="filter_name"><?php _e('Passenger Name', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                        <input type="text" id="filter_name" name="filter_name" placeholder="John Doe" value="<?php echo(isset($_GET['filter_name']) ? $_GET['filter_name'] : '') ?>">
                    </div>

                    <div class="mage-custom-filter-item">
                        <label for="filter_email"><?php _e('Passenger Email', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                        <input type="text" id="filter_email" name="filter_email" placeholder="email@domain.com" value="<?php echo(isset($_GET['filter_email']) ? $_GET['filter_email'] : '') ?>">
                    </div>

                    <div class="mage-custom-filter-item">
                        <label for="filter_phone"><?php _e('Passenger Phone', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                        <input type="text" id="filter_phone" name="filter_phone" placeholder="123456789" value="<?php echo(isset($_GET['filter_phone']) ? $_GET['filter_phone'] : '') ?>">
                    </div>

                    <div class="mage-custom-filter-item">
                        <label for="filter_booking_date"><?php _e('Booking Date', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                        <input type="text" id="filter_booking_date" name="filter_booking_date" placeholder="<?php echo mage_wp_date(date('Y-m-d')); ?>" value="<?php echo(isset($_GET['filter_booking_date']) ? $_GET['filter_booking_date'] : '') ?>">
                    </div>

                    <div class="mage-custom-filter-item">
                        <label for="filter_order_id"><?php _e('Order Id', 'addon-bus--ticket-booking-with-seat-pro') ?></label>
                        <input type="text" id="filter_order_id" name="filter_order_id" placeholder="0001" value="<?php echo(isset($_GET['filter_order_id']) ? $_GET['filter_order_id'] : '') ?>">
                    </div>

                    <div class="extra_field_for_single_bus" style="width: 100%">

                        <?php

                        if($bus_id){
                            $gender = array_key_exists('wbtm_user_gender', $mage_meta) ? strip_tags($mage_meta['wbtm_user_gender'][0]) : false;
                            if($gender){
                                ?>
                                <div class="mage-custom-filter-item-new">
                                    <label for="mage-f-bus-type">Select Gender</label>
                                    <select id="mage-f-bus-type" name="wbtm_user_gender">
                                        <option value="">Select Gender</option>
                                        <option <?php echo(isset($_GET['wbtm_user_gender']) ? (($_GET['wbtm_user_gender'] == 'Male')?'selected':''): '') ?> value="Male">Male</option>
                                        <option <?php echo(isset($_GET['wbtm_user_gender']) ? (($_GET['wbtm_user_gender'] == 'Female')?'selected':''): '') ?> value="Female">Female</option>
                                    </select>
                                </div>
                            <?php }

                            $custom_field = unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
                            if($custom_field) {
                                foreach($custom_field as $epv) {
                                    if($epv['filed_type']=='text'){
                                        ?>
                                        <div class="mage-custom-filter-item-new">
                                            <label for="filter_order_id"><?php echo $epv['field_label'] ?></label>
                                            <input type="text" id="<?php echo $epv['field_id'] ?>" name="<?php echo $epv['field_id'] ?>" placeholder="<?php echo $epv['field_label'] ?>" value="<?php echo(isset($_GET[$epv['field_id']]) ? $_GET[$epv['field_id']] : '') ?>">
                                        </div>
                                        <?php
                                    }
                                }
                            }
                        }

                        ?>

                    </div>











                    <div class="mage-custom-filter-item">
                        <button type="submit"><?php _e('Filter', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
                    </div>




                </div>
            </div>

        </form>



        <?php

    }


    function filtering_query($bus_id,$bus_type='',$route_type=''){

        $seat_booked_status_default = array(1, 2);
        $seat_booked_status = (isset(get_option('wbtm_bus_settings')['bus_seat_booked_on_order_status']) ? get_option('wbtm_bus_settings')['bus_seat_booked_on_order_status'] : $seat_booked_status_default);
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key' => 'wbtm_status',
                'value' => $seat_booked_status,
                'compare' => 'IN'
            ),
        );

        $filter_query = array(
            'relation' => 'AND',
        );

        // Filter Meta
        $has_filter = false;
        $filter_text = '';

        // Bus Id

        if ($bus_id) {
            array_push($filter_query, array(
                'key' => 'wbtm_bus_id',
                'value' => $bus_id,
                'compare' => '='
            ));
            $has_filter = true;
            $filter_text .= ' <span class="mage-filted_item">Bus -> ' . $bus_id . '</span>';
        }

        // Bus type

        // Journey Date
        $j_date = (isset($_GET['j_date']) ? $_GET['j_date'] : null);
        $j_dates = array($j_date);
        if($bus_id && $j_date) {
            $bus_start_stops_arr = get_post_meta($bus_id, 'wbtm_bus_bp_stops', true);
            $bus_start_stops_arr = $bus_start_stops_arr ? maybe_unserialize($bus_start_stops_arr) : array();
            $is_midnight = mage_bus_is_midnight_trip($bus_start_stops_arr);
            if($is_midnight) {
                $next_date = date('Y-m-d', strtotime('+1 day', strtotime($j_date)));
                array_push($j_dates, $next_date);
            }
        }

        if ($j_date) {
            if($bus_type == 'sub') {
                $o_date = date('Y-m-d', strtotime($j_date));
                array_push($filter_query, array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wbtm_journey_date',
                        'value' => $o_date,
                        'compare' => '<=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'wbtm_sub_end_date',
                        'value' => $o_date,
                        'compare' => '>=',
                        'type' => 'DATE'
                    )
                ));
            } else {
                array_push($filter_query, array(
                    'key' => 'wbtm_journey_date',
                    'value' => $j_dates,
                    'compare' => 'IN'
                ));
            }
            $has_filter = true;
            $filter_text .= ' <span class="mage-filted_item">Journey Date -> ' . $j_date . '</span>';
        }
        // Name
        $filter_name = (isset($_GET['filter_name']) ? $_GET['filter_name'] : null);
        if ($filter_name) {
            array_push($filter_query, array(
                'key' => 'wbtm_user_name',
                'value' => $filter_name,
                'compare' => 'LIKE'
            ));
            $has_filter = true;
            $filter_text .= ' <span class="mage-filted_item">Customer Name -> ' . $filter_name . '</span>';
        }
        // Email
        $filter_email = (isset($_GET['filter_email']) ? $_GET['filter_email'] : null);
        if ($filter_email) {
            array_push($filter_query, array(
                'key' => 'wbtm_user_email',
                'value' => $filter_email,
                'compare' => '='
            ));
            $has_filter = true;
            $filter_text .= ' <span class="mage-filted_item">Customer Email -> ' . $filter_email . '</span>';
        }
        // Phone No
        $filter_phone = (isset($_GET['filter_phone']) ? $_GET['filter_phone'] : null);
        if ($filter_phone) {
            array_push($filter_query, array(
                'key' => 'wbtm_user_phone',
                'value' => $filter_phone,
                'compare' => '='
            ));
            $has_filter = true;
            $filter_text .= ' <span class="mage-filted_item">Customer Phone no -> ' . $filter_phone . '</span>';
        }

        /*form builder custom */

        if($bus_id){
            $custom_field = unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
            if($custom_field) {
                foreach($custom_field as $epv) {
                    if($epv['filed_type']=='text'){

                        $field_id = $epv['field_id'];
                        $field_search_value = isset($_GET[$field_id]) ? $_GET[$field_id] : '';

                        if ($field_search_value) {
                            array_push($filter_query, array(
                                'key' => 'wbtm_user_additional',
                                'value' => $field_search_value,
                                'compare' => 'LIKE'
                            ));
                            $has_filter = true;
                            $filter_text .= ' <span class="mage-filted_item">'.$epv['field_label'].' -> ' . $field_search_value . '</span>';
                        }
                    }
                }
            }

            $wbtm_user_gender = isset($_GET['wbtm_user_gender']) ? $_GET['wbtm_user_gender'] : '';

            if ($wbtm_user_gender) {
                array_push($filter_query, array(
                    'key' => 'wbtm_user_gender',
                    'value' => $wbtm_user_gender,
                    'compare' => '='
                ));
                $has_filter = true;
                $filter_text .= ' <span class="mage-filted_item">Gender -> ' . $wbtm_user_gender . '</span>';
            }
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
            $filter_text .= ' <span class="mage-filted_item">Customer Phone no -> ' . $filter_booking_date . '</span>';
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

        // Bus Type
        if ($bus_type === 'sub') {
            array_push($filter_query, array(
                'key' => 'wbtm_billing_type',
                'value' => null,
                'compare' => '!='
            ));
            $has_filter = true;
            $filter_text .= " <span class='mage-filted_item'>".mage_bus_setting_value('bus_menu_label', 'Bus')." Type -> Subscription</span>";
        } else {
            $generalType = array(
                'relation' => 'OR',
                array(
                    'key' => 'wbtm_billing_type',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'wbtm_billing_type',
                    'value' => '',
                    'compare' => '=',
                ),
            );
            array_push($filter_query, $generalType);
            $has_filter = true;
            $filter_text .= ' <span class="mage-filted_item">'.mage_bus_setting_value('bus_menu_label', 'Bus').' Type -> General</span>';
        }

        // Route Type

        if ($route_type == 'city_zone') {
            array_push($filter_query, array(
                'key' => 'wbtm_city_zone',
                'value' => null,
                'compare' => '!='
            ));
            $has_filter = true;
            $filter_text .= '<span class="mage-filted_item"> Route Type -> Zone</span>';
        }


        //$extra_p_fields_values = maybe_unserialize(get_post_meta($passenger_id, 'wbtm_user_additional', true));


        if ($has_filter) {
            array_push($meta_query, $filter_query);
        }

        return array($meta_query,$filter_text);

    }


    function main_table($wbtmmain,$res,$offset,$magepdf,$route_type,$bus_type,$bus_id,$mage_meta){

        ?>
        <table class="wp-list-table widefat striped posts wbtm_passenger_list_table" style="width: 100%">
            <thead>
            <tr>
                <th>Sl</th>
                <?php   $this->billing_default_heading_custom($wbtmmain); ?>
                <?php   $this->custom_heading_html(); ?>
                <?php   $this->four_register_form_heading($mage_meta,$bus_id); ?>
                <?php   $this->form_builder_custom_heading($bus_id); ?>
                <th><?php _e('Seat', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Order', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php echo mage_bus_setting_value('bus_menu_label', 'Bus').' '.__('Name', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Booking Date', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <?php $subscription_fields_heading = $this->subscription_fields_heading(); ?>
                <th><?php _e('Price', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Check in', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Status', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
                <th><?php _e('Action', 'addon-bus--ticket-booking-with-seat-pro'); ?></th>
            </tr>
            </thead>
            <tbody>

            <?php

            $index = $offset + 1;
            foreach ($res as $_passger) {
                $passenger_id = $_passger->ID;
                $order_id = get_post_meta($passenger_id, 'wbtm_order_id', true);
                $order = wc_get_order($order_id);
                $billing = array();
                if($order) {
                    $billing = $order->get_address('billing');
                }
                $order_meta_data = get_post_meta($order_id);
                $download_url = $magepdf->get_invoice_ajax_url(array('order_id' => $order_id));
                ?>

                <tr>
                    <td><?php echo $index++; ?></td>
                    <?php
                    $this->billing_default_value_custom($wbtmmain,$passenger_id,$billing);
                    $this->custom_fields_value($order_id);
                    $this->four_register_form_value($passenger_id,$bus_id,$mage_meta);
                    $this->form_builder_custom_value($passenger_id,$bus_id);
                    ?>

                    <td><?php echo get_post_meta($passenger_id, 'wbtm_seat', true); ?></td>
                    <td>#<?php echo get_post_meta($passenger_id, 'wbtm_order_id', true); ?></td>
                    <td><?php echo get_the_title(get_post_meta($passenger_id, 'wbtm_bus_id', true)) . "<span class='wbtm_coach_no'>" . get_post_meta(get_post_meta($passenger_id, 'wbtm_bus_id', true), 'wbtm_bus_no', true).'</span>'; ?></td>
                    <td><?php echo mage_wp_date(get_post_meta($passenger_id, 'wbtm_booking_date', true)); ?></td>
                    <td style="width:86px">
                        <?php
                        $j_date = get_post_meta($passenger_id, 'wbtm_journey_date', true);
                        echo mage_wp_date($j_date);
                        ?>
                        <?php echo mage_wp_time(get_post_meta($passenger_id, 'wbtm_user_start', true)); ?>
                    </td>
                    <?php
                       $this->subscription_fields_value($subscription_fields_heading,$passenger_id);
                    ?>
                   <?php if($route_type != 'city_zone') {
                        echo '<td>'.get_post_meta($passenger_id, 'wbtm_boarding_point', true).'</td>';
                        echo '<td>'.get_post_meta($passenger_id, 'wbtm_droping_point', true).'</td>';
                    } ?>

                    <?php
                    $picup_point = get_post_meta($passenger_id, 'wbtm_pickpoint', true);
                    echo (!$subscription_fields_heading) ? '<td>'.($picup_point ? $picup_point : "-").'</td>' : ''
                    ?>

                    <td>
                        <?php $this->show_price_html($passenger_id); ?>
                    </td>
                 <?php
                  if($bus_type == 'sub') {
                        $checkin_count = get_post_meta($passenger_id, 'wbtm_ticket_checkin_count', true);
                        $checkin_limit = '∞';
                        if(function_exists('wbtm_ticket_checkin_limit')) {
                            $checkin_limit = wbtm_ticket_checkin_limit($bus_id, $sub_route_type, $boarding, $dropping, $city_zone, $billing_type);
                        }

                        // Check in Count
                        if ($checkin_count) {
                            echo '<td>' . $checkin_count .' / <span>'.$checkin_limit. '</span></td>';
                        } else {
                            echo '<td>0 / <span>'.$checkin_limit.'</span></td>';
                        }
                    } else {
                        $general_check_in_check = get_post_meta($passenger_id, 'wbtm_ticket_status', true);
                        echo '<td style="width:86px;text-align:center;">'.($general_check_in_check == "2" ? '<span class="wbtm_ticket_checking wbtm_checked_in">Checked</span>' : '<span class="wbtm_ticket_checking wbtm_not_checked_in">Not Checked</span>').'</td>';
                    }

                    ?>
                    <td>
                        <?php
                        if ($order) {
                            echo ($order->get_status() ? ucfirst($order->get_status()) : null);
                        }
                        ?>
                        <?php //do_action('wbtm_ticket_status',$_passger->ticket_status); ?>
                    </td>
                    <td style="width:90px">
                        <div>
                            <a href="<?php echo admin_url('edit.php?post_type=wbtm_bus&page=passenger_list&mode=passenger_info_edit&booking_id='.$passenger_id) ?>" title="<?php _e('Passenger info edit', 'addon-bus--ticket-booking-with-seat-pro'); ?>"><span class="dashicons dashicons-edit"></span></a>
                            <a href="<?php echo $download_url; ?>" target="_blank" title="Download PDF"><span
                                        class="dashicons dashicons-tickets-alt"></span></a>

                            <a href="<?php echo get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=passenger_list&action=delete_seat&booking_id=<?php echo $_passger->ID; ?>"
                               title="Delete Data" onclick="javascript:return confirm('Are you sure you want to delete this seat?')"><span class="dashicons dashicons-no"></span></a>
                        </div>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>

        <?php
    }



     function billing_default_heading_custom($wbtmmain){

         // Defautl Billing field
         $billing_default_heading = '';

         $billing_default_fields_setting = $wbtmmain->bus_get_option('default_billing_fields_setting', 'ticket_manager_settings', array());

         $p_name = (in_array('p_name', $billing_default_fields_setting) ? true : false);
         $p_phone = (in_array('p_phone', $billing_default_fields_setting) ? true : false);
         $p_email = (in_array('p_email', $billing_default_fields_setting) ? true : false);
         $p_company = (in_array('p_company', $billing_default_fields_setting) ? true : false);
         $p_address = (in_array('p_address', $billing_default_fields_setting) ? true : false);
         $p_city = (in_array('p_city', $billing_default_fields_setting) ? true : false);
         $p_state = (in_array('p_state', $billing_default_fields_setting) ? true : false);
         $p_postcode = (in_array('p_postcode', $billing_default_fields_setting) ? true : false);
         $p_country = (in_array('p_country', $billing_default_fields_setting) ? true : false);
         $p_total_paid = (in_array('p_total_paid', $billing_default_fields_setting) ? true : false);
         $p_payment_method = (in_array('p_payment_method', $billing_default_fields_setting) ? true : false);


         if ($p_name) {
             $billing_default_heading .= "<th>" . __('Name', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_phone) {
             $billing_default_heading .= "<th>" . __('Phone', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_email) {
             $billing_default_heading .= "<th>" . __('Email', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_company) {
             $billing_default_heading .= "<th>" . __('Company', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_address) {
             $billing_default_heading .= "<th>" . __('Address', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_city) {
             $billing_default_heading .= "<th>" . __('City', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_state) {
             $billing_default_heading .= "<th>" . __('State', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_postcode) {
             $billing_default_heading .= "<th>" . __('Postcode', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_country) {
             $billing_default_heading .= "<th>" . __('Country', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         if ($p_payment_method) {
             $billing_default_heading .= "<th>" . __('Payment Method', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }

         echo $billing_default_heading;

     }

     function billing_default_value_custom($wbtmmain,$passenger_id,$billing){
         $billing_default_fields_setting = $wbtmmain->bus_get_option('default_billing_fields_setting', 'ticket_manager_settings', array());


         $p_name = (in_array('p_name', $billing_default_fields_setting) ? true : false);
         $p_phone = (in_array('p_phone', $billing_default_fields_setting) ? true : false);
         $p_email = (in_array('p_email', $billing_default_fields_setting) ? true : false);
         $p_company = (in_array('p_company', $billing_default_fields_setting) ? true : false);
         $p_address = (in_array('p_address', $billing_default_fields_setting) ? true : false);
         $p_city = (in_array('p_city', $billing_default_fields_setting) ? true : false);
         $p_state = (in_array('p_state', $billing_default_fields_setting) ? true : false);
         $p_postcode = (in_array('p_postcode', $billing_default_fields_setting) ? true : false);
         $p_country = (in_array('p_country', $billing_default_fields_setting) ? true : false);
         $p_total_paid = (in_array('p_total_paid', $billing_default_fields_setting) ? true : false);
         $p_payment_method = (in_array('p_payment_method', $billing_default_fields_setting) ? true : false);




         // Default Field
         $billing_default_body = '';
         if ($p_name) {
             $billing_default_body .= "<td>" . get_post_meta($passenger_id, 'wbtm_user_name', true) . "</td>";
         }
         if ($p_phone) {
             $billing_default_body .= "<td>" . get_post_meta($passenger_id, 'wbtm_user_phone', true) . "</td>";
         }
         if ($p_email) {
             $billing_default_body .= "<td>" . get_post_meta($passenger_id, 'wbtm_user_email', true) . "</td>";
         }
         if ($p_company) {
             $billing_default_body .= "<td>" . ($billing ? $billing['company'] : '') . "</td>";
         }
         if ($p_address) {
             $billing_default_body .= "<td>" . ($billing ? $billing['address_1'] : '') . "</td>";
         }
         if ($p_city) {
             $billing_default_body .= "<td>" . ($billing ? $billing['city'] : '') . "</td>";
         }
         if ($p_state) {
             $billing_default_body .= "<td>" . ($billing ? $billing['state'] : '') . "</td>";
         }
         if ($p_postcode) {
             $billing_default_body .= "<td>" . ($billing ? $billing['postcode'] : '') . "</td>";
         }
         if ($p_country) {
             $billing_default_body .= "<td>" . ($billing ? $billing['country'] : '') . "</td>";
         }

         if ($p_payment_method) {
             $billing_default_body .= "<td>" . (isset($order_meta_data['_payment_method']) ? $order_meta_data['_payment_method'][0] : '') . "</td>";
         }
         echo $billing_default_body;
     }

     function form_builder_custom_heading($bus_id){
         $form_builder_custom_heading = '';
         if($bus_id){
             $custom_field = unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
             if($custom_field) {
                 foreach($custom_field as $epv) {
                     $form_builder_custom_heading .= '<th>'. $epv['field_label'].'</th>';
                 }
             }
         }else{
             $form_builder_custom_heading .= "<th>" . __('Extra Info', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }
         echo $form_builder_custom_heading;
     }

     function form_builder_custom_value($passenger_id,$bus_id){

         if($bus_id){
             /*form_builder_custom_form use wbtm_user_additional for name and value */
             $extra_p_fields_values = (get_post_meta($passenger_id, 'wbtm_user_additional', true));



             if(!$extra_p_fields_values ){
                 $extra_p_fields_values = [];
             }
             $field_array = count($extra_p_fields_values);
             //echo $field_array;
             $index = 0;
             /*form_builder_custom_form use attendee_reg_form not value */
             $custom_field = unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
             if($custom_field) {
                 foreach($custom_field as $epv) {
                     if($field_array > $index){
                         echo '<td>'.$extra_p_fields_values[$index]['value'].'</td>';
                     }else{
                         echo '<td>'.'-'.'</td>';
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
                     $extra_p_fields_value_str .= sprintf("<strong>%s</strong> = %s", $epv['name'], $epv['value']) . ((count($extra_p_fields_values) - 1 == $epv_i) ? "" : " + ");
                     $epv_i++;
                 }
             }
             echo '<td>'.($extra_p_fields_value_str ? $extra_p_fields_value_str : "-").'</td>';
         }
     }

     function custom_heading_html(){

         // Custom FIeld
         $get_settings = get_option('wbtm_bus_settings');
         $get_val = isset($get_settings['custom_fields']) ? $get_settings['custom_fields'] : '';
         $custom_fields = $get_val ? $get_val : null;
         $custom_heading_html = '';
         $custom_tbody_html = '';
         if ($custom_fields) {
             $custom_fields_arr = explode(',', $custom_fields);
             if ($custom_fields_arr) {
                 foreach ($custom_fields_arr as $item) {
                     $custom_heading_html .= '<th>' . (($item) ? ucfirst(str_replace('_', ' ', $item)) : "") . '</th>';
                 }
             }
         }

         echo $custom_heading_html;
     }

     function four_register_form_heading($mage_meta,$bus_id){

        if($bus_id){
            if ($mage_meta) {
                $four_register_form_heading = '';
                $gender = array_key_exists('wbtm_user_gender', $mage_meta) ? strip_tags($mage_meta['wbtm_user_gender'][0]) : false;
                if($gender){
                    $four_register_form_heading .= '<th>' . 'Gender' . '</th>';
                }
                if (array_key_exists('wbtm_user_extra_bag', $mage_meta)) {
                    $extra_bag = strip_tags($mage_meta['wbtm_user_extra_bag'][0]);
                    $extra_bag_qty = isset($mage_meta['wbtm_extra_bag_price']) ? strip_tags($mage_meta['wbtm_extra_bag_price'][0]) : 0;
                    if($extra_bag && $extra_bag_qty){
                        $four_register_form_heading .= '<th>' . 'Extra Bag' . '</th>';
                    }
                }
                echo $four_register_form_heading;
            }
        }
    }


     function subscription_fields_heading(){

         $subscription_fields_heading = '';
         $bus_type = isset($_GET['bus_type']) ? $_GET['bus_type'] : 'general';
         if ($bus_type == 'sub') {
             $subscription_fields_heading .= "<th>" . __('Valid till', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
             $subscription_fields_heading .= "<th>" . __('Billing Type', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
             $subscription_fields_heading .= "<th>" . __('Zone', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
         }

         $route_type = isset($_GET['route_type']) ? $_GET['route_type'] : '';

         if ($subscription_fields_heading) {
             echo "<th>" . __('Start Date', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
             echo $subscription_fields_heading;
             if ($route_type != 'city_zone') {
                 echo "<th>" . __('From', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
                 echo "<th>" . __('To', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
             }
         } else {
             echo "<th>" . __('Journey Date', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
             echo "<th>" . __('From', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
             echo "<th>" . __('To', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";
             echo "<th>" . __('Pickup Point', 'addon-bus--ticket-booking-with-seat-pro') . "</th>";

         }
     }

     function custom_fields_value($order_id){
         $get_settings = get_option('wbtm_bus_settings');
         $get_val = isset($get_settings['custom_fields']) ? $get_settings['custom_fields'] : '';
         $custom_fields = $get_val ? $get_val : null;
         $custom_tbody_html = '';
         if ($custom_fields) {
             $custom_fields_arr = explode(',', $custom_fields);
             if ($custom_fields_arr) {
                 foreach ($custom_fields_arr as $item) {
                     $item = trim($item);
                     $custom_tbody_html .= '<td>' . get_post_meta($order_id, 'wbtm_custom_field_' . $item, true) . '</td>';
                 }
             }
         }
         echo $custom_tbody_html;
     }

     function four_register_form_value($passenger_id,$bus_id,$mage_meta){
        if($bus_id && $mage_meta){
            $four_register_form_value = '';
            $gender = array_key_exists('wbtm_user_gender', $mage_meta) ? strip_tags($mage_meta['wbtm_user_gender'][0]) : false;
            if($gender){
                $four_register_form_value .= "<td>" . get_post_meta($passenger_id, 'wbtm_user_gender', true) . "</td>";
            }
            if (array_key_exists('wbtm_user_extra_bag', $mage_meta)) {
                $extra_bag = strip_tags($mage_meta['wbtm_user_extra_bag'][0]);
                $extra_bag_qty = isset($mage_meta['wbtm_extra_bag_price']) ? strip_tags($mage_meta['wbtm_extra_bag_price'][0]) : 0;
                if($extra_bag && $extra_bag_qty){
                    $four_register_form_value .= "<td>" . get_post_meta($passenger_id, 'wbtm_user_extra_bag', true) . "</td>";
                }
            }
            echo $four_register_form_value;
        }


    }

     function subscription_fields_value($subscription_fields_heading,$passenger_id){

        if ($subscription_fields_heading) {

            $billing_type = get_post_meta($passenger_id, 'wbtm_billing_type', true);
            $city_zone_id = get_post_meta($passenger_id, 'wbtm_city_zone', true);

            if(function_exists('wbtm_ticket_checkin_limit')) {
                $bus_id = get_post_meta($passenger_id, 'wbtm_bus_id', true);
                $sub_route_type = get_post_meta($bus_id, 'mtsa_subscription_route_type', true);
                $boarding = get_post_meta($passenger_id, 'wbtm_boarding_point', true);
                $dropping = get_post_meta($passenger_id, 'wbtm_droping_point', true);
                $city_zone = get_post_meta($passenger_id, 'wbtm_city_zone', true);
                $billing_type = get_post_meta($passenger_id, 'wbtm_billing_type', true);
                $checkin_limit = wbtm_ticket_checkin_limit($bus_id, $sub_route_type, $boarding, $dropping, $city_zone, $billing_type);
            }

            // Billing Type
            if ($billing_type) {
                echo '<td>' . mage_wp_date(mtsa_calculate_valid_date($j_date, $billing_type)) . '</td>';
                echo '<td>' . $billing_type . '</td>';
            } else {
                echo '<td></td>';
            }

            // City Zone
            if ($city_zone_id) {
                $city_zone_data = get_term($city_zone_id);
                echo '<td>' . $city_zone_data->name . '</td>';
            } else {
                echo '<td></td>';
            }
        }
    }

    function show_price_html($passenger_id){
        $show_price_html = '';
        $show_price = get_post_meta($passenger_id, 'wbtm_bus_fare', true);
        if(get_post_meta($passenger_id, '_wbtm_tp', true)) {
            if( get_post_meta($passenger_id, '_wbtm_tp', true) < $show_price ) {
                $show_price_html .= '<span style="text-decoration:line-through;display:block">'.wc_price(get_post_meta($passenger_id, 'wbtm_bus_fare', true)).'</span>';
                $show_price_html .= '<span>'.wc_price(get_post_meta($passenger_id, '_wbtm_tp', true)).'</span>';
            }
        }
        echo $show_price_html ? $show_price_html : wc_price($show_price);
    }



    /*
    * $current_page = Current Page
    * $pages = Total page number
    * return all button link
    */
    function mage_pagination($current_page, $pages)
    {
        $main_link = '?post_type=wbtm_bus&page=passenger_list';
        $bus_id = (isset($_GET['bus_id']) ? $_GET['bus_id'] : null);
        if ($bus_id) {
            $bus_id = explode('-', $bus_id);
            $bus_id = $bus_id[0];

            $main_link .= '&bus_id=' . $bus_id;
        }

        $j_date = (isset($_GET['j_date']) ? $_GET['j_date'] : null);
        if ($j_date) {
            $main_link .= '&j_date=' . $j_date;
        }

        // The "back" link
        $prevlink = ($current_page > 1) ? '<a class="mage_paginate_link" href="' . $main_link . '&paged=1" title="First page">&laquo;</a> <a class="mage_paginate_link" href="' . $main_link . '&paged=' . ($current_page - 1) . '" title="Previous page">&lsaquo;</a>' : '<span class="disabled">&laquo;</span> <span class="disabled">&lsaquo;</span>';

        // The "forward" link
        $nextlink = ($current_page < $pages) ? '<a class="mage_paginate_link" href="' . $main_link . '&paged=' . ($current_page + 1) . '" title="Next page">&rsaquo;</a> <a class="mage_paginate_link" href="' . $main_link . '&paged=' . $pages . '" title="Last page">&raquo;</a>' : '<span class="disabled">&rsaquo;</span> <span class="disabled">&raquo;</span>';

        // Display the paging information
        $output = '<div class="mage-pagination"><p>' . $prevlink . ' Page ' . $current_page . ' of ' . $pages . ' ' . $nextlink . ' </p></div>';
        return $output;
    }


// Passenger Info Editing
    function wbtm_passenger_info_edit($booking_id) {


        if(isset($_POST['passenger_info_update'])) {

            $user_name = isset($_POST['wbtm_user_name']) ? $_POST['wbtm_user_name'] : null;
            $user_email = isset($_POST['wbtm_user_email']) ? $_POST['wbtm_user_email'] : null;
            $user_phone = isset($_POST['wbtm_user_phone']) ? $_POST['wbtm_user_phone'] : null;
            $user_gender = isset($_POST['wbtm_user_gender']) ? $_POST['wbtm_user_gender'] : null;
            $user_address = isset($_POST['wbtm_user_address']) ? $_POST['wbtm_user_address'] : null;

            // Update Booking data
            update_post_meta($booking_id, 'wbtm_user_name', $user_name);
            update_post_meta($booking_id, 'wbtm_user_email', $user_email);
            update_post_meta($booking_id, 'wbtm_user_phone', $user_phone);
            update_post_meta($booking_id, 'wbtm_user_gender', $user_gender);
            update_post_meta($booking_id, 'wbtm_user_address', $user_address);

            // Additional Data
            if(isset($_POST['user_additional_name'])) {
                $user_additional_update = array();
                if($_POST['user_additional_name']) {
                    foreach($_POST['user_additional_name'] as $item) {
                        $user_additional_update[] = array(
                            'name' => $item['name'],
                            'value' => $item['value'],
                        );
                    }
                }

                update_post_meta($booking_id, 'wbtm_user_additional', $user_additional_update);
            }

        }


        // Get Booking data
        $user_name = get_post_meta($booking_id, 'wbtm_user_name', true);
        $user_email = get_post_meta($booking_id, 'wbtm_user_email', true);
        $user_phone = get_post_meta($booking_id, 'wbtm_user_phone', true);
        $user_gender = get_post_meta($booking_id, 'wbtm_user_gender', true);
        $user_address = get_post_meta($booking_id, 'wbtm_user_address', true);

        // Passenger Additional
        $user_additional = get_post_meta($booking_id, 'wbtm_user_additional', true);
        $user_additional_html = '';
        if($user_additional) {
            $i = 0;
            foreach($user_additional as $info) {
                $user_additional_html .= '<tr>';
                $user_additional_html .= '<th>'.__($info['name'], "ddon-bus--ticket-booking-with-seat-pro").'</th>';
                $user_additional_html .= '<td><input type="hidden" name="user_additional_name['.$i.'][name]" value="'.$info["name"].'"><input type="text" name="user_additional_name['.$i.'][value]" value="'.$info["value"].'"></td>';
                $user_additional_html .= '</tr>';
                $i++;
            }
        }

        // HTML
        echo '<h2 style="line-height:1">'.__('Update Passenger Info', 'addon-bus--ticket-booking-with-seat-pro').'</h2>';
        echo '<span style="display:inline-block;margin-bottom:5px">Booking no #'.$booking_id.'</span>'

        ?>
        <form action="" method="POST">
            <table class="wp-list-table widefat striped posts extra_service_list_table" style="width: 100%;margin-bottom: 10px;">
                <tr>
                    <th><?php _e('Name', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                    <td><input type="text" name="wbtm_user_name" value="<?php echo $user_name; ?>"></td>
                </tr>
                <th><?php _e('Email', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <td><input type="text" name="wbtm_user_email" value="<?php echo $user_email; ?>"></td>
                </tr>
                <th><?php _e('Phone', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <td><input type="text" name="wbtm_user_phone" value="<?php echo $user_phone; ?>"></td>
                </tr>
                <th><?php _e('Address', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                <td><input type="text" name="wbtm_user_address" value="<?php echo $user_address; ?>"></td>
                </tr>
                <tr>
                    <th><?php _e('Gender', 'addon-bus--ticket-booking-with-seat-pro') ?></th>
                    <td>
                        <select name="wbtm_user_gender">
                            <option value=""><?php _e('Select Gender', 'addon-bus--ticket-booking-with-seat-pro') ?></option>
                            <option value="male" <?php echo strtolower($user_gender) == 'male' ? 'selected' : null; ?>><?php _e('Male', 'addon-bus--ticket-booking-with-seat-pro') ?></option>
                            <option value="female" <?php echo strtolower($user_gender) == 'female' ? 'selected' : null; ?>><?php _e('Female', 'addon-bus--ticket-booking-with-seat-pro') ?></option>
                        </select>
                    </td>
                </tr>
                <?php
                if($user_additional_html) {
                    echo $user_additional_html;
                }
                ?>
            </table>
            <input style="background-color:#4caf50;" class="wbtm_btn" type="submit" name="passenger_info_update" value="<?php _e('Update Passenger Info', 'addon-bus--ticket-booking-with-seat-pro'); ?>">
            <a class="wbtm_btn" href="<?php echo admin_url('edit.php?post_type=wbtm_bus&page=passenger_list') ?>"><?php _e('Go back', 'addon-bus--ticket-booking-with-seat-pro'); ?></a>
        </form>
        <?php

        return;
    }

    function wbbm_custom_field_for_single_bus(){

        $bus_id = isset($_POST['bus_id'])?$_POST['bus_id']:'0';
        $mage_meta = get_post_custom($bus_id);



                  if($bus_id){
                            $gender = array_key_exists('wbtm_user_gender', $mage_meta) ? strip_tags($mage_meta['wbtm_user_gender'][0]) : false;
                            if($gender){
                                ?>
                                <div class="mage-custom-filter-item-new">
                                    <label for="mage-f-bus-type">Select Gender</label>
                                    <select id="mage-f-bus-type" name="wbtm_user_gender">
                                        <option value="">Select Gender</option>
                                        <option <?php echo(isset($_GET['wbtm_user_gender']) ? (($_GET['wbtm_user_gender'] == 'Male')?'selected':''): '') ?> value="Male">Male</option>
                                        <option <?php echo(isset($_GET['wbtm_user_gender']) ? (($_GET['wbtm_user_gender'] == 'Female')?'selected':''): '') ?> value="Female">Female</option>
                                    </select>
                                </div>
                            <?php }

                            $custom_field = unserialize(get_post_meta($bus_id, 'attendee_reg_form', true));
                            if($custom_field) {
                                foreach($custom_field as $epv) {
                                    if($epv['filed_type']=='text'){
                                        ?>
                                        <div class="mage-custom-filter-item-new">
                                            <label for="filter_order_id"><?php echo $epv['field_label'] ?></label>
                                            <input type="text" id="<?php echo $epv['field_id'] ?>" name="<?php echo $epv['field_id'] ?>" placeholder="<?php echo $epv['field_label'] ?>" value="<?php echo(isset($_GET[$epv['field_id']]) ? $_GET[$epv['field_id']] : '') ?>">
                                        </div>
                                        <?php
                                    }
                                }
                            }
                        }

                  die;
    }


}

