<?php

class WbtmProSettings{

    public function __construct(){
        add_filter('wbtm_submenu_setings_panels', array($this,'wbtm_pro_settings'),90,1);
    }

    public function wbtm_pro_settings($te){

        $pdf_settings = array(
            'page_nav' 	=> __( '<i class="fas fa-file-pdf"></i> PDF', 'addon-bus--ticket-booking-with-seat-pro' ),
            'priority' => 10,
            'page_settings' => array(
                'section_20' => array(
                    'title' 	=> 	__('PDF General Settings','addon-bus--ticket-booking-with-seat-pro'),
                    'nav_title' 	=> 	__('General','addon-bus--ticket-booking-with-seat-pro'),
                    'description' 	=> __('This is section details','addon-bus--ticket-booking-with-seat-pro'),
                    'options' 	=> array(
                        array(
                            'id'		    => 'pdf_file_name',
                            'title'		    => __('PDF file name','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('PDF ticket filename','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('','addon-bus--ticket-booking-with-seat-pro'),
                        ),
                        array(
                            'id'        => 'show_hide_pdf_price',
                            //'field_name'		    => 'text_multi_field',
                            'title'        => __('Show/hide price in pdf', 'addon-bus--ticket-booking-with-seat-pro'),
                            'details'    => __('Show or hide price in pdf ticket.<br> <span style="color:#9e9e9e">Default Show</span>', 'addon-bus--ticket-booking-with-seat-pro'),
                            'default'        => 'show',
                            'value'        => 'show',
                            'multiple'        => false,
                            'type'            => 'select',
                            'args'        => array(
                                'show'    => __('Show', 'addon-bus--ticket-booking-with-seat-pro'),
                                'hide'    => __('Hide', 'addon-bus--ticket-booking-with-seat-pro')
                            ),
                        ),
                        array(
                            'id'		=> 'merge_pdf_ticket',
                            //'field_name'		    => 'text_multi_field',
                            'title'		=> __('Merge Pdf Ticket','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	=> __('If YES, then multiple seats of the same order will generate one ticket.<br> <span style="color:#9e9e9e">Default No</span>','addon-bus--ticket-booking-with-seat-pro'),
                            'default'		=> 'no',
                            'value'		=> 'no',
                            'multiple'		=> false,
                            'type'		    => 'select',
                            'args'		=> array(
                                'yes'	=> __('Yes','addon-bus--ticket-booking-with-seat-pro'),
                                'no'	=> __('No','addon-bus--ticket-booking-with-seat-pro')                               
                            ),
                        ),
                        array(
                            'id'		    => 'pdf_logo',
                            'title'		    => __('Logo ','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('PDF Logo','addon-bus--ticket-booking-with-seat-pro'),
                            'placeholder'	=> 'https://i.imgur.com/GD3zKtz.png',
                            'type'		=> 'media',
                        ),
 
                        array(
                            'id'		    => 'pdf_bacckground_image',
                            'title'		    => __('Background Image ','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Select PDF Background Image','addon-bus--ticket-booking-with-seat-pro'),
                            'placeholder'	=> 'https://i.imgur.com/GD3zKtz.png',
                            'type'		=> 'media',
                        ),
                        array(
                            'id'		=> 'pdf_backgroud_color',
                            'title'		=> __('PDF Background Color','addon-bus--ticket-booking-with-seat-pro'),
                            // 'details'	=> __('Description of colorpicker field','addon-bus--ticket-booking-with-seat-pro'),
                            'default'	=> '#ffffff',
                            'value'		=> '#ffffff',
                            'type'		=> 'colorpicker',
                        ),                        
                        array(
                            'id'		=> 'pdf_text_color',
                            'title'		=> __('PDF Text Color','addon-bus--ticket-booking-with-seat-pro'),
                            // 'details'	=> __('Description of colorpicker field','addon-bus--ticket-booking-with-seat-pro'),
                            'default'	=> '#000000',
                            'value'		=> '#000000',
                            'type'		=> 'colorpicker',
                        ),                        
                        array(
                            'id'		    => 'pdf_company_address',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Company Address','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Enter your Company Address','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'textarea',
                            'default'		=> '',
                            'placeholder'   => __('Company Address','addon-bus--ticket-booking-with-seat-pro'),
                        ),

                        array(
                            'id'		    => 'pdf_company_phone',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Company Phone','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Enter your Company Phone No','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('Company phone','addon-bus--ticket-booking-with-seat-pro'),
                        ),

                        array(
                            'id'		    => 'pdf_company_email',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Company Email','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Enter your Company Email','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('Company Email','addon-bus--ticket-booking-with-seat-pro'),
                        ),
                        array(
                            'id'		    => 'pdf_terms_title',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Terms & Condition Title','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Enter Terms & Condition Title','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('Terms & Condition Title','addon-bus--ticket-booking-with-seat-pro'),
                        ),
                        array(
                            'id'		=> 'pdf_terms_text',
                            'title'		=> __('Terms & Condition Text','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	=> __('Terms & Condition Text','addon-bus--ticket-booking-with-seat-pro'),
                            'editor_settings'=>array('textarea_name'=>'pdf_terms_text_fields', 'editor_height'=>'150px'),
                            'placeholder' => __('Terms & Condition Text','addon-bus--ticket-booking-with-seat-pro'),
                            'default'		=> '',
                            'type'		=> 'textarea',
                        ),
                    )
                ),

                'section_2' => array(
                    'title' 	=> 	__('PDF Email Settings','addon-bus--ticket-booking-with-seat-pro'),
                    'nav_title' 	=> 	__('Email Settings','addon-bus--ticket-booking-with-seat-pro'),
                    // 'description' 	=> __('This is section details','addon-bus--ticket-booking-with-seat-pro'),
                    'options' 	=> array(
                        array(
                            'id'		=> 'email_send_pdf',
                            //'field_name'		    => 'text_multi_field',
                            'title'		=> __('Send Ticket','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	=> __('Send pdf to email?','addon-bus--ticket-booking-with-seat-pro'),
                            'default'		=> 'yes',
                            'value'		=> 'yes',
                            'multiple'		=> false,
                            'type'		    => 'select',
                            'args'		=> array(
                                'yes'	=> __('Yes','addon-bus--ticket-booking-with-seat-pro'),
                                'no'	=> __('No','addon-bus--ticket-booking-with-seat-pro')                                
                            ),
                        ),

                        array(
                            'id'		=> 'pdf_email_send_on',
                            //'field_name'		    => 'text_multi_field',
                            'title'		=> __('Send Email on','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	=> __('Send email with the ticket as attachment when these order status comes                            ','addon-bus--ticket-booking-with-seat-pro'),
                            // 'default'		=> array('option_3','option_2'),
                            // 'value'		    => array('option_2'),
                            'type'		    => 'checkbox_multi',
                            'args'		=> array(
                                'pending'	=> __('Pending','addon-bus--ticket-booking-with-seat-pro'),
                                'processing'=> __('Processing','addon-bus--ticket-booking-with-seat-pro'),
                                'completed'	=> __('Completed','addon-bus--ticket-booking-with-seat-pro'),
                                'refunded'	=> __('Refunded','addon-bus--ticket-booking-with-seat-pro'),
                                'cancelled'	=> __('Cancelled','addon-bus--ticket-booking-with-seat-pro'),
                                'on-hold'	=> __('On Hold','addon-bus--ticket-booking-with-seat-pro'),
                                'failed'	=> __('Failed','addon-bus--ticket-booking-with-seat-pro'),
                            ),
                        ),
                        array(
                            'id'		    => 'pdf_email_subject',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Email Subject','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Enter Email Subject','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('Email Subject','addon-bus--ticket-booking-with-seat-pro'),
                        ),
                        array(
                            'id'		=> 'pdf_email_text',
                            'title'		=> __('Email Content','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	=> __('Email Content','addon-bus--ticket-booking-with-seat-pro'),
                            //'editor_settings'=>array('textarea_name'=>'wp_editor_field', 'editor_height'=>'150px'),
                            'placeholder' => __('Email Content','addon-bus--ticket-booking-with-seat-pro'),
                            'default'		=> '',
                            'type'		=> 'wp_editor',
                        ),
                        array(
                            'id'		    => 'pdf_email_admin_notification_email',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Admin Notification Email','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Admin Notification Email','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('Admin Notification Email','addon-bus--ticket-booking-with-seat-pro'),
                        ),
                        array(
                            'id'		    => 'pdf_email_form_name',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Email From Name','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Email From Name','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('Email From Name','addon-bus--ticket-booking-with-seat-pro'),
                        ),
                        array(
                            'id'		    => 'pdf_email_form_email',
                            //'field_name'		    => 'some_id_text_field_1',
                            'title'		    => __('Email From Email','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Email From Email','addon-bus--ticket-booking-with-seat-pro'),
                            'type'		    => 'text',
                            'default'		=> '',
                            'placeholder'   => __('Email From','addon-bus--ticket-booking-with-seat-pro'),
                        ),
                    )
                ),
            ),
        );

        // ***************
        $custom_field_settings = array(
            'page_nav' 	=> __( '<i class="fas fa-shopping-cart"></i> Checkout Field', 'addon-bus--ticket-booking-with-seat-pro' ),
            'priority' => 10,
            'page_settings' => array(
                'section_20' => array(
                    'title' 	    => 	__('Checkout Field','addon-bus--ticket-booking-with-seat-pro'),
                    'nav_title' 	=> 	__('General','addon-bus--ticket-booking-with-seat-pro'),
                    'description' 	=> __('This is section details','addon-bus--ticket-booking-with-seat-pro'),
                    'options' 	    => array(
                        array(
                            'id'		    => 'custom_fields',
                            'title'		    => __('Custom Fields ','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	    => __('Select checkout fields name what you want to show in the passenger list using <span style="color:#ff9800">"comma-separated value."</span>','addon-bus--ticket-booking-with-seat-pro'),
                            'placeholder'	=> 'nid_no',
                            'type'		    => 'text',
                        ),
                        array(
                            'id'		=> 'default_billing_fields_setting',
                            //'field_name'		    => 'text_multi_field',
                            'title'		=> __('Default Fields','addon-bus--ticket-booking-with-seat-pro'),
                            'details'	=> __('Select checkout fields name what you want to show in the passenger list.','addon-bus--ticket-booking-with-seat-pro'),
                            // 'default'		=> array('option_3','option_2'),
                            // 'value'		    => array('option_2'),
                            'type'		    => 'checkbox_multi',
                            'args'		=> array(
                                'p_name'	=> __('Name','addon-bus--ticket-booking-with-seat-pro'),
                                'p_phone'=> __('Phone No','addon-bus--ticket-booking-with-seat-pro'),
                                'p_email'	=> __('Email','addon-bus--ticket-booking-with-seat-pro'),
                                'p_company'	=> __('Company','addon-bus--ticket-booking-with-seat-pro'),
                                'p_address'	=> __('Address','addon-bus--ticket-booking-with-seat-pro'),
                                'p_city'	=> __('City','addon-bus--ticket-booking-with-seat-pro'),
                                'p_state'	=> __('State','addon-bus--ticket-booking-with-seat-pro'),
                                'p_postcode'	=> __('Postcode','addon-bus--ticket-booking-with-seat-pro'),
                                'p_country'	=> __('Country','addon-bus--ticket-booking-with-seat-pro'),
                                'p_payment_method'	=> __('Payment Method','addon-bus--ticket-booking-with-seat-pro'),
                            ),
                        ),
                    )
                ),
            ),
        );

        
    $wbtm_pro_settings =  array(
            'wbtm-pdf-settings'        => $pdf_settings,
            'wbtm-custom-field-settings'        => $custom_field_settings,
      );
    
    return array_merge($te,$wbtm_pro_settings);
    }
    
}
new WbtmProSettings();