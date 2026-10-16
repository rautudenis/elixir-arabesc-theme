<?php
/**
 * Footer comun (landing + pagini legale): date firmă, link-uri legale, ANPC, banner cookie-uri.
 */
defined( 'ABSPATH' ) || exit;
$co = ea_company();
?>
<footer class="ea-footer">
	<div class="ea-footer__row">
		<a class="ea-footer__brand" href="<?php echo esc_url( ea_landing_url() ); ?>"><img class="ea-footer__logo" src="<?php echo esc_url( EA_MB_URI . '/assets/img/elixir-arabesc-logo.png' ); ?>" alt="Elixir Arabesc" width="900" height="345"></a>
		<nav class="ea-footer__links" aria-label="Informații legale">
			<?php foreach ( ea_legal_pages() as $slug => $def ) : ?>
				<a href="<?php echo esc_url( ea_legal_url( $slug ) ); ?>"><?php echo esc_html( $def[2] ); ?></a>
			<?php endforeach; ?>
			<a href="#" data-ea-consent-open>Setări cookie-uri</a>
		</nav>
	</div>
	<div class="ea-footer__anpc">
		<div class="ea-footer__left">
			<div class="ea-footer__badges">
			<a class="ea-anpc" href="https://www.anpc.ro/sal" target="_blank" rel="noopener"><span class="ea-anpc__k">ANPC</span><span class="ea-anpc__t">Soluționarea alternativă a litigiilor</span></a>
			<a class="ea-anpc" href="https://eservicii.anpc.ro" target="_blank" rel="noopener"><span class="ea-anpc__k">ANPC</span><span class="ea-anpc__t">Reclamații online</span></a>
			</div>
			<p class="ea-footer__legal">
		<?php echo esc_html( $co['name'] ); ?> · CUI <?php echo esc_html( $co['cui'] ); ?> · <?php echo esc_html( $co['regcom'] ); ?> · <?php echo esc_html( $co['address'] ); ?> · <a href="mailto:<?php echo esc_attr( $co['email'] ); ?>"><?php echo esc_html( $co['email'] ); ?></a> · <a href="tel:+4<?php echo esc_attr( preg_replace( '/\D/', '', $co['phone'] ) ); ?>"><?php echo esc_html( $co['phone'] ); ?></a><br>
		© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $co['brand'] ); ?>. Toate drepturile rezervate.
	</p>
		</div>
		<a class="ea-netopia" href="https://netopia-payments.com" target="_blank" rel="noopener" aria-label="Plăți securizate prin Netopia Payments"><img src="<?php echo esc_url( EA_MB_URI . '/assets/img/netopia-payments.svg' ) . '?v=' . filemtime( EA_MB_DIR . '/assets/img/netopia-payments.svg' ); ?>" alt="Netopia Payments – Mastercard, Visa" width="418" height="75" loading="lazy"></a>
	</div>
</footer>

<div class="ea-consent" id="ea-consent" role="dialog" aria-label="Preferințe cookie-uri" hidden>
	<p class="ea-consent__txt">Folosim cookie-uri strict necesare pentru funcționarea magazinului și, doar cu acordul tău, cookie-uri de marketing (Meta și TikTok) pentru măsurarea reclamelor. Detalii în <a href="<?php echo esc_url( ea_legal_url( 'politica-de-cookie-uri' ) ); ?>">Politica de cookie-uri</a>.</p>
	<div class="ea-consent__btns">
		<button type="button" class="ea-consent__btn ea-consent__btn--ghost" data-ea-consent="necessary">Doar necesare</button>
		<button type="button" class="ea-consent__btn" data-ea-consent="all">Accept toate</button>
	</div>
</div>
