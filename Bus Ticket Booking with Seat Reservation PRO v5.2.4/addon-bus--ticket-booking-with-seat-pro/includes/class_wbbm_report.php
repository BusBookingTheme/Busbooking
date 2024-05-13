<?php
if (!defined('ABSPATH')) {
    die;
}


if (!class_exists('WbbmReport')) {
    class WbbmReport
    {
        protected $text_domain = '';
        protected $post_type = '';
        protected $post_slug = '';
        public $page_for = '';
        public $this_page = '';
        public $currency = '';

        public function __construct($post_type, $post_slug, $text_domain)
        {
            session_start();
            $this->post_type = $post_type;
            $this->post_slug = $post_slug;
            $this->text_domain = $text_domain;
            $this->currency = '$';
            $this->this_page = get_admin_url() . 'edit.php?post_type=' . $this->post_type . '&page=' . $this->post_slug;

            add_action('admin_menu', array($this, 'wbbm_report_page'), 90);

            $this->page_for = isset($_GET['page_for']) ? $_GET['page_for'] : 'sales';

            // Ajax Handler
            add_action('wp_ajax_wbbm_get_bus_details', array($this, 'wbbm_get_bus_details'));
            add_action('wp_ajax_nopriv_wbbm_get_bus_details', array($this, 'wbbm_get_bus_details'));

            // Tab assign
            add_action('wp_ajax_wbtm_tab_assign', array($this, 'wbtm_tab_assign_callback'));
            add_action('wp_ajax_nopriv_wbtm_tab_assign', array($this, 'wbtm_tab_assign_callback'));

            // Order wise details
            add_action('wp_ajax_wbbm_get_order_details', array($this, 'wbbm_get_order_details_callback'));
            add_action('wp_ajax_nopriv_wbbm_get_order_details', array($this, 'wbbm_get_order_details_callback'));


        }

        public function wbbm_report_page()
        {
            add_submenu_page('edit.php?post_type=' . $this->post_type, __('Reports','addon-bus--ticket-booking-with-seat-pro'), __('Reports','addon-bus--ticket-booking-with-seat-pro'), 'manage_options', $this->post_slug, array($this, 'wbbm_reports_entry_point'));
        }

        /*
         * Page Entry Point
        */
        public function wbbm_reports_entry_point()
        {

            if (isset($_GET['wbbm_csv_export'])) {
                $this->wbbm_export_csv_callback();
            }
            if (isset($_GET['wbbm_detail_export_csv'])) {
                $bus_id = $_GET['detail_bus_id'];
                $this->wbbm_detail_export_csv($bus_id);
            }

            if (isset($_GET['wbbm_order_wise_csv_export'])) {

                $this->wbbm_order_wise_export_csv_callback();
            }

            $this->start_div('wbbm_page_wrapper'); // Main Wrap Start

            // Tab
            $this->section_tab();
            // Tab END

            // Content
            $this->section_content();
            // Content END

            $this->end_div(); // Main Wrap End
        }

        /*
         * Page Heading
        */
        public function section_heading($heading)
        {
            echo '<h1>' . strtoupper($heading) . '</h1>';
        }

        public function current_tab()
        {
            return isset($_SESSION['current_tab']) ? $_SESSION['current_tab'] : 'one';
        }

        public function wbtm_tab_assign_callback()
        {
            // unset($_SESSION['filter_where']);
            $tab = isset($_POST['tab_no']) ? $_POST['tab_no'] : null;
            if ($tab) {
                $_SESSION['current_tab'] = $tab;
            } else {
                $_SESSION['current_tab'] = 'one';
            }
        }

        public function section_tab()
        {
            ?>

            <div class="wbtm-page-top">
                <div class="wbtm-page-top-inner">
                    <h3>
                        <?php _e('Select Report', 'addon-bus--ticket-booking-with-seat-pro'); ?>
                    </h3>
                    <ul class="wbtm_tab_link_wrap">
                        <li class="clickme">
                            <button data-tag="sells_details_report" data-tab-no="one"
                                    class="wbtm_tab_link wbtm_btn_primary <?php echo $this->current_tab() == 'one' ? 'wbtm_tab_active' : '' ?>"><?php _e('Sales details Report', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
                        </li>
                        <li class="clickme">
                            <a href="<?php echo get_admin_url() . 'edit.php?post_type=wbtm_bus&page=bus_report'; ?>"
                               class="wbtm_tab_link wbtm_btn_primary"><?php _e('Quick book status report', 'addon-bus--ticket-booking-with-seat-pro') ?></a>
                        </li>
                        <li class="clickme">
                            <button data-tag="order_wise_details_report" data-tab-no="three"
                                    class="wbtm_tab_link wbtm_btn_primary  <?php echo $this->current_tab() == 'three' ? 'wbtm_tab_active' : '' ?>"><?php _e('Order wise details Report', 'addon-bus--ticket-booking-with-seat-pro') ?></button>
                        </li>
                    </ul>
                </div>
            </div>

            <?php
        }

        // Get Filter Field
        public function get_filter_field($from_date, $to_date)
        {
            switch ($this->page_for) {
                default :
                    $filter_fields = $this->filter_sales_fields($from_date, $to_date);
            }

            return $filter_fields;
        }

        // Get Content Data
        public function get_content_data(): array
        {
            switch ($this->page_for) {
                default :
                    $content_data = $this->content_sales();
            }

            return $content_data;
        }

        /*
         * Section Filter
        */
        public function section_filter($from_date, $to_date)
        {
            $this->start_div('wbbm_page_filter_wrapper'); // Wrapper Start
            $this->start_div('wbbm_page_filter_inner'); // Inner Start
            $this->start_div('wbbm_page_filter_top'); // Top Start

            $filter_active = '';
            if (isset($_GET['filter_by'])) {
                $filter_active = $_GET['filter_by'];
            }
            ?>
            <ul>
                <li class="<?php echo($filter_active == 'last_year' ? 'filter_active' : '') ?>"><a
                            href="<?php echo $this->this_page . '&filter_by=last_year'; ?>"><?php  _e('Last Year','addon-bus--ticket-booking-with-seat-pro'); ?></a>
                </li>
                <li class="<?php echo($filter_active == 'this_year' ? 'filter_active' : '') ?>"><a
                            href="<?php echo $this->this_page . '&filter_by=this_year'; ?>"><?php  _e('This Year','addon-bus--ticket-booking-with-seat-pro'); ?></a>
                </li>
                <li class="<?php echo($filter_active == 'last_month' ? 'filter_active' : '') ?>"><a
                            href="<?php echo $this->this_page . '&filter_by=last_month'; ?>"><?php  _e('Last Month','addon-bus--ticket-booking-with-seat-pro'); ?></a>
                </li>
                <li class="<?php echo($filter_active == 'this_month' ? 'filter_active' : '') ?>"><a
                            href="<?php echo $this->this_page . '&filter_by=this_month'; ?>"><?php  _e('This Month','addon-bus--ticket-booking-with-seat-pro'); ?></a>
                </li>
                <li class="<?php echo($filter_active == 'last_week' ? 'filter_active' : '') ?>"><a
                            href="<?php echo $this->this_page . '&filter_by=last_week'; ?>"><?php  _e('Last Week','addon-bus--ticket-booking-with-seat-pro'); ?></a>
                </li>
            </ul>
            <?php
            echo '<a href="' . $this->this_page . '" class="wbbm_btn_new reset-filter">' . __('Reset all filter','addon-bus--ticket-booking-with-seat-pro') . '</a>';
            $this->end_div(); // Top End

            $this->start_div('wbbm_page_filter_bottom'); // Bottom Start
            ?>
            <label class="sec-label"><?php  _e('Filter By','addon-bus--ticket-booking-with-seat-pro') ?></label>
            <form action="<?php echo get_admin_url(); ?>edit.php?post_type=wbbm_bus&page=wbbm-reports" method="GET">
                <input type="hidden" name="post_type" value="<?php echo $this->post_type ?>">
                <input type="hidden" name="page" value="<?php echo $this->post_slug ?>">
                <div class="wbbm-form-inner">

                    <?php $this->get_filter_field($from_date, $to_date); ?>

                    <input type="submit" class="wbbm_btn_new" name="submit"
                           value="<?php  _e('Search','addon-bus--ticket-booking-with-seat-pro'); ?>">
                </div>
            </form>
            <?php
            $this->end_div(); // Bottom End
            $this->end_div(); // Inner End
            $this->end_div(); // Content End
        }

        /*
         * Section Content
        */
        public function section_content()
        {
            ?>

            <div style="clear: both;"></div>
            <div id="container">
                <div class="wbtm_content_item <?php echo $this->current_tab() == 'one' ? 'active' : 'hide' ?>"
                     id="sells_details_report">
                    <?php $this->content_tab_one(); ?>
                </div>

                <div class="wbtm_content_item <?php echo $this->current_tab() == 'three' ? 'active' : 'hide' ?>"
                     id="order_wise_details_report">
                    <?php $this->content_tab_three(); ?>
                </div>
            </div>

            <?php
        }

        protected function content_tab_one()
        {
            // Heading
            $this->section_heading(__('Sales Details Report','addon-bus--ticket-booking-with-seat-pro'));
            // Heading END

            // Filter
            $this->section_filter('one_from_date', 'one_to_date');
            // Filter END

            $this->start_div('wbbm_page_content_wrapper'); // Filter Start
            $get_content_data = $this->get_content_data();
            $head = $get_content_data['head'];
            $body = $get_content_data['body'];

            if (isset($_SESSION['filter_text'])) : ?>
                <div style="text-align:right">
                    <form action="" method="GET">
                        <!-- <button id="wbbm_export_csv" class="wbbm_btn_new">Export CSV</button> -->
                        <input type="hidden" name="post_type" value="<?php echo $this->post_type ?>">
                        <input type="hidden" name="page" value="<?php echo $this->post_slug ?>">
                        <input style="background: #47a96e;font-weight: 700;" class="wbbm_btn_new" type="submit"
                               name="wbbm_csv_export"
                               value="<?php _e('Export CSV','addon-bus--ticket-booking-with-seat-pro'); ?>">
                    </form>
                </div>
            <?php endif;
            ?>

            <div id="wbbm_report_table_main">
                <?php echo isset($_SESSION['filter_text']) ? '<p class="wbbm-report-heading">' . $_SESSION['filter_text'] . '</p>' : ''; ?>
                <table class="wbbm-main-table">
                    <thead>
                    <tr>
                        <?php
                        switch ($this->page_for) {
                            default :
                                foreach ($head as $th) {
                                    echo '<th>' . $th . '</th>';
                                }
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (!empty($head) && !empty($body)) {
                        switch ($this->page_for) {
                            default :
                                echo $body;
                                break;
                        }
                    } else {
                        printf('<p class="wbbm_no_data_found">%s</p>', (isset($_GET['submit']) ? __("No data found!",'addon-bus--ticket-booking-with-seat-pro') : ""));
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <?php
            $this->end_div(); // Content End
        }

        protected function content_tab_three()
        {
            // Heading
            $this->section_heading(__('Order wise Details Report','addon-bus--ticket-booking-with-seat-pro'));
            // Heading END

            // Filter
            $this->section_filter('three_from_date', 'three_to_date');
            // Filter END

            $this->start_div('wbbm_page_content_wrapper'); // Filter Start

            // Query
            $filter_where = $_SESSION['filter_where'];

            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => 'IN',
                    'value' => array(1, 2)
                ),
                $filter_where
            );
            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'meta_query' => $meta_query
            );

            $res = new WP_Query($args);
            $total_order = 0;
            $total_seat = 0;
            $total_price = 0;


            // Start
            $data_html = '';
            if ($res && $filter_where) :
                $all_orders = [];
                while ($res->have_posts()) {
                    $res->the_post();
                    $order_id = get_post_meta(get_the_ID(), 'wbtm_order_id', true);
                    $show_price = $original_price = get_post_meta(get_the_ID(), 'wbtm_bus_fare', true);
                    // $any_date_return = wbtm_anydate_return(get_the_ID(), $order_id);
                    // if($any_date_return['status'] === 'on') {
                    //     $show_price = $original_price = $show_price + $any_date_return['price'];
                    // }
                    $discount_price = $this->wbbm_post_meta(get_the_ID(), '_wbtm_tp') ?? 0 ;
                    if($discount_price && $original_price > $discount_price) {
                        $show_price = $discount_price;
                    }
                    $all_orders[] = array(
                        'id' => get_post_meta(get_the_ID(), 'wbtm_order_id', true),
                        'original_price' => $original_price,
                        'amount' => $show_price,
                        'booking_id' => get_the_ID(),
                        'is_return' => get_post_meta(get_the_ID(), 'wbtm_is_return', true) ? get_post_meta(get_the_ID(), 'wbtm_is_return', true) : 0
                    );
                }
                wp_reset_postdata();

                $i = 0;
                while ($res->have_posts()) : $res->the_post();
                    $id = get_the_ID();
                    $order = wc_get_order($this->wbbm_post_meta($id, 'wbtm_order_id'));

                    $current_order = $this->wbbm_post_meta($id, 'wbtm_order_id');
                    $prev_order = (isset($prev_order) ? $prev_order : $current_order);

                    $original_price = 0;
                    $amount = 0;
                    $count = 0;
                    $j = 0;
                    $is_return = 0;
                    $seat = array();

                    // Remove trash order
                    if (($order && $order->get_status() != 'processing' && $order->get_status() != 'completed')) {
                        continue;
                    }

                    if ($current_order != $prev_order || $i == 0) {
                        foreach ($all_orders as $o) {
                            if ($current_order == $o['id']) {
                                $original_price += (float)$o['original_price'];
                                $amount += (float)$o['amount'];
                                $count += $j + 1;
                                $seat[] = $this->wbbm_post_meta($o['booking_id'], 'wbtm_seat');
                                $is_return += (int) $o['is_return'];
                            }
                        }

                        // Extra Service Price
                        $get_es_prices = get_post_meta($current_order, '_extra_services', true);
                        $es_price = 0;
                        if ($get_es_prices) {
                            foreach ($get_es_prices as $price) {
                                $es_price += $price['qty'] * $price['price'];
                            }
                        }

                        $data_html .= '<tr>';
                        $data_html .= '<td>#' . $current_order . '</td>';
                        $data_html .= '<td>' . ($order ? $order->get_formatted_billing_full_name() : "") . '</td>';
                        $data_html .= '<td>' . $this->wbbm_post_meta($id, 'wbtm_booking_date') . '</td>';
                        $data_html .= '<td>' . $this->wbbm_post_meta($id, 'wbtm_boarding_point') . '</td>';
                        $data_html .= '<td>' . $this->wbbm_post_meta($id, 'wbtm_droping_point') . '</td>';
                        $data_html .= '<td>' . (($is_return > 1 && $count > 1) ? 'Yes' : 'No') . '</td>';
                        $data_html .= '<td>' . $count . '</td>';
                        $data_html .= '<td>' . ($seat ? implode(', ', $seat) : '') . '</td>';
                        $data_html .= '<td>' . ($original_price > $amount ? '<span style="text-decoration: line-through">'.wc_price($original_price).'</span><span style="display:block">'.wc_price($amount).'</span>' : wc_price($amount)) . '</td>';
                        $data_html .= '<td>' . ($es_price ? wc_price($es_price) : null) . '</td>';
                        $data_html .= '<td>' . wc_price($amount + $es_price) . '</td>';
                        $data_html .= '<td>' . ($order ? $order->get_status() : "") . '</td>';
                        $data_html .= '<td class="wbbm_order_detail--report" data-order-id="' . $current_order . '"><img class="wbbm_report_loading" src="' . plugin_dir_url(__FILE__) . '../' . 'img/loading.gif' . '"/> <div class="action-btn-wrap"><button class="wbbm_detail_inside">' . __("Details Inside",'addon-bus--ticket-booking-with-seat-pro') . '</button></div></td>';
                        $data_html .= '</tr>';

                        $total_order++;
                        $total_seat += $count;
                        $total_price += $amount + $es_price;
                    }

                    $j++;

                    $prev_order = $current_order;
                    $i++;
                endwhile; endif;
            wp_reset_postdata();
            // END
            ?>


            <div id="wbbm_report_table_main">
                <?php echo isset($_SESSION['filter_text']) ? '<p class="wbbm-report-heading">' . $_SESSION['filter_text'] . '</p>' : ''; ?>
                <div class="wbbm-table-top">
                    <div class="left">
                        <div class="item">
                            <strong><?php  _e('Number of Order','addon-bus--ticket-booking-with-seat-pro') ?>:</strong>
                            <span><?php echo $total_order; ?></span>
                        </div>
                        <div class="item">
                            <strong><?php  _e('Total Ticket','addon-bus--ticket-booking-with-seat-pro') ?>:</strong>
                            <span><?php echo $total_seat; ?></span>
                        </div>
                        <div class="item">
                            <strong><?php  _e('Sold Amount','addon-bus--ticket-booking-with-seat-pro') ?>:</strong>
                            <span><?php echo wc_price($total_price); ?></span>
                        </div>
                    </div>
                    <div class="right">
                        <?php
                        if (isset($_SESSION['filter_text'])) : ?>
                            <div style="text-align:right">
                                <!-- <button id="wbbm_export_pdf" class="wbbm_btn_new">PDF Download</button> -->

                                <form action="" method="GET">
                                    <!-- <button id="wbbm_export_csv" class="wbbm_btn_new">Export CSV</button> -->
                                    <input type="hidden" name="post_type" value="<?php echo $this->post_type ?>">
                                    <input type="hidden" name="page" value="<?php echo $this->post_slug ?>">
                                    <input style="background: #47a96e;font-weight: 700;" class="wbbm_btn_new"
                                           type="submit"
                                           name="wbbm_order_wise_csv_export"
                                           value="&#8595; Export CSV">
                                </form>
                            </div>
                        <?php endif;
                        ?>
                    </div>
                </div>
                <table class="wbbm-main-table-order-wise">
                    <thead>
                    <tr>
                        <th><?php  _e('Order no','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Billing Name','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Booking Date','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Boarding','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Dropping','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Round Trip','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Qty','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Seat','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Seat Price','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Extra Service Price','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Total Price','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Status','addon-bus--ticket-booking-with-seat-pro') ?></th>
                        <th><?php  _e('Action','addon-bus--ticket-booking-with-seat-pro') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Output Data -->
                    <?php echo $data_html; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $this->end_div(); // Content End
        }

        // Filter fields for Sales
        public function filter_sales_fields($fd, $td)
        {
            $filter_active = 'filter_active';
            $bus_id = null;
            if (isset($_GET['bus_id'])) {
                $bus_id = $_GET['bus_id'] != '' ? $filter_active : null;
            }

            $from_date = null;
            if (isset($_GET['from_date'])) {
                $from_date = $_GET['from_date'] != '' ? $filter_active : null;
            }

            $to_date = null;
            if (isset($_GET['to_date'])) {
                $to_date = $_GET['to_date'] != '' ? $filter_active : null;
            }

            $boarding_point = null;
            if (isset($_GET['boarding_point'])) {
                $boarding_point = $_GET['boarding_point'] != '' ? $filter_active : null;
            }

            $dropping_point = null;
            if (isset($_GET['dropping_point'])) {
                $dropping_point = $_GET['dropping_point'] != '' ? $filter_active : null;
            }

            $routes = get_terms(array(
                'taxonomy' => 'wbtm_bus_stops',
                'hide_empty' => false,
            ));

            // All users
            $users = get_users();
            // echo '<pre>'; print_r($users); die;

            $buses = new WP_Query($this->buses());
            $count = $buses->found_posts;
            if ($count > 0) : ?>
                <div class="wbbm-field-group">
                    <label for="user_id"><?php  _e('By Seller','addon-bus--ticket-booking-with-seat-pro') ?></label>
                    <select class="<?php echo($bus_id ? $filter_active : '') ?>" name="user_id" id="user_id">
                        <option value=""><?php  _e('Select Seller','addon-bus--ticket-booking-with-seat-pro') ?></option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo $user->ID; ?>" <?php
                            if (isset($_GET['user_id'])) {
                                if ($user->ID == $_GET['user_id']) {
                                    echo 'selected';
                                }
                            }
                            ?>>
                                <?php echo ucfirst($user->data->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wbbm-field-group">
                    <label for="bus_id"><?php echo __('By', 'addon-bus--ticket-booking-with-seat-pro').' '.mage_bus_setting_value('bus_menu_label', 'Bus'); ?></label>
                    <select class="<?php echo($bus_id ? $filter_active : '') ?>" name="bus_id" id="bus_id">
                        <option value=""><?php echo __('Select','addon-bus--ticket-booking-with-seat-pro').' '. mage_bus_setting_value('bus_menu_label', 'Bus') ?></option>
                        <?php while ($buses->have_posts()) : $buses->the_post(); ?>
                            <option value="<?php echo get_the_id() ?>" <?php
                            if (isset($_GET['bus_id'])) {
                                if (get_the_ID() == $_GET['bus_id']) {
                                    echo 'selected';
                                }
                            }
                            ?>>
                                <?php the_title(); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php
            if ($routes) : ?>
                <div class="wbbm-field-group">
                    <label for="boarding_point"><?php  _e('Boarding Point','addon-bus--ticket-booking-with-seat-pro') ?></label>
                    <select name="boarding_point" id="boarding_point"
                            class="<?php echo($boarding_point ? $filter_active : '') ?>">
                        <option value=""><?php  _e('Boarding Point','addon-bus--ticket-booking-with-seat-pro') ?></option>
                        <?php foreach ($routes as $route) : ?>
                            <option value="<?php echo $route->name ?>" <?php
                            if (isset($_GET['boarding_point'])) {
                                if ($route->name == $_GET['boarding_point']) {
                                    echo 'selected';
                                }
                            }
                            ?>>
                                <?php echo $route->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wbbm-field-group">
                    <label for="to_date"><?php  _e('Dropping Point','addon-bus--ticket-booking-with-seat-pro') ?></label>
                    <select name="dropping_point" id="dropping_point"
                            class="<?php echo($dropping_point ? $filter_active : '') ?>">
                        <option value=""><?php  _e('Dropping Point','addon-bus--ticket-booking-with-seat-pro') ?></option>
                        <?php foreach ($routes as $route) : ?>
                            <option value="<?php echo $route->name ?>" <?php
                            if (isset($_GET['dropping_point'])) {
                                if ($route->name == $_GET['dropping_point']) {
                                    echo 'selected';
                                }
                            }
                            ?>>
                                <?php echo $route->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="wbbm-field-group">
                <label for="<?php echo $fd ?>"><?php  _e('From Date','addon-bus--ticket-booking-with-seat-pro') ?></label>
                <input class="from_date <?php echo($from_date ? $filter_active : '') ?>" type="text"
                       id="<?php echo $fd ?>"
                       name="from_date"
                       value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : '' ?>"
                       placeholder="yyyy-mm-dd">
            </div>
            <div class="wbbm-field-group">
                <label for="<?php echo $td ?>"><?php  _e('To Date','addon-bus--ticket-booking-with-seat-pro') ?></label>
                <input class="to_date <?php echo($to_date ? $filter_active : '') ?>" type="text" id="<?php echo $td ?>"
                       name="to_date"
                       value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : '' ?>" placeholder="yyyy-mm-dd">
            </div>
            <?php
        }

        // Content for Sales
        public function content_sales(): array
        {
            $res = $this->filter_query();
            $i = 0;
            $t = 0;

            $html = '';
            if ($res) {

                foreach ($res as $id => $total) {

                    $html .= '<tr>';
                    $html .= '<td>' . get_post_meta($id, 'wbtm_bus_no', true) . '</td>';
                    $html .= '<td>' . get_the_title($id) . '</td>';
                    $html .= '<td>' . ($total ? wc_price($total) : 0) . '</td>';
                    $html .= '<td class="wbbm_bus_detail--report" data-bus-id="' . $id . '"><img class="wbbm_report_loading" src="' . plugin_dir_url(__FILE__) . '../' . 'img/loading.gif' . '"/> <div class="action-btn-wrap"><button class="wbbm_detail_inside">' . __("Details Inside",'addon-bus--ticket-booking-with-seat-pro') . '</button> <form action="" method="GET"><input type="hidden" name="post_type" value="' . $this->post_type . '"><input type="hidden" name="page" value="' . $this->post_slug . '"><input type="hidden" name="detail_bus_id" value="' . $id . '"><button type="submit" name="wbbm_detail_export_csv">' . __("Export Csv",'addon-bus--ticket-booking-with-seat-pro') . '</button></form></div></td>';
                    $html .= '</tr>';

                    $t += $total;
                    $i++;
                }
                // Grand total row
                $html .= '<tr>';
                $html .= '<td colspan="2">' . __('Grand Total','addon-bus--ticket-booking-with-seat-pro') . '</td>';
                $html .= '<td colspan="2">' . ($t ? wc_price($t) : 0) . '</td>';
                $html .= '</tr>';

            } else {
                $html .= '<tr>';
                $html .= '<td colspan="2">' . __('Grand Total:','addon-bus--ticket-booking-with-seat-pro') . '</td>';
                $html .= '<td colspan="2">0</td>';
                $html .= '</tr>';
            }


            return array(
                'head' => array(
                    mage_bus_setting_value('bus_menu_label') . ' ' . __('No', 'addon-bus--ticket-booking-with-seat-pro'),
                    mage_bus_setting_value('bus_menu_label').' '.__('Name','addon-bus--ticket-booking-with-seat-pro'),
                    __('Amount','addon-bus--ticket-booking-with-seat-pro'),
                    __('Action','addon-bus--ticket-booking-with-seat-pro'),
                ),
                'body' => $html
            );
        }

        /*
         * DB Query
        */
        public function filter_query()
        {
            $query_where = '';
            $filter_text = '';
            $c_date = date('Y-m-d');

            $query_string = $_GET;
            $from_date = isset($query_string['from_date']) ? $query_string['from_date'] : '';
            $to_date = isset($query_string['to_date']) ? $query_string['to_date'] : '';
            $bus_id = isset($query_string['bus_id']) ? $query_string['bus_id'] : '';
            $user_id = isset($query_string['user_id']) ? $query_string['user_id'] : '';
            $boarding_point = isset($query_string['boarding_point']) ? $query_string['boarding_point'] : '';
            $dropping_point = isset($query_string['dropping_point']) ? $query_string['dropping_point'] : '';

            $filter_meta_query = array();

            if ($from_date != '') {
                $f_date = date('Y-m-d', strtotime($from_date));
                $filter_meta_query[] = array(
                    'key' => 'wbtm_booking_date',
                    'compare' => ">=",
                    'value' => date('Y-m-d H:i a', strtotime($f_date . ' 12:01 am')),
                );
                $query_where = "AND booking_date BETWEEN '$f_date' AND '$c_date'";

                $filter_text = 'From ' . date('Y-m-d', strtotime($from_date)) . ' To ' . $c_date;
            }


            if ($from_date != '' && $to_date != '') {
                $f_date = date('Y-m-d', strtotime($from_date));
                $t_date = date('Y-m-d', strtotime($to_date . ' 23:59:59'));
                $filter_meta_query = array(
                    array(
                        'key' => 'wbtm_booking_date',
                        'compare' => ">=",
                        'value' => date('Y-m-d H:i a', strtotime($f_date . ' 12:01 am')),
                    ),
                    array(
                        'key' => 'wbtm_booking_date',
                        'compare' => '<=',
                        'value' => date('Y-m-d H:i a', strtotime($t_date . ' 11:59 pm'))
                    ),
                );

                $query_where = "AND booking_date BETWEEN '$f_date' AND '$t_date'";

                $filter_text = 'From ' . date('Y-m-d', strtotime($f_date)) . ' To ' . date('Y-m-d', strtotime($t_date));
            }

            if ($user_id != '') {
                $query_where .= " AND bus_id = '$user_id'";

                if (!empty($filter_meta_query)) {
                    array_push($filter_meta_query,
                        array(
                            'key' => 'wbtm_user_id',
                            'compare' => '=',
                            'value' => $user_id
                        )
                    );
                } else {
                    $filter_meta_query[] = array(
                        'key' => 'wbtm_user_id',
                        'compare' => '=',
                        'value' => $user_id
                    );
                }

                $filter_text .= ' Sales by <span style="text-decoration: underline;">' . ucfirst(get_userdata($user_id)->data->display_name) . '</span>';
            }

            if ($bus_id != '') {
                $query_where .= " AND bus_id = '$bus_id'";

                if (!empty($filter_meta_query)) {
                    array_push($filter_meta_query,
                        array(
                            'key' => 'wbtm_bus_id',
                            'compare' => '=',
                            'value' => $bus_id
                        )
                    );
                } else {
                    $filter_meta_query[] = array(
                        'key' => 'wbtm_bus_id',
                        'compare' => '=',
                        'value' => $bus_id
                    );
                }

                $filter_text .= ' Under <span style="text-decoration: underline;">' . get_the_title($bus_id) . '</span>';
            }

            if ($boarding_point != '') {
                $query_where .= " AND boarding_point = '$boarding_point'";
                if (!empty($filter_meta_query)) {
                    array_push($filter_meta_query,
                        array(
                            'key' => 'wbtm_boarding_point',
                            'compare' => '=',
                            'value' => $boarding_point
                        )
                    );
                } else {
                    $filter_meta_query[] = array(
                        'key' => 'wbtm_boarding_point',
                        'compare' => '=',
                        'value' => $boarding_point
                    );
                }
            }

            if ($dropping_point != '') {
                $query_where .= " AND droping_point = '$dropping_point'";
                if (!empty($filter_meta_query)) {
                    array_push($filter_meta_query,
                        array(
                            'key' => 'wbtm_droping_point',
                            'compare' => '=',
                            'value' => $dropping_point
                        )
                    );
                } else {
                    $filter_meta_query[] = array(
                        'key' => 'wbtm_droping_point',
                        'compare' => '=',
                        'value' => $dropping_point
                    );
                }
            }

            if (!empty($filter_meta_query)) {
                // $_SESSION['filter_text'] = _e('Showing Data') . ' ' . $filter_text;
                $_SESSION['filter_text'] = $filter_text;
            } else {
                unset($_SESSION['filter_text']);
            }


            if (isset($query_string['filter_by'])) {
                switch (strtolower($query_string['filter_by'])) {
                    case 'last_year' :
                        $f_date = date("Y-m-d", strtotime("last year January 1st"));
                        $t_date = date("Y-m-d", strtotime("last year December 31st"));
                        $filter_text = __('Last Year\'s','addon-bus--ticket-booking-with-seat-pro');
                        break;
                    case 'this_year' :
                        $f_date = date('Y') . '-01-01';
                        $t_date = date('Y') . '-12-31';
                        $filter_text = __('This Year\'s','addon-bus--ticket-booking-with-seat-pro');
                        break;
                    case 'last_month' :
                        $f_date = date("Y-m-d", strtotime("first day of previous month"));
                        $f_date = date("Y-m-d", strtotime($f_date . ' 23:59:59'));
                        $t_date = date("Y-m-d", strtotime("last day of previous month"));
                        $t_date = date("Y-m-d", strtotime($t_date . ' 23:59:59'));
                        $filter_text = __('Last Month\'s','addon-bus--ticket-booking-with-seat-pro');
                        break;
                    case 'this_month' :
                        $f_date = date("Y-m-d", strtotime("first day of this month"));
                        $t_date = date("Y-m-d", strtotime("last day of this month"));
                        $filter_text = __('This Month\'s','addon-bus--ticket-booking-with-seat-pro');
                        break;
                    case 'last_week' :
                        $f_date = date("Y-m-d", strtotime("-7 days"));
                        $t_date = date("Y-m-d", strtotime("yesterday"));
                        $filter_text = __('Last Week\'s','addon-bus--ticket-booking-with-seat-pro');
                        break;
                }

                $filter_meta_query = array(
                    array(
                        'key' => 'wbtm_booking_date',
                        'compare' => '>=',
                        'value' => date('Y-m-d H:i a', strtotime($f_date . ' 12:01 am')),
                        // 'type' => 'DATE'
                    ),
                    array(
                        'key' => 'wbtm_booking_date',
                        'compare' => '<=',
                        'value' => date('Y-m-d H:i a', strtotime($t_date . ' 11:59 pm'))
                        // 'type' => 'DATE'
                    ),
                );

                $query_where = "AND booking_date >= '$f_date' AND booking_date <= '$t_date'";

                if ($filter_text != '') {
                    $_SESSION['filter_text'] = __('Showing','addon-bus--ticket-booking-with-seat-pro') . ' ' . $filter_text . ' Data';
                } else {
                    unset($_SESSION['filter_text']);
                }
            }

            // echo $query_where;
            if ($filter_meta_query != '') {
                $_SESSION['filter_where'] = $filter_meta_query;
            } else {
                unset($_SESSION['filter_where']);
            }

            // Final Query
            if ($filter_meta_query) {

                // Bus lists
                $bus_lists = $this->bus_lists();
                // Bus lists END

                // Main Query
                $meta_query = array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wbtm_status',
                        'compare' => 'IN',
                        'value' => array(1, 2)
                    ),
                    $filter_meta_query
                );

                $args = array(
                    'post_type' => 'wbtm_bus_booking',
                    'posts_per_page' => -1,
                    'order' => 'DESC',
                    'meta_query' => $meta_query
                );

                $res = new WP_Query($args);

                $all_buses = array();
                while ($res->have_posts()) {
                    $res->the_post();
                    $order_id = $this->wbbm_post_meta(get_the_ID(), 'wbtm_order_id');
                    if (!$order_id) continue;
                    
                    $order = wc_get_order($order_id);
                    if(!$order) continue;

                    if(($order->get_status() != 'processing' && $order->get_status() != 'completed')) {
                        continue;
                    }

                    $show_price = $original_price = get_post_meta(get_the_ID(), 'wbtm_bus_fare', true);
                    $discount_price = $this->wbbm_post_meta(get_the_ID(), '_wbtm_tp') ?? 0;
                    if ($discount_price && $original_price > $discount_price) {
                        $show_price = $discount_price;
                    }
                    
                    $all_buses[] = array(
                        'id' => get_post_meta(get_the_ID(), 'wbtm_bus_id', true),
                        'amount' => $show_price,
                        'booking' => get_post_meta(get_the_ID(), 'wbtm_booking_date', true),
                    );
                }
                wp_reset_postdata();
                // Main Query END

                $final = array();
                if ($bus_lists) {
                    foreach ($bus_lists as $bus) {
                        $amount = 0;
                        foreach ($all_buses as $data) {
                            if ($bus == $data['id']) {
                                $amount += (float)$data['amount'];
                                $final[$bus] = $amount;
                            }
                        }
                    }
                }

            }

            // echo '<pre>'; print_r($final); die;

            if (isset($final)) {
                return $final;
            } else {
                return null;
            }
        }

        public function filter_day_items(): array
        {
            return array(
                array(
                    'value' => 7,
                    'name' => __('7 Days','addon-bus--ticket-booking-with-seat-pro'),
                ),
                array(
                    'value' => 10,
                    'name' => __('10 Days','addon-bus--ticket-booking-with-seat-pro'),
                ),
                array(
                    'value' => 15,
                    'name' => __('15 Days','addon-bus--ticket-booking-with-seat-pro'),
                ),
                array(
                    'value' => 30,
                    'name' => __('30 Days','addon-bus--ticket-booking-with-seat-pro'),
                ),
                array(
                    'value' => 120,
                    'name' => __('120 Days','addon-bus--ticket-booking-with-seat-pro'),
                ),

            );
        }

        public function buses()
        {
            $args = array(
                'post_type' => 'wbtm_bus',
                'posts_per_page' => -1
            );

            return $args;
        }

        /*
         * Language
         * */
        public function lang($text): string
        {
            $text = __($text, $this->text_domain);

            return $text;
        }

        /*
         * Start Div
        */
        public function start_div($className = null, $id = null)
        {
            echo '<div ' . ($className ? "class=$className" : "") . ' ' . ($id ? "id=$id" : "") . '>';
        }

        /*
         * End Div
        */
        public function end_div()
        {
            echo '</div>';
        }

        // Ajax
        public function wbbm_get_bus_details()
        {
            $bus_id = $_POST['bus_id'];
            $filter_where = isset($_SESSION['filter_where'])?$_SESSION['filter_where']:'';


            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => 'IN',
                    'value' => array(1, 2)
                ),
                array(
                    'key' => 'wbtm_bus_id',
                    'compare' => '=',
                    'value' => $bus_id
                ),
                $filter_where
            );

            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'meta_qeury' => $meta_query
            );

            $res = new WP_Query($args);
            //echo '<pre>';print_r($res); echo '<pre>';die;
            // ***********

            $html = '';
            if ($res) {
                $html .= '<tr class="wbbm_report_detail"><td colspan="4">';
                $html .= '<table><thead><tr>';
                $html .= '<th>Order No</th><th>Booking date</th><th>Name</th><th>Seat</th><th>Boarding</th><th>Dropping</th><th>Price</th><th>Status</th>';
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                while ($res->have_posts()) {
                    $res->the_post();
                    $id = get_the_ID();
                    $order = wc_get_order($this->wbbm_post_meta($id, 'wbtm_order_id'));

                    $show_price = $original_price = get_post_meta(get_the_ID(), 'wbtm_bus_fare', true);
                    $discount_price = $this->wbbm_post_meta(get_the_ID(), '_wbtm_tp') ?? 0 ;
                    if($discount_price && $original_price > $discount_price) {
                        $show_price = $discount_price;
                    }

                    // Remove trash order
                    if (($res->the_post() && $order->get_status() != 'processing' && $order->get_status() != 'completed')) {
                        continue;
                    }

                    $html .= '<tr>';
                    $html .= '<td>#' . $this->wbbm_post_meta($id, 'wbtm_order_id') . '</td><td>' . $this->wbbm_post_meta($id, 'wbtm_booking_date') . '</td><td>' . ucfirst($this->wbbm_post_meta($id, 'wbtm_user_name')) . '</td><td>' . ucfirst($this->wbbm_post_meta($id, 'wbtm_seat')) . '</td><td>' . $this->wbbm_post_meta($id, 'wbtm_boarding_point') . '</td><td>' . $this->wbbm_post_meta($id, 'wbtm_droping_point') . '</td>';

                    $html .= '<td>' . ($original_price > $show_price ? '<span style="text-decoration: line-through">'.wc_price($original_price).'</span><span style="display:block">'.wc_price($show_price).'</span>' : wc_price($show_price)) . '</td>';
                    $html .= '<td>' . ($order ? $order->get_status() : "") . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</td></tr>';
            }
            wp_reset_postdata();

            echo $html;
            exit;
        }

        public function wbbm_get_order_details_callback()
        {
            $order_id = $_POST['order_id'];
            $filter_where = $_SESSION['filter_where'];

            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => 'IN',
                    'value' => array(1, 2)
                ),
                array(
                    'key' => 'wbtm_order_id',
                    'compare' => '=',
                    'value' => $order_id
                ),
                $filter_where
            );

            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'meta_query' => $meta_query
            );

            $res = new WP_Query($args);

            $html = '';
            if ($res) {
                $html .= '<tr class="wbbm_report_detail"><td colspan="13">';
                $html .= '<table><thead><tr>';
                $html .= '<th>Name</th><th>Email</th><th>Phone</th><th>Seat</th><th>Seat Price</th>';
                // $html .= '<th>Name</th><th>Email</th><th>Phone</th><th>Seat</th><th>Seat Price</th><th>Any date Return</th>';
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                while ($res->have_posts()) {
                    $res->the_post();
                    $id = get_the_ID();
                    $order_id = wc_get_order($this->wbbm_post_meta($id, 'wbtm_order_id'));
                    $show_price = $this->wbbm_post_meta($id, 'wbtm_bus_fare');
                    $discount_price = $this->wbbm_post_meta($id, '_wbtm_tp') ?? 0 ;
                    if($discount_price) {
                        $show_price = $discount_price;
                    }
                    // $any_date_return = wbtm_anydate_return($id, $order_id);
                    

                    $html .= '<tr>';
                    $html .= '<td>' . ucfirst($this->wbbm_post_meta($id, 'wbtm_user_name')) . '</td><td>' . $this->wbbm_post_meta($id, 'wbtm_user_email') . '</td><td>' . $this->wbbm_post_meta($id, 'wbtm_user_phone') . '</td><td>' . ucfirst($this->wbbm_post_meta($id, 'wbtm_seat')) . '</td>';

                    $html .= '<td>' . wc_price($show_price) . '</td>';
                    // $html .= '<td>' . ($any_date_return['status'] ? ucfirst($any_date_return['status']) : 'Off') . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</td></tr>';
            }
            wp_reset_postdata();

            echo $html;
            exit;
        }

        protected function wbbm_post_meta($id, $key)
        {
            if ($id && $key) {
                return get_post_meta($id, $key, true);
            } else {
                return false;
            }
        }

        // Export CSV
        public function wbbm_export_csv_callback()
        {
            $filter_meta_query = $_SESSION['filter_where'];
            $filter_text = $_SESSION['filter_text'];
            $msg = false;

            // Bus Lists
            $bus_lists = $this->bus_lists();
            // Bus lists END

            // Main Query
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => 'IN',
                    'value' => array(1, 2)
                ),
                $filter_meta_query
            );

            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'meta_query' => $meta_query
            );

            $res = new WP_Query($args);
            $all_buses = array();
            while ($res->have_posts()) {
                $res->the_post();

                $order = wc_get_order($this->wbbm_post_meta(get_the_ID(), 'wbtm_order_id'));

                if(!$order) continue;

                // Remove trash order
                if (($order->get_status() != 'processing' && $order->get_status() != 'completed')) {
                    continue;
                }

                $all_buses[] = array(
                    'id' => get_post_meta(get_the_ID(), 'wbtm_bus_id', true),
                    'amount' => get_post_meta(get_the_ID(), 'wbtm_bus_fare', true),
                );
            }
            wp_reset_postdata();
            // Main Query END

            $data_rows = array();
            if ($all_buses) {
                $domain = $filter_text ? $filter_text : '';
                $filename = 'Report_' . $domain . '_' . time() . '.csv';

                $header_row = array(
                    'Coach no',
                    'Bus Name',
                    'Amount'
                );

                $g_total = 0;
                $final = array();
                if ($bus_lists) {
                    foreach ($bus_lists as $bus) {
                        $amount = 0;
                        foreach ($all_buses as $data) {
                            if ($bus == $data['id']) {
                                $amount += (float)$data['amount'];
                                $final[$bus] = $amount;

                                $g_total += (float)$data['amount'];
                            }
                        }
                    }
                }

                if ($final) {
                    foreach ($final as $id => $amount) {
                        $data_rows[] = array(
                            get_post_meta($id, 'wbtm_bus_no', true),
                            get_the_title($id),
                            $amount
                        );
                    }
                }

                if ($data_rows) {
                    array_push($data_rows, array('Total', '', $g_total));
                    $this->csv($header_row, $data_rows, $filename);
                }
                $msg = true;
            }
        }

        public function wbbm_order_wise_export_csv_callback()
        {
            $filter_where = $_SESSION['filter_where'];
            $filter_text = $_SESSION['filter_text'];
            $msg = false;

            // Main Query
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => 'IN',
                    'value' => array(1, 2)
                ),
                $filter_where
            );

            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'meta_query' => $meta_query
            );

            $res = new WP_Query($args);

            $header_row = array(
                'Order no',
                'Billing Name',
                'Booking Date',
                'Boarding',
                'Dropping',
                'Qty',
                'Seat',
                'Seat Price',
                'Extra Service Price',
                'Total Price',
                'Status',
            );

            $data_rows = array();
            if ($res) :
                $all_orders = [];
                while ($res->have_posts()) {
                    $res->the_post();
                    $all_orders[] = array(
                        'id' => get_post_meta(get_the_ID(), 'wbtm_order_id', true),
                        'amount' => get_post_meta(get_the_ID(), 'wbtm_bus_fare', true) ? get_post_meta(get_the_ID(), 'wbtm_bus_fare', true) : 0,
                        'booking_id' => get_the_ID()
                    );
                }
                wp_reset_postdata();

                $i = 0;
                while ($res->have_posts()) : $res->the_post();
                    $id = get_the_ID();
                    $order = wc_get_order($this->wbbm_post_meta($id, 'wbtm_order_id'));

                    if(!$order) continue;

                    $current_order = $this->wbbm_post_meta($id, 'wbtm_order_id');
                    $prev_order = (isset($prev_order) ? $prev_order : $current_order);

                    $amount = 0;
                    $count = 0;
                    $j = 0;
                    $seat = array();

                    if ($current_order != $prev_order || $i == 0) {
                        foreach ($all_orders as $o) {
                            if ($current_order == $o['id']) {
                                $amount += (float)$o['amount'];
                                $count += $j + 1;
                                $seat[] = $this->wbbm_post_meta($o['booking_id'], 'wbtm_seat');
                            }
                        }

                        // Extra Service Price
                        $get_es_prices = get_post_meta($current_order, '_extra_services', true);
                        $es_price = 0;
                        if ($get_es_prices) {
                            foreach ($get_es_prices as $price) {
                                $es_price += $price['qty'] * $price['price'];
                            }
                        }

                        $data_rows[] = array(
                            $current_order,
                            ($order ? $order->get_formatted_billing_full_name() : ""),
                            $this->wbbm_post_meta($id, 'wbtm_booking_date'),
                            $this->wbbm_post_meta($id, 'wbtm_boarding_point'),
                            $this->wbbm_post_meta($id, 'wbtm_droping_point'),
                            $count,
                            ($seat ? implode(', ', $seat) : ''),
                            $amount,
                            ($es_price ? $es_price : null),
                            ($amount + $es_price),
                            ($order ? $order->get_status() : "")
                        );
                    }

                    $j++;

                    $prev_order = $current_order;
                    $i++;
                endwhile; endif;
            wp_reset_postdata();
            // END


            if ($data_rows) {
                $domain = $filter_text ? $filter_text : '';
                $filename = 'Report_' . $domain . '_' . time() . '.csv';

                $this->csv($header_row, $data_rows, $filename);
                $msg = true;
            }
        }

        public function wbbm_detail_export_csv($bus_id)
        {
            $filter_where = $_SESSION['filter_where'];
            $filter_text = $_SESSION['filter_text'];
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => 'IN',
                    'value' => array(1, 2)
                ),
                array(
                    'key' => 'wbtm_bus_id',
                    'compare' => '=',
                    'value' => $bus_id
                ),
                $filter_where
            );

            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'meta_query' => $meta_query
            );

            $res = new WP_Query($args);
            // ***********

            if ($bus_id) {

                if ($res) {
                    $domain = $filter_text ? str_replace(' ', '_', $filter_text) : '';
                    $filename = 'Report_' . $domain . '_' . time() . '.csv';

                    $header_row = array(
                        'Order no',
                        'Booking Date',
                        'Name',
                        'Type',
                        'Boarding',
                        'Dropping',
                        'Price',
                        'Discounted Price',
                        'Status',
                    );

                    $g_total = 0;
                    $data_rows = array();
                    while ($res->have_posts()) {
                        $res->the_post();
                        $id = get_the_ID();
                        $order = wc_get_order($this->wbbm_post_meta($id, 'wbtm_order_id'));

                        // Remove trash order
                        if (($order->get_status() != 'processing' && $order->get_status() != 'completed')) {
                            continue;
                        }

                        $show_price = $original_price = get_post_meta($id, 'wbtm_bus_fare', true);
                        $discount_price = $this->wbbm_post_meta($id, '_wbtm_tp') ?? 0 ;
                        if($discount_price && $original_price > $discount_price) {
                            $show_price = $discount_price;
                        }
                        $data_rows[] = array(
                            $this->wbbm_post_meta($id, 'wbtm_order_id'),
                            $this->wbbm_post_meta($id, 'wbtm_booking_date'),
                            ucfirst($this->wbbm_post_meta($id, 'wbtm_user_name')),
                            ucfirst($this->wbbm_post_meta($id, 'wbtm_seat')),
                            $this->wbbm_post_meta($id, 'wbtm_boarding_point'),
                            $this->wbbm_post_meta($id, 'wbtm_droping_point'),
                            $this->wbbm_post_meta($id, 'wbtm_bus_fare'),
                            ($original_price > $show_price ? $show_price : ''),
                            ($order ? $order->get_status() : "")
                        );

                        $g_total += ($original_price > $show_price ? $show_price : $original_price);
                    }

                    if ($data_rows) {
                        array_push($data_rows, array('', '', '', '', '', 'Total', $g_total, ''));
                        $this->csv($header_row, $data_rows, $filename);
                    }
                }
            }
        }

        public function wbbm_order_wise_detail_export_csv($order_id)
        {
            $filter_where = $_SESSION['filter_where'];
            $filter_text = isset($_SESSION['filter_text']) ? $_SESSION['filter_text'] : '';

            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'wbtm_status',
                    'compare' => 'IN',
                    'value' => array(1, 2)
                ),
                array(
                    'key' => 'wbtm_order_id',
                    'compare' => '=',
                    'value' => $order_id
                ),
                $filter_where
            );

            $args = array(
                'post_type' => 'wbtm_bus_booking',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'meta_query' => $meta_query
            );

            $res = new WP_Query($args);

            // ***********

            if ($order_id) {
                if ($res) {
                    $domain = $filter_text ? str_replace(' ', '_', $filter_text) : '';
                    $filename = 'Report_' . $domain . '_' . time() . '.csv';

                    $header_row = array(
                        'Name',
                        'Email',
                        'Phone',
                        'Seat',
                        'Price'
                    );

                    $g_total = 0;
                    $data_rows = array();
                    while ($res->have_posts()) {
                        $res->the_post();
                        $id = get_the_ID();
                        $order = wc_get_order($this->wbbm_post_meta($id, 'wbtm_order_id'));
                        $data_rows[] = array(
                            ucfirst($this->wbbm_post_meta($id, 'wbtm_user_name')),
                            $this->wbbm_post_meta($id, 'wbtm_user_email'),
                            $this->wbbm_post_meta($id, 'wbtm_user_phone'),
                            $this->wbbm_post_meta($id, 'wbtm_seat'),
                            $this->wbbm_post_meta($id, 'wbtm_seat'),
                            $this->currency . (($this->wbbm_post_meta($id, 'wbtm_bus_fare') > 0) ? $this->wbbm_post_meta($id, 'wbtm_bus_fare') : 0)
                        );

//                        $g_total += $this->wbbm_post_meta($id, 'wbtm_bus_fare');
                    }

                    wp_reset_postdata();

                    if ($data_rows) {
//                        array_push($data_rows, array('', '', '', '', '', '', 'Total', $g_total));
                        $this->csv($header_row, $data_rows, $filename);
                    }
                }
            }
        }

        public function csv($header_row, $data_rows, $filename)
        {
            $fh = fopen('php://output', 'w');
            ob_clean(); // clean slate
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
            ob_flush(); // dump buffer
            fclose($fh);
            die();
        }

        public function bus_lists()
        {
            $bus_lists = array();
            $bus_args = array(
                'post_type' => 'wbtm_bus',
                'posts_per_page' => -1,
            );

            $bus_res = new WP_Query($bus_args);
            if ($bus_res) {
                while ($bus_res->have_posts()) {
                    $bus_res->the_post();
                    $bus_lists[] = get_the_ID();
                }
            }
            wp_reset_postdata();

            return $bus_lists;
        }

        public function __destruct()
        {
            if( session_id() )
                session_write_close();
        }

    }

    new WbbmReport('wbtm_bus', 'wbtm-reports', 'addon-bus--ticket-booking-with-seat-pro');
}