<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( is_array( $activeMarketplaces ) && ! empty( $activeMarketplaces ) ) {
	?>
	<div class="ced-marketplaces-heading-main-wrapper">
		<div class="ced-marketplaces-heading-wrapper">
			<h2><?php esc_attr_e( 'Active Marketplaces', 'woocommerce-catch-integration' ); ?></h2>
		</div>
	</div>
	<div class="ced-marketplaces-card-view-wrapper">
		<?php
		foreach ( $activeMarketplaces as $key => $value ) {
			$url = admin_url( 'admin.php?page=' . $value['menu_link'] );
			?>
			<div class="ced-marketplace-card <?php echo esc_attr( $value['name'] ); ?>">
				<a href="<?php echo esc_url( $url ); ?>">
					<div class="thumbnail">
						<div class="thumb-img">
							<img class="img-responsive center-block integration-icons" src="<?php echo esc_url( $value['card_image_link'] ); ?>" height="auto" width="auto">
						</div>
					</div>
					<div class="mp-label"><?php echo esc_attr( $value['name'] ); ?></div>
				</a>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}
?>
