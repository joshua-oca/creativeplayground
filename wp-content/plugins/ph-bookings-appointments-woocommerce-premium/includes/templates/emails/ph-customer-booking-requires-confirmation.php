<?php
/**
 * PH Customer booking cancelled email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * @hooked WC_Emails::email_header() Output the email header
*/
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php
		echo __( 'Hi', 'bookings-and-appointments-for-woocommerce' ).' '.$customer_full_name.',<br><br>';
		printf( __( 'Your Booking request at %s is awaiting approval.', 'bookings-and-appointments-for-woocommerce'), $email->blog_name );
	?>
</p>

<span style='color:<?php echo $email_base_color;?>;font-size:20px;'>
	<?php 
		printf( '['.__( 'Order','bookings-and-appointments-for-woocommerce').' #%s] (%s)', $order->get_order_number(), ph_wp_date($wp_date_format) );
	?>
</span>
<br><br>
	
<!-- Product details -->
<?php
	$order_items = $order->get_items();
	$table_td_style = "style='border: 1px solid #dddddd; padding:10px;font-size:17px;'";
	$table_td_title_style = "style='border: 1px solid #dddddd; padding:10px;font-size:20px;'";
	if( ! empty($order_items) ) 
	{
		?>
		<table  style='border-collapse:collapse; width:100%;color:<?php echo $email_text_color;?>'>
			<tr>
				<td <?php echo $table_td_title_style;?>>
					<?php echo __( 'Product', 'bookings-and-appointments-for-woocommerce');?>
				</td>
				<td <?php echo $table_td_title_style;?>>
					<?php echo __( 'Price', 'bookings-and-appointments-for-woocommerce');?>
				</td>
			</tr>
		<?php
		foreach( $order_items as $order_item_id => $order_item ) 
		{
			$product 	= $order_item->get_product();
			if( empty($product) || $product->get_type() !='phive_booking' || (!empty($item_id) && $item_id != $order_item_id) ){
				continue;
			}
			?>
			<tr>
				<td <?php echo $table_td_style;?>>
					<?php echo $order_item->get_name().$email->ph_get_order_item_meta_data($order_item); ?>
				</td>
				<td <?php echo $table_td_style;?>>
					<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $order_item ));?>
				</td>
			</tr>
			<?php
		}
		// cost details with email
		if (!empty($order_items))
		{
			$item_totals = $order->get_order_item_totals();
			if ( $item_totals ) 
			{
				foreach ( $item_totals as $total ) 
				{
					?>
					<tr>
						<td <?php echo $table_td_style;?>>
							<b><?php echo wp_kses_post( $total['label'] ); ?></b>
						</td>
						<td <?php echo $table_td_style;?>>
							<?php echo wp_kses_post( $total['value'] ); ?>
						</td>
					</tr>
					<?php
				}
			}
		}
		?>
		</table>
		<?php
	}

?>
<br>
<br>

<?php

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
