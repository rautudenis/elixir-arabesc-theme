<?php
/**
 * Comandă rapidă într-un singur pas.
 *
 *  POST /wp-json/elixir/v1/order   (JS)
 *  POST admin-post.php?action=ea_mb_order   (fallback fără JS)
 *
 * Prețurile din client nu sunt folosite niciodată: totalurile se calculează din produsele WooCommerce.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Token anti-abuz independent de utilizator (nonce-ul WP e legat de user și expiră/dă mismatch
 * pentru admini logați sau pagini din cache). Valid ~2 zile, regenerat automat de JS la nevoie.
 */
function ea_mb_token( $offset = 0 ) {
	return hash_hmac( 'sha256', 'ea_order|' . ( (int) floor( time() / 43200 ) - $offset ), wp_salt( 'auth' ) );
}

function ea_mb_token_valid( $t ) {
	if ( ! is_string( $t ) || '' === $t ) {
		return false;
	}
	for ( $i = 0; $i < 4; $i++ ) {
		if ( hash_equals( ea_mb_token( $i ), $t ) ) {
			return true;
		}
	}
	return false;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'elixir/v1', '/token', array(
		'methods'             => 'GET',
		'callback'            => function () {
			nocache_headers();
			return array( 'token' => ea_mb_token() );
		},
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'elixir/v1', '/order', array(
		'methods'             => 'POST',
		'callback'            => 'ea_mb_rest_order',
		'permission_callback' => '__return_true',
	) );
} );

add_action( 'admin_post_nopriv_ea_mb_order', 'ea_mb_form_order' );
add_action( 'admin_post_ea_mb_order', 'ea_mb_form_order' );

function ea_mb_rest_order( WP_REST_Request $req ) {
	$res = ea_mb_process( $req->get_params() );
	return new WP_REST_Response( $res, ! empty( $res['ok'] ) ? 200 : ( isset( $res['errors'] ) ? 422 : ( ! empty( $res['expired'] ) ? 403 : 400 ) ) );
}

function ea_mb_form_order() {
	$back = wp_get_referer() ? remove_query_arg( 'ea_err', wp_get_referer() ) : home_url( '/' );
	$back = strtok( $back, '#' );
	$res  = ea_mb_process( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification -- verificat în ea_mb_process.

	if ( ! empty( $res['ok'] ) ) {
		wp_safe_redirect( $res['redirect'] );
		exit;
	}
	$codes = isset( $res['errors'] ) ? implode( ',', array_keys( $res['errors'] ) ) : 'x';
	wp_safe_redirect( add_query_arg( 'ea_err', rawurlencode( $codes ), $back ) . '#ea-order' );
	exit;
}

function ea_mb_throttled() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'ea_mb_rl_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 8 ) {
		return true;
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
	return false;
}

/** Potrivește textul județului cu un cod de stat WooCommerce; altfel păstrează textul. */
function ea_mb_state_code( $county ) {
	$norm = function ( $s ) {
		$s = remove_accents( mb_strtolower( $s ) );
		$s = preg_replace( '/\b(judetul|jud\.?|municipiul|mun\.?)\b/', '', $s );
		return trim( preg_replace( '/[^a-z]/', '', $s ) );
	};
	$want = $norm( $county );
	$states = WC()->countries->get_states( 'RO' );
	if ( $want && is_array( $states ) ) {
		foreach ( $states as $code => $label ) {
			if ( $norm( $label ) === $want || strtolower( $code ) === strtolower( trim( $county ) ) ) {
				return $code;
			}
		}
	}
	return $county;
}

function ea_mb_find_variation( $choice, $bundle ) {
	$product = wc_get_product( (int) get_option( 'ea_mb_product_id' ) );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return null;
	}
	foreach ( $product->get_children() as $vid ) {
		$v = wc_get_product( $vid );
		if ( $v && $v->get_meta( '_ea_choice' ) === $choice && (int) $v->get_meta( '_ea_bundle' ) === $bundle && $v->is_purchasable() ) {
			return $v;
		}
	}
	return null;
}

function ea_mb_shipping_item( $kind, $cost ) {
	$ids  = get_option( 'ea_mb_shipping', array() );
	$item = new WC_Order_Item_Shipping();
	if ( 'free' === $kind ) {
		$item->set_method_title( 'Livrare gratuită' );
		$item->set_method_id( 'free_shipping' );
		$item->set_instance_id( (int) ( $ids['free'] ?? 0 ) );
		$item->set_total( 0 );
	} else {
		$item->set_method_title( 'easybox' === $kind ? 'Locker easybox' : 'Curier la adresă' );
		$item->set_method_id( 'flat_rate' );
		$item->set_instance_id( (int) ( $ids[ $kind ] ?? 0 ) );
		$item->set_total( $cost );
	}
	return $item;
}

/**
 * Validează, creează comanda și întoarce un array pentru JSON / redirect.
 */
function ea_mb_process( $d ) {
	if ( ! function_exists( 'wc_create_order' ) ) {
		return array( 'ok' => false, 'message' => 'WooCommerce nu este activ.' );
	}
	if ( ! empty( $d['ea_website'] ) ) { // Honeypot: răspuns „de succes” fals pentru boți.
		return array( 'ok' => true, 'status' => 'cod', 'redirect' => home_url( '/' ), 'first_name' => '', 'total' => 0 );
	}
	if ( ! ea_mb_token_valid( $d['nonce'] ?? '' ) ) {
		return array( 'ok' => false, 'expired' => true, 'message' => 'Nu am putut trimite comanda. Încearcă din nou.' );
	}
	if ( ea_mb_throttled() ) {
		return array( 'ok' => false, 'message' => 'Prea multe încercări. Te rugăm să ne suni sau să încerci peste câteva minute.' );
	}

	$s       = ea_mb_settings();
	$name    = trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( $d['name'] ?? '' ) ) );
	$phone   = preg_replace( '/[\s.\-]/', '', sanitize_text_field( $d['phone'] ?? '' ) );
	$email   = sanitize_email( $d['email'] ?? '' ); // opțional
	$county  = sanitize_text_field( $d['county'] ?? '' );
	$city    = sanitize_text_field( $d['city'] ?? '' );
	$address = sanitize_text_field( $d['address'] ?? '' );
	$choice  = in_array( $d['choice'] ?? '', array( 'm', 'f', 'x' ), true ) ? $d['choice'] : '';
	$bundle  = (int) ( $d['bundle'] ?? 0 );
	$ship    = 'easybox' === ( $d['ship'] ?? '' ) ? 'easybox' : 'courier';

	$errors = array();
	if ( count( preg_split( '/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY ) ) < 2 ) {
		$errors['name'] = 1;
	}
	if ( ! preg_match( '/^(\+?40|0)7\d{8}$/', $phone ) ) {
		$errors['phone'] = 1;
	}
	if ( '' !== trim( (string) ( $d['email'] ?? '' ) ) && ! is_email( $email ) ) {
		$errors['email'] = 1;
	}
	if ( '' === $county ) {
		$errors['county'] = 1;
	}
	if ( '' === $city ) {
		$errors['city'] = 1;
	}
	if ( mb_strlen( $address ) < 5 ) {
		$errors['address'] = 1;
	}
	if ( empty( $d['terms'] ) ) {
		$errors['terms'] = 1; // Acceptarea Termenilor și a Politicii de confidențialitate este obligatorie.
	}
	if ( $errors ) {
		return array( 'ok' => false, 'errors' => $errors );
	}

	$variation = ( $choice && $bundle >= 1 && $bundle <= 2 ) ? ea_mb_find_variation( $choice, $bundle ) : null;
	if ( ! $variation ) {
		return array( 'ok' => false, 'message' => 'Produsul nu este disponibil momentan.' );
	}

	// Plată: COD implicit; card doar dacă există gateway disponibil.
	$pay_id = 'cod';
	if ( 'card' === ( $d['pay'] ?? '' ) ) {
		$card = ea_mb_card_gateway();
		if ( $card ) {
			$pay_id = $card;
		}
	}
	$gateways = WC()->payment_gateways()->payment_gateways();
	$gateway  = $gateways[ $pay_id ] ?? null;

	$order = wc_create_order( array( 'status' => 'pending', 'created_via' => 'ea_landing' ) );
	if ( is_wp_error( $order ) ) {
		return array( 'ok' => false, 'message' => 'Nu am putut crea comanda. Încearcă din nou.' );
	}

	$order->add_product( $variation, 1 );

	$free = $bundle >= 2;
	$order->add_item( ea_mb_shipping_item( $free ? 'free' : $ship, (float) ( 'easybox' === $ship ? $s['easybox_cost'] : $s['courier_cost'] ) ) );

	$parts = preg_split( '/\s+/u', $name );
	$first = array_shift( $parts );
	$addr  = array(
		'first_name' => $first,
		'last_name'  => implode( ' ', $parts ),
		'address_1'  => 'easybox' === $ship ? 'Locker easybox: ' . $address : $address,
		'city'       => $city,
		'state'      => ea_mb_state_code( $county ),
		'country'    => 'RO',
	);
	$order->set_address( $addr + array( 'phone' => $phone, 'email' => $email ), 'billing' );
	$order->set_address( $addr, 'shipping' );

	if ( $gateway ) {
		$order->set_payment_method( $gateway );
	}

	// Reducere pentru plata online cu cardul (doar dacă s-a ales efectiv cardul).
	$pct = (float) $s['card_discount'];
	if ( 'cod' !== $pay_id && $pct > 0 ) {
		$disc = round( (float) $order->get_subtotal() * $pct / 100, 2 );
		if ( $disc > 0 ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_name( 'Reducere plată online cu cardul (' . rtrim( rtrim( number_format( $pct, 2, '.', '' ), '0' ), '.' ) . '%)' );
			$fee->set_total( -$disc );
			$fee->set_tax_status( 'none' );
			$order->add_item( $fee );
		}
	}

	$order->update_meta_data( '_ea_box_type', $choice );
	$order->update_meta_data( '_ea_bundle', $bundle );
	$order->update_meta_data( '_ea_source', 'landing' );
	$ref = wp_get_raw_referer();
	$order->update_meta_data( '_ea_landing_url', $ref ? strtok( $ref, '?#' ) : home_url( '/' ) );
	$order->update_meta_data( '_ea_ship_kind', $ship );
	// Date brute ca să putem reumple formularul dacă plata cu cardul nu reușește.
	$order->update_meta_data( '_ea_form', array( 'name' => $name, 'phone' => $phone, 'email' => $email, 'county' => $county, 'city' => $city, 'address' => $address ) );
	$order->update_meta_data( '_ea_terms_accepted', current_time( 'mysql' ) );

	$order->calculate_totals( false );
	$order->save();

	/** Pentru integrări: AWB, facturare, pixeli server-side. */
	do_action( 'ea_mb_order_created', $order, $d );
	do_action( 'woocommerce_checkout_order_created', $order );
	do_action( 'woocommerce_checkout_order_processed', $order->get_id(), array( 'payment_method' => $pay_id, 'billing_phone' => $phone ), $order );

	$result = array(
		'ok'           => true,
		'order_id'     => $order->get_id(),
		'order_number' => $order->get_order_number(),
		'total'        => (float) $order->get_total(),
		'currency'     => $order->get_currency(),
		'first_name'   => $first,
		'items'        => array( array( 'id' => $variation->get_id(), 'name' => $variation->get_name(), 'price' => (float) $variation->get_price(), 'qty' => 1 ) ),
	);

	if ( 'cod' === $pay_id ) {
		$order->update_status( 'processing', 'Comandă rapidă din landing (ramburs).' );
		$result['status']   = 'cod';
		$result['redirect'] = $order->get_checkout_order_received_url();
	} else {
		$result['status']   = 'card';
		$result['redirect'] = $order->get_checkout_payment_url(); // fallback

		// Direct la Netopia, fără pagina order-pay: luăm formularul semnat de gateway și îl trimitem din browser.
		if ( $gateway && 'netopiapayments' === $pay_id && method_exists( $gateway, 'generate_netopia_form' ) ) {
			$_REQUEST['method'] = 'credit_card';
			// Gateway-ul citește WC()->cart; în REST coșul nu e încărcat implicit.
			if ( null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
				wc_load_cart();
			}
			$html = '';
			ob_start();
			try {
				$html = $gateway->generate_netopia_form( $order->get_id() );
			} catch ( \Throwable $e ) {
				$html = '';
			}
			ob_end_clean();
			if ( is_string( $html ) && preg_match( '/<form[^>]*action="([^"]+)"/i', $html, $m_action )
				&& preg_match_all( '/<input[^>]*type="hidden"[^>]*name="([^"]+)"[^>]*value="([^"]*)"/i', $html, $m_fields, PREG_SET_ORDER ) ) {
				$fields = array();
				foreach ( $m_fields as $f ) {
					$fields[ $f[1] ] = html_entity_decode( $f[2], ENT_QUOTES );
				}
				if ( ! empty( $fields['env_key'] ) && ! empty( $fields['data'] ) ) {
					$result['netopia'] = array(
						'action' => html_entity_decode( $m_action[1], ENT_QUOTES ),
						'fields' => $fields,
					);
				}
			}
		}
	}
	return $result;
}


/**
 * Comenzile din landing plătite cu cardul nu trebuie să ajungă pe paginile WooCommerce:
 * - plătită → landing cu mesajul de mulțumire (?ea_done);
 * - refuzată/anulată/neconfirmată → landing cu formularul reumplut și un mesaj de eroare (?ea_fail).
 * Se aplică pe „order-received” și pe „order-pay” (fallback), doar cu cheia comenzii validă.
 */
add_action( 'template_redirect', function () {
	if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
		return;
	}
	$order_id = 0;
	if ( is_wc_endpoint_url( 'order-received' ) ) {
		$order_id = absint( get_query_var( 'order-received' ) );
	} elseif ( is_wc_endpoint_url( 'order-pay' ) ) {
		$order_id = absint( get_query_var( 'order-pay' ) );
	}
	$order = $order_id ? wc_get_order( $order_id ) : null;
	$key   = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
	if ( ! $order || 'ea_landing' !== $order->get_created_via() || 'cod' === $order->get_payment_method() || ! hash_equals( $order->get_order_key(), $key ) ) {
		return;
	}
	$url = (string) $order->get_meta( '_ea_landing_url' );
	if ( '' === $url ) {
		return;
	}
	$paid = $order->has_status( array( 'processing', 'completed', 'on-hold' ) );
	$arg  = $paid ? 'ea_done' : 'ea_fail';
	wp_safe_redirect( add_query_arg( array( $arg => $order_id, 'key' => $key ), $url ) );
	exit;
}, 5 );
