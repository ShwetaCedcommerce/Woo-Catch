(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	 var ajaxUrl   = ced_catch_admin_obj.ajax_url;
	 var ajaxNonce = ced_catch_admin_obj.ajax_nonce;
	 var shop_id   = ced_catch_admin_obj.shop_id;
	 var parsed_response;
	 /*-----------------------Pop Up on Clicking Add Account In Account Section---------------*/

	$( document ).on(
		'change',
		'.ced_catch_sync_existing_products',
		function(){
			var is_sync_enabled = $( this ).val();
			is_sync_enabled     = jQuery.trim( is_sync_enabled );
			if (is_sync_enabled == 'yes') {
				$( ".ced_catch_auto_sync_existing_products" ).css( 'display','contents' );
			} else {
				$( ".ced_catch_auto_sync_existing_products" ).css( 'display','none' );
			}
		}
	);

	$(document).ready(function(){
        $("#search_product_id-search-input").attr("placeholder","Enter Product Id, Title Or SKU");
    });

	$( document ).on(
		'click',
		'.ced_catch_popup',
		function(e){
			$( this ).next().toggle();
			$( '.ced_catch_loader' ).show();
			var prod_id = $( this ).attr( 'data-id' );
			var shop_id = $( this ).attr( 'data-shopid' );
			$.ajax(
				{
					url : ajaxUrl,
					type : 'post',
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_display_product_status',
						shop_id:shop_id,
						prod_id:prod_id
					},
					//dataType : 'json',
					success: function(response)
				{
						$( '.ced_catch_loader' ).hide();
						$( '.modal-content' ).html( response );
					}
				}
			);
		}
	);

	$( document ).on(
		'click',
		'.close',
		function(e){
			// $(".ced_catch_bid_data").hide();
			$( '.modal' ).hide();
		}
	);

	$( document ).on(
		'click',
		'.ced_catch_parent_element',
		function(){
			if ($( this ).find( '.ced_catch_instruction_icon' ).hasClass( "dashicons-arrow-down-alt2" )) {
				$( this ).find( '.ced_catch_instruction_icon' ).removeClass( "dashicons-arrow-down-alt2" );
				$( this ).find( '.ced_catch_instruction_icon' ).addClass( "dashicons-arrow-up-alt2" );
			} else if ($( this ).find( '.ced_catch_instruction_icon' ).hasClass( "dashicons-arrow-up-alt2" )) {
				$( this ).find( '.ced_catch_instruction_icon' ).addClass( "dashicons-arrow-down-alt2" );
				$( this ).find( '.ced_catch_instruction_icon' ).removeClass( "dashicons-arrow-up-alt2" );
			}
			$( this ).next( '.ced_catch_child_element' ).slideToggle( 'slow' );
		}
	);

	$( document ).on(
		'click',
		"#ced_catch_update_product_attributes",
		function(){
			$( '.ced_catch_loader' ).show();
			var shop_id = $( this ).attr( 'data-id' );
			$.ajax(
				{
					url : ajaxUrl,
					type : 'post',
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_update_product_attributes',
						shop_id:shop_id
					},
					//dataType : 'json',
					success: function(response)
				{
						$( '.ced_catch_loader' ).hide();
						if (response != '') {
							var notice = "";
							notice    += "<div class='notice'><p>" + response + "</p></div>";
							$( ".success-admin-notices" ).html( notice );
						}
						window.setTimeout( function(){window.location.reload()}, 1000 );
					}
				}
			);

		}
	);

	$( document ).on(
		'click',
		'.ced_catch_shipment_tracking',
		function(){
			var ced_catch_carrier_code    = $( '#ced_catch_carrier_code' ).val();
			var ced_catch_carrier_name    = $( '#ced_catch_carrier_name' ).val();
			var ced_catch_carrier_url     = $( '#ced_catch_carrier_url' ).val();
			var ced_catch_tracking_number = $( '#ced_catch_tracking_number' ).val();
			var order_id                  = $( this ).data( 'order_id' );
			$( '.ced_catch_loader' ).show();
			$.ajax(
				{
					url : ajaxUrl,
					type : 'post',
					data : {
						ajax_nonce:ajaxNonce,
						action : 'ced_catch_shipment_tracking',
						ced_catch_carrier_code : ced_catch_carrier_code,
						ced_catch_carrier_name :ced_catch_carrier_name,
						ced_catch_carrier_url : ced_catch_carrier_url,
						ced_catch_tracking_number : ced_catch_tracking_number,
						order_id :order_id,
					},
					dataType : 'json',
					success : function(response) {
						$( '.ced_catch_loader' ).hide();
						if (response != '') {
							var notice = "";
							notice    += "<div class='notice notice-success'><p>" + response + "</p></div>";
							$( ".admin_notice" ).html( notice );
						}
					}
				}
			);
		}
	);

	$( document ).on(
		'change',
		'#ced_catch_map_order_status',
		function(e) {

			var ced_catch_order_status = $( this ).attr( 'data-catch-order-status' );
			var woo_order_status       = $( this ).val();
			$( '.ced_catch_loader' ).show();
			$.ajax(
				{
					url : ajaxUrl,
					type : 'post',
					data : {
						ajax_nonce:ajaxNonce,
						action : 'ced_catch_map_order_status',
						woo_order_status:woo_order_status,
						ced_catch_order_status:ced_catch_order_status,
					},
					success : function(response) {
						$( '.ced_catch_loader' ).hide();
						if (response != '') {
							var notice = "";
							notice    += "<div class='notice notice-success'><p>" + response + "</p></div>";
						}

					}
				}
			);
		}
	);

	$( document ).on(
		'keyup' ,
		'#ced_catch_search_product_name' ,
		function() {
			var keyword = $( this ).val();
			if ( keyword.length < 3 ) {
				var html = '';
				html    += '<li>Please enter 3 or more characters.</li>';
				$( document ).find( '.ced-catch-search-product-list' ).html( html );
				$( document ).find( '.ced-catch-search-product-list' ).show();
				return;
			}
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						keyword : keyword,
						action : 'ced_catch_search_product_name',
					},
					type:'POST',
					success : function( response ) {
						parsed_response = jQuery.parseJSON( response );
						$( document ).find( '.ced-catch-search-product-list' ).html( parsed_response.html );
						$( document ).find( '.ced-catch-search-product-list' ).show();
					},
					error : function( error ) {

					}
				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'.ced_catch_searched_product' ,
		function() {
			$( '.ced_catch_loader' ).show();
			var post_id = $( this ).data( 'post-id' );
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						post_id : post_id,
						action : 'ced_catch_get_product_metakeys',
					},
					type:'POST',
					success : function( response ) {
						$( '.ced_catch_loader' ).hide();
						parsed_response = jQuery.parseJSON( response );
						$( document ).find( '.ced-catch-search-product-list' ).hide();
						$( ".ced_catch_render_meta_keys_content" ).html( parsed_response.html );
						$( ".ced_catch_render_meta_keys_content" ).show();
					}
				}
			);
		}
	);

	$( document ).on(
		'change',
		'.ced_catch_meta_key',
		function(){
			$( '.ced_catch_loader' ).show();
			var metakey = $( this ).val();
			var operation;
			if ( $( this ).is( ':checked' ) ) {
				operation = 'store';
			} else {
				operation = 'remove';
			}

			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_process_metakeys',
						metakey : metakey ,
						operation : operation,
					},
					type : 'POST',
					success: function(response)
				{
						$( '.ced_catch_loader' ).hide();
					}
				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'.ced_catch_navigation' ,
		function() {
			$( '.ced_catch_loader' ).show();
			var page_no = $( this ).data( 'page' );
			$( '.ced_catch_metakey_body' ).hide();
			window.setTimeout( function() {$( '.ced_catch_loader' ).hide()},500 );
			$( document ).find( '.ced_catch_metakey_list_' + page_no ).show();
		}
	);

	$( document ).on(
		'click',
		'.ced_catch_add_account_button',
		function(){

			$( document ).find( '.ced_catch_add_account_popup_main_wrapper' ).addClass( 'show' );

		}
	);
	$( document ).on(
		'click',
		'.ced_catch_add_account_popup_close',
		function(){

			$( document ).find( '.ced_catch_add_account_popup_main_wrapper' ).removeClass( 'show' );

		}
	);
	$( document ).on(
		'click',
		'.ced_catch_profile_popup_close',
		function(){

			$( document ).find( '.ced_catch_preview_product_popup_main_wrapper' ).removeClass( 'show' );

		}
	);

	$( document ).on(
		'click',
		'#ced_catch_authorise_account_button',
		function(){

			var ApiKey        = $( '.ced_catch_auth_input' ).val();
			var OperationMode = jQuery.trim( $( '#ced_catch_operation_mode' ).val() );
			$( document ).find( '.ced_catch_preview_product_popup_main_wrapper' ).removeClass( 'show' );

			if (ApiKey == "") {
				$( ".ced_catch_auth_input" ).attr( 'style','border:0.5px solid red' );return;
			}
			if (OperationMode == "") {
				$( "#ced_catch_operation_mode" ).attr( 'style','border:0.5px solid red' );return;
			} else {
				$( ".ced_catch_auth_input" ).removeAttr( 'style' );
				$( "#ced_catch_operation_mode" ).removeAttr( 'style' );
				$( document ).find( '.ced_catch_add_account_popup_main_wrapper' ).removeClass( 'show' );
			}
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_authorise_account',
						ApiKey:ApiKey,
					},
					type : 'POST',
					success: function(response)
				{
						var response = jQuery.parseJSON( response );
						var message  = jQuery.trim( response.msg );
						if (response.status == 400) {
							var notice = "";
							notice    += "<div class='notice notice-error'><p>" + message + "</p></div>";
							$( ".manage_labels" ).append( notice );
						}
						window.setTimeout( function(){window.location.reload()}, 1000 );
					}
				}
			);

		}
	);

	$( document ).on(
		'click',
		'#ced_catch_category_refresh_button',
		function(){

			$( '.ced_catch_loader' ).show();
			var store_id = $( this ).attr( 'data-shop_id' );
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_category_refresh_button',
						store_id:store_id
					},
					type : 'POST',
					success: function(response)
				{
						$( '.ced_catch_loader' ).hide();
						var response  = jQuery.parseJSON( response );
						var response1 = jQuery.trim( response.message );
						if (response1 == "Shop is Not Active") {
							var notice = "";
							notice    += "<div class='notice notice-error'><p>Currently Shop is not Active . Please activate your Shop in order to refresh categories.</p></div>";
							$( ".success-admin-notices" ).append( notice );
							return;
						} else if (response.status == 400) {
							var notice = "";
							notice    += "<div class='notice notice-error'><p>" + response1 + "</p></div>";
							$( ".success-admin-notices" ).append( notice );
							return;
						} else {
							var notice = "";
							notice    += "<div class='notice notice-success'><p>" + response1 + "</p></div>";
							$( ".success-admin-notices" ).append( notice );
							window.setTimeout( function(){window.location.reload()}, 3000 );
						}
					}
				}
			);

		}
	);

	$( document ).on(
		'change',
		'.ced_catch_select_store_category_checkbox',
		function(){
			var store_category_id = $( this ).attr( 'data-categoryID' );
			if ( $( this ).is( ':checked' ) ) {
				$( '#ced_catch_categories_' + store_category_id ).show( 'slow' );
			} else {
				$( '#ced_catch_categories_' + store_category_id ).hide( 'slow' );
			}
		}
	);

	$( document ).on(
		'change',
		'.ced_catch_select_category',
		function(){

			var store_category_id            = $( this ).attr( 'data-storeCategoryID' );
			var catch_store_id               = $( this ).attr( 'data-catchStoreId' );
			var selected_catch_category_id   = $( this ).val();
			var selected_catch_category_name = $( this ).find( "option:selected" ).text();
			var level                        = $( this ).attr( 'data-level' );
			if (selected_catch_category_name != '--Select--') {
				$( this ).css( 'border-color',"green" );
			}

			console.log( selected_catch_category_name );
			if ( level != '8' ) {
				$( '.ced_catch_loader' ).show();
				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce : ajaxNonce,
							action : 'ced_catch_fetch_next_level_category',
							level : level,
							name : selected_catch_category_name,
							id : selected_catch_category_id,
							store_id : store_category_id,
							catch_store_id : catch_store_id,
						},
						type : 'POST',
						success: function(response)
					{
							$( '.ced_catch_loader' ).hide();
							if ( response != 'No-Sublevel' ) {
								for (var i = 1; i < 8; i++) {
									$( '#ced_catch_categories_' + store_category_id ).find( '.ced_catch_level' + (parseInt( level ) + i) + '_category' ).closest( "td" ).remove();
								}
								if (response != 0 && selected_catch_category_id != "" ) {
									$( '#ced_catch_categories_' + store_category_id ).append( response );
								}
							} else {
								$( '#ced_catch_categories_' + store_category_id ).find( '.ced_catch_level' + (parseInt( level ) + 1) + '_category' ).remove();
							}
						}
					}
				);
			}

		}
	);

	$( document ).on(
		'click',
		'.ced_catch_instruction_icon',
		function(e){
			$( '.ced_catch_display_product_specific' ).css( 'display','block' );
		}
	);

	$( document ).on(
		'click',
		'.ced_catch_offer_instruction_icon',
		function(e){
			$( '.ced_catch_display_offer_specific' ).css( 'display','block' );
		}
	);

	$( document ).on(
		'click',
		'#ced_catch_save_category_button',
		function(){

			var  catch_category_array   = [];
			var  store_category_array   = [];
			var  catch_category_name    = [];
			var catch_store_id          = $( this ).attr( 'data-catchStoreID' );
			var level                   = [];
			var cat_level               = [];
			var category_value          = '';
			var store_category_ids      = [];
			var store_category_id       = '';
			var selected_catch_category = '';

			jQuery( '.ced_catch_select_store_category_checkbox' ).each(
				function(key) {
					if ( jQuery( this ).is( ':checked' ) ) {
						var store_category_id          = $( this ).attr( 'data-categoryID' );
						cat_level                      = $( '#ced_catch_categories_' + store_category_id ).find( "td:last" ).attr( 'data-catlevel' );
						var selected_catch_category_id = $( '#ced_catch_categories_' + store_category_id ).find( '.ced_catch_level' + cat_level + '_category' ).val();

						if ( selected_catch_category_id == '' || selected_catch_category_id == null ) {
							selected_catch_category_id = $( '#ced_catch_categories_' + store_category_id ).find( '.ced_catch_level' + (parseInt( cat_level ) - 1) + '_category' ).val();
						}
						var category_name = '';
						$( '#ced_catch_categories_' + store_category_id ).find( 'select' ).each(
							function(key1){
								category_name += $( this ).find( "option:selected" ).text() + ' --> ';
								category_value = category_name;
							}
						);

						var name_len = 0;
						if ( selected_catch_category_id != '' && selected_catch_category_id != null ) {
							catch_category_array.push( selected_catch_category_id );
							store_category_array.push( store_category_id );

							name_len      = category_name.length;
							category_name = category_name.substring( 0, name_len - 5 );
							category_name = category_name.trim();
							name_len      = category_name.length;
							if ( category_name.lastIndexOf( '--Select--' ) > 0 ) {
								category_name = category_name.trim();
								category_name = category_name.replace( '--Select--', '' );
								name_len      = category_name.length;
								category_name = category_name.substring( 0, name_len - 5 );
							}
							name_len = category_name.length;

							catch_category_name.push( category_name );
						}
					}
				}
			);
			var n = category_value.search( '--Select--' );
			$( '.ced_catch_loader' ).show();
			if (n < 0) {
				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce : ajaxNonce,
							action : 'ced_catch_map_categories_to_store',
							catch_category_array : catch_category_array,
							store_category_array : store_category_array,
							catch_category_name : catch_category_name,
							catch_store_id : catch_store_id,
						},
						type : 'POST',
						success: function(response)
					{
							$( '.ced_catch_loader' ).hide();
							var html = "<div class='notice notice-success'><p>Profile Created Successfully</p></div>";
							$( "#profile_create_message" ).html( html );
							$( 'html, body' ).animate(
								{
									scrollTop: parseInt( $( "body" ).offset().top )
								},
								2000
							);
							window.setTimeout( function(){window.location.reload()}, 2000 );
						}
					}
				);
			} else {
				$( '.ced_catch_loader' ).hide();
				var html = "<div class='notice notice-success'><p>Please select Category</p></div>";
				$( "#profile_create_message" ).html( html );
				$( 'html, body' ).animate(
					{
						scrollTop: parseInt( $( "body" ).offset().top )
					},
					2000
				);
			}
		}
	);

	$( document ).on(
		'change',
		"#ced_catch_operation_mode",
		function(){
			$( '.ced_catch_loader' ).show();
			var operationMode = $( "#ced_catch_operation_mode" ).val();
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_save_operation_mode',
						operationMode : operationMode,
					},
					type : 'POST',
					success: function(response)
				{
						$( '.ced_catch_loader' ).hide();
					}
				}
			);

		}
	);

	$( document ).on(
		'click',
		"#ced_catch_fetch_orders",
		function(){
			$( '.ced_catch_loader' ).show();
			var shop_id = $( this ).attr( 'data-id' );
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_manual_fetch_orders',
						shop_id:shop_id
					},
					type : 'POST',
					success: function(response)
				{
							 $( '.ced_catch_loader' ).hide();
							window.location.reload();
					}
						}
			);

		}
	);

		$( document ).on(
			'click',
			"#ced_catch_get_order",
			function(){
				$( '.ced_catch_loader' ).show();
				var shop_id  = $( this ).attr( 'data-id' );
				var order_id = $( "#ced_catch_order_id" ).val();
				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce : ajaxNonce,
							action : 'ced_catch_fetch_orders_with_specific_order_id',
							shop_id:shop_id,
							order_id:order_id
						},
						type : 'POST',
						success: function(response)
							{
								$( '.ced_catch_loader' ).hide();
								window.location.reload();
						}
						}
				);

			}
		);

	$( document ).on(
		'click',
		".ced_catch_process_order_button",
		function(){
			$( '.ced_catch_loader' ).show();
			var shop_id   = $( this ).attr( 'data-id' );
			var order_id  = $( this ).attr( 'data-OrderId' );
			var operation = $( this ).val();
			operation     = jQuery.trim( operation );
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_catch_process_catch_orders',
						shop_id:shop_id,
						order_id:order_id,
						operation:operation
					},
					type : 'POST',
					success: function(response)
				{
							 $( '.ced_catch_loader' ).hide();
					}
						}
			);

		}
	);

	$( document ).on(
		'click',
		'#ced_catch_bulk_operation',
		function(e){
			e.preventDefault();
			var operation = $( ".bulk-action-selector" ).val();
			if (operation <= 0 ) {
				var notice = "";
				notice    += "<div class='notice notice-error'><p>Please Select Operation To Be Performed</p></div>";
				$( ".success-admin-notices" ).append( notice );
			} else {
				var operation         = $( ".bulk-action-selector" ).val();
				var catch_products_id = new Array();
				$( '.catch_products_id:checked' ).each(
					function(){
						var prodIds   = $( this ).val();
						var profileId = $( this ).data( 'profileid' );

						if ( profileId != '' ) {
							var data = {
								profileId : profileId,
								prodIds: prodIds
							};
							catch_products_id.push( data );
						}
					}
				);
				performBulkAction( catch_products_id,operation );
			}

		}
	);

	function performBulkAction(catch_products_id,operation)
	 {
		if (catch_products_id == "") {
			var notice = "";
			notice    += "<div class='notice notice-error'><p>No Products Selected Or Profile Not Assigned</p></div>";
			$( ".success-admin-notices" ).append( notice );
		}
		$( '.ced_catch_loader' ).show();
		$.ajax(
			{
				url : ajaxUrl,
				data : {
					ajax_nonce : ajaxNonce,
					action : 'ced_catch_process_bulk_action',
					operation_to_be_performed : operation,
					id : catch_products_id,
					shopid:shop_id
				},
				type : 'POST',
				success: function(response)
			{
					$( '.ced_catch_loader' ).hide();
					var response  = jQuery.parseJSON( response );
					var response1 = jQuery.trim( response.message );
					if (response1 == "Shop is Not Active") {
						var notice = "";
						notice    += "<div class='notice notice-error'><p>Currently Shop is not Active . Please activate your Shop in order to perform operations.</p></div>";
						$( ".success-admin-notices" ).append( notice );
						return;
					} else if (response.status == 200) {
						var id               = response.prodid;
						var Response_message = jQuery.trim( response.message );
						var notice           = "";
						notice              += "<div class='notice notice-success'><p>" + response.message + "</p></div>";
						$( ".success-admin-notices" ).append( notice );
						if (Response_message == 'Product ' + id + ' Deleted Successfully') {
							$( "#" + id + "" ).html( '<b class="not_completed">Not Uploaded</b>' );
							$( "." + id + "" ).remove();
						} else if (response.unlist != '') {
							$( "#" + id + "" ).html( '<b class="relist_notify">UnListed</b>' );
							$( "." + id + "" ).remove();
						} else {
							$( "#" + id + "" ).html( '<b class="success_upload_on_catch">Uploaded</b>' );
						}

					} else if (response.status == 400) {
						var notice = "";
						notice    += "<div class='notice notice-error'><p>" + response.message + "</p></div>";
						$( ".success-admin-notices" ).append( notice );
					}
				}
			}
		);
	}
})( jQuery );
