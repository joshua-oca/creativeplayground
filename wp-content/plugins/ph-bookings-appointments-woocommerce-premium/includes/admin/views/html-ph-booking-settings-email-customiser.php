<?php
if( ! defined('ABSPATH') )	exit;

		$reminder_email_settings_id = 'ph_bookings_settings_notifications';

		
		if( isset($_POST['ph_bookings_notification_email_content']) )
		{
			// error_log(print_r($_POST,1));
			$settings = array(
				'reminder_email_enabled'		=>	isset($_POST['ph_bookings_enable_notifications']) ? true : false,
				'reminder_email_subject'	=>	$_POST['ph_bookings_notification_email_subject'],
				'reminder_email_content'	=>	$_POST['ph_bookings_notification_email_content'],
				'reminder_email_notification_time'	=>	$_POST['ph_bookings_notification_time']
			);
			update_option('ph_bookings_settings_notifications', $settings );

		}
		$reminder_email_settings 		= get_option( $reminder_email_settings_id, array() );
		$reminder_email_enabled			= ! empty($reminder_email_settings['reminder_email_enabled']) ? $reminder_email_settings['reminder_email_enabled'] : false;
		$reminder_email_subject	= ! empty($reminder_email_settings['reminder_email_subject']) ? $reminder_email_settings['reminder_email_subject'] : __( 'Bookings Reminders', 'ph-bookings-reminder-notifications');
		$reminder_email_content	= ! empty($reminder_email_settings['reminder_email_content']) ? $reminder_email_settings['reminder_email_content'] : get_default_email_content();

		// Multiple Backslashes issue
		$reminder_email_content	= str_replace( "\\","",$reminder_email_content);

		do_action( 'wpml_register_single_string', 'bookings-and-appointments-for-woocommerce', 'reminder_email_subject_translation', $reminder_email_subject );		// WPML support
		do_action( 'wpml_register_single_string', 'bookings-and-appointments-for-woocommerce', 'reminder_email_content_translation', $reminder_email_content );		// WPML support

		$reminder_email_notification_time	= ! empty($reminder_email_settings['reminder_email_notification_time']) ? $reminder_email_settings['reminder_email_notification_time'] : 60;

		/**
		 * Get Bookings Notification Settings.
		 */
		function get_booking_reminder_settings() {
			// if( empty($notification_settings) )	$notification_settings = get_option( $reminder_email_settings_id, array() );
			// return $notification_settings;
		}

		/**
		 * Get Default Email Content.
		 */
		function get_default_email_content() {
			$email_content = "Hi [CUSTOMER_NAME],<br><br>I request you to check the bookings details for your appointment with [SITE_NAME].<br><br>[BOOKING_DETAILS]<br><br>We look forward to serving you.<br>Regards<br>Admin";
			return $email_content;
		}
?>
<p><i><?php echo __('This section allows you to configure Reminder and Follow up Emails for your bookings. Please configure them below :', 'bookings-and-appointments-for-woocommerce');?>
<br><?php echo __('Please Note : Default Emails like “New Booking Order Emails, Booking Cancellation Emails and Booking Approval Emails are sent automatically when applicable, no configuration is required for them.', 'bookings-and-appointments-for-woocommerce');?></i></p>
<form method="post" action="" id="">
	<h2><?php _e('Reminder Email','bookings-and-appointments-for-woocommerce');?></h2>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_enable_notifications"><?php echo __('Enable Booking Reminder Emails', 'bookings-and-appointments-for-woocommerce');?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<input type="checkbox" name="ph_bookings_enable_notifications" id="ph_bookings_enable_notifications" <?php echo ( $reminder_email_enabled ? 'checked' : null);?> >
				
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_notification_email_subject"><?php echo __('Reminder Email Subject', 'bookings-and-appointments-for-woocommerce');?></label>
			</th>
			<td class="forminp forminp-text">
				<input type="text" name="ph_bookings_notification_email_subject" id="ph_bookings_notification_email_subject" value="<?php echo $reminder_email_subject;?>" style="width:40%;">
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_notification_email_content"><?php echo __('Reminder Email Content', 'bookings-and-appointments-for-woocommerce');?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea name="ph_bookings_notification_email_content" rows="20" cols="70" placeholder="<?php echo get_default_email_content(); ?>" ><?php echo $reminder_email_content;?></textarea>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_notification_time"><?php echo __('Enter how many minutes in advance the reminder email should be sent', 'bookings-and-appointments-for-woocommerce');?></label>
			</th>
			<td class="forminp forminp-textarea">
			<input type="text" name="ph_bookings_notification_time" id="ph_bookings_notification_time" value="<?php echo $reminder_email_notification_time;?>" style="width:40%;">
			</td>
		</tr>
	</table>

	<p class="submit">
		<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes"><?php _e('Save changes','bookings-and-appointments-for-woocommerce');?></button>
	</p>
</form>




<!-- Followup email setting -->

<?php

if( isset($_POST['ph_bookings_follow_up_email_content']) )
{
	$settings = array(
		'followup_email_enabled'		=>	isset($_POST['ph_bookings_enable_followup_email']) ? true : false,
		'followup_email_subject'	=>	$_POST['ph_bookings_followup_email_subject'],
		'followup_email_content'	=>	$_POST['ph_bookings_followup_email_content'],
		'followup_email_followup_time'	=>	$_POST['ph_bookings_followup_time']
	);
	update_option( 'ph_booking_follow_up_email', $settings );
}


$followup_email_content = "Hi [CUSTOMER_NAME],<br><br>";
$followup_email_content .="Thank you for booking with [SITE_NAME].<br>";
$followup_email_content .="Hope you enjoyed our services. <br>";
$followup_email_content .="We look forward to serving you again.<br><br>";
$followup_email_content .="With Regards,<br>";
$followup_email_content .="Admin";
$followup_email_settings 		= get_option( 'ph_booking_follow_up_email', array() );
// error_log(print_r($followup_email_settings,1));
$followup_email_enabled			= ! empty($followup_email_settings['followup_email_enabled']) ? $followup_email_settings['followup_email_enabled'] : false;
$followup_email_subject	= ! empty($followup_email_settings['followup_email_subject']) ? $followup_email_settings['followup_email_subject'] : 'Thanks for Booking with Us..!';
$followup_email_content	= ! empty($followup_email_settings['followup_email_content']) ? $followup_email_settings['followup_email_content'] : $followup_email_content;

// Multiple backslashes issue
$followup_email_content	= str_replace( "\\","",$followup_email_content);

do_action( 'wpml_register_single_string', 'bookings-and-appointments-for-woocommerce', 'followup_email_subject_translation', $followup_email_subject );	
do_action( 'wpml_register_single_string', 'bookings-and-appointments-for-woocommerce', 'followup_email_content_translation', $followup_email_content );		// WPML support

$followup_email_notification_time	= ! empty($followup_email_settings['followup_email_followup_time']) ? $followup_email_settings['followup_email_followup_time'] : 60;
		

?>

<form method="post" action="" id="">
	<h2><?php _e('Follow up Email','bookings-and-appointments-for-woocommerce');?></h2>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_enable_followup_email"><?php echo __('Enable Follow up Emails', 'bookings-and-appointments-for-woocommerce');?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<input type="checkbox" name="ph_bookings_enable_followup_email" id="ph_bookings_enable_followup_email" <?php echo ( $followup_email_enabled ? 'checked' : null);?> >
				<!-- <span>Follow Up Email Enable</span> -->
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_followup_email_subject"><?php echo __('Follow up Email Subject', 'bookings-and-appointments-for-woocommerce');?></label>
			</th>
			<td class="forminp forminp-text">
				<input type="text" name="ph_bookings_followup_email_subject" id="ph_bookings_followup_email_subject" value="<?php echo $followup_email_subject;?>" style="width:40%;">
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_followup_email_content"><?php echo __('Follow up Email Content', 'bookings-and-appointments-for-woocommerce');?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea name="ph_bookings_followup_email_content" rows="20" cols="70" placeholder="<?php //echo Ph_Booking_Reminder_Common::get_default_email_content(); ?>" ><?php echo $followup_email_content;?></textarea>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="ph_bookings_followup_time"><?php echo __('Enter how many minutes after the booking the follow up email should be sent', 'bookings-and-appointments-for-woocommerce');?>
				</label>
			</th>
			<td class="forminp forminp-textarea">
			<input type="text" name="ph_bookings_followup_time" id="ph_bookings_followup_time" value="<?php echo $followup_email_notification_time;?>" style="width:40%;">
			</td>
		</tr>
	</table>

	<p class="submit">
		<button name="ph_bookings_follow_up_email_content" class="button-primary woocommerce-save-button" type="submit" value="Save changes"><?php _e('Save changes','bookings-and-appointments-for-woocommerce');?></button>
	</p>
</form>