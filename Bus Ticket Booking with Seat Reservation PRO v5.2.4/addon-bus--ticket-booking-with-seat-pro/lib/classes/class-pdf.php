<?php
/*
 * @Author         MagePeople
 * Copyright:     MagePeople
 */

if (!defined('ABSPATH')) {
    exit;
}
// if direct access
if (!class_exists('MagePDF')) {
    class MagePDF
    {

        public $settings = array();

        public function __construct()
        {

            $this->set_shortcodes();
            $this->add_shortcodes();

        }

        public function get_templates()
        {

            $templates = array();
            $arr_templates = apply_filters('mage_pdf_templates_internal', array(
                'flat' => __('Flat'),
            ));

            foreach ($arr_templates as $template_name => $label) {

                $templates[$template_name] = array(
                    'label' => $label,
                    'thumb' => sprintf('%1$spublic/pdf/templates/pdf-templates/%2$s/%2$s.png', WBTMPRO_PLUGIN_DIR, $template_name),
                    'template' => sprintf('%1$spublic/pdf/templates/pdf-templates/%2$s/template.php', WBTMPRO_PLUGIN_DIR, $template_name),
                    'stylesheet' => sprintf('%1$spublic/pdf/templates/pdf-templates/%2$s/style.css', WBTMPRO_PLUGIN_DIR, $template_name),
                );
            }

            return apply_filters('mage_pdf_templates', $templates);
        }

        public function generate_pdf($order_id = 0, $html = "", $download_pdf = false, $save_pdf = false)
        {
            $upload_dir = wp_upload_dir();
            $get_settings = get_option('wbtm_bus_settings');
            $pdf_filename = isset($get_settings['pdf_file_name']) && $get_settings['pdf_file_name'] ? $get_settings['pdf_file_name'] : $get_settings['bus_menu_label'];

            if ($order_id == 0) {
                return new WP_Error('invalid_data', __('Invalid order id provided'));
            }

            $pdf_filename = str_replace(array('\'', '"'), '', $pdf_filename) . '-' . $order_id . '.pdf';

            $ticket_name = $upload_dir['basedir'] . '/' . __('Bus_ticket_', 'addon-bus--ticket-booking-with-seat-pro') . $order_id . '.pdf';
            // $file_name = __('Bus_ticket_', 'addon-bus--ticket-booking-with-seat-pro') . $order_id . '.pdf';
            $html = empty($html) ? $this->get_order_pdf_html($order_id, 'pdf') : $html;
            //echo $html;die;

            $mpdfVersion = mep_get_mpdf_support_version();
            $mpdfVersion = !$mpdfVersion ? 2.0 : $mpdfVersion;
            if (mep_get_mpdf_support_version() > 1.0) {
                $mpdf = new \Mpdf\Mpdf();
            } else {
                $mpdf = new mPDF();
            }
            $mpdf->allow_charset_conversion = true; // Set by default to TRUE
            $mpdf->autoScriptToLang = true;
            $mpdf->baseScript = 1;
            $mpdf->autoVietnamese = true;
            $mpdf->autoArabic = true;
            $mpdf->autoLangToFont = true;

            header('Content-Type: application/pdf');

            echo $mpdf->WriteHTML($html);
            $download_pdf ? $mpdf->Output($pdf_filename, 'D') : $mpdf->Output($pdf_filename, 'I');
            exit;
        }

        public function get_pdf_ticket_attachment_file($order_id = 0, $html = "", $download_pdf = true, $save_pdf = false)
        {

            if ($order_id == 0) {
                return new WP_Error('invalid_data', __('Invalid order id provided'));
            }

            $upload_dir = wp_upload_dir();
            $html = empty($html) ? $this->get_order_pdf_html($order_id, 'pdf') : $html;
            $get_settings = get_option('wbtm_bus_settings');
            $pdf_filename = isset($get_settings['pdf_file_name']) && $get_settings['pdf_file_name'] ? $get_settings['pdf_file_name'] : $get_settings['bus_menu_label'];
            $ticket_name = $upload_dir['basedir'] . '/' . $pdf_filename .'_' . $order_id . '.pdf';
            if (mep_get_mpdf_support_version() > 1.0) {
                $mpdf = new \Mpdf\Mpdf();
            } else {
                $mpdf = new mPDF();
            }
            $mpdf->allow_charset_conversion = true; // Set by default to TRUE
            $mpdf->autoScriptToLang = true;
            $mpdf->baseScript = 1;
            $mpdf->autoVietnamese = true;
            $mpdf->autoArabic = true;
            $mpdf->autoLangToFont = true;

            $mpdf->WriteHTML($html);

            $mpdf->Output($ticket_name, 'F');
            return $ticket_name;

        }

        public function get_order_pdf_html($order_id = 0, $type = "", $email_type = null, $oid = null)
        {

            if ($order_id == 0) {
                return new WP_Error('invalid_data', __('Invalid order id provided'));
            }

            $shortcode = sprintf('[order-pdf order_id="%s" type="%s" template="flat" email_type="%s" oid="%s"]',
                $order_id, $type, $email_type, $oid
            );

            ob_start();
            echo do_shortcode($shortcode);
            return ob_get_clean();
        }

        public function get_invoice_ajax_url($args = array())
        {

            $default_args = array(
                'action' => 'generate_pdf',
                // 'doccument_type'    => 'pdf',
                // 'order_id'          => '',
            );

            $args = wp_parse_args($args, $default_args);
            $build_url = http_build_query($args);
            $nonce_url = wp_nonce_url(admin_url("admin-ajax.php?" . $build_url), $args['action']);

            return apply_filters('mage_filters_invoice_ajax_url', $nonce_url);
        }

        public function get_order_data($order_id = 0, $return_as = false)
        {

            if ($order_id == 0) {
                return new WP_Error('invalid_data', __('Invalid order id provided'));
            }

            $data = array();
            $order = wc_get_order($order_id);
            $data = $order->get_data();

            $item_total = 0;
            foreach ($order->get_items() as $item_id => $item) {

                $total = isset($item['total']) ? (float) $item['total'] : 0;
                $subtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : 0;

                $data['items'][] = array_merge($item->get_data(), array(
                    'thumbnail_url' => get_the_post_thumbnail_url($item->get_product_id(), array(50, 50)),
                    'permalink' => get_the_permalink($item->get_product_id()),
                    'discount' => $subtotal > $total ? $subtotal - $total : 0,
                ));

                $item_total += $subtotal;
            }

            $data['order_date'] = $order->get_date_created()->date('M j, Y');
            $data['item_total'] = $item_total;
            $data['order'] = $order;

            if ($return_as && $return_as == 'object') {

                $data['billing'] = isset($data['billing']) ? (object) $data['billing'] : (object) array();
                $data['shipping'] = isset($data['shipping']) ? (object) $data['shipping'] : (object) array();

                return (object) $data;
            }
            if ($return_as && $return_as == 'json') {
                return json_encode($data);
            }

            return $data;
        }

        public function print_error($wp_error)
        {

            $classes = array($wp_error->get_error_code());

            if (is_admin()) {
                $classes[] = 'is-dismissible';
            }

            printf("<div class='notice notice-error error wooin-notice %s'><p>%s</p></div>",
                implode(' ', $classes), $wp_error->get_error_message()
            );
        }

        public function get_option($option_name = '', $default = '')
        {

            $option_value = get_option($option_name, $default);
            $option_value = empty($option_value) ? $default : $option_value;

            return $option_value;
        }

        private function set_shortcodes()
        {

            $this->shortcodes = apply_filters('mage_filter_shortcodes', array(
                'order-pdf' => array(
                    'file-slug' => 'order-pdf',
                ),
            ));
        }

        private function add_shortcodes()
        {

            foreach ($this->shortcodes as $shortcode => $args):
                add_shortcode($shortcode, array($this, 'shortcode_content_display'), $shortcode);
            endforeach;
        }

        public function shortcode_content_display($atts, $content = null, $shortcode = null)
        {

            ob_start();

            if (is_wp_error($shortcode_html = $this->get_shortcode_html($shortcode, $atts))) {

                return ob_get_clean();
            }

            echo $shortcode_html;
            return ob_get_clean();
        }

        public function get_shortcode_html($shortcode, $atts)
        {

            $file_slug = isset($this->shortcodes[$shortcode]['file-slug']) ? $this->shortcodes[$shortcode]['file-slug'] : '';

            $template_dir = sprintf("%spublic/pdf/templates/%s.php", WBTMPRO_PLUGIN_DIR, $file_slug);
            $template_dir = apply_filters('mage_filter_shortcode_template_dir', $template_dir, $shortcode);
            $template_dir = file_exists($template_dir) ? $template_dir : '';

            if (empty($template_dir)) {
                return new WP_Error('empty_data', sprintf(__('Template file not found for shortcode : [%s]', 'woo-invoice'), $shortcode));
            }

            ob_start();
            include $template_dir;
            return ob_get_clean();
        }

        public function send_email($order_id = '', $order = false, $email_type = null, $oid = null)
        {
            global $wbtmmain;
            if (empty($order_id) || !$order) {
                return false;
            }

            $subject = $wbtmmain->bus_get_option('pdf_email_subject', 'ticket_manager_settings', 'PDF Ticket Confirmation');
            $content = $wbtmmain->bus_get_option('pdf_email_text', 'ticket_manager_settings', 'Here is PDF Ticket Confirmation Attachment');
            $form_name = $wbtmmain->bus_get_option('pdf_email_form_name', 'ticket_manager_settings', get_bloginfo('name'));
            $form_email = $wbtmmain->bus_get_option('pdf_email_form_email', 'ticket_manager_settings', get_bloginfo('admin_email'));
            $admin_notify_email = $wbtmmain->bus_get_option('pdf_email_admin_notification_email', 'ticket_manager_settings', get_bloginfo('admin_email'));
            $email_sent_status = $wbtmmain->bus_get_option('pdf_email_send_on', 'ticket_manager_settings', array());
            $email_status = $wbtmmain->bus_get_option('email_send_pdf', 'ticket_manager_settings', 'yes');

            // if( ! empty( $subject ) && ! empty( $content ) ) {

            $attachments = array();
            $headers = array(
                sprintf("From: %s <%s>", $form_name, $form_email),
                'Content-Type: text/html; charset=UTF-8'
            );
            if ($email_status == 'yes') {

                $attathment_file_url = $this->get_pdf_ticket_attachment_file($order_id, "", false, true, $email_type, $oid);

                // $attachments[] = $attathment_file_url;
                $attachments[] = $attathment_file_url;

                // }

                $email_address_arr = array(
                    $order->get_billing_email(), $admin_notify_email,
                    // $this->settings->mage_email_to
                );

                // Ticket Download link
                $ticket_download_link = do_shortcode('[wbtm-ticket-link order-id="'.$order_id.'"]');
                $content .= '<br>'. $ticket_download_link;

                $email_address = implode(",", $email_address_arr);

                wp_mail($email_address, $subject, $content, $headers, $attachments);
            }
        }
    }
    global $magepdf;
    $magepdf = new MagePDF();
}