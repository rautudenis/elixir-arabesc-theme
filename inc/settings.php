<?php
/**
 * Setări → Mystery Box.
 */
defined( 'ABSPATH' ) || exit;

const EA_MB_OPTION = 'ea_mb_settings';

function ea_mb_defaults() {
	return array(
		// Prețuri.
		'price1'          => 119,
		'price2'          => 229,
		'price3'          => 299,
		'courier_cost'    => 30,
		'easybox_cost'    => 20,
		// Boostere (on/off).
		'countdown'       => 1,
		'viewers'         => 1,
		'stock_bar'       => 1,
		'price_reserved'  => 1,
		'live_toasts'     => 1,
		'whatsapp'        => 1,
		'sticky_bar'      => 1,
		'ugc_grid'        => 1,
		'card_option'     => 1,
		'only_landing'    => 1,
		'card_gateway'    => '',
		// Tracking (se încarcă doar după consimțământ).
		'card_discount'   => 5,
		'meta_pixel_id'   => '',
		'tiktok_pixel_id' => '',
		// Valori.
		'stock_left'      => 37,
		'whatsapp_number' => '40774646182',
		'rating'          => 4.9,
		'review_count'    => 327,
		'dist'            => array( 88, 9, 2, 1 ),
		'ugc'             => array(
			array( 'image' => '', 'label' => 'foto client' ),
			array( 'image' => '', 'label' => 'foto client' ),
			array( 'image' => '', 'label' => 'foto client' ),
			array( 'image' => '', 'label' => 'video unboxing' ),
		),
		'reviews'         => array(
			array( 'stars' => 5, 'text' => 'Am luat-o pentru mine și soțul. Toate patru miros superb, iar cel cu oud ține de dimineața până seara.', 'name' => 'Andreea M.', 'city' => 'București', 'box' => 'Cutia 2+2' ),
			array( 'stars' => 5, 'text' => 'Nu credeam că la 119 lei primesc patru parfumuri de 100 ml bune. M-am înșelat. Am comandat deja a doua cutie.', 'name' => 'Mihai D.', 'city' => 'Cluj-Napoca', 'box' => 'Cutia 4 Bărbați' ),
			array( 'stars' => 5, 'text' => 'Am oferit-o cadou mamei. Cutia arată foarte elegant, nici n-a mai trebuit ambalată.', 'name' => 'Ioana P.', 'city' => 'Iași', 'box' => 'Cutia 4 Femei' ),
			array( 'stars' => 5, 'text' => 'Livrare în două zile, am plătit la curier. Simplu, fără bătăi de cap.', 'name' => 'Alexandru T.', 'city' => 'Timișoara', 'box' => 'Cutia 4 Bărbați' ),
			array( 'stars' => 5, 'text' => 'Partea cu surpriza e cea mai frumoasă. Am descoperit un parfum vanilat pe care acum îl port zilnic.', 'name' => 'Elena R.', 'city' => 'Brașov', 'box' => 'Cutia 4 Femei' ),
			array( 'stars' => 4, 'text' => 'Trei mi-au plăcut enorm, unul e prea dulce pentru mine. Pentru prețul ăsta, merită clar.', 'name' => 'Cristina V.', 'city' => 'Constanța', 'box' => 'Cutia 2+2' ),
		),
		'toasts'          => array(
			array( 'name' => 'Andreea', 'city' => 'Cluj-Napoca', 'choice' => 'Cutia 2+2', 'gender' => 'f' ),
			array( 'name' => 'Mihai', 'city' => 'București', 'choice' => 'Cutia 4 Bărbați', 'gender' => 'm' ),
			array( 'name' => 'Elena', 'city' => 'Iași', 'choice' => 'Cutia 4 Femei', 'gender' => 'f' ),
			array( 'name' => 'Radu', 'city' => 'Timișoara', 'choice' => '2 cutii 4 Bărbați', 'gender' => 'm' ),
			array( 'name' => 'Ioana', 'city' => 'Brașov', 'choice' => 'Cutia 2+2', 'gender' => 'f' ),
			array( 'name' => 'Cristian', 'city' => 'Constanța', 'choice' => 'Cutia 4 Bărbați', 'gender' => 'm' ),
			array( 'name' => 'Larisa', 'city' => 'Oradea', 'choice' => '2 cutii 4 Femei', 'gender' => 'f' ),
			array( 'name' => 'Vlad', 'city' => 'Sibiu', 'choice' => 'Cutia 2+2', 'gender' => 'm' ),
		),
	);
}

/** Setările salvate, completate cu valorile implicite ale prototipului. */
function ea_mb_settings() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( EA_MB_OPTION, array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), ea_mb_defaults() );
	}
	return $cache;
}
add_action( 'update_option_' . EA_MB_OPTION, function () {
	// Invalidează cache-ul static la următorul request și sincronizează WooCommerce.
	ea_mb_sync_woocommerce( wp_parse_args( get_option( EA_MB_OPTION, array() ), ea_mb_defaults() ) );
} );

/** Formatează un preț ca în prototip: "119 lei" / "12,50 lei". */
function ea_mb_fmt( $n ) {
	$n = (float) $n;
	return ( floor( $n ) === $n ? (string) (int) $n : number_format( $n, 2, ',', '' ) ) . ' lei';
}

/* ---------- Sincronizare cu WooCommerce (prețuri, livrare) ---------- */

function ea_mb_sync_woocommerce( $s ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$prices = array( 1 => (float) $s['price1'], 2 => (float) $s['price2'], 3 => (float) $s['price3'] );

	$product_id = (int) get_option( 'ea_mb_product_id' );
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( $product && $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( ! $v ) {
				continue;
			}
			$bundle = (int) $v->get_meta( '_ea_bundle' );
			if ( isset( $prices[ $bundle ] ) ) {
				$v->set_regular_price( $prices[ $bundle ] );
				$v->save();
			}
		}
		WC_Product_Variable::sync( $product_id );
	}

	$ids = get_option( 'ea_mb_shipping', array() );
	foreach ( array( 'courier' => 'courier_cost', 'easybox' => 'easybox_cost' ) as $k => $key ) {
		if ( ! empty( $ids[ $k ] ) ) {
			$opt = 'woocommerce_flat_rate_' . (int) $ids[ $k ] . '_settings';
			$cur = get_option( $opt, array() );
			if ( is_array( $cur ) ) {
				$cur['cost'] = (string) (float) $s[ $key ];
				update_option( $opt, $cur );
			}
		}
	}
	if ( ! empty( $ids['free'] ) ) {
		$opt = 'woocommerce_free_shipping_' . (int) $ids['free'] . '_settings';
		$cur = get_option( $opt, array() );
		if ( is_array( $cur ) ) {
			$cur['min_amount'] = (string) (float) $s['price2'];
			update_option( $opt, $cur );
		}
	}
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}

/**
 * Stocul afișat azi: se resetează zilnic (miezul nopții, ora site-ului), valoare „aleatorie” dar stabilă
 * toată ziua pentru toți vizitatorii. Maximul = setarea „Stoc afișat” (37 → niciodată sub 63% vândut).
 */
function ea_mb_stock_today( $max ) {
	$max = max( 1, (int) $max );
	$min = min( 12, $max );
	$day = wp_date( 'Y-m-d' );
	$n   = hexdec( substr( hash_hmac( 'sha256', 'ea_stock|' . $day, wp_salt( 'auth' ) ), 0, 8 ) );
	return $min + ( $n % ( $max - $min + 1 ) );
}

/* ---------- Pagina de setări ---------- */

add_action( 'admin_menu', function () {
	add_options_page( 'Mystery Box', 'Mystery Box', 'manage_options', 'ea-mystery-box', 'ea_mb_render_settings' );
} );

add_action( 'admin_init', function () {
	register_setting( 'ea_mb_group', EA_MB_OPTION, array( 'sanitize_callback' => 'ea_mb_sanitize' ) );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'settings_page_ea-mystery-box' === $hook ) {
		wp_enqueue_media();
	}
} );

function ea_mb_sanitize( $in ) {
	$d   = ea_mb_defaults();
	$in  = is_array( $in ) ? wp_unslash( $in ) : array();
	$out = array();

	foreach ( array( 'countdown', 'viewers', 'stock_bar', 'price_reserved', 'live_toasts', 'whatsapp', 'sticky_bar', 'ugc_grid', 'card_option', 'only_landing' ) as $k ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	foreach ( array( 'price1', 'price2', 'price3', 'courier_cost', 'easybox_cost' ) as $k ) {
		$out[ $k ] = max( 0, (float) ( $in[ $k ] ?? $d[ $k ] ) );
	}
	$out['stock_left']      = max( 1, min( 100, (int) ( $in['stock_left'] ?? $d['stock_left'] ) ) );
	$out['review_count']    = max( 0, (int) ( $in['review_count'] ?? $d['review_count'] ) );
	$out['rating']          = max( 1, min( 5, (float) str_replace( ',', '.', $in['rating'] ?? $d['rating'] ) ) );
	$out['whatsapp_number'] = preg_replace( '/\D/', '', $in['whatsapp_number'] ?? '' );
	$out['card_gateway']    = sanitize_key( $in['card_gateway'] ?? '' );
	$out['card_discount']   = max( 0, min( 50, (float) str_replace( ',', '.', $in['card_discount'] ?? $d['card_discount'] ) ) );
	$out['meta_pixel_id']   = preg_replace( '/\D/', '', $in['meta_pixel_id'] ?? '' );
	$out['tiktok_pixel_id'] = preg_replace( '/[^A-Za-z0-9]/', '', $in['tiktok_pixel_id'] ?? '' );

	$out['dist'] = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$out['dist'][ $i ] = max( 0, min( 100, (int) ( $in['dist'][ $i ] ?? $d['dist'][ $i ] ) ) );
	}

	$out['reviews'] = array();
	foreach ( (array) ( $in['reviews'] ?? array() ) as $r ) {
		if ( trim( $r['text'] ?? '' ) === '' ) {
			continue;
		}
		$out['reviews'][] = array(
			'stars' => max( 1, min( 5, (int) ( $r['stars'] ?? 5 ) ) ),
			'text'  => sanitize_textarea_field( $r['text'] ),
			'name'  => sanitize_text_field( $r['name'] ?? '' ),
			'city'  => sanitize_text_field( $r['city'] ?? '' ),
			'box'   => sanitize_text_field( $r['box'] ?? '' ),
		);
	}
	$out['toasts'] = array();
	foreach ( (array) ( $in['toasts'] ?? array() ) as $t ) {
		if ( trim( $t['name'] ?? '' ) === '' ) {
			continue;
		}
		$out['toasts'][] = array(
			'name'   => sanitize_text_field( $t['name'] ),
			'city'   => sanitize_text_field( $t['city'] ?? '' ),
			'choice' => sanitize_text_field( $t['choice'] ?? '' ),
			'gender' => 'f' === ( $t['gender'] ?? 'm' ) ? 'f' : 'm',
		);
	}
	$out['ugc'] = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$out['ugc'][ $i ] = array(
			'image' => esc_url_raw( $in['ugc'][ $i ]['image'] ?? '' ),
			'label' => sanitize_text_field( $in['ugc'][ $i ]['label'] ?? '' ),
		);
	}
	return $out;
}

function ea_mb_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = wp_parse_args( get_option( EA_MB_OPTION, array() ), ea_mb_defaults() );
	$n = EA_MB_OPTION;

	$toggle = function ( $key, $label ) use ( $s, $n ) {
		printf( '<tr><th scope="row">%s</th><td><label><input type="checkbox" name="%s[%s]" value="1" %s> Activat</label></td></tr>', esc_html( $label ), esc_attr( $n ), esc_attr( $key ), checked( ! empty( $s[ $key ] ), true, false ) );
	};
	$field = function ( $key, $label, $type = 'number', $extra = '' ) use ( $s, $n ) {
		printf( '<tr><th scope="row"><label for="ea-%1$s">%2$s</label></th><td><input id="ea-%1$s" type="%3$s" name="%4$s[%1$s]" value="%5$s" class="regular-text" %6$s></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( $type ), esc_attr( $n ), esc_attr( $s[ $key ] ), $extra ); // phpcs:ignore WordPress.Security.EscapeOutput
	};
	?>
	<div class="wrap">
		<h1>Mystery Box</h1>
		<p>Setările landing-ului (<code>page-templates/mystery-box.php</code>). Elementele de social proof sunt editabile aici și nu se iau din WooCommerce.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'ea_mb_group' ); ?>

			<h2>Prețuri</h2>
			<p class="description">Se sincronizează automat cu variațiile produsului, produsul-cadou și metodele de livrare din WooCommerce. Cutia cu 4 parfumuri de 100 ml.</p>
			<table class="form-table"><?php
				$field( 'price1', '1 cutie (lei)', 'number', 'min="1" step="1"' );
				$field( 'price2', '2 cutii (lei)', 'number', 'min="1" step="1"' );
				$field( 'price3', '3 cutii (lei)', 'number', 'min="1" step="1"' );
				$field( 'courier_cost', 'Curier la adresă (lei)', 'number', 'min="0" step="1"' );
				$field( 'easybox_cost', 'Locker easybox (lei)', 'number', 'min="0" step="1"' );
			?></table>

			<h2>Boostere</h2>
			<table class="form-table"><?php
				$toggle( 'countdown', 'Countdown până la miezul nopții' );
				$toggle( 'viewers', 'Contor „persoane văd oferta acum”' );
				$toggle( 'stock_bar', 'Bara de stoc' );
				$toggle( 'price_reserved', 'Timer „preț rezervat” (10 min) în modal' );
				$toggle( 'live_toasts', 'Notificări comenzi recente' );
				$toggle( 'whatsapp', 'Buton WhatsApp' );
				$toggle( 'sticky_bar', 'Bara fixă de jos' );
				$toggle( 'ugc_grid', 'Grid poze clienți' );
				$toggle( 'card_option', 'Opțiunea „Card online” în modal' );
				$toggle( 'only_landing', 'Doar landing activ (celelalte pagini redirecționează aici)' );
				$field( 'card_discount', 'Reducere plată cu cardul (%) – 0 = fără reducere', 'number', 'min="0" max="50" step="0.5"' );
				$field( 'card_gateway', 'ID gateway card (opțional)', 'text', 'placeholder="ex. stripe, netopia"' );
				$field( 'stock_left', 'Stoc maxim afișat (zilnic se alege aleatoriu între 12 și această valoare; 37 = minim 63% vândut)', 'number', 'min="1" max="100"' );
				$field( 'whatsapp_number', 'Număr WhatsApp', 'text', 'placeholder="40700000000"' );
			?></table>

			<h2>Pixeli reclame</h2>
			<p class="description">Se încarcă numai după ce vizitatorul alege „Accept toate” în bannerul de cookie-uri. Lasă gol pentru a dezactiva.</p>
			<table class="form-table"><?php
				$field( 'meta_pixel_id', 'Meta Pixel ID', 'text', 'placeholder="1234567890123456"' );
				$field( 'tiktok_pixel_id', 'TikTok Pixel ID', 'text', 'placeholder="C1A2B3C4D5E6F7G8H9"' );
			?></table>

			<h2>Recenzii</h2>
			<table class="form-table"><?php
				$field( 'rating', 'Rating', 'number', 'min="1" max="5" step="0.1"' );
				$field( 'review_count', 'Număr recenzii', 'number', 'min="0"' );
			?>
				<tr><th scope="row">Distribuție (5★ / 4★ / 3★ / 2★, %)</th><td>
					<?php for ( $i = 0; $i < 4; $i++ ) : ?>
						<input type="number" min="0" max="100" style="width:70px" name="<?php echo esc_attr( $n ); ?>[dist][<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $s['dist'][ $i ] ); ?>">
					<?php endfor; ?>
				</td></tr>
			</table>

			<h2>Carduri de recenzie</h2>
			<div id="ea-reviews" class="ea-rep">
				<?php foreach ( $s['reviews'] as $i => $r ) : ea_mb_review_row( $n, $i, $r ); endforeach; ?>
			</div>
			<p><button type="button" class="button" data-add="ea-reviews">+ Adaugă recenzie</button></p>

			<h2>Poze clienți (UGC) – 4 sloturi</h2>
			<table class="form-table">
				<?php for ( $i = 0; $i < 4; $i++ ) : $u = $s['ugc'][ $i ]; ?>
					<tr><th scope="row">Slot <?php echo (int) $i + 1; ?></th><td>
						<input type="url" class="regular-text ea-ugc-url" name="<?php echo esc_attr( $n ); ?>[ugc][<?php echo (int) $i; ?>][image]" value="<?php echo esc_attr( $u['image'] ); ?>" placeholder="URL imagine">
						<button type="button" class="button ea-pick-media">Alege din Media</button>
						<input type="text" name="<?php echo esc_attr( $n ); ?>[ugc][<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $u['label'] ); ?>" placeholder="Etichetă (când nu e imagine)">
					</td></tr>
				<?php endfor; ?>
			</table>

			<h2>Notificări comenzi recente</h2>
			<p class="description">Se rotesc în ordine, câte una la 16 secunde. „acum X min” se calculează automat.</p>
			<div id="ea-toasts" class="ea-rep">
				<?php foreach ( $s['toasts'] as $i => $t ) : ea_mb_toast_row( $n, $i, $t ); endforeach; ?>
			</div>
			<p><button type="button" class="button" data-add="ea-toasts">+ Adaugă notificare</button></p>

			<?php submit_button(); ?>
		</form>
	</div>

	<template id="ea-tpl-ea-reviews"><?php ea_mb_review_row( $n, '__i__', array( 'stars' => 5, 'text' => '', 'name' => '', 'city' => '', 'box' => '' ) ); ?></template>
	<template id="ea-tpl-ea-toasts"><?php ea_mb_toast_row( $n, '__i__', array( 'name' => '', 'city' => '', 'choice' => '', 'gender' => 'm' ) ); ?></template>

	<style>
		.ea-rep .ea-row{background:#fff;border:1px solid #c3c4c7;padding:10px;margin-bottom:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start}
		.ea-rep textarea{width:420px;max-width:100%}
	</style>
	<script>
	(function () {
		document.querySelectorAll('[data-add]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var id = btn.getAttribute('data-add'), box = document.getElementById(id);
				var tpl = document.getElementById('ea-tpl-' + id).innerHTML.replace(/__i__/g, Date.now());
				box.insertAdjacentHTML('beforeend', tpl);
			});
		});
		document.addEventListener('click', function (e) {
			if (e.target.matches('.ea-del')) e.target.closest('.ea-row').remove();
			if (e.target.matches('.ea-pick-media')) {
				var input = e.target.parentNode.querySelector('.ea-ugc-url');
				var frame = wp.media({ multiple: false, library: { type: 'image' } });
				frame.on('select', function () { input.value = frame.state().get('selection').first().toJSON().url; });
				frame.open();
			}
		});
	})();
	</script>
	<?php
}

function ea_mb_review_row( $n, $i, $r ) {
	$p = esc_attr( $n ) . '[reviews][' . esc_attr( $i ) . ']';
	?>
	<div class="ea-row">
		<select name="<?php echo $p; // phpcs:ignore ?>[stars]"><?php for ( $k = 5; $k >= 1; $k-- ) : ?><option value="<?php echo (int) $k; ?>" <?php selected( (int) $r['stars'], $k ); ?>><?php echo (int) $k; ?>★</option><?php endfor; ?></select>
		<textarea rows="3" name="<?php echo $p; // phpcs:ignore ?>[text]" placeholder="Text recenzie"><?php echo esc_textarea( $r['text'] ); ?></textarea>
		<input type="text" name="<?php echo $p; // phpcs:ignore ?>[name]" value="<?php echo esc_attr( $r['name'] ); ?>" placeholder="Nume (Andreea M.)">
		<input type="text" name="<?php echo $p; // phpcs:ignore ?>[city]" value="<?php echo esc_attr( $r['city'] ); ?>" placeholder="Oraș">
		<input type="text" name="<?php echo $p; // phpcs:ignore ?>[box]" value="<?php echo esc_attr( $r['box'] ); ?>" placeholder="Cutia 2+2">
		<button type="button" class="button ea-del">Șterge</button>
	</div>
	<?php
}

function ea_mb_toast_row( $n, $i, $t ) {
	$p = esc_attr( $n ) . '[toasts][' . esc_attr( $i ) . ']';
	?>
	<div class="ea-row">
		<input type="text" name="<?php echo $p; // phpcs:ignore ?>[name]" value="<?php echo esc_attr( $t['name'] ); ?>" placeholder="Prenume">
		<input type="text" name="<?php echo $p; // phpcs:ignore ?>[city]" value="<?php echo esc_attr( $t['city'] ); ?>" placeholder="Oraș">
		<input type="text" name="<?php echo $p; // phpcs:ignore ?>[choice]" value="<?php echo esc_attr( $t['choice'] ); ?>" placeholder="Cutia 2+2">
		<select name="<?php echo $p; // phpcs:ignore ?>[gender]">
			<option value="m" <?php selected( $t['gender'], 'm' ); ?>>Fiolă bărbați</option>
			<option value="f" <?php selected( $t['gender'], 'f' ); ?>>Fiolă femei</option>
		</select>
		<button type="button" class="button ea-del">Șterge</button>
	</div>
	<?php
}
