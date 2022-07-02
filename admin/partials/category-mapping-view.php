<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file = CED_CATCH_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}
$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';

$catchCategorieslevel1 = file_get_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/categoryList.json' );

$catchCategorieslevel1 = json_decode( $catchCategorieslevel1, true );
$woo_store_categories  = get_terms( 'product_cat' );

$category_level = '';
?>
<div id="profile_create_message"></div>
<div class="ced_catch_heading">
	<div class="ced_catch_render_meta_keys_wrapper ced_catch_global_wrap">
		<div class="ced_catch_parent_element">
			<h2>
				<label class="basic_heading ced_catch_render_meta_keys_toggle"><?php esc_html_e( 'INSTRUCTION', 'catch-woocommerce-integration' ); ?></label>
				<span class="dashicons dashicons-arrow-down-alt2 ced_catch_instruction_icon"></span>
			</h2>
		</div>
		<div class="ced_catch_child_element default_modal">
			<ul type="disc">
				<li><?php echo esc_html_e( 'In this section you will need to map the woocommerce store categories to the catch categories.' ); ?></li>
				<li><?php echo esc_html_e( 'You need to select the woocommerce category using the checkbox on the left side and list of catch categories will appear in dropdown.Select the catch category in which you want to list the products of the selected woocmmerce category on catch.' ); ?></li>
				<li><?php echo esc_html_e( 'Click Save mapping option at the bottom.Once you map the categories' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=profiles-view&shop_id=' . $shop_id ) ); ?>">profiles</a> <?php esc_attr_e( 'will automatically be created and you can use the Profiles in order to override the settings of Product Export Settigs in Global Settings at category level.' ); ?></li>
			</ul>
		</div>
	</div>
</div>
<div class="ced_catch_category_mapping_wrapper" id="ced_catch_category_mapping_wrapper">
	<div class="ced_catch_store_categories_listing" id="ced_catch_store_categories_listing">
		<table class="wp-list-table widefat fixed striped posts ced_catch_store_categories_listing_table" id="ced_catch_store_categories_listing_table">
			<thead>
				<tr>
				<th><b><?php esc_html_e( 'Select', 'woocommerce-catch-integration' ); ?></b></th>
				<th colspan="2"><b><?php esc_html_e( 'Store Categories', 'woocommerce-catch-integration' ); ?></b></th>
				<th colspan="3"><b><?php esc_html_e( 'Mapped to Catch Category', 'woocommerce-catch-integration' ); ?></b></th>
				<th colspan="2"><button class="button-primary"  name="ced_catch_refresh_categories" id="ced_catch_category_refresh_button" data-shop_id=<?php echo esc_attr( $shop_id ); ?> ><?php esc_html_e( 'Refresh Catch Categories', 'woocommerce-catch-integration' ); ?></button></th></tr>
			</thead>
			<tbody>
				<?php
				function ced_catch_categories_tree( $value, $cat_name ) {
					if ( 0 != $value->parent ) {
						$parent_id = $value->parent;
						$sbcatch2  = get_term( $parent_id );
						if ( ! empty( $sbcatch2 ) ) {
							$sbcatch2_name = $sbcatch2->name . ' -> ';
						}
						$cat_name = $sbcatch2_name . '' . $cat_name;
						if ( 0 != $sbcatch2->parent ) {
							$cat_name = ced_catch_categories_tree( $sbcatch2, $cat_name );
						}
					}
					return $cat_name;
				}
				foreach ( $woo_store_categories as $key => $value ) {
					$cat_name = $value->name;
					$cat_name = ced_catch_categories_tree( $value, $cat_name );
					?>
					<tr class="ced_catch_store_category" id="<?php echo 'ced_catch_store_category_' . esc_attr( $value->term_id ); ?>">
						<td>
							<input type="checkbox" class="ced_catch_select_store_category_checkbox" name="ced_catch_select_store_category_checkbox[]" data-categoryID="<?php echo esc_attr( $value->term_id ); ?>"></input>
						</td>
						<td colspan="2">
							<span class="ced_catch_store_category_name"><?php echo esc_attr( $cat_name ); ?></span>
						</td>
						<?php
						$category_mapped_to          = get_term_meta( $value->term_id, 'ced_catch_mapped_category_' . $shop_id, true );
						$alreadyMappedCategoriesName = get_option( 'ced_woo_catch_mapped_categories_name', array() );
						$category_mapped_name_to     = isset( $alreadyMappedCategoriesName[ $shop_id ][ $category_mapped_to ] ) ? $alreadyMappedCategoriesName[ $shop_id ][ $category_mapped_to ] : '';
						if ( '' != $category_mapped_to && null != $category_mapped_to && '' != $category_mapped_name_to && null != $category_mapped_name_to ) {
							?>
							<td colspan="5">
								<span>
									<b><?php echo esc_attr( $category_mapped_name_to ); ?></b>
								</span>
							</td>
							<?php
						} else {
							?>
							<td colspan="5">
								<span class="ced_catch_category_not_mapped">
									<b><?php esc_html_e( 'Category Not Mapped', 'woocommerce-catch-integration' ); ?></b>
								</span>
							</td>
							<?php
						}
						?>
					
					</tr>
					<tr class="ced_catch_categories" id="<?php echo 'ced_catch_categories_' . esc_attr( $value->term_id ); ?>">
						<td></td>
						<td data-catlevel="1">
							<select class="ced_catch_level1_category ced_catch_select_category select2 ced_catch_select2 select_boxes_cat_map" name="ced_catch_level1_category[]" data-level=1 data-storeCategoryID="<?php echo esc_attr( $value->term_id ); ?>" data-catchStoreId="<?php echo esc_attr( $shop_id ); ?>">
								<option value="">--<?php esc_html_e( 'Select', 'woocommerce-catch-integration' ); ?>--</option>
							<?php
							foreach ( $catchCategorieslevel1 as $key1 => $value1 ) {
								if ( isset( $value1['label'] ) && '' != $value1['label'] && empty( $value1['parent_code'] ) ) {
									?>
									<option value="<?php echo esc_attr( $value1['code'] ); ?>"><?php echo esc_attr( $value1['label'] ); ?></option>	
									<?php
								}
							}
							?>
							</select>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<div class="ced_catch_category_mapping_header ced_catch_hidden" id="ced_catch_category_mapping_header">
		<a class="button-primary" href="" id="ced_catch_cancel_category_button">
			<?php esc_html_e( 'Cancel', 'woocommerce-catch-integration' ); ?>
		</a>
		<button class="button-primary" data-catchStoreID="<?php echo esc_attr( $shop_id ); ?>"  id="ced_catch_save_category_button">
			<?php esc_html_e( 'Save Mapping', 'woocommerce-catch-integration' ); ?>
		</button>
	</div>

</div>
