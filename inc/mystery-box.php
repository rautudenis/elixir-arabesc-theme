<?php
/**
 * Mystery Box: setări, enqueue, endpoint comandă rapidă.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/order-endpoint.php';
require_once __DIR__ . '/legal.php';

const EA_MB_TEMPLATE = 'page-templates/mystery-box.php';

function ea_mb_is_landing() {
	return is_page() && get_page_template_slug() === EA_MB_TEMPLATE;
}

/** Pagini cu designul landing-ului: landing + paginile legale. */
function ea_mb_is_shell() {
	return ea_mb_is_landing() || ea_is_legal();
}

/**
 * Assets: doar CSS/JS-ul landing-ului; stilurile temei/pluginurilor sunt scoase de pe această pagină
 * ca designul să rămână fidel prototipului.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! ea_mb_is_shell() ) {
		return;
	}

	$styles = wp_styles();
	foreach ( (array) $styles->queue as $handle ) {
		if ( in_array( $handle, array( 'admin-bar', 'dashicons' ), true ) ) {
			continue;
		}
		wp_dequeue_style( $handle );
	}
	$scripts = wp_scripts();
	foreach ( (array) $scripts->queue as $handle ) {
		if ( preg_match( '/^(woodmart|wd-|elementor|swiper|photoswipe)/', $handle ) ) {
			wp_dequeue_script( $handle );
		}
	}

	wp_enqueue_style( 'ea-fonts', EA_MB_URI . '/assets/fonts/fonts.css', array(), EA_MB_VERSION );
	wp_enqueue_style( 'ea-mystery-box', EA_MB_URI . '/assets/mystery-box.css', array( 'ea-fonts' ), filemtime( EA_MB_DIR . '/assets/mystery-box.css' ) );
	wp_enqueue_script( 'ea-consent', EA_MB_URI . '/assets/consent.js', array(), filemtime( EA_MB_DIR . '/assets/consent.js' ), true );
	$set = ea_mb_settings();
	wp_add_inline_script( 'ea-consent', 'window.EA_TRACK=' . wp_json_encode( array(
		'meta'   => (string) ( $set['meta_pixel_id'] ?? '' ),
		'tiktok' => (string) ( $set['tiktok_pixel_id'] ?? '' ),
	) ) . ';', 'before' );
	wp_enqueue_script( 'ea-legal', EA_MB_URI . '/assets/legal.js', array(), filemtime( EA_MB_DIR . '/assets/legal.js' ), true );
	wp_add_inline_script( 'ea-legal', 'window.EA_LEGAL=' . wp_json_encode( array( 'endpoint' => esc_url_raw( rest_url( 'elixir/v1/withdrawal' ) ), 'tokenUrl' => esc_url_raw( rest_url( 'elixir/v1/token' ) ) ) ) . ';', 'before' );
	if ( ea_mb_is_landing() ) {
		wp_enqueue_script( 'ea-mystery-box', EA_MB_URI . '/assets/mystery-box.js', array(), filemtime( EA_MB_DIR . '/assets/mystery-box.js' ), true );
		wp_add_inline_script( 'ea-mystery-box', 'window.EA_MB=' . wp_json_encode( ea_mb_js_config() ) . ';', 'before' );
	}
}, 9999 );


/**
 * Pe landing scoatem hook-urile temei părinte (Woodmart): CSS, JS, bottom toolbar, side cart etc.
 * Ce vine din plugin-uri (pixeli, GA4, WooCommerce) rămâne neatins.
 */
add_action( 'template_redirect', function () {
	if ( ! ea_mb_is_shell() ) {
		return;
	}
	global $wp_filter;
	$parent = wp_normalize_path( get_template_directory() ) . '/';
	$hooks  = array( 'wp_enqueue_scripts', 'wp_head', 'wp_footer', 'wp_body_open', 'wp_print_styles', 'wp_print_scripts', 'wp_print_footer_scripts' );

	foreach ( $hooks as $hook ) {
		if ( empty( $wp_filter[ $hook ] ) ) {
			continue;
		}
		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $id => $cb ) {
				try {
					$fn = $cb['function'];
					if ( is_array( $fn ) ) {
						$ref = new ReflectionMethod( $fn[0], $fn[1] );
					} elseif ( is_string( $fn ) && strpos( $fn, '::' ) === false ) {
						$ref = new ReflectionFunction( $fn );
					} elseif ( $fn instanceof Closure ) {
						$ref = new ReflectionFunction( $fn );
					} else {
						continue;
					}
					$file = wp_normalize_path( (string) $ref->getFileName() );
				} catch ( Throwable $e ) {
					continue;
				}
				if ( strpos( $file, $parent ) === 0 ) {
					unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $id ] );
				}
			}
		}
	}
	add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
}, 999 );

add_filter( 'body_class', function ( $classes ) {
	return ea_mb_is_shell() ? array( 'ea-body' ) : $classes;
}, 9999 );

/** Configurația trimisă către JS. Totul vine din setări, nu din WooCommerce live. */
function ea_mb_js_config() {
	$s = ea_mb_settings();

	$toasts = array();
	foreach ( $s['toasts'] as $t ) {
		$toasts[] = array(
			'name'   => $t['name'],
			'city'   => $t['city'],
			'choice' => $t['choice'],
			'color'  => 'f' === $t['gender'] ? '#c27a6c' : '#8a5a30',
		);
	}

	// Întoarcere de la plata cu cardul: datele comenzii pentru mesajul de mulțumire din modal.
	$done  = null;
	$d_id  = isset( $_GET['ea_done'] ) ? absint( $_GET['ea_done'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$d_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$d_ord = ( $d_id && function_exists( 'wc_get_order' ) ) ? wc_get_order( $d_id ) : null;
	if ( $d_ord && 'ea_landing' === $d_ord->get_created_via() && hash_equals( $d_ord->get_order_key(), $d_key ) && $d_ord->has_status( array( 'processing', 'completed', 'on-hold' ) ) ) {
		$d_items = array();
		foreach ( $d_ord->get_items() as $it ) {
			$d_items[] = array( 'id' => $it->get_variation_id() ?: $it->get_product_id(), 'name' => $it->get_name(), 'price' => (float) $d_ord->get_item_total( $it ), 'qty' => $it->get_quantity() );
		}
		$done = array(
			'order_id'     => $d_ord->get_id(),
			'order_number' => $d_ord->get_order_number(),
			'total'        => (float) $d_ord->get_total(),
			'currency'     => $d_ord->get_currency(),
			'name'         => trim( $d_ord->get_billing_first_name() . ' ' . $d_ord->get_billing_last_name() ),
			'phone'        => $d_ord->get_billing_phone(),
			'choice'       => (string) $d_ord->get_meta( '_ea_box_type' ),
			'bundle'       => (int) $d_ord->get_meta( '_ea_bundle' ),
			'items'        => $d_items,
		);
	}

	// Plată cu cardul eșuată/anulată: reumplem formularul ca să poată încerca din nou.
	$fail  = null;
	$f_id  = isset( $_GET['ea_fail'] ) ? absint( $_GET['ea_fail'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$f_ord = ( $f_id && function_exists( 'wc_get_order' ) ) ? wc_get_order( $f_id ) : null;
	if ( $f_ord && 'ea_landing' === $f_ord->get_created_via() && hash_equals( $f_ord->get_order_key(), $d_key ) && ! $f_ord->has_status( array( 'processing', 'completed', 'on-hold' ) ) ) {
		$form = (array) $f_ord->get_meta( '_ea_form' );
		$fail = array(
			'order_id' => $f_ord->get_id(),
			'form'     => $form + array( 'name' => '', 'phone' => '', 'email' => '', 'county' => '', 'city' => '', 'address' => '' ),
			'choice'   => (string) $f_ord->get_meta( '_ea_box_type' ),
			'bundle'   => (int) $f_ord->get_meta( '_ea_bundle' ),
			'ship'     => (string) $f_ord->get_meta( '_ea_ship_kind' ),
		);
		if ( $f_ord->has_status( 'pending' ) ) {
			$f_ord->update_status( 'cancelled', 'Plata cu cardul nu a fost finalizată (client întors în landing).' );
		}
	}

	return array(
		'done'        => $done,
		'fail'        => $fail,
		'prices'      => array( 1 => (float) $s['price1'], 2 => (float) $s['price2'], 3 => (float) $s['price3'] ),
		'basePrice'   => (float) $s['price1'],
		'courier'     => (float) $s['courier_cost'],
		'easybox'     => (float) $s['easybox_cost'],
		'toasts'      => $s['live_toasts'] ? $toasts : array(),
		'whatsapp'    => preg_replace( '/\D/', '', $s['whatsapp_number'] ),
		'cardEnabled' => ea_mb_card_gateway() !== '',
		'cardDiscount' => (float) $s['card_discount'],
		'endpoint'    => esc_url_raw( rest_url( 'elixir/v1/order' ) ),
		'nonce'       => ea_mb_token(),
		'tokenUrl'    => esc_url_raw( rest_url( 'elixir/v1/token' ) ),
	);
}

/** Gateway-ul de card: setarea manuală sau primul gateway activ diferit de COD. */
function ea_mb_card_gateway() {
	$s = ea_mb_settings();
	if ( ! $s['card_option'] || ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return '';
	}
	$gateways = WC()->payment_gateways()->get_available_payment_gateways();
	if ( ! empty( $s['card_gateway'] ) && isset( $gateways[ $s['card_gateway'] ] ) && ea_mb_gateway_ready( $gateways[ $s['card_gateway'] ] ) ) {
		return $s['card_gateway'];
	}
	foreach ( $gateways as $id => $gw ) {
		if ( ! in_array( $id, array( 'cod', 'bacs', 'cheque' ), true ) && ea_mb_gateway_ready( $gw ) ) {
			return $id;
		}
	}
	return '';
}

/** Live: Netopia fără semnătură de cont sau certificat nu poate genera plata, deci nu îl oferim clienților. */
function ea_mb_gateway_ready( $gw ) {
	if ( empty( $gw->id ) || 'netopiapayments' !== $gw->id ) {
		return true;
	}
	if ( 'yes' === $gw->environment ) {
		return true; // sandbox: rămâne vizibil pentru teste
	}
	$cert = get_option( 'woocommerce_netopiapayments_live_cer_content', '' );
	return ! empty( $gw->account_id ) && ! empty( $cert );
}

/** Ascunde ratele plătite când livrarea gratuită e disponibilă (și în checkout-ul standard). */
add_filter( 'woocommerce_package_rates', function ( $rates ) {
	$free = array();
	foreach ( $rates as $id => $rate ) {
		if ( 'free_shipping' === $rate->get_method_id() ) {
			$free[ $id ] = $rate;
		}
	}
	return $free ? $free : $rates;
}, 100 );

/** Date comandă în adminul WooCommerce. */
add_action( 'woocommerce_admin_order_data_after_shipping_address', function ( $order ) {
	if ( 'landing' !== $order->get_meta( '_ea_source' ) ) {
		return;
	}
	$labels = array( 'm' => '4 Bărbați', 'f' => '4 Femei', 'x' => '2+2 Mix' );
	$box    = $order->get_meta( '_ea_box_type' );
	echo '<p><strong>Mystery Box:</strong> ' . esc_html( $labels[ $box ] ?? $box ) . ' · ' . (int) $order->get_meta( '_ea_bundle' ) . ' cutii'
		. '</p>';
} );

/**
 * Doar landing-ul e activ pe frontend: orice altă pagină publică (checkout, coș, magazin, produs, blog)
 * redirecționează către el. Rămân accesibile: wp-admin, REST, admin-post, plata cu cardul
 * (order-pay) și pagina de mulțumire (order-received). Se oprește din Setări → Mystery Box.
 */
add_action( 'template_redirect', function () {
	$s = ea_mb_settings();
	if ( empty( $s['only_landing'] ) || ea_mb_is_landing() || is_preview() || is_customize_preview() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}
	// Paginile legale rămân accesibile.
	$legal = array_filter( array_merge( ea_legal_page_ids(), array(
		(int) get_option( 'wp_page_for_privacy_policy' ),
		function_exists( 'wc_terms_and_conditions_page_id' ) ? (int) wc_terms_and_conditions_page_id() : 0,
	) ) );
	if ( $legal && is_page( $legal ) ) {
		return;
	}
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => EA_MB_TEMPLATE,     // phpcs:ignore WordPress.DB.SlowDBQuery
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( $pages && ! is_page( $pages[0] ) ) {
		wp_safe_redirect( get_permalink( $pages[0] ), 302 );
		exit;
	}
}, 1 );

/**
 * Pagina de plată (order-pay) și mulțumirea (order-received) nu afișează meniul, căutarea,
 * coșul sau sidebar-ul temei: clientul nu are pe unde ieși din flux.
 */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}
	$s = ea_mb_settings();
	if ( empty( $s['only_landing'] ) ) {
		return;
	}
	echo '<style id="ea-checkout-lock">'
		. '.whb-header,.whb-top-bar,.wd-page-nav,.page-title,.sidebar-container,.wd-toolbar,.wd-sticky-btn,.wd-header-mobile-nav,.mobile-nav,.footer-container,#footer,.wd-prefooter{display:none!important}'
		. '.site-content{flex:0 0 100%!important;max-width:100%!important;width:100%!important}'
		. '</style>';
}, 99 );
