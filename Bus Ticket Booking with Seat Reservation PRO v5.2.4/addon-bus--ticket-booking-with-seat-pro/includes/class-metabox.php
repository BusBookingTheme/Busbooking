<?php
if ( ! defined('ABSPATH')) exit;  // if direct access 
if(!class_exists('AddMetaBox')){
    // require_once( ABSPATH . '/wp-content/plugins/bus-ticket-booking-with-seat-reservation/lib/classes/class-meta-box.php' );


    $filename = WP_PLUGIN_DIR . '/bus-ticket-booking-with-seat-reservation/lib/classes/class-meta-box.php';

    if (is_file($filename)) {
        require_once($filename);
    }

}

if(class_exists('AddMetaBox')){
    class WBTMProMetaBox{

        public function __construct(){
            $this->meta_boxs();
        }

        public function meta_boxs(){
            global $wbtmmain, $wbtmcore;
            $bus_on_day = array(
                'page_nav' 	=> __( '<i class="fas fa-cog"></i> Nav Title 2', 'addon-bus--ticket-booking-with-seat-pro' ),
                'priority' => 10,
                'sections' => array(
                    'section_2' => array(
                        'title' 	=> 	__('','addon-bus--ticket-booking-with-seat-pro'),
                        'description' 	=> __('','addon-bus--ticket-booking-with-seat-pro'),
                        'options' 	=> array(

                            array(
                                'id'		=> 'wbtm_full_name',
                                'type'		    => 'checkbox',
                                'title' 	=> 	__('Name','addon-bus--ticket-booking-with-seat-pro'),
                                'args'		=> array(
                                    '1'	=> __('Full Name','addon-bus--ticket-booking-with-seat-pro'),
                                ),
                            ),

                            array(
                                'id'		=> 'wbtm_reg_email',
                                'type'		    => 'checkbox',
                                'title' 	=> 	__('Email Address','addon-bus--ticket-booking-with-seat-pro'),
                                'args'		=> array(
                                    '1'	=> __('Email','addon-bus--ticket-booking-with-seat-pro'),
                                ),
                            ),

                            array(
                                'id'		=> 'wbtm_reg_phone',
                                'type'		    => 'checkbox',
                                'title' 	=> 	__('Phone','addon-bus--ticket-booking-with-seat-pro'),
                                'args'		=> array(
                                    '1'	=> __('Phone','addon-bus--ticket-booking-with-seat-pro'),
                                ),
                            ),

                            array(
                                'id'		=> 'wbtm_user_gender',
                                'type'		    => 'checkbox',
                                'title' 	=> 	__('Gender','addon-bus--ticket-booking-with-seat-pro'),
                                'args'		=> array(
                                    '1'	=> __('Gender','addon-bus--ticket-booking-with-seat-pro'),
                                ),
                            ),

                            array(
                                'id'		    => 'wbtm_user_extra_bag',
                                'type'		    => 'checkbox',
                                'title' 	=> 	__('Extra Bag','addon-bus--ticket-booking-with-seat-pro'),
                                'args'		    => array(
                                    '1'	        => __('Extra Bag','addon-bus--ticket-booking-with-seat-pro'),
                                ),
                            ),
                            array(
                                'id'		    => 'wbtm_extra_max_qty',
                                'type'		    => 'text',
                                'args' => '',
                                'title'		    => __('Maximum Bag Allow','addon-bus--ticket-booking-with-seat-pro'),
                            ),
                            array(
                                'id'		    => 'wbtm_extra_bag_price',
                                'type'		    => 'text',
                                'args' => '',
                                'title'		    => __('Per Bag Price','addon-bus--ticket-booking-with-seat-pro'),
                            ),
                            array(
                                'id'		=> 'attendee_reg_form',
                                'title'		=> __('Registration Form Builder','addon-bus--ticket-booking-with-seat-pro'),
                                'details'	=> __('Build Your Attendee Form','addon-bus--ticket-booking-with-seat-pro'),
                                'collapsible'=>true,
                                'type'		=> 'repeatable',
                                'btn_text'   => __('Add New Field','addon-bus--ticket-booking-with-seat-pro'),
                                'title_field' => 'field_label',
                                'args' => '',
                                'fields'    => array(
                                    array(
                                        'type'=>'text',
                                        'default'=>'',
                                        'item_id'=>'field_label',
                                        'name'=>'Field Label',
                                        'args' => ''
                                    ),
                                    array(
                                        'type'=>'text',
                                        'default'=>'',
                                        'item_id'=>'field_id',
                                        'name'=> __('Unique ID (Required Field, If this field is empty no info will be saved)','addon-bus--ticket-booking-with-seat-pro'),
                                        'args' => ''
                                    ),
                                    array(
                                        'type'      =>'select',
                                        'default'   =>'option_1',
                                        'item_id'   =>'filed_type',
                                        'name'      =>'Type',
                                        'args'      => array(
                                            'text'      => __('Text','addon-bus--ticket-booking-with-seat-pro'),
                                            'number'    =>__('Number','addon-bus--ticket-booking-with-seat-pro'),
                                            'select'    =>__('Select','addon-bus--ticket-booking-with-seat-pro'),
                                            'checkbox'  =>__('Checkbox','addon-bus--ticket-booking-with-seat-pro'),
                                            'radio'     =>__('Radio','addon-bus--ticket-booking-with-seat-pro'),
                                            'textarea'  =>__('Textarea','addon-bus--ticket-booking-with-seat-pro'),
                                            'email'     =>__('Email','addon-bus--ticket-booking-with-seat-pro')
                                        )
                                    ),
                                    array(
                                        'type'=>'checkbox',
                                        'item_id'=>'checkbox_field',
                                        'name'=>'Required?',
                                        'default'=>'',
                                        'args'=> array(
                                            'required'=> __('Required','addon-bus--ticket-booking-with-seat-pro')
                                        )
                                    ),
                                    array(
                                        'type'=>'textarea',
                                        'default'=>'',
                                        'details'	=> __('Please Enter values for Select/Checkbox & Radio Fields. Must be comma seperated. Ex: Male,Female','addon-bus--ticket-booking-with-seat-pro'),
                                        'item_id'=>'field_values',
                                        'name'=> __('Enter values for Select/Checkbox & Radio Fields. Must be comma seperated. Ex: Male,Female','addon-bus--ticket-booking-with-seat-pro'),
                                        'args' => ''
                                    ),
                                ),
                            )
                        )
                    ),

                ),
            );

            $onday_args = array(
                'meta_box_id'               => 'bus_meta_boxes_user_registration',
                'meta_box_title'            => __( 'Registration Form', 'addon-bus--ticket-booking-with-seat-pro' ),
                //'callback'       => '_meta_box_callback',
                'screen'                    => array( 'wbtm_bus'),
                'context'                   => 'normal', // 'normal', 'side', and 'advanced'
                'priority'                  => 'high', // 'high', 'low'
                'callback_args'             => array(),
                'nav_position'              => 'none', // right, top, left, none
                'item_name'                 => "MagePeople",
                'item_version'              => "2.0",
                'panels' 	                => array('bus_on_day' => $bus_on_day

                ),
            );


            $BusoNDay = new AddMetaBox( $onday_args );
        }
    }
    new WBTMProMetaBox();
}