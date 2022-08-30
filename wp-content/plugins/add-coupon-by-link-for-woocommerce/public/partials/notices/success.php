<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $notices ) {
	return;
}

?>

<?php foreach ( $notices as $notice ) : ?>
	<div id="acblw-notice-<?php echo $notice['data']['code']; ?>" class="acblw_success woocommerce-message success" <?php echo wc_get_notice_data_attr( $notice ); ?> role="alert" >
		<div class="pi-message"><?php echo wc_kses_notice( $notice['notice'] ); ?></div>
	</div>
<?php endforeach; ?>