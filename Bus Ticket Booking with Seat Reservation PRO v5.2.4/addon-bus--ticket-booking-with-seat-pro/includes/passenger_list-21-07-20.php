<?php 

add_action( 'admin_menu', 'wbtm_passenger_list_menu' );

function wbtm_passenger_list_menu() {
	add_submenu_page('edit.php?post_type=wbtm_bus', __('Passenger List','addon-bus--ticket-booking-with-seat-pro'), __('Passenger List','wbtm-menu'), 'manage_options', 'passenger_list', 'wbtm_passenger_list');
}


function wbtm_passenger_list(){
global $wpdb,$magepdf;
$table_name = $wpdb->prefix."wbtm_bus_booking_list";




if(isset($_GET['action'])&&$_GET['action']=='delete_seat'){
	// echo 'Yes Did';
	$booking_id = strip_tags($_GET['booking_id']);
	$status =3;	
    $del = update_post_meta($booking_id, 'wbtm_status', $status);;
    if($del){
    	?>
<div id="message" class="updated notice notice-success is-dismissible"><p><?php _e('Seat Deleted','addon-bus--ticket-booking-with-seat-pro'); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.','addon-bus--ticket-booking-with-seat-pro'); ?></span></button></div>
    	<?php
    }
}

if(isset($_GET['bus_id'])){
	$bus_id = strip_tags($_GET['bus_id']);
	$j_date = strip_tags($_GET['j_date']);
	$bid_arr = explode('-', $bus_id);
	$bid = $bid_arr[0];
	$btime = $bid_arr[1];

}else{
	$bus_id =0;
	$j_date = date('Y-m-d');
}


	?>
<div class="wrap">
<h2>Passenger List</h2>
<form action="<?php echo get_admin_url(); ?>edit.php?post_type=wbtm_bus&page=passenger_list" method="get">
<table>
	<tr>
		<td>
		Filter List By:
	   </td>
	   <td class="the_select">
	   	<select name="bus_id" id="select2" class="select2" required>
	   		<option value=""><?php _e('Select Bus','addon-bus--ticket-booking-with-seat-pro'); ?></option>
				<?php 
				$args = array(
				'post_type' => 'wbtm_bus',
				'posts_per_page' => -1
				);
				$loop = new WP_Query($args);
				while ($loop->have_posts()) {
					$loop->the_post();
					$start = get_post_meta(get_the_id(),'wbtm_bus_no',true);
					$busit = get_the_id()."-".$start;
					?>
						<option value="<?php echo $busit; ?>" <?php if($busit==$bus_id){ echo 'selected'; } ?>><?php the_title(); ?> - <?php echo $start; ?></option>
					<?php
				}
				?>
	   	</select>
	   </td>
	   <td>

	   	<input type="hidden" name="post_type" value="wbtm_bus">
	   	<input type="hidden" name="page" value="passenger_list">
	   	<input type="text" id="ja_date" name="j_date" value="<?php echo $j_date; ?>"></td>
	   <td><button type="submit">Filter</button></form></td>
	   <td>
	   </td>
</tr>
</table>

<?php 
if(isset($_GET['bus_id'])){
	?>
        <div class="wrap alignright">
            <form method='get' action="edit.php">
                <input type="hidden" name='bus_id' value="<?php echo $bid; ?>"/>
                <input type="hidden" name='j_date' value="<?php echo $j_date; ?>"/>
                <input type="hidden" name="post_type" value="wbtm_bus">
	   			<input type="hidden" name="page" value="passenger_list">
                <input type="hidden" name='noheader' value="1"/>               
                <input type="hidden" name='action' value="export_passenger_list"/>               
                <input style="display:none" type="radio" name='format' id="formatCSV" value="csv" checked="checked"/>
                <input type="submit" name='export' id="csvExport" value="Export to CSV"/>
            </form>
        </div>
<table class="wp-list-table widefat fixed striped posts">
	<thead>
		<tr>
			<th><?php _e('Name','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('Email','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<!-- <th>Email</th> -->
			<th><?php _e('Seat','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('Bus Name','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('Journey Date','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('Start','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('End','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('Order','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('Status','addon-bus--ticket-booking-with-seat-pro'); ?></th>
			<th><?php _e('Action','addon-bus--ticket-booking-with-seat-pro'); ?></th>
		</tr>
	</thead>
	<tbody>
<?php 
$args = array(
	'post_type' => 'wbtm_bus_booking',
	'posts_per_page' => -1,
	'meta_query'  => array(
		'relation' => 'AND',
		array(
			'relation' => 'AND',
				array(
					'key'     => 'wbtm_journey_date',
					'value' => $j_date,
					'compare' => '='									
				),
				array(
					'key'     => 'wbtm_bus_id',
					'value' => $bid,
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
echo $passenger->request;
$passger_query = $passenger->posts;
// echo '<pre>';
// print_r($passger_query);
// die;
foreach ($passger_query as $_passger) {
$passenger_id = $_passger->ID;
$order_id = get_post_meta($passenger_id,'wbtm_order_id',true);
$order = wc_get_order( $order_id );
$download_url = $magepdf->get_invoice_ajax_url( array( 'order_id' => $order_id ) );
?>
		<tr>
			<td><?php echo get_post_meta($passenger_id,'wbtm_user_name',true); ?></td>
			<td><?php echo get_post_meta($passenger_id,'wbtm_user_email',true); ?></td>
			<!-- <td><?php echo $_passger->user_email; ?></td> -->
			<td><?php echo get_post_meta($passenger_id,'wbtm_seat',true); ?></td>
			<td><?php echo get_the_title(get_post_meta($passenger_id,'wbtm_bus_id',true))."-".get_post_meta(get_post_meta($passenger_id,'wbtm_bus_id',true),'wbtm_bus_no',true); ?></td>
			
			<!-- <td><?php //echo get_wbtm_datetime(get_post_meta( $passenger_id, 'wbtm_journey_date', true ).' '.get_post_meta( $passenger_id, 'wbtm_bus_start', true ),'date-time-text'); ?></td> -->
			<td><?php echo get_post_meta( $passenger_id, 'wbtm_journey_date', true ); ?></td>
			<td><?php echo get_post_meta($passenger_id,'wbtm_boarding_point',true); ?></td>
			<td><?php echo get_post_meta($passenger_id,'wbtm_droping_point',true); ?></td>
			<td><?php echo get_post_meta($passenger_id,'wbtm_order_id',true); ?></td>
			<td>
				<?php echo $order->get_status(); ?>
				<?php //do_action('wbtm_ticket_status',$_passger->ticket_status); ?>
					
				</td>
			<td>

			<a href="<?php echo $download_url; ?>" title="Download PDF"><span class="dashicons dashicons-tickets-alt"></span></a>

			<a href="<?php echo get_admin_url(); ?>edit.php?bus_id=<?php echo $_GET['bus_id']; ?>&post_type=wbtm_bus&page=passenger_list&j_date=<?php echo $_GET['j_date']; ?>&action=delete_seat&booking_id=<?php echo $_passger->ID; ?>" title="Delete Data"><span class="dashicons dashicons-no"></span></a>
				
			</td>
		</tr>
	<?php
}
		?>
	</tbody>
</table>
<?php } ?>
	</div>
	<?php
}