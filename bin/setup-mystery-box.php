<?php
/**
 * Rulat prin: wp eval-file bin/setup-mystery-box.php
 * Idempotent – îl poți rula de mai multe ori.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}
if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce nu este activ.' );
}

function ea_log( $m ) {
	WP_CLI::log( '• ' . $m );
}

$s = wp_parse_args( get_option( 'ea_mb_settings', array() ), ea_mb_defaults() );

/* 1. Setări magazin ---------------------------------------------------- */
update_option( 'woocommerce_default_country', 'RO' );
update_option( 'woocommerce_currency', 'RON' );
update_option( 'woocommerce_currency_pos', 'right_space' );
update_option( 'woocommerce_price_decimal_sep', ',' );
update_option( 'woocommerce_price_thousand_sep', '.' );
update_option( 'woocommerce_allowed_countries', 'specific' );
update_option( 'woocommerce_specific_allowed_countries', array( 'RO' ) );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_calc_taxes', 'no' );
ea_log( 'Setări magazin: România / RON.' );

/* 2. Produsul variabil ------------------------------------------------- */
$labels  = array( 'm' => '4 Bărbați', 'f' => '4 Femei', 'x' => '2+2 Mix' );
$bundles = array( 1 => '1 cutie', 2 => '2 cutii', 3 => '3 cutii' );
$prices  = array( 1 => (float) $s['price1'], 2 => (float) $s['price2'], 3 => (float) $s['price3'] );

$pid     = (int) get_option( 'ea_mb_product_id' );
$product = $pid ? wc_get_product( $pid ) : null;
if ( ! $product || ! $product->is_type( 'variable' ) ) {
	$product = new WC_Product_Variable();
}
$product->set_name( 'Mystery Box' );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_short_description( 'Patru parfumuri arabești diferite, o singură surpriză. Alegi pentru cine e cutia.' );
$product->set_sku( 'ea-mystery-box' );
$product->set_manage_stock( true );
$product->set_stock_quantity( 500 );

$a_type = new WC_Product_Attribute();
$a_type->set_name( 'Tip cutie' );
$a_type->set_options( array_values( $labels ) );
$a_type->set_position( 0 );
$a_type->set_visible( true );
$a_type->set_variation( true );
$a_pack = new WC_Product_Attribute();
$a_pack->set_name( 'Pachet' );
$a_pack->set_options( array_values( $bundles ) );
$a_pack->set_position( 1 );
$a_pack->set_visible( true );
$a_pack->set_variation( true );
$product->set_attributes( array( $a_type, $a_pack ) );
$product->set_default_attributes( array( 'tip-cutie' => $labels['x'], 'pachet' => $bundles[1] ) );
$pid = $product->save();
update_option( 'ea_mb_product_id', $pid );

// Variații existente → map după meta.
$existing = array();
foreach ( $product->get_children() as $vid ) {
	$v = wc_get_product( $vid );
	$existing[ $v->get_meta( '_ea_choice' ) . '|' . $v->get_meta( '_ea_bundle' ) ] = $v;
}
foreach ( $labels as $ck => $cl ) {
	foreach ( $bundles as $bn => $bl ) {
		$v = $existing[ $ck . '|' . $bn ] ?? new WC_Product_Variation();
		$v->set_parent_id( $pid );
		$v->set_attributes( array( 'tip-cutie' => $cl, 'pachet' => $bl ) );
		$v->set_regular_price( $prices[ $bn ] );
		$v->set_status( 'publish' );
		$v->set_manage_stock( false );
		$v->set_stock_status( 'instock' );
		$v->update_meta_data( '_ea_choice', $ck );
		$v->update_meta_data( '_ea_bundle', $bn );
		$v->save();
	}
}
WC_Product_Variable::sync( $pid );
ea_log( "Produs Mystery Box #$pid cu 9 variații ({$prices[1]}/{$prices[2]}/{$prices[3]})." );

/* Imagine produs din media library */
if ( ! $product->get_image_id() ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$src = EA_MB_DIR . '/assets/img/cutie-parfumuri-fara-fundal.png';
	$tmp = wp_tempnam( $src );
	copy( $src, $tmp );
	$att = media_handle_sideload( array( 'name' => 'cutie-parfumuri-fara-fundal.png', 'tmp_name' => $tmp ), $pid, 'Mystery Box Elixir Arabesc' );
	if ( ! is_wp_error( $att ) ) {
		$product->set_image_id( $att );
		$product->save();
		ea_log( "Imagine adăugată în Media Library (#$att)." );
	}
}

/* 4. Livrare ----------------------------------------------------------- */
$zone_id = (int) get_option( 'ea_mb_zone_id' );
$zone    = $zone_id ? new WC_Shipping_Zone( $zone_id ) : null;
if ( ! $zone || ! $zone->get_id() ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'România' );
	$zone->add_location( 'RO', 'country' );
	$zone->save();
	update_option( 'ea_mb_zone_id', $zone->get_id() );
}
$ids = get_option( 'ea_mb_shipping', array() );
$valid = array();
foreach ( $zone->get_shipping_methods() as $m ) {
	$valid[] = $m->get_instance_id();
}
$make = function ( $key, $type, $settings ) use ( &$ids, $zone, $valid ) {
	if ( empty( $ids[ $key ] ) || ! in_array( (int) $ids[ $key ], $valid, true ) ) {
		$ids[ $key ] = $zone->add_shipping_method( $type );
	}
	$opt = "woocommerce_{$type}_{$ids[$key]}_settings";
	update_option( $opt, array_merge( (array) get_option( $opt, array() ), $settings ) );
};
$make( 'courier', 'flat_rate', array( 'title' => 'Curier la adresă', 'tax_status' => 'none', 'cost' => (string) $s['courier_cost'] ) );
$make( 'easybox', 'flat_rate', array( 'title' => 'Locker easybox', 'tax_status' => 'none', 'cost' => (string) $s['easybox_cost'] ) );
$make( 'free', 'free_shipping', array( 'title' => 'Livrare gratuită', 'requires' => 'min_amount', 'min_amount' => (string) $s['price2'] ) );
update_option( 'ea_mb_shipping', $ids );
WC_Cache_Helper::get_transient_version( 'shipping', true );
ea_log( 'Livrare: curier ' . $s['courier_cost'] . ', easybox ' . $s['easybox_cost'] . ', gratuită ≥ ' . $s['price2'] . ' lei.' );

/* 5. Plăți: COD activ și primul ---------------------------------------- */
$cod = (array) get_option( 'woocommerce_cod_settings', array() );
$cod['enabled']     = 'yes';
$cod['title']       = 'Plata la livrare (ramburs)';
$cod['description'] = 'Plătești curierului, numerar sau card, când ai cutia în mână.';
update_option( 'woocommerce_cod_settings', $cod );
$order_opt = (array) get_option( 'woocommerce_gateway_order', array() );
$new = array( 'cod' => 0 );
$i = 1;
foreach ( $order_opt as $gw => $pos ) {
	if ( 'cod' !== $gw ) {
		$new[ $gw ] = $i++;
	}
}
update_option( 'woocommerce_gateway_order', $new );
ea_log( 'Ramburs (COD) activat și setat primul.' );

/* 6. Pagina ------------------------------------------------------------ */
$page = get_page_by_path( 'mystery-box' );
if ( ! $page ) {
	$page_id = wp_insert_post( array(
		'post_title'  => 'Mystery Box',
		'post_name'   => 'mystery-box',
		'post_type'   => 'page',
		'post_status' => 'publish',
	) );
} else {
	$page_id = $page->ID;
}
update_post_meta( $page_id, '_wp_page_template', EA_MB_TEMPLATE );
ea_log( 'Pagina Mystery Box: ' . get_permalink( $page_id ) );

/* 7. Setări implicite -------------------------------------------------- */
if ( false === get_option( EA_MB_OPTION ) ) {
	add_option( EA_MB_OPTION, ea_mb_defaults() );
	ea_log( 'Setări Mystery Box inițializate cu valorile prototipului.' );
}

flush_rewrite_rules();
WP_CLI::success( 'Mystery Box este configurat. Pagina: ' . get_permalink( $page_id ) );
