<?php
if (!defined('ABSPATH')) exit;


if (!function_exists('mep_license_error_code')) {
function mep_license_error_code($license_data,$item_name='this Plugin'){
    switch ($license_data->error) {
      case 'expired':
          $message = sprintf(
              __('Your license key expired on %s.'),
              date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp')))
          );
          break;
      case 'revoked':
          $message = __('Your license key has been disabled.');
          break;
      case 'missing':
          $message = __('Invalid license.');
          break;
      case 'invalid':
      case 'site_inactive':
          $message = __('Your license is not active for this URL.');
      break;
      case 'item_name_mismatch':
          $message = sprintf(__('This appears to be an invalid license key for %s.'), $item_name);
          break;
      case 'no_activations_left':
          $message = __('Your license key has reached its activation limit.');
          break;
      default:
          $message = __('An error occurred, please try again.');
          break;
  }
  return $message;
  }
}


add_action('wp_ajax_mep_wl_ajax_license_activate', 'mep_wl_ajax_license_activate');
add_action('wp_ajax_nopriv_mep_wl_ajax_license_activate', 'mep_wl_ajax_license_activate');
if (!function_exists('mep_wl_ajax_license_activate')) {
function mep_wl_ajax_license_activate(){

        $nonce                      = sanitize_text_field($_REQUEST['nonce']);
        $license                    = sanitize_text_field($_REQUEST['key']);
        $key_option_name            = sanitize_text_field($_REQUEST['key_option_name']);
        $status_option_name         = sanitize_text_field($_REQUEST['status_option_name']);
        $expire_option_name         = sanitize_text_field($_REQUEST['expire_option_name']);
        $order_id_option_name       = sanitize_text_field($_REQUEST['order_id_option_name']);
        $item_name                  = sanitize_text_field($_REQUEST['item_name']);
        $item_id                    = sanitize_text_field($_REQUEST['item_id']);
        $user_type                  = isset($_REQUEST['user_type']) ? sanitize_text_field($_REQUEST['user_type']) : 'new';


        $plugin_user_status_type    = $key_option_name.'_type';

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_id'    => $item_id,
            'url'        => home_url()
        );

        // Call the custom API.
        $response     = wp_remote_post(WBTM_STORE_URL, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));
        $license_data = json_decode(wp_remote_retrieve_body($response));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                $message = (is_wp_error($response) && !empty($response->get_error_message())) ? $response->get_error_message() : __('An error occurred, please try again.');
        }else{
        
            if (false === $license_data->success) {
                $message    = mep_license_error_code($license_data, $item_name);
            }else{
                $payment_id = $license_data->payment_id;
                $expire = $license_data->expires;
                $message = __("Success, License Key is valid for the plugin $item_name. Your Order id is $payment_id. Validity of this licenses is $expire.","mage-eventpress");
            }
        }
        if($license_data->success){
            echo $message;
            update_option($key_option_name, $license);
            update_option($expire_option_name, $license_data->expires);
            update_option($order_id_option_name, $license_data->payment_id);            
            update_option($plugin_user_status_type, $user_type);            
        }else{
            update_option($key_option_name, '');
            update_option($expire_option_name, '');
            update_option($order_id_option_name, '');  
        }
        update_option($status_option_name, $license_data->license);
die();
}
}

add_action('wp_ajax_mep_wl_ajax_license_deactivate', 'mep_wl_ajax_license_deactivate');
add_action('wp_ajax_nopriv_mep_wl_ajax_license_deactivate', 'mep_wl_ajax_license_deactivate');
if (!function_exists('mep_wl_ajax_license_deactivate')) {
function mep_wl_ajax_license_deactivate(){
        $key_option_name            = sanitize_text_field($_REQUEST['key_option_name']);
        $status_option_name         = sanitize_text_field($_REQUEST['status_option_name']);
        $expire_option_name         = sanitize_text_field($_REQUEST['expire_option_name']);
        $order_id_option_name       = sanitize_text_field($_REQUEST['order_id_option_name']);
        $item_name                  = sanitize_text_field($_REQUEST['item_name']);
        $item_id                    = sanitize_text_field($_REQUEST['item_id']);

        update_option($key_option_name, '');
        update_option($expire_option_name, '');
        update_option($order_id_option_name, ''); 
        update_option($status_option_name, 'invalid');
die();
}
}


add_action('wbtm_license_page_addon_list', 'wbtm_pro_licensing');
function wbtm_pro_licensing()
{
    $key_option_name        = 'wbtm_pro_license_key';
    $status_option_name     = 'wbtm_pro_license_status';
    $expire_option_name     = 'wbtm_pro_license_expire';
    $order_id_option_name   = 'wbtm_pro_license_order_id';
    $active_btn_id          = 'wbtm_pro_active';
    $deactive_btn_id        = 'wbtm_pro_deactive';
    $license                = get_option($key_option_name);
    $status                 = get_option($status_option_name);
?>
    <tr>
        <td><?php _e(WBTM_PRO_NAME, 'mage-eventpress-waitlist'); ?></td>
        <td><?php echo get_option($order_id_option_name); ?></td>
        <td style="text-transform:capitalize"><?php echo mep_license_expire_date(get_option($expire_option_name)); ?></td>
        <td> <input id="<?php echo $key_option_name; ?>" name="<?php echo $key_option_name; ?>" type="text" style="width:260px" value="<?php esc_attr_e($license); ?>" /></td>
        <td>
            <?php if ($status !== false && $status == 'valid') { ?>
                <span style="color:green;text-transform:capitalize"><?php _e('Active', 'mage-eventpress-wl'); ?></span>
            <?php } else { ?>
                <span style="color:red;text-transform:capitalize"><?php _e('Deactive', 'mage-eventpress-wl'); ?></span>
            <?php } ?>
        </td>
        <td>
            <?php if ($status !== false && $status == 'valid') { ?>
                <button id='<?php echo $deactive_btn_id; ?>' class='button btn btn-license'><?php _e('Deactive', 'mage-eventpress-wl'); ?></button>
            <?php } else {
                wp_nonce_field('edd_sample_nonce', 'edd_sample_nonce'); ?>
                <button id='<?php echo $active_btn_id; ?>' class='button btn btn-license'><?php _e('Active', 'mage-eventpress-wl'); ?></button>
            <?php } ?>

            <script>
                (function($) {
                    'use strict';
                    jQuery('#<?php echo $active_btn_id; ?>').on('click', function() {
                        var key = jQuery('#<?php echo $key_option_name; ?>').val();
                        var nonce = jQuery('#_wpnonce').val();
                        if (key) {
                            jQuery.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    "action"                : "mep_wl_ajax_license_activate",
                                    "nonce"                 : nonce,
                                    "key_option_name"       : '<?php echo $key_option_name; ?>',
                                    "status_option_name"    : '<?php echo $status_option_name; ?>',
                                    "expire_option_name"    : '<?php echo $expire_option_name; ?>',
                                    "order_id_option_name"  : '<?php echo $order_id_option_name; ?>',
                                    "item_name"             : '<?php echo WBTM_PRO_NAME; ?>',
                                    "item_id"               : '<?php echo WBTM_PRO_ID; ?>',
                                    "key"                   : key
                                },
                                beforeSend: function() {
                                    jQuery('.mep_licensae_info').html('<h5 class=""><?php echo 'Please wait.. License Key is checking'; ?></h5>');
                                },
                                success: function(data) {
                                    jQuery('.mep_licensae_info').html(data);
                                    window.location.reload();
                                }
                            });
                        } else {
                            alert('<?php _e('Please Enter the License Key', 'mage-eventpress-waitlist'); ?>');
                        }
                        return false;
                    });

                    jQuery('#<?php echo $deactive_btn_id; ?>').on('click', function() {
                        if (confirm('Are You Sure to Deactivate this license ? \n\n 1. Ok : To Deactive . \n 2. Cancel : To Cancel .')) {
                            jQuery.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    "action"                : "mep_wl_ajax_license_deactivate",
                                    "key_option_name"       : '<?php echo $key_option_name; ?>',
                                    "status_option_name"    : '<?php echo $status_option_name; ?>',
                                    "expire_option_name"    : '<?php echo $expire_option_name; ?>',
                                    "order_id_option_name"  : '<?php echo $order_id_option_name; ?>',
                                    "item_name"             : '<?php echo WBTM_PRO_NAME; ?>',
                                    "item_id"               : '<?php echo WBTM_PRO_ID; ?>',
                                    // "key"                : key
                                },
                                beforeSend: function() {
                                    jQuery('.mep_licensae_info').html('<h5 class=""><?php echo 'Please wait.. License Key is deactivating'; ?></h5>');
                                },
                                success: function(data) {
                                    jQuery('.mep_licensae_info').html(data);
                                    window.location.reload();
                                }
                            });

                        } else {
                            return false;
                        }
                        return false;
                    });
                })(jQuery);
            </script>
        </td>
    </tr>
<?php
}



add_action('admin_footer','mep_admin_menu_license_display_style',90);
if(!function_exists('mep_admin_menu_license_display_style')){
    function mep_admin_menu_license_display_style(){
        ?>
        <style>
        a#mep_settings_licensing-tab {
            display: block!important;
        }
        </style>
        <?php
    }
}