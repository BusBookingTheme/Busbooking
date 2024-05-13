<?php
class WbtmProFunction
{

    public function __construct()
    {
        $this->add_hooks();
    }

    public function add_hooks()
    {
        add_action('mage_bus_hidden_customer_info_form', array($this, 'bus_hidden_customer_info_form'), 99);
        add_action('wbtm_reg_fields', array($this, 'display_reg_form'), 99);

        add_action('wbtm_reg_fields_checkout', array($this, 'display_reg_form_checkout'), 99, 10);

        add_action('wbtm_form_builder_data', array($this, 'show_form_builder_data'), 99);

        add_action('woocommerce_thankyou', array($this, 'show_ticket_info_in_thankyou_page'));

        add_action('wp_ajax_generate_pdf', array($this, 'mep_events_generate_pdf'));

        add_action('wp_ajax_nopriv_generate_pdf', array($this, 'mep_events_generate_pdf'));

        add_action('woocommerce_order_status_changed', array($this, 'bus_ticket_pdf_send'), 99, 4);
    }

    //customer info form builder
    public static function bus_hidden_customer_info_form($bus_id = '', $seatType = '', $passengerType = '', $dd = 'no')
    {
        $id = $bus_id ? $bus_id : get_the_id();
        $mage_meta = get_post_custom($id);
        if ($mage_meta) {
            $full_name = array_key_exists('wbtm_full_name', $mage_meta) ? strip_tags($mage_meta['wbtm_full_name'][0]) : false;
            $email = array_key_exists('wbtm_reg_email', $mage_meta) ? strip_tags($mage_meta['wbtm_reg_email'][0]) : false;
            $phone = array_key_exists('wbtm_reg_phone', $mage_meta) ? strip_tags($mage_meta['wbtm_reg_phone'][0]) : false;
            $address = array_key_exists('wbtm_reg_address', $mage_meta) ? strip_tags($mage_meta['wbtm_reg_address'][0]) : false;
            $gender = array_key_exists('wbtm_user_gender', $mage_meta) ? strip_tags($mage_meta['wbtm_user_gender'][0]) : false;

            $custom_field = unserialize(get_post_meta($id, 'attendee_reg_form', true));
            if (array_key_exists('wbtm_user_extra_bag', $mage_meta)) {
                $extra_bag = strip_tags($mage_meta['wbtm_user_extra_bag'][0]);
                $extra_bag_qty = strip_tags($mage_meta['wbtm_extra_max_qty'][0]);
                $extra_bag_price = strip_tags($mage_meta['wbtm_extra_bag_price'][0]);

            } else {
                $extra_bag = false;
                $extra_bag_price = 0;
                $extra_bag_qty = 0;
            }

            if ($full_name || $email || $phone || $address || $gender || $custom_field || $extra_bag) {
                $seatType_name = ($seatType == 'ES' || $seatType == 'es' ? __('Extra service details information', 'addon-bus--ticket-booking-with-seat-pro') : __('Passenger information details', 'addon-bus--ticket-booking-with-seat-pro') . ': ' . $seatType);
                ?>
                <div class="mage_hidden_customer_info_form <?php echo 'seat_name_' . $seatType; ?>">
                    <div class="mage_form_list radius">
                        <div class="mage_title"><h5><?php echo $seatType_name; ?></h5></div>
                        <div class="mage_form_list_body justifyBetween" style="align-items: start;">
                            <input type='hidden' name='seat_name[]' value='<?php echo $seatType ?>'/>
                            <input type='hidden' name='passenger_type[]' value='<?php echo $passengerType ?>'/>
                            <input type='hidden' name='bus_dd[]' value='<?php echo $dd; ?>'/>
                            <label class="<?php echo $full_name ? '' : 'mage_hidden' ?>">
                                <span><?php _e('Enter Your Name', 'addon-bus--ticket-booking-with-seat-pro');?></span>
                                <input class="mage_form_control" type="<?php echo $full_name ? 'text' : 'hidden' ?>" name="wbtm_user_name[]" placeholder="<?php _e('Enter Your Name', 'addon-bus--ticket-booking-with-seat-pro');?>" <?php echo $full_name ? 'required' : '' ?> />
                            </label>
                            <label class="<?php echo $email ? '' : 'mage_hidden' ?>">
                            <span><?php _e('Enter Your Email', 'addon-bus--ticket-booking-with-seat-pro');?></span>
                                <input class="mage_form_control" type="<?php echo $email ? 'email' : 'hidden' ?>" name="wbtm_user_email[]" placeholder="<?php _e('Enter Your Email', 'addon-bus--ticket-booking-with-seat-pro');?>" <?php echo $email ? 'required' : '' ?> />
                            </label>
                            <label class="<?php echo $phone ? '' : 'mage_hidden' ?>">
                            <span><?php _e('Enter Your Phone', 'addon-bus--ticket-booking-with-seat-pro');?></span>
                                <input class="mage_form_control" type="<?php echo $phone ? 'text' : 'hidden' ?>" name="wbtm_user_phone[]" placeholder="<?php _e('Enter Your Phone', 'addon-bus--ticket-booking-with-seat-pro');?>" <?php echo $phone ? 'required' : '' ?> />
                            </label>
                            <label class="<?php echo $address ? '' : 'mage_hidden' ?>">
                            <span><?php _e('Enter Your Address', 'addon-bus--ticket-booking-with-seat-pro');?></span>
                                <textarea class="mage_form_control <?php echo $address ? '' : 'mage_hidden' ?>" type="<?php echo $address ? 'text' : 'hidden' ?>" name="wbtm_user_address[]" placeholder="<?php _e('Enter you address', 'addon-bus--ticket-booking-with-seat-pro');?>" <?php echo $address ? 'required' : '' ?> rows="3"></textarea>
                            </label>
                            <?php

                            if ($custom_field) {
                    foreach ($custom_field as $field) {
                        $input_type = $field['filed_type'];
                        $name = $field['field_id'];
                        $placeholder = $field['field_label'];
                        $required = array_key_exists('checkbox_field', $field) ? 'required' : '';
                        $type_option_value = explode(',', ($field['field_values'] ? $field['field_values'] : false));

                        if ($input_type == 'date' || $input_type == 'text' || $input_type == 'email' || $input_type == 'number') {
                            echo '<label><span>' . $placeholder . '</span>';
                            echo '<input class="mage_form_control" type="' . $input_type . '" name="' . $name . '[]" placeholder="' . $placeholder . '" ' . $required . ' />';
                            echo '</label>';
                        }
                        if ($input_type == 'number') {
                            echo '<label class="justifyBetween"><span>' . $placeholder . '</span>';
                            echo '<div class="mage_form_qty"><div class="mage_flex mage_qty_dec"><span class="fa fa-minus"></span></div><input class="mage_form_control" type="number" value="0" name="' . $name . '[]" placeholder="0" ' . $required . ' /><div class="mage_flex mage_qty_inc"><span class="fa fa-plus"></span></div></div>';
                            echo '</label>';
                        }
                        if ($input_type == 'textarea') {
                            echo '<label><span style="display:block">' . $placeholder . '</span>';
                            echo '<textarea style="width:100%" class="mage_form_control" type="text" name="' . $name . '[]" placeholder="' . $placeholder . '" ' . $required . '" rows="3" ></textarea>';
                            echo '</label>';
                        }
                        if ($input_type == 'checkbox' || $input_type == 'radio') {
                            echo '<div class="mage_group_input">';
                            foreach ($type_option_value as $value) {
                                echo '<label><input type="' . $input_type . '" value="' . $value . '" name="' . $name . '[]" />' . $value . '</label>';
                            }
                            echo '</div>';
                        }
                        if ($input_type == 'select') {
                            echo '<label><span>' . $placeholder . '</span>';
                            echo '<select class="mage_form_control" name="' . $name . '[]" ' . $required . '>';
                            echo '<option value=" " disabled selected>';
                            _e('Please Select', 'addon-bus--ticket-booking-with-seat-pro');
                            echo '</option>';
                            foreach ($type_option_value as $value) {
                                echo '<option value="' . $value . '">' . $value . '</option>';
                            }
                            echo '</select>';
                            echo '</label>';
                        }
                    }
                }
                ?>
                            <label class="<?php echo $gender ? '' : 'mage_hidden' ?>">
                                <span style="display:block"><?php _e('Gender', 'addon-bus--ticket-booking-with-seat-pro');?></span>
                                <select style="width:100%;max-width:initial" class="mage_form_control" name="wbtm_user_gender[]">
                                    <option value=" " disabled selected><?php _e('Please Select', 'addon-bus--ticket-booking-with-seat-pro');?></option>
                                    <option value="Male"><?php _e('Male', 'addon-bus--ticket-booking-with-seat-pro');?></option>
                                    <option value="Female"><?php _e('Female', 'addon-bus--ticket-booking-with-seat-pro');?></option>
                                </select>
                            </label>
                            <?php if ($extra_bag_price >= 0 && $extra_bag_price != ''): ?>
                            <label class="justifyBetween <?php echo $extra_bag ? '' : 'mage_hidden' ?>" style="margin-top:20px;background: #fff;padding-left: 10px;">
                                <span><?php _e('Extra Bag :', 'addon-bus--ticket-booking-with-seat-pro');?> <?php echo strip_tags(wc_price($extra_bag_price)); ?> <small>/ <?php _e('Per bag', 'addon-bus--ticket-booking-with-seat-pro');?></small></span>
                                <div class="mage_form_qty">
                                    <div class="mage_flex mage_qty_dec"><span class="fa fa-minus"></span></div>
                                    <input type="<?php echo $extra_bag ? 'text' : 'hidden' ?>" class="mage_form_control extra_bag_qty" data-price="<?php echo $extra_bag_price; ?>" name="extra_bag_quantity[]" value="0" min="0" max="<?php echo $extra_bag_qty; ?>" required/>
                                    <div class="mage_flex mage_qty_inc"><span class="fa fa-plus"></span></div>
                                </div>
                            </label>
                            <?php endif;?>
                        </div>
                    </div>
                    <input type="hidden" name="custom_reg_user" value="yes"/>
                </div>
                <?php
} else {
                // echo '<input type="hidden" name="custom_reg_user" value="no" />';
            }
        }
    }

    public function create_reg_form($label, $id, $type, $required, $extra_info)
    {
        // print_r($required);
        ob_start();
        if ($type == 'text' || $type == 'number' || $type == 'email') {
            ?>
         <input type='<?php echo $type; ?>' name='<?php echo $id; ?>[]' class='form-input mage-reg-form-input' placeholder='<?php echo $label; ?>' <?php echo $required[0]; ?> /><?php
}
        if ($type == 'textarea') {
            ?><textarea name='<?php echo $id; ?>[]' class='form-input mage-reg-form-input' placeholder='<?php echo $label; ?>' <?php echo $required[0]; ?>></textarea><?php
}
        if ($type == 'select') {
            $values = explode(',', $extra_info);
            echo '<p>' . $label . '</p>';
            ?><select name='<?php echo $id; ?>[]' class='form-input mage-reg-form-select' <?php echo $required[0]; ?>><?php foreach ($values as $_values) {?><option value='<?php echo $_values; ?>'><?php echo $_values; ?></option><?php }?></select><?php
}
        if ($type == 'checkbox' || $type == 'radio') {
            $values = explode(',', $extra_info);
            $count = 1;
            echo '<p class=mage-reg-form-title>' . $label . '</p>';
            foreach ($values as $_values) {
                ?><label for='<?php echo $id . '-' . $count; ?>'><input type='<?php echo $type; ?>' name='<?php echo $id; ?>[]' id='<?php echo $id . '-' . $count; ?>' value='<?php echo $_values; ?>'/> <?php echo $_values; ?></label><?php
$count++;
            }
        }
        return ob_get_clean();
    }

    public function show_form_builder_data($bus)
    {
        $reg_form_arr = unserialize(get_post_meta($bus, 'attendee_reg_form', true));

        if (is_array($reg_form_arr) && sizeof($reg_form_arr) > 0) {
            foreach ($reg_form_arr as $reg_form) {
                echo '<div class=reg-form-fields-row>' . $this->create_reg_form($reg_form['field_label'], $reg_form['field_id'], $reg_form['filed_type'], $reg_form['checkbox_field'], $reg_form['field_values']) . '';

            }
        }

    }

    public function display_reg_form($seat_name)
    {
        $bus_meta = get_post_custom(get_the_id());
        if ($bus_meta) {
            if (array_key_exists('wbtm_full_name', $bus_meta)) {
                $mep_full_name = strip_tags($bus_meta['wbtm_full_name'][0]);
            } else { $mep_full_name = '';}

            if (array_key_exists('wbtm_reg_email', $bus_meta)) {
                $mep_reg_email = strip_tags($bus_meta['wbtm_reg_email'][0]);
            } else { $mep_reg_email = '';}

            if (array_key_exists('wbtm_reg_phone', $bus_meta)) {
                $mep_reg_phone = strip_tags($bus_meta['wbtm_reg_phone'][0]);
            } else { $mep_reg_phone = '';}
            if (array_key_exists('wbtm_reg_address', $bus_meta)) {
                $mep_reg_address = strip_tags($bus_meta['wbtm_reg_address'][0]);
            } else { $mep_reg_address = '';}
            if (array_key_exists('wbtm_user_gender', $bus_meta)) {
                $wbtm_user_gender = strip_tags($bus_meta['wbtm_user_gender'][0]);
            } else { $wbtm_user_gender = '';}
            if (array_key_exists('wbtm_user_extra_bag', $bus_meta)) {
                $wbtm_user_extra_bag = strip_tags($bus_meta['wbtm_user_extra_bag'][0]);
                $wbtm_user_extra_bag_qty = isset($bus_meta['wbtm_extra_max_qty']) ? strip_tags($bus_meta['wbtm_extra_max_qty'][0]) : 0;
                $wbtm_user_extra_bag_price = isset($bus_meta['wbtm_extra_bag_price']) ? strip_tags($bus_meta['wbtm_extra_bag_price'][0]) : 0;

            } else {
                $wbtm_user_extra_bag = '';
                $wbtm_user_extra_bag_qty = 0;
                $wbtm_user_extra_bag_price = 0;

            }

            if ($mep_full_name || $mep_reg_email || $mep_reg_phone || $mep_reg_address || $wbtm_user_gender) {
                ?><div class='mep-user-info-sec'><h5><?php echo __("Passenger info", "addon-bus--ticket-booking-with-seat-pro") . "(Seat: " . $seat_name . ")"; ?></h5><input type='<?php if ($mep_full_name) {echo 'text';} else {echo 'hidden';}?>' <?php if ($mep_full_name) {?> required='required' <?php }?> name='wbtm_user_name[]' class='mep_input' placeholder='<?php _e("Enter Your Name", "addon-bus--ticket-booking-with-seat-pro");?>'/><input type='<?php if ($mep_reg_email) {echo 'email';} else {echo 'hidden';}?>' <?php if ($mep_reg_email) {?> required='required' <?php }?> name='wbtm_user_email[]' class='mep_input' placeholder='<?php _e("Enter Your Email", "addon-bus--ticket-booking-with-seat-pro");?>'/><input type='<?php if ($mep_reg_phone) {echo 'text';} else {echo 'hidden';}?>' <?php if ($mep_reg_phone) {?> required='required' <?php }?> name='wbtm_user_phone[]' class='mep_input' placeholder='<?php _e("Enter Your Phone", "addon-bus--ticket-booking-with-seat-pro");?>'/><textarea name='wbtm_user_address[]' class='mep_input <?php if ($mep_reg_address) {echo 'mep-show';} else {echo 'mep-hidden';}?>' rows='3' <?php if ($mep_reg_address) {?> required='required' <?php }?> placeholder='<?php _e("Enter you address", "addon-bus--ticket-booking-with-seat-pro");?>'></textarea><label class='<?php if ($wbtm_user_gender) {echo 'mep-show';} else {echo 'mep-hidden';}?>' for='gen' style='text-align: left;'><?php _e("Gender", "addon-bus--ticket-booking-with-seat-pro");?><select name='wbtm_user_gender[]' id='gen'><option value=''><?php _e("Please Select", "addon-bus--ticket-booking-with-seat-pro");?></option><option value='Male'><?php _e("Male", "addon-bus--ticket-booking-with-seat-pro");?></option><option value='Female'><?php _e("Female", "addon-bus--ticket-booking-with-seat-pro");?></option></select></label><?php do_action('wbtm_form_builder_data', get_the_id());?><?php if ($wbtm_user_extra_bag) {?><?php _e('Extra Bag ( Per bag', 'addon-bus--ticket-booking-with-seat-pro');
                    echo strip_tags(wc_price($wbtm_user_extra_bag_price));?> )<?php }?> <input id='' class='input-text qty text extra-qty-box' step='1' min='0' max='<?php echo $wbtm_user_extra_bag_qty; ?>' name='extra_bag_quantity[]' value='0' title='Qty' size='4' pattern='[0-9]*' inputmode='numeric' type='<?php if ($wbtm_user_extra_bag) {echo 'number';} else {echo 'hidden';}?>'/><input type='hidden' name='custom_reg_user' value='yes'/></div><?php } else {echo '<input type=hidden name=custom_reg_user value=no/>';}
        }
    }

    public function display_reg_form_checkout($bus_id, $j_date, $selected_seats, $fare, $start, $end, $time, $type, $count, $show_return)
    {
        $bus_meta = get_post_custom($bus_id);

        if ($show_return) {
            $type = 'one-way';
        } else {
            $type = $type;
        }

// print_r($bus_id);
        if ($bus_meta) {
            if (array_key_exists('wbtm_full_name', $bus_meta)) {
                $mep_full_name = strip_tags($bus_meta['wbtm_full_name'][0]);
            } else { $mep_full_name = '';}

            if (array_key_exists('wbtm_reg_email', $bus_meta)) {
                $mep_reg_email = strip_tags($bus_meta['wbtm_reg_email'][0]);
            } else { $mep_reg_email = '';}

            if (array_key_exists('wbtm_reg_phone', $bus_meta)) {
                $mep_reg_phone = strip_tags($bus_meta['wbtm_reg_phone'][0]);
            } else { $mep_reg_phone = '';}
            if (array_key_exists('wbtm_reg_address', $bus_meta)) {
                $mep_reg_address = strip_tags($bus_meta['wbtm_reg_address'][0]);
            } else { $mep_reg_address = '';}
            if (array_key_exists('wbtm_user_gender', $bus_meta)) {
                $wbtm_user_gender = strip_tags($bus_meta['wbtm_user_gender'][0]);
            } else { $wbtm_user_gender = '';}
            if (array_key_exists('wbtm_user_extra_bag', $bus_meta)) {
                $wbtm_user_extra_bag = strip_tags($bus_meta['wbtm_user_extra_bag'][0]);
                $wbtm_user_extra_bag_qty = strip_tags($bus_meta['wbtm_extra_max_qty'][0]);
                $wbtm_user_extra_bag_price = strip_tags($bus_meta['wbtm_extra_bag_price'][0]);

            } else {
                $wbtm_user_extra_bag = '';
                $wbtm_user_extra_bag_qty = 0;
                $wbtm_user_extra_bag_price = 0;

            }

            if ($mep_full_name || $mep_reg_email || $mep_reg_phone || $mep_reg_address || $wbtm_user_gender) {

                foreach ($selected_seats as $seat) {
                    $uid = $count . $seat['wbtm_seat_name'];
                    //    print_r($seat);

                    //$bus_id,$j_date,$selected_seats,$fare,$start,$end,$time

                    ?>
             <?php //echo $mep_full_name; ?>
            <div class='mep-user-info-sec <?php if ($type == 'return') {echo 'mep-hidden';}?>'>
                <ul>
                    <!-- <li><strong>Bus:</strong> <?php echo get_the_title($bus_id); ?></li> -->
                    <li><?php echo $start . ' - ' . $end; ?> - <?php echo date('D, d M Y', strtotime($j_date)) . ' ' . date('H:i A', strtotime($time)) ?></li>
                    <!-- <li><strong>Seat:</strong> <?php echo $seat['wbtm_seat_name']; ?></li> -->
                    <!-- <li><strong>Journey Date:</strong> </li> -->
                </ul>
               <?php // _e('Passenger:'); echo $count; ?>
                <input type="hidden" name='wbtm_user_bus_id[]' value='<?php echo $bus_id; ?>'>
                <input type="hidden" name='wbtm_user_start[]' value='<?php echo $start; ?>'>
                <input type="hidden" name='wbtm_user_end[]' value='<?php echo $end; ?>'>
                <input type="hidden" name='wbtm_user_seat[]' value='<?php echo $seat['wbtm_seat_name']; ?>'>
                <input type="hidden" name='wbtm_user_fare[]' value='<?php echo $fare; ?>'>
                <input type="hidden" name='wbtm_user_j_date[]' value='<?php echo $j_date; ?>'>
                <input type="hidden" name='wbtm_user_j_time[]' value='<?php echo $time; ?>'>
                <div class='user_reg_sec_checkout'>
                <p class='form-row'>
                <label for='bus_user_name_<?php echo $uid; ?>'>
                <?php _e("Enter Your Name", "addon-bus--ticket-booking-with-seat-pro");?><abbr class="required" title="required">*</abbr>

                <input id='bus_user_name_<?php echo $uid; ?>' type='<?php if ($type == 'return') {echo 'hidden';} else {if ($mep_full_name) {echo 'text';} else {echo 'hidden';}}?>' <?php if ($mep_full_name) {?> required='required' <?php }?> name='wbtm_user_name[]' class='passenger_name' placeholder='<?php //_e("Enter Your Name","addon-bus--ticket-booking-with-seat-pro"); ?>'/>
                </label>
                </p>

                <p class='form-row'>
                    <label for='bus_user_email_<?php echo $uid; ?>'>
                    <?php _e("Enter Your Email", "addon-bus--ticket-booking-with-seat-pro");?><abbr class="required" title="required">*</abbr>
                        <input id='bus_user_email_<?php echo $uid; ?>' type='<?php if ($type == 'return') {echo 'hidden';} else {if ($mep_reg_email) {echo 'email';} else {echo 'hidden';}}?>' <?php if ($mep_reg_email) {?> required='required' <?php }?> name='wbtm_user_email[]' class='passenger_email' placeholder='<?php //_e("Enter Your Email","addon-bus--ticket-booking-with-seat-pro"); ?>'/>
                    </label>
                </p>

            </div>
                <p class='form-row' style='display:<?php if ($type == 'return') {echo 'none';} else {if ($mep_reg_phone) {echo 'block';} else {echo 'none';}}?>'>
                <label for='bus_user_phone_<?php echo $uid; ?>'>
                <?php _e("Enter Your Phone", "addon-bus--ticket-booking-with-seat-pro");?><abbr class="required" title="required">*</abbr>
                    <input id='bus_user_phone_<?php echo $uid; ?>' type='<?php if ($type == 'return') {echo 'hidden';} else {if ($mep_reg_phone) {echo 'text';} else {echo 'hidden';}}?>' <?php if ($mep_reg_phone) {?> required='required' <?php }?> name='wbtm_user_phone[]' class='mep_input' placeholder='<?php _e("Enter Your Phone", "addon-bus--ticket-booking-with-seat-pro");?>'/>
                </label>
            </p>

            <p class='form-row' style='display:none'>
                <label for='bus_user_address_<?php echo $uid; ?>'>
                <textarea id='bus_user_address_<?php echo $uid; ?>' name='wbtm_user_address[]' class='mep_input <?php if ($type == 'return') {echo 'mep-hidden';} else {if ($mep_reg_address) {echo 'mep-show';} else {echo 'mep-hidden';}}?>' rows='3' <?php if ($mep_reg_address) {?> required='required' <?php }?> placeholder='<?php _e("Enter you address", "addon-bus--ticket-booking-with-seat-pro");?>'></textarea>
                </label>
            </p>

                <label class='<?php if ($type == 'return') {echo 'mep-hidden';} else {if ($wbtm_user_gender) {echo 'mep-show';} else {echo 'mep-hidden';}}?>' for='gen' style='text-align: left;'>
                <?php _e("Gender", "addon-bus--ticket-booking-with-seat-pro");?><select name='wbtm_user_gender[]' id='gen'><option value=''><?php _e("Please Select", "addon-bus--ticket-booking-with-seat-pro");?></option><option value='Male'><?php _e("Male", "addon-bus--ticket-booking-with-seat-pro");?></option><option value='Female'><?php _e("Female", "addon-bus--ticket-booking-with-seat-pro");?></option></select>
                </label>

                <?php do_action('wbtm_form_builder_data', get_the_id());?><?php if ($wbtm_user_extra_bag) {?>
                <?php _e('Extra Bag ( Per bag', 'addon-bus--ticket-booking-with-seat-pro');
                        echo strip_tags(wc_price($wbtm_user_extra_bag_price));?> )<?php }?>
                <input id='' class='input-text qty text extra-qty-box' step='1' min='0' max='<?php echo $wbtm_user_extra_bag_qty; ?>' name='extra_bag_quantity[]' value='0' title='Qty' size='4' pattern='[0-9]*' inputmode='numeric' type='<?php if ($wbtm_user_extra_bag) {echo 'number';} else {echo 'hidden';}?>'/>

                <input type='hidden' name='checkout_reg_user' value='yes'/>

                </div>
                <?php
}
            } else {
                echo '<input type=hidden name=checkout_reg_user value=no/>';
            }
        }
    }

    public function show_ticket_info_in_thankyou_page()
    {
        global $wbtmmain, $magepdf;
        $order_id = wc_get_order_id_by_order_key($_GET['key']);
        $order = wc_get_order($order_id);
        $order_meta = get_post_meta($order_id);
        $download_url = $magepdf->get_invoice_ajax_url(array('order_id' => $order_id));
        if ($wbtmmain->wbtm_order_status_allow($order->get_status())) {
            printf('<center><a class="btn btn-primary wbtm-button" href="%s">%s</a></center>', $download_url, __('Download Ticket', 'addon-bus--ticket-booking-with-seat-pro'));
        }
    }

    public function mep_events_generate_pdf()
    {

        if (empty($_GET['action']) || !check_admin_referer($_GET['action'])) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-invoice'));
        }

        $order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
        $document_type = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : '';

        global $magepdf;

//    header("Content-Type: application/pdf; charset=UTF-8");

        // echo '<pre>'; print_r( $wbtm ); echo '</pre>';

        echo $magepdf->generate_pdf($order_id, "", true, false);
        exit;
    }

    public function sent_ticket_email($order_id, $order)
    {
        global $wpdb, $magepdf, $wbtmmain;
        $args = array(
            'post_type' => array('mage_ticket_attendee'),
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'qr_order_id',
                    'value' => $order_id,
                    'compare' => '=',
                ),
            ),
        );
        $loop = new WP_Query($args);
        while ($loop->have_posts()) {
            $loop->the_post();
            $magepdf->send_email(get_the_id(), $order);
        }
    }

    public function bus_ticket_pdf_send($order_id, $from_status, $to_status, $order)
    {
        global $wpdb, $magepdf, $wbtmmain;
        // Getting an instance of the order object
        $order = wc_get_order($order_id);
        $order_meta = get_post_meta($order_id);
        $wbtm_email_status = $wbtmmain->bus_get_option('pdf_email_send_on', 'ticket_manager_settings', array());

        // foreach ( $order->get_items() as $item_id => $item_values ) {
        //     $item_id        = $item_id;
        // }

        // $ticket_id      = $wbtmmain->wbtm_get_order_meta($item_id,'_bus_id');
        // if (get_post_type($ticket_id) == 'wbtm_bus') {

        if (!empty($wbtm_email_status)) {

            $wbtm_email_status = empty($wbtm_email_status) ? array() : $wbtm_email_status;

            if ($order->has_status('processing')) {

                if (in_array($order->get_status(), $wbtm_email_status)) {
                    $type = 'order-email';
                    $oid = $order_id;
                    $magepdf->send_email($order_id, $order, $type, $oid);
                }
            }

            if ($order->has_status('pending')) {

                if (in_array($order->get_status(), $wbtm_email_status)) {
                    $magepdf->send_email($order_id, $order);
                }
            }

            if ($order->has_status('cancelled')) {

                if (in_array($order->get_status(), $wbtm_email_status)) {
                    $magepdf->send_email($order_id, $order);
                }
            }
            if ($order->has_status('completed')) {

                if (in_array($order->get_status(), $wbtm_email_status)) {
                    $type = 'order-email';
                    $oid = $order_id;
                    $magepdf->send_email($order_id, $order, $type, $oid);
                }
            }
            if ($order->has_status('refunded')) {

                if (in_array($order->get_status(), $wbtm_email_status)) {
                    $magepdf->send_email($order_id, $order);
                }
            }
            if ($order->has_status('on-hold')) {

                if (in_array($order->get_status(), $wbtm_email_status)) {
                    $magepdf->send_email($order_id, $order);
                }
            }

        }
    }

    public static function wbtm_get_ticket_link($order_id, $pdf_mode = 'D') {
        global $magepdf;

        $upload_dir   = wp_upload_dir();
        if( $order_id == 0 ) return new WP_Error( 'invalid_data', __('Invalid order id provided') );
        $ticket_name = $upload_dir['basedir'].'/'.__('Bus_ticket_','addon-bus--ticket-booking-with-seat-pro').$order_id.'.pdf';
        $file_name = __('Bus_ticket_','addon-bus--ticket-booking-with-seat-pro').$order_id.'.pdf';
        $html   = empty( $html ) ? $magepdf->get_order_pdf_html( $order_id, 'pdf' ) : $html;
        // echo $html;die;

        if(mep_get_mpdf_support_version() > 1.0){
            $mpdf = new \Mpdf\Mpdf();
        }else{
            $mpdf = new mPDF();
        }
        $mpdf->allow_charset_conversion=true;  // Set by default to TRUE
        $mpdf->autoScriptToLang = true;
        $mpdf->baseScript = 1;
        $mpdf->autoVietnamese = true;
        $mpdf->autoArabic = true;
        $mpdf->autoLangToFont = true;

        header('Content-Type: application/pdf');

        echo $mpdf->WriteHTML($html);
        $mpdf->Output($file_name, $pdf_mode);
        exit;
    }
//}

}
new WbtmProFunction();

if (!function_exists('mep_get_mpdf_support_version')) {
    function mep_get_mpdf_support_version()
    {
        if (is_plugin_active('magepeople-pdf-support-master/mage-pdf.php')) {
            $data = get_plugin_data(ABSPATH . "wp-content/plugins/magepeople-pdf-support-master/mage-pdf.php", false, false);
            return $data['Version'];
        } elseif (is_plugin_active('mage-pdf-support/mage-pdf.php')) {
            $data = get_plugin_data(ABSPATH . "wp-content/plugins/mage-pdf-support/mage-pdf.php", false, false);
            return $data['Version'];
        } else {
            return 1;
        }
    }
}
