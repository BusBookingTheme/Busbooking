<?php 
if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
class WBTMPRO_Cpt{
	
	public function __construct(){
		add_action( 'init', array($this, 'register_cpt' ));
	}


	public function register_cpt(){
		$labels = array(
			'name'                  => _x( 'Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'singular_name'         => _x( 'Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'menu_name'             => __( 'Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'name_admin_bar'        => __( 'Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'archives'              => __( 'Passenger List List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'attributes'            => __( 'Passenger List List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'parent_item_colon'     => __( 'Passenger List Item:', 'addon-bus--ticket-booking-with-seat-pro' ),
			'all_items'             => __( 'All Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'add_new_item'          => __( 'Add New Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'add_new'               => __( 'Add New Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'new_item'              => __( 'New Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'edit_item'             => __( 'Edit Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'update_item'           => __( 'Update Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'view_item'             => __( 'View Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'view_items'            => __( 'View Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'search_items'          => __( 'Search Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'not_found'             => __( 'Passenger List Not found', 'addon-bus--ticket-booking-with-seat-pro' ),
			'not_found_in_trash'    => __( 'Passenger List Not found in Trash', 'addon-bus--ticket-booking-with-seat-pro' ),
			'featured_image'        => __( 'Passenger List Feature Image', 'addon-bus--ticket-booking-with-seat-pro' ),
			'set_featured_image'    => __( 'Set Passenger List featured image', 'addon-bus--ticket-booking-with-seat-pro' ),
			'remove_featured_image' => __( 'Remove Passenger List featured image', 'addon-bus--ticket-booking-with-seat-pro' ),
			'use_featured_image'    => __( 'Use as Passenger List featured image', 'addon-bus--ticket-booking-with-seat-pro' ),
			'insert_into_item'      => __( 'Insert into Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Passenger List', 'addon-bus--ticket-booking-with-seat-pro' ),
			'items_list'            => __( 'Passenger List list', 'addon-bus--ticket-booking-with-seat-pro' ),
			'items_list_navigation' => __( 'Passenger List list navigation', 'addon-bus--ticket-booking-with-seat-pro' ),
			'filter_items_list'     => __( 'Filter Passenger List list', 'addon-bus--ticket-booking-with-seat-pro' ),
		);
	
	    $args = array(
	        'public'                => false,
	        'labels'                => $labels,
	        'menu_icon'             => 'dashicons-slides',
	        'supports'              => array('title'),
            'rewrite'               => array('slug' => 'passenger-list'),
            // 'show_in_menu' => false,
            // 'show_in_menu' => 'edit.php?post_type=wbtm_bus',
            'capability_type' => 'post',
            'capabilities' => array(
            'create_posts' => 'do_not_allow',
            ),
            'map_meta_cap' => true,             

	    );
	   	 register_post_type( 'wbtm_bus_booking', $args );

	}

}
new WBTMPRO_Cpt();