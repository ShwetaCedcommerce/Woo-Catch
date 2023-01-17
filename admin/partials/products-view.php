<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_CATCH_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CatchListProducts extends WP_List_Table {


	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Product', 'woocommerce-catch-integration' ), // singular name of the listed records
				'plural'   => __( 'Products', 'woocommerce-catch-integration' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}

	/**
	 *
	 * Function for preparing data to be displayed
	 */

	public function prepare_items() {

		global $wpdb;

		$per_page  = apply_filters( 'ced_catch_products_per_page', 50 );
		$post_type = 'product';
		$columns   = $this->get_columns();
		$hidden    = array();
		$sortable  = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();

		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		$this->items = self::get_product_details( $per_page, $current_page, $post_type );
		$count       = self::get_count( $per_page, $current_page );

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 *
	 * Function for get product data
	 */
	public function get_product_details( $per_page = '', $page_number = 1, $post_type = '' ) {
		$filterFile = CED_CATCH_DIRPATH . 'admin/partials/products-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}
		$shop_id      = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$getImportIds = get_option( 'ced_catch_import_ids_' . $shop_id, array() );

		$instanceOf_FilterClass = new FilterClass();
		$args                   = $this->GetFilteredData( $per_page, $page_number );

		$sql = '';
		global $wpdb;
		$post_ids = array();
		if( isset( $args['search_value'] ) ) {
			$search_value = $args['search_value'];
			$search_value = str_replace(',', ' ', $search_value);
			$search_like  = '%'.$search_value.'%';
			$sql = "SELECT `P`.`ID`, `P`.`post_parent` FROM `{$wpdb->prefix}posts` AS `P` INNER JOIN `{$wpdb->prefix}postmeta` AS `PM` ON `P`.`ID` = `PM`.`post_id` WHERE (`PM`.`meta_key` = %s AND `PM`.`meta_value` LIKE %s) OR (`P`.`ID`= %d ) OR (`PM`.`post_id`=%d) OR (`P`.`post_title` LIKE %s)";
			$post_ids = $wpdb->get_results( $wpdb->prepare( $sql,'_sku',$search_like,$search_value,$search_value,$search_like ), 'ARRAY_A' );
		
			$productID = array();
			if( !empty( $post_ids ) && is_array( $post_ids) ) {
				foreach ($post_ids as $key => $value) {
					if( isset( $value['post_parent'] ) && 0 < $value['post_parent'] ) {
						$productID[] = $value['post_parent'];
					} else {
						$productID[] = $value['ID'];
					}
				}
			}
			
			if( !empty( $productID ) && is_array( $productID ) ) {
				$productID = array_unique( $productID );
				$args = array(
					'post_type'      => $post_type,
					'posts_per_page' => $per_page,
					'post__in'          => $productID,
				);
			} else {
				$args = array();
			}
			unset( $args['search_value'] );
		} else {
			if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) ) {
				$args = $args;
			} else {
				$args = array(
					'post_type'      => $post_type,
					'posts_per_page' => $per_page,
					'paged'          => $page_number,
				);
			}
		}

		$loop           = new WP_Query( $args );
		$product_data   = $loop->posts;
		$woo_categories = get_terms( 'product_cat', array( 'hide_empty' => false ) );
		$woo_products   = array();
		foreach ( $product_data as $key => $value ) {
			$get_product_data = wc_get_product( $value->ID );

			if ( ! is_object( $get_product_data ) ) {
				continue;
			}

			$get_product_data                     = $get_product_data->get_data();
			$woo_products[ $key ]['category_ids'] = isset( $get_product_data['category_ids'] ) ? $get_product_data['category_ids'] : array();
			$woo_products[ $key ]['id']           = $value->ID;
			$woo_products[ $key ]['name']         = $get_product_data['name'];
			$woo_products[ $key ]['stock']        = $get_product_data['stock_quantity'];
			if ( ! empty( $productstatusbySku ) ) {
				foreach ( $productstatusbySku as $key3 => $value3 ) {
					if ( $value3['sku'] == $get_product_data['sku'] ) {
						$woo_products[ $key ]['status'] = $value3['status'];
					}
				}
			}
			$woo_products[ $key ]['sku']   = $get_product_data['sku'];
			$woo_products[ $key ]['price'] = $get_product_data['price'];
			$Image_url_id                  = $get_product_data['image_id'];
			$woo_products[ $key ]['image'] = wp_get_attachment_url( $Image_url_id );
			foreach ( $woo_categories as $key1 => $value1 ) {
				if ( isset( $get_product_data['category_ids'][0] ) ) {
					if ( $value1->term_id == $get_product_data['category_ids'][0] ) {
						$woo_products[ $key ]['category'] = $value1->name;
					}
				}
			}
		}

		if ( isset( $_POST['filter_button'] ) ) {
			$nonce_verification_filter = isset( $_POST['nonce_verification_filter'] ) ? sanitize_text_field( $_POST['nonce_verification_filter'] ) : '';
			if ( wp_verify_nonce( $nonce_verification_filter, 'nonce_verification_filter' ) ) {
				$woo_products = $instanceOf_FilterClass->ced_catch_filters_on_products( $woo_products, $nonce_verification_filter );
			}
		} elseif ( isset( $_POST['s'] ) && '' != $_POST['s'] ) {
			$filteredProducts          = $this->ced_catchGetAllposts();
			$search_product            = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';
			$substring                 = stripcslashes( strtolower( $search_product ) );
			$nonce_verification_search = isset( $_POST['nonce_verification_filter'] ) ? sanitize_text_field( $_POST['nonce_verification_filter'] ) : '';
			if ( wp_verify_nonce( $nonce_verification_search, 'nonce_verification_filter' ) ) {
				$woo_products = $instanceOf_FilterClass->productSearch_box( $filteredProducts, $substring, $nonce_verification_search );
			}
		}
		return $woo_products;
	}

	public function ced_catchGetAllposts() {
		return array();
		$args           = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
		);
		$loop           = new WP_Query( $args );
		$product_data   = $loop->posts;
		$woo_categories = get_terms( 'product_cat', array( 'hide_empty' => false ) );
		$woo_products   = array();
		foreach ( $product_data as $key => $value ) {
			$get_product_data = wc_get_product( $value->ID );
			if ( ! is_object( $get_product_data ) ) {
				continue;
			}
			$get_product_data                    = $get_product_data->get_data();
			$woo_products[ $key ]['category_id'] = isset( $get_product_data['category_ids'][0] ) ? $get_product_data['category_ids'][0] : '';
			$woo_products[ $key ]['id']          = $value->ID;
			$woo_products[ $key ]['name']        = $get_product_data['name'];
			$woo_products[ $key ]['stock']       = $get_product_data['stock_quantity'];
			$woo_products[ $key ]['sku']         = $get_product_data['sku'];
			$woo_products[ $key ]['price']       = $get_product_data['price'];
			$Image_url_id                        = $get_product_data['image_id'];
			$woo_products[ $key ]['image']       = wp_get_attachment_url( $Image_url_id );
			foreach ( $woo_categories as $key1 => $value1 ) {
				if ( isset( $get_product_data['category_ids'][0] ) ) {
					if ( $value1->term_id == $get_product_data['category_ids'][0] ) {
						$woo_products[ $key ]['category'] = $value1->name;
					}
				}
			}
		}
		return $woo_products;
	}

	/**
	 *
	 * Text displayed when no data is available
	 */
	public function no_items() {
		esc_attr_e( 'No Products To Show.', 'woocommerce-catch-integration' );
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */

	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/*
	 * Render the bulk edit checkbox
	 *
	 */
	public function column_cb( $item ) {
		$shop_id               = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$get_catch_category_id = '';
		$profileid             = '';
		$category_ids          = isset( $item['category_ids'] ) ? $item['category_ids'] : array();
		foreach ( $category_ids as $index => $data ) {
			$get_catch_category_id_data = get_term_meta( $data );
			$get_catch_category_id      = isset( $get_catch_category_id_data[ 'ced_catch_mapped_category_' . $shop_id ] ) ? $get_catch_category_id_data[ 'ced_catch_mapped_category_' . $shop_id ] : '';
			if ( ! empty( $get_catch_category_id ) ) {
				break;
			}
		}

		if ( ! empty( $get_catch_category_id ) ) {
			foreach ( $get_catch_category_id as $key => $catch_id ) {
				$get_catch_profile_assigned = get_option( 'ced_woo_catch_mapped_categories_name' );
				$get_catch_profile_assigned = isset( $get_catch_profile_assigned[ $shop_id ][ $catch_id ] ) ? $get_catch_profile_assigned[ $shop_id ][ $catch_id ] : '';
			}
			$profileid = isset( $get_catch_category_id_data[ 'ced_catch_profile_id_' . $shop_id ] ) ? $get_catch_category_id_data[ 'ced_catch_profile_id_' . $shop_id ] : '';
			$profileid = $profileid[0];
		}
		return sprintf(
			'<input type="checkbox" name="catch_product_ids[]" data-profileid="%s" class="catch_products_id" value="%s" />',
			$profileid,
			$item['id']
		);
	}

	/**
	 *
	 * Function for name column
	 */
	public function column_name( $item ) {
		$url           = get_edit_post_link( $item['id'], '' );
		$actions['id'] = 'ID:' . $item['id'];

		echo '<b><a class="ced_catch_prod_name" href="' . esc_url( $url ) . '">' . esc_attr( $item['name'] ) . '</a></b><br>';
		return $this->row_actions( $actions );
	}


	/**
	 *
	 * Function for profile column
	 */
	public function column_profile( $item ) {
		$shop_id                      = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$profile_id                   = array();
		$get_profile_id_of_prod_level = get_post_meta( $item['id'], 'ced_catch_profile_assigned' . $shop_id, true );
		if ( ! empty( $get_profile_id_of_prod_level ) ) {
			global $wpdb;
			$profile_name = $wpdb->get_results( $wpdb->prepare( "SELECT `profile_name` FROM `{$wpdb->prefix}ced_catch_profiles` WHERE `id` = %d", $get_profile_id_of_prod_level ), 'ARRAY_A' );

			if ( ! empty( $profile_name ) ) {
				echo '<b>' . esc_attr( $profile_name[0]['profile_name'] ) . '</b>';
			}
			$profile_id = $get_profile_id_of_prod_level;
		} else {
			$get_catch_category_id = '';
			$category_ids          = isset( $item['category_ids'] ) ? $item['category_ids'] : array();
			foreach ( $category_ids as $index => $data ) {
				$get_catch_category_id_data = get_term_meta( $data );
				$get_catch_category_id      = isset( $get_catch_category_id_data[ 'ced_catch_mapped_category_' . $shop_id ] ) ? $get_catch_category_id_data[ 'ced_catch_mapped_category_' . $shop_id ] : '';
				if ( ! empty( $get_catch_category_id ) ) {
					break;
				}
			}

			if ( ! empty( $get_catch_category_id ) ) {
				foreach ( $get_catch_category_id as $key => $catch_id ) {
					$get_catch_profile_assigned = get_option( 'ced_woo_catch_mapped_categories_name' );
					$get_catch_profile_assigned = isset( $get_catch_profile_assigned[ $shop_id ][ $catch_id ] ) ? $get_catch_profile_assigned[ $shop_id ][ $catch_id ] : '';
				}

				if ( isset( $get_catch_profile_assigned ) && ! empty( $get_catch_profile_assigned ) ) {
					echo '<b>' . esc_attr( $get_catch_profile_assigned ) . '</b>';
				}
				$profile_id = isset( $get_catch_category_id_data[ 'ced_catch_profile_id_' . $shop_id ] ) ? $get_catch_category_id_data[ 'ced_catch_profile_id_' . $shop_id ] : '';
				$profile_id = $profile_id[0];
			} else {
				$cat_mapping_section = admin_url( 'admin.php?page=ced_catch&section=category-mapping-view&shop_id=' . $shop_id );
				echo "<span class='not_completed'>Category not mapped<span><p>Please map category <a href='" . esc_url( $cat_mapping_section ) . "' target='_blank'><i>here</i></a></p>";
			}
		}

		if ( $profile_id ) {
			$edit_profile_url = admin_url( 'admin.php?page=ced_catch&section=profiles-view&shop_id=' . $shop_id . '&profileID=' . $profile_id . '&panel=edit' );
			$actions['edit']  = '<a href="' . $edit_profile_url . '">' . __( 'Edit', 'woocommerce-catch-integration' ) . '</a>';
			return $this->row_actions( $actions, true );
		}
	}
	/**
	 *
	 * Function for stock column
	 */
	public function column_stock( $item ) {

		$catch_stock  = get_post_meta( $item['id'], 'ced_catch_custom_stock', true );
		$stock_status = get_post_meta( $item['id'], '_stock_status', true );
		if ( 'outofstock' == $stock_status ) {
			return '<b class="stock_alert" >' . __( 'Out Of Stock', 'woocommerce-catch-integration' ) . '</b>';
		} elseif ( $catch_stock ) {
			return '<b>' . $catch_stock . '</b>';
		} elseif ( '' != $item['stock'] ) {
			return '<b>' . $item['stock'] . '</b>';
		} else {
			return '<b>10</b>';
		}
	}
	/**
	 *
	 * Function for category column
	 */
	public function column_category( $item ) {
		if ( isset( $item['category'] ) ) {
			return '<b>' . $item['category'] . '</b>';
		}

	}
	/**
	 *
	 * Function for price column
	 */
	public function column_price( $item ) {
		$catch_price = get_post_meta( $item['id'], 'ced_catch_custom_price', true );
		if ( $catch_price ) {
			return get_woocommerce_currency_symbol() . ' <b class="success_upload_on_catch">' . $catch_price . '</b>';
		}
		return get_woocommerce_currency_symbol() . '&nbsp<b class="success_upload_on_catch">' . $item['price'] . '</b>';
	}
	/**
	 *
	 * Function for product type column
	 */
	public function column_type( $item ) {
		$product = wc_get_product( $item['id'] );
		if ( ! is_object( $product ) ) {
			return;
		}
		$product_type = $product->get_type();
		return '<b>' . $product_type . '</b>';
	}
	/**
	 *
	 * Function for sku column
	 */
	public function column_sku( $item ) {
		return '<b>' . $item['sku'] . '</b>';
	}
	/**
	 *
	 * Function for image column
	 */
	public function column_image( $item ) {
		return '<img height="50" width="50" src="' . $item['image'] . '">';
	}

	/**
	 *
	 * Function for reference number
	 */
	public function column_referenceNo( $item ) {
		$referenceNo = get_post_meta( $item['id'], '_wpm_gtin_code', true );
		if ( '' != $referenceNo ) {
			echo '<b>' . esc_attr( $referenceNo ) . '</b>';
		}
	}

	public function column_status( $item ) {
		$shop_id        = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$wpuploadDir    = wp_upload_dir();
		$baseDir        = $wpuploadDir['basedir'];
		$uploadDir      = $baseDir . '/cedcommerce/catch/feeds/Product';
		$ImportIds      = get_option( 'ced_catch_import_ids_' . $shop_id, array() );
		$ImportIds      = array_reverse( $ImportIds );
		$product_status = get_post_meta( $item['id'], 'ced_catch_product_on_catch_' . $shop_id, true );

		$pro_sync_Status = get_post_meta( $item['id'], 'ced_catch_product_sync_by_identifier_' . $shop_id, true );

		$ced_catch_validation_error = get_post_meta( $item['id'], 'ced_catch_validation_error', true );
		$r                          = false;

		$prod_data = wc_get_product( $item['id'] );
		if ( ! is_object( $prod_data ) ) {
			return;
		}

		$type              = $prod_data->get_type();
		$ced_status        = '';
		$ced_second_status = '';
		if ( $product_status || ! empty( $pro_sync_Status ) ) {
			if ( 'yes' == $pro_sync_Status ) {
				$ced_status = '<b><a>Synced</a></b>';
			}
			if ( $product_status ) {
				$ced_status = '<b><a>Uploaded</a></b>';
			}
			print_r( $ced_status );
		} else {
			$status_reason      = get_post_meta( $item['id'], 'ced_catch_uploaded_product_status_reason' . $shop_id, true );
			$status             = get_post_meta( $item['id'], 'ced_catch_uploaded_product_status' . $shop_id, true );
			$ced_status        .= '<b>' . str_replace( '"', '', $status_reason ) . '</b>';
			$ced_second_status .= '<b>' . $status . '</b>';

			if ( ! empty( $status_reason ) ) {
				delete_post_meta( $item['id'], 'ced_catch_validation_error' );
				print_r( $ced_status );
			} elseif ( ! empty( $status ) ) {
				delete_post_meta( $item['id'], 'ced_catch_validation_error' );
				print_r( $ced_second_status );
			} else {
				echo '<b>Not Uploaded</b>';
				echo '<div class="ced_catch_error_data catch-hidden"><ul>';
				if ( ! empty( $ced_catch_validation_error ) ) {
					$show_validation_error = '<label data-shopid="' . $shop_id . '" data-id="' . $item['id'] . '" class="ced_catch_popup ced_catch_brand">View Errors</label><div id="myModal" class="modal"><div class="modal-content"></div></div>';
					print_r( $show_validation_error );
				}
				echo '</ul></div>';
			}
		}
	}
	/**
	 * Associative array of columns
	 *
	 * @return array
	 */

	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( 'Product Image', 'woocommerce-catch-integration' ),
			'name'     => __( 'Product Name', 'woocommerce-catch-integration' ),
			'type'     => __( 'Product Type', 'woocommerce-catch-integration' ),
			'price'    => __( 'Product Price', 'woocommerce-catch-integration' ),
			'profile'  => __( 'Profile Assigned', 'woocommerce-catch-integration' ),
			'sku'      => __( 'Product Sku', 'woocommerce-catch-integration' ),
			'stock'    => __( 'Product Stock', 'woocommerce-catch-integration' ),
			'category' => __( 'Woo Category', 'woocommerce-catch-integration' ),
			'status'   => __( 'Product Status', 'woocommerce-catch-integration' ),
		);
		$columns = apply_filters( 'ced_catch_alter_product_table_columns', $columns );
		return $columns;
	}


	/**
	 * Function to return count of total products to make sortable.
	 *
	 * @return array
	 */

	public function get_count( $per_page, $page_number ) {
		$args = $this->GetFilteredData( $per_page, $page_number );
		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) ) {
			$args = $args;
		} else {
			$args = array( 'post_type' => 'product' );
		}
		$loop         = new WP_Query( $args );
		$product_data = $loop->posts;
		$product_data = $loop->found_posts;

		return $product_data;
	}

	public function GetFilteredData( $per_page, $page_number ) {
		$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		if ( isset( $_GET['status_sorting'] ) || isset( $_GET['pro_cat_sorting'] ) || isset( $_GET['pro_type_sorting'] ) || isset( $_GET['searchBy'] ) || isset( $_GET['pro_tag_sorting'] ) || isset( $_GET['pro_stock_sorting'] ) ) {
			if ( ! empty( $_REQUEST['pro_cat_sorting'] ) ) {
				$pro_cat_sorting = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';
				if ( '' != $pro_cat_sorting ) {
					$selected_cat          = array( $pro_cat_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_cat';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_cat;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( ! empty( $_REQUEST['pro_tag_sorting'] ) ) {
				$pro_tag_sorting = isset( $_GET['pro_tag_sorting'] ) ? sanitize_text_field( $_GET['pro_tag_sorting'] ) : '';
				if ( '' != $pro_tag_sorting ) {
					$selected_tag          = array( $pro_tag_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_tag';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_tag;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( ! empty( $_REQUEST['pro_post_status'] ) ) {
				$pro_post_status      = isset( $_GET['pro_post_status'] ) ? sanitize_text_field( $_GET['pro_post_status'] ) : '';
				$selected_post_status = $pro_post_status;
				$args['post_status']  = $selected_post_status;
			}

			if ( ! empty( $_REQUEST['pro_type_sorting'] ) ) {
				$pro_type_sorting = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( $_GET['pro_type_sorting'] ) : '';
				if ( '' != $pro_type_sorting ) {
					$selected_type         = array( $pro_type_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_type';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_type;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( ! empty( $_REQUEST['status_sorting'] ) ) {
				$status_sorting = isset( $_GET['status_sorting'] ) ? sanitize_text_field( $_GET['status_sorting'] ) : '';
				if ( '' != $status_sorting ) {
					$meta_query = array();
					if ( 'Uploaded' == $status_sorting ) {
						$args['orderby'] = 'meta_value_num';
						$args['order']   = 'ASC';

						$meta_query[] = array(
							'key'     => 'ced_catch_product_on_catch_' . $shop_id,
							'compare' => 'EXISTS',
						);
					} elseif ( 'NotUploaded' == $status_sorting ) {
						$meta_query[] = array(
							'key'     => 'ced_catch_product_on_catch_' . $shop_id,
							'compare' => 'NOT EXISTS',
						);
					} elseif ( 'PENDING' == $status_sorting ) {
						$meta_query[] = array(
							'relation' => 'AND',
							array(
								'key'     => 'ced_catch_product_on_catch_' . $shop_id,
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'ced_catch_uploaded_product_status' . $shop_id,
								'value'   => $status_sorting,
								'compare' => '=',
							),
						);
					}
					$args['meta_query'] = $meta_query;
				}
			}

			if ( ! empty( $_REQUEST['product_synced_status'] ) ) {
				$product_synced_status = isset( $_GET['product_synced_status'] ) ? sanitize_text_field( $_GET['product_synced_status'] ) : '';
				if ( '' != $product_synced_status ) {
					if ( 'yes' == $product_synced_status ) {
						$args['orderby'] = 'meta_value_num';
						$args['order']   = 'ASC';

						$meta_query[] = array(
							'key'     => 'ced_catch_product_sync_by_identifier_' . $shop_id,
							'compare' => 'EXISTS',
						);
					} elseif ( 'no' == $product_synced_status ) {
						$meta_query[] = array(
							'key'     => 'ced_catch_product_sync_by_identifier_' . $shop_id,
							'compare' => 'NOT EXISTS',
						);
					}
					$args['meta_query'] = $meta_query;
				}
			}

			if ( ! empty( $_REQUEST['pro_stock_sorting'] ) ) {
				$pro_stock_sorting     = isset( $_GET['pro_stock_sorting'] ) ? sanitize_text_field( $_GET['pro_stock_sorting'] ) : '';
				$selected_stock_status = array( $pro_stock_sorting );
				$meta_query[]          = array(
					'key'   => '_stock_status',
					'value' => $selected_stock_status,
				);
				$args['meta_query']    = $meta_query;
			}

			if ( ! empty( $_REQUEST['searchBy'] ) ) {
				$searchBy = isset( $_GET['searchBy'] ) ? sanitize_text_field( $_GET['searchBy'] ) : '';
				if ( '' != $searchBy ) {
					$meta_query         = array();
					$args['search_value'] = $searchBy;
				}
			}

			$args['post_type']      = 'product';
			$args['posts_per_page'] = $per_page;
			$args['paged']          = $page_number;
			return $args;
		}

	}
	/**
	 *
	 * Render bulk actions
	 */

	protected function bulk_actions( $which = '' ) {
		if ( 'top' == $which ) :
			if ( is_null( $this->_actions ) ) {
				$this->_actions = $this->get_bulk_actions();
				/**
				 * Filters the list table Bulk Actions drop-down.
				 *
				 * The dynamic portion of the hook name, `$this->screen->id`, refers
				 * to the ID of the current screen, usually a string.
				 *
				 * This filter can currently only be used to remove bulk actions.
				 *
				 * @since 3.5.0
				 *
				 * @param array $actions An array of the available bulk actions.
				 */
				$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
				$two            = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_attr( 'Select bulk action' ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-action-selector ">';
			echo '<option value="-1">' . esc_attr( 'Bulk Actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_catch_bulk_operation' ) );
			echo "\n";
		endif;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'upload_product'            => __( 'Upload/Update Products', 'woocommerce-catch-integration' ),
			'upload_offer'              => __( 'Upload Offers/Update Inventory', 'woocommerce-catch-integration' ),
			'upload_offer_with_product' => __( 'Upload Offer With Product', 'woocommerce-catch-integration' ),
			'remove_offer'              => __( 'Remove Offers', 'woocommerce-catch-integration' ),
		);
		return $actions;
	}
	/**
	 *
	 * Function for rendering html
	 */
	public function renderHTML() {
		?>
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
						<li><?php echo esc_html_e( 'This section lets you perform multiple operation such as Upload/Update product from woocommerce to catch.In order to perform any operation from the Bulk Actions dropdown you need to select the product using the checkbox on the left side in the product list column and hit Apply button.You will get the notification for each performed operation.' ); ?></li>
						 <li><b><?php echo esc_html_e( 'Here you can perform the following tasks : Upload/Update Product, Upload Offers/Update Inventory, Upload Offers with Products, Remove Offers' ); ?></b></li>
						<li><?php echo esc_html_e( 'You can also filter out the product on the basis of category , type, stock, tag and catch status.' ); ?></li>
						<li>
						<?php
						echo esc_html_e(
							'The Search Product option lets you find product using product sku.
						'
						);
						?>
						</li>
						<li>
						<?php
						echo esc_html_e(
							'Once the product is successfuly uploaded on catch you will have the product status uploaded.
						'
						);
						?>
						</li>
						<li><b>
						<?php
						echo esc_html_e(
							'Important : .
						'
						);
						?>
						</b></li>
						<li>
						<?php
						echo esc_html_e(
							'Filename for param file is required :- Please map required attribute at profile section.
						'
						);
						?>
						</li>
						<li>
						<?php
						echo esc_html_e(
							'After Uploading products successfuly you will need wait for approval from catch team. If products approved then you will need to upload offers for those products using upload offers/update inventory bulk action.'
						);
						?>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable ced_catch_category_mapping_wrapper">
					<?php
					$status_actions = array(
						'Uploaded'    => __( 'Uploaded', 'woocommerce-catch-integration' ),
						'NotUploaded' => __( 'Not Uploaded', 'woocommerce-catch-integration' ),
						'PENDING'     => __( 'Pending Approval', 'woocommerce-catch-integration' ),
					);
					$stock_actions  = array(
						'instock'    => __( 'instock', 'woocommerce-catch-integration' ),
						'outofstock' => __( 'outofstock', 'woocommerce-catch-integration' ),
					);
					$post_status    = array(
						'draft'   => __( 'draft', 'woocommerce-catch-integration' ),
						'publish' => __( 'publish', 'woocommerce-catch-integration' ),
					);

					$status_sync_by_identifier = array(
						'yes' => __( 'Yes', 'woocommerce-catch-integration' ),
						'no'  => __( 'No', 'woocommerce-catch-integration' ),
					);

					$product_types = get_terms( 'product_type', array( 'hide_empty' => false ) );
					$temp_array    = array();
					foreach ( $product_types as $key => $value ) {
						if ( 'simple' == $value->name || 'variable' == $value->name ) {
							$temp_array_type[ $value->term_id ] = ucfirst( $value->name );
						}
					}
					$product_types      = $temp_array_type;
					$product_categories = get_terms( 'product_cat', array( 'hide_empty' => false ) );

					$product_tag = get_terms( 'product_tag', array( 'hide_empty' => false ) );

					$temp_array_tag = array();
					foreach ( $product_tag as $key => $value ) {
						$temp_array_tag[ $value->term_id ] = $value->name;
					}
					$product_tag = $temp_array_tag;

					$temp_array = array();
					foreach ( $product_categories as $key => $value ) {
						$temp_array[ $value->term_id ] = $value->name;
					}
					$product_categories = $temp_array;

					$previous_selected_status = isset( $_GET['status_sorting'] ) ? sanitize_text_field( $_GET['status_sorting'] ) : '';

					$previous_synced_status = isset( $_GET['product_synced_status'] ) ? sanitize_text_field( $_GET['product_synced_status'] ) : '';

					$previous_selected_type  = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( $_GET['pro_type_sorting'] ) : '';
					$previous_selected_stock = isset( $_GET['pro_stock_sorting'] ) ? sanitize_text_field( $_GET['pro_stock_sorting'] ) : '';
					$previous_selected_tag   = isset( $_GET['pro_tag_sorting'] ) ? sanitize_text_field( $_GET['pro_tag_sorting'] ) : '';
					$previous_post_status    = isset( $_GET['pro_post_status'] ) ? sanitize_text_field( $_GET['pro_post_status'] ) : '';
					echo '<div class="ced_catch_wrap">';
					echo '<form method="post" action="">';
					echo '<input type="hidden" id="nonce_verification_filter" name="nonce_verification_filter" value="' . esc_attr( wp_create_nonce( 'nonce_verification_filter' ) ) . '"/>';
					echo '<div class="ced_catch_top_wrapper">';
					echo '<select name="status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Status', 'woocommerce-catch-integration' ) . '</option>';
					foreach ( $status_actions as $name => $title ) {
						$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';

					echo '<select name="product_synced_status" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Synced Product', 'woocommerce-catch-integration' ) . '</option>';
					foreach ( $status_sync_by_identifier as $name => $title ) {
						$selectedStatus = ( $previous_synced_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';

					$previous_selected_cat = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';

					$dropdown_cat_args = array(
						'name'            => 'pro_cat_sorting',
						'show_count'      => 1,
						'hierarchical'    => 1,
						'depth'           => 10,
						'taxonomy'        => 'product_cat',
						'class'           => 'select_boxes_product_page',
						'selected'        => $previous_selected_cat,
						'show_option_all' => 'Product Category',
					);
					wp_dropdown_categories( $dropdown_cat_args );

					echo '<select name="pro_type_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Type', 'woocommerce-catch-integration' ) . '</option>';
					foreach ( $product_types as $name => $title ) {
						$selectedType = ( $previous_selected_type == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';
					echo '<select name="pro_stock_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Stock', 'woocommerce-catch-integration' ) . '</option>';
					foreach ( $stock_actions as $name => $title ) {
						$selectedType = ( $previous_selected_stock == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';
					echo '<select name="pro_tag_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Tag', 'woocommerce-catch-integration' ) . '</option>';
					foreach ( $product_tag as $name => $title ) {

						$selectedType = ( $previous_selected_tag == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';

					submit_button( __( 'Filter', 'ced-catch' ), 'action', 'filter_button', false, array() );
					echo '</div>';
					$this->search_box( 'Search Products', 'search_product_id', 'search_product' );
					echo '</form>';
					echo '</div>';

					?>
					<form method="post">
						<input type="hidden" id="nonce_verification" name="nonce_verification" value="<?php echo esc_attr( wp_create_nonce( 'nonce_verification' ) ); ?>"/>	
						<?php
						$this->display();
						?>
					</form>

				</div>
			</div>
			<div class="clear"></div>
		</div>
		<div class="ced_catch_preview_product_popup_main_wrapper"></div>
		<?php
	}
}

$ced_catch_products_obj = new CatchListProducts();
$ced_catch_products_obj->prepare_items();
