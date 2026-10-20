<?php
/**
 * Pagini legale (Termeni, Confidențialitate, Cookie-uri, Retur, Litigii/ANPC):
 * definiție, creare automată și helperi pentru link-uri.
 */
defined( 'ABSPATH' ) || exit;

const EA_LEGAL_TEMPLATE = 'page-templates/legal.php';
const EA_LEGAL_VERSION  = '1';

/** slug => [titlu, fișier conținut, etichetă scurtă footer] */
function ea_legal_pages() {
	return array(
		'termeni-si-conditii'            => array( 'Termeni și condiții', 'tsc.html', 'Termeni și condiții' ),
		'politica-de-confidentialitate'  => array( 'Politica de confidențialitate', 'pdc.html', 'Confidențialitate' ),
		'politica-de-cookie-uri'         => array( 'Politica de cookie-uri', 'cookies.html', 'Cookie-uri' ),
		'retur-si-dreptul-de-retragere'  => array( 'Retur și dreptul de retragere', 'retur.html', 'Retur și retragere' ),
		'solutionarea-litigiilor-anpc'   => array( 'Soluționarea litigiilor (ANPC)', 'litigii.html', 'Litigii și ANPC' ),
	);
}

function ea_legal_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

function ea_is_legal() {
	return is_page() && get_page_template_slug() === EA_LEGAL_TEMPLATE;
}

/** URL-ul landing-ului (pagina cu șablonul Mystery Box), altfel home. */
function ea_landing_url() {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => EA_MB_TEMPLATE,     // phpcs:ignore WordPress.DB.SlowDBQuery
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	return $pages ? get_permalink( $pages[0] ) : home_url( '/' );
}

/** Date firmă (afișate în footer și în paginile legale). */
function ea_company() {
	return array(
		'name'    => 'RAUDEN PREMIUM GROUP SRL',
		'brand'   => 'Elixir Arăbesc',
		'cui'     => '44335210',
		'regcom'  => 'J2021002125357',
		'address' => 'Str. Babadag nr. 2, Bl. 4, Et. 1, Ap. 42, Timișoara, jud. Timiș, 300138',
		'email'   => 'contact@elixirarabesc.ro',
		'phone'   => '0774 646 182',
	);
}

/**
 * Creează paginile lipsă (o singură dată pe versiune). Nu suprascrie niciodată conținutul editat în admin.
 */
add_action( 'init', function () {
	if ( get_option( 'ea_legal_version' ) === EA_LEGAL_VERSION ) {
		return;
	}
	foreach ( ea_legal_pages() as $slug => $def ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			update_post_meta( $page->ID, '_wp_page_template', EA_LEGAL_TEMPLATE );
			continue;
		}
		$file    = EA_MB_DIR . '/inc/legal-content/' . $def[1];
		$content = file_exists( $file ) ? file_get_contents( $file ) : '';
		$id      = wp_insert_post( array(
			'post_title'   => $def[0],
			'post_name'    => $slug,
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => $content,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', EA_LEGAL_TEMPLATE );
		}
	}
	$terms   = get_page_by_path( 'termeni-si-conditii' );
	$privacy = get_page_by_path( 'politica-de-confidentialitate' );
	if ( $terms ) {
		update_option( 'woocommerce_terms_page_id', $terms->ID );
	}
	if ( $privacy ) {
		update_option( 'wp_page_for_privacy_policy', $privacy->ID );
	}
	update_option( 'ea_legal_version', EA_LEGAL_VERSION );
}, 20 );

/** Paginile legale nu sunt redirecționate către landing. */
function ea_legal_page_ids() {
	$ids = array();
	foreach ( array_keys( ea_legal_pages() ) as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$ids[] = $p->ID;
		}
	}
	return $ids;
}

/* ---------- Formular de retragere (OUG 34/2014) ---------- */

add_action( 'init', function () {
	register_post_type( 'ea_withdrawal', array(
		'labels'       => array( 'name' => 'Retrageri', 'singular_name' => 'Retragere', 'menu_name' => 'Retrageri' ),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-undo',
		'supports'     => array( 'title' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
} );

add_shortcode( 'ea_withdrawal_form', function () {
	ob_start();
	?>
	<form class="ea-wform" id="ea-wform" novalidate>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( ea_mb_token() ); ?>">
		<div class="ea-hp" aria-hidden="true"><label>Website<input type="text" name="ea_website" tabindex="-1" autocomplete="off"></label></div>
		<p class="ea-wform__to">Către: <?php echo esc_html( ea_company()['name'] ); ?>, <?php echo esc_html( ea_company()['address'] ); ?>, e-mail: <?php echo esc_html( ea_company()['email'] ); ?>, telefon: <?php echo esc_html( ea_company()['phone'] ); ?></p>
		<p class="ea-wform__intro">Vă informez prin prezenta că renunț la contractul de vânzare a produselor de mai jos.</p>
		<div class="ea-wform__grid">
			<label class="ea-wform__f"><span>Nume și prenume *</span><input name="name" autocomplete="name" required></label>
			<label class="ea-wform__f"><span>E-mail *</span><input name="email" type="email" autocomplete="email" required></label>
			<label class="ea-wform__f"><span>Telefon</span><input name="phone" type="tel" autocomplete="tel"></label>
			<label class="ea-wform__f"><span>Număr comandă *</span><input name="order" placeholder="ex. 1234" required></label>
			<label class="ea-wform__f"><span>Data comenzii</span><input name="ordered" type="date"></label>
			<label class="ea-wform__f"><span>Data primirii produselor *</span><input name="received" type="date" required></label>
		</div>
		<label class="ea-wform__f"><span>Produsele din care mă retrag *</span><textarea name="products" rows="2" required placeholder="ex. Mystery Box 2+2 (1 cutie)"></textarea></label>
		<label class="ea-wform__f"><span>Adresa consumatorului *</span><input name="address" autocomplete="street-address" required></label>
		<label class="ea-wform__f"><span>Cont IBAN pentru rambursare (doar la comenzi plătite ramburs)</span><input name="iban" autocomplete="off"></label>
		<div class="ea-wform__err" data-ea-wform-err hidden></div>
		<button type="submit" class="ea-btn ea-btn--lg ea-wform__btn">Trimite declarația de retragere</button>
		<p class="ea-wform__note">Veți primi pe e-mail o confirmare de primire. Datele sunt prelucrate conform <a href="<?php echo esc_url( ea_legal_url( 'politica-de-confidentialitate' ) ); ?>">Politicii de confidențialitate</a>.</p>
	</form>
	<div class="ea-wform__ok" id="ea-wform-ok" hidden>
		<strong>Declarația de retragere a fost trimisă.</strong>
		<p>Ți-am trimis o confirmare de primire pe e-mail. Te vom contacta cu instrucțiunile de returnare a coletului.</p>
	</div>
	<?php
	return ob_get_clean();
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'elixir/v1', '/withdrawal', array(
		'methods'             => 'POST',
		'callback'            => 'ea_withdrawal_submit',
		'permission_callback' => '__return_true',
	) );
} );

function ea_withdrawal_submit( WP_REST_Request $req ) {
	$d = $req->get_params();
	if ( ! empty( $d['ea_website'] ) ) {
		return array( 'ok' => true ); // honeypot
	}
	if ( ! ea_mb_token_valid( $d['nonce'] ?? '' ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'expired' => true, 'message' => 'Sesiune expirată. Încearcă din nou.' ), 403 );
	}
	if ( ea_mb_throttled() ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Prea multe încercări. Scrie-ne la ' . ea_company()['email'] . '.' ), 429 );
	}

	$f = array(
		'name'     => sanitize_text_field( $d['name'] ?? '' ),
		'email'    => sanitize_email( $d['email'] ?? '' ),
		'phone'    => sanitize_text_field( $d['phone'] ?? '' ),
		'order'    => sanitize_text_field( $d['order'] ?? '' ),
		'ordered'  => sanitize_text_field( $d['ordered'] ?? '' ),
		'received' => sanitize_text_field( $d['received'] ?? '' ),
		'products' => sanitize_textarea_field( $d['products'] ?? '' ),
		'address'  => sanitize_text_field( $d['address'] ?? '' ),
		'iban'     => sanitize_text_field( $d['iban'] ?? '' ),
	);
	$errors = array();
	foreach ( array( 'name', 'order', 'received', 'products', 'address' ) as $k ) {
		if ( '' === $f[ $k ] ) {
			$errors[ $k ] = 1;
		}
	}
	if ( ! is_email( $f['email'] ) ) {
		$errors['email'] = 1;
	}
	if ( $errors ) {
		return new WP_REST_Response( array( 'ok' => false, 'errors' => $errors, 'message' => 'Completează câmpurile marcate cu *.' ), 422 );
	}

	$co    = ea_company();
	$body  = "Declarație de retragere din contract (OUG 34/2014)\n\n"
		. "Nume: {$f['name']}\nE-mail: {$f['email']}\nTelefon: {$f['phone']}\n"
		. "Comanda nr.: {$f['order']}\nData comenzii: {$f['ordered']}\nData primirii: {$f['received']}\n"
		. "Produse: {$f['products']}\nAdresă: {$f['address']}\nIBAN: {$f['iban']}\n"
		. 'Trimis la: ' . wp_date( 'd.m.Y H:i' ) . "\n";
	$id = wp_insert_post( array(
		'post_type'    => 'ea_withdrawal',
		'post_status'  => 'publish',
		'post_title'   => 'Comanda ' . $f['order'] . ' – ' . $f['name'],
		'post_content' => $body,
	) );
	if ( ! $id || is_wp_error( $id ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Nu am putut înregistra cererea. Scrie-ne la ' . $co['email'] . '.' ), 500 );
	}

	$from = 'From: Elixir Arabesc <contact@elixirarabesc.ro>';
	wp_mail( $co['email'], 'Retragere din contract – comanda ' . $f['order'], $body, array( $from, 'Reply-To: ' . $f['name'] . ' <' . $f['email'] . '>' ) );
	wp_mail(
		$f['email'],
		'Confirmare primire – declarație de retragere (comanda ' . $f['order'] . ')',
		"Bună, {$f['name']},\n\nAm primit declarația ta de retragere din contract pentru comanda {$f['order']}, în data de " . wp_date( 'd.m.Y, H:i' ) . ".\nÎți vom trimite instrucțiunile de returnare a coletului. Rambursarea se face în maximum 14 zile de la data la care am fost informați, conform legii.\n\nDetaliile transmise:\n\n{$body}\n{$co['brand']} – {$co['name']}\n{$co['email']}\n",
		array( $from, 'Reply-To: ' . $co['email'] )
	);
	return array( 'ok' => true );
}
