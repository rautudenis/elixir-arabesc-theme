<?php
/**
 * Template Name: Mystery Box
 *
 * Landing page fără header/footer Woodmart.
 */
defined( 'ABSPATH' ) || exit;

$s       = ea_mb_settings();
$img     = EA_MB_URI . '/assets/img/cutie-parfumuri-fara-fundal.png';
$p1      = ea_mb_fmt( $s['price1'] );
$per     = ea_mb_fmt( round( $s['price1'] / 4 ) );
$p2      = ea_mb_fmt( $s['price2'] );
$courier = ea_mb_fmt( $s['courier_cost'] );
$easybox = ea_mb_fmt( $s['easybox_cost'] );
$save2   = ea_mb_fmt( $s['price1'] * 2 - $s['price2'] + $s['courier_cost'] );
$rating  = number_format( (float) $s['rating'], 1, ',', '' );
$count   = (int) $s['review_count'] . ' recenzii';
$stock   = ea_mb_stock_today( $s['stock_left'] );
$sold    = max( 5, min( 97, 100 - $stock ) ) . '%';
$card    = ea_mb_card_gateway() !== '';
$disc_pct = (float) $s['card_discount'] > 0 ? rtrim( rtrim( number_format( (float) $s['card_discount'], 1, ',', '' ), '0' ), ',' ) : '';
$wa      = preg_replace( '/\D/', '', $s['whatsapp_number'] );

// Data estimată de livrare (azi + 2 zile lucrătoare); JS o recalculează în browser.
$days   = array( 'duminică', 'luni', 'marți', 'miercuri', 'joi', 'vineri', 'sâmbătă' );
$months = array( 1 => 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie' );
$dt     = new DateTime( 'now', wp_timezone() );
for ( $n = 0; $n < 2; ) {
	$dt->modify( '+1 day' );
	if ( ! in_array( (int) $dt->format( 'w' ), array( 0, 6 ), true ) ) {
		$n++;
	}
}
$delivery = $days[ (int) $dt->format( 'w' ) ] . ', ' . (int) $dt->format( 'j' ) . ' ' . $months[ (int) $dt->format( 'n' ) ];

$err_codes = isset( $_GET['ea_err'] ) ? array_filter( explode( ',', sanitize_text_field( wp_unslash( $_GET['ea_err'] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification

/** Mic helper pentru stele. */
$stars = function ( $n ) {
	return str_repeat( '★', (int) $n ) . str_repeat( '☆', 5 - (int) $n );
};
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<script>document.documentElement.classList.add('ea-js');</script>

<div class="ea-page" id="ea-page">

	<div class="ea-topbar">
		<?php if ( $s['countdown'] ) : ?>
			<span>Oferta <?php echo esc_html( $p1 ); ?> se încheie în <span class="ea-topbar__time"><span data-ea="hh">00</span>:<span data-ea="mm">00</span>:<span data-ea="ss">00</span></span></span>
			<span class="ea-topbar__dot">·</span>
		<?php endif; ?>
		<span>Plata la livrare</span><span class="ea-topbar__dot">·</span><span>Livrare gratuită de la 2 cutii</span>
	</div>

	<header class="ea-header">
		<div class="ea-brand">
			<img class="ea-brand__logo" src="<?php echo esc_url( EA_MB_URI . '/assets/img/elixir-arabesc-logo.png' ); ?>" alt="Elixir Arabesc – parfumuri orientale" width="900" height="345">
		</div>
		<a href="#ea-order" class="ea-btn ea-btn--sm" data-ea-open>Comandă · <?php echo esc_html( $p1 ); ?></a>
	</header>

	<!-- HERO -->
	<section class="ea-hero">
		<div class="ea-dots ea-dots--hero"></div>
		<div class="ea-hero__grid">
			<div class="ea-hero__copy">
				<div class="ea-hero__meta">
					<button type="button" class="ea-rating" data-ea-reviews>
						<span class="ea-rating__stars">★★★★★</span>
						<span class="ea-rating__val"><?php echo esc_html( $rating ); ?></span>
						<span class="ea-rating__count"><?php echo esc_html( $count ); ?></span>
					</button>
					<?php if ( $s['viewers'] ) : ?>
						<span class="ea-viewers"><span class="ea-live-dot"></span><strong data-ea="viewers">24</strong> persoane văd oferta acum</span>
					<?php endif; ?>
				</div>

				<h1 class="ea-hero__title">Patru parfumuri.<br><em>Un singur secret.</em></h1>
				<p class="ea-hero__lead">Alegi pentru cine e cutia, noi alegem 4 parfumuri arabești diferite. Le descoperi abia când deschizi cutia.</p>


				<div class="ea-spec">
					<span class="ea-spec__ar" aria-hidden="true">عطور عربية</span>
					<div class="ea-spec__main">
						<span class="ea-spec__big">4 × 100 ml</span>
						<span class="ea-spec__txt"><strong>Parfumuri arabești</strong> · 100% Elixir Arabesc</span>
					</div>
					<span class="ea-spec__note">Primești 4 sticle de parfum, fiecare de 100 ml — nu mostre, nu testere.</span>
				</div>

				<div class="ea-pricebox">
					<div class="ea-pricebox__row">
						<div class="ea-pricebox__amount"><span class="ea-pricebox__num"><?php echo (int) $s['price1']; ?></span><span class="ea-pricebox__cur">lei</span></div>
						<div class="ea-pricebox__info">
							<span class="ea-pricebox__title">Prețul întregii cutii · 4 parfumuri</span>
							<span>4 parfumuri × <strong>100 ml</strong> · doar <strong>~<?php echo esc_html( $per ); ?></strong> per parfum</span>
							<span>2 cutii: <strong><?php echo esc_html( $p2 ); ?></strong> + livrare gratuită</span>
						</div>
					</div>
					<?php if ( $s['stock_bar'] ) : ?>
						<div class="ea-stock">
							<div class="ea-stock__row"><span class="ea-stock__left">Au mai rămas <?php echo $stock; ?> cutii din stocul de azi</span><span class="ea-stock__sold"><?php echo esc_html( $sold ); ?> vândut</span></div>
							<div class="ea-stock__bar"><div class="ea-stock__fill" style="width:<?php echo esc_attr( $sold ); ?>"></div></div>
						</div>
					<?php endif; ?>
				</div>

				<div class="ea-pick">
					<span class="ea-pick__label">Alege cutia</span>
					<div class="ea-pick__grid">
						<button type="button" class="ea-seg" data-ea-choice="m"><span class="ea-seg__n">4</span><span class="ea-seg__t">Bărbați</span></button>
						<button type="button" class="ea-seg" data-ea-choice="f"><span class="ea-seg__n">4</span><span class="ea-seg__t">Femei</span></button>
						<button type="button" class="ea-seg is-on" data-ea-choice="x"><span class="ea-seg__n">2+2</span><span class="ea-seg__t">Mix</span></button>
					</div>
				</div>

				<?php if ( $s['countdown'] ) : ?>
					<div class="ea-timer">
						<span class="ea-timer__label">Oferta se încheie în</span>
						<div class="ea-timer__boxes">
							<div class="ea-timer__box"><span class="ea-timer__num" data-ea="hh">00</span><span class="ea-timer__unit">ore</span></div>
							<span class="ea-timer__sep">:</span>
							<div class="ea-timer__box"><span class="ea-timer__num" data-ea="mm">00</span><span class="ea-timer__unit">min</span></div>
							<span class="ea-timer__sep">:</span>
							<div class="ea-timer__box"><span class="ea-timer__num ea-timer__num--gold" data-ea="ss">00</span><span class="ea-timer__unit">sec</span></div>
						</div>
					</div>
				<?php endif; ?>

				<div class="ea-cta-block">
					<a href="#ea-order" class="ea-btn ea-btn--hero" data-ea-open>
						<span>Comandă acum · <?php echo esc_html( $p1 ); ?></span>
						<span class="ea-btn__sub">Plătești la livrare, fără card</span>
					</a>
					<div class="ea-delivery">Comandă azi și o primești <strong data-ea="delivery"><?php echo esc_html( $delivery ); ?></strong></div>
					<div class="ea-checks"><span>✓ Fără cont</span><span>✓ Te sunăm pentru confirmare</span><span>✓ Retur 14 zile</span></div>
				</div>
			</div>

			<div class="ea-hero__visual">
				<div class="ea-hero__arabic" aria-hidden="true">إكسير</div>
				<div class="ea-arch">
					<div class="ea-arch__outline"></div>
					<div class="ea-arch__bg"></div>
					<img class="ea-arch__img" src="<?php echo esc_url( $img ); ?>" alt="Mystery Box Elixir Arabesc cu 4 parfumuri" width="898" height="759">
					<div class="ea-badge">
						<div class="ea-badge__ring"></div>
						<div class="ea-badge__txt"><span class="ea-badge__num"><?php echo (int) $s['price1']; ?></span><span class="ea-badge__cap">lei / 4 × 100 ml</span></div>
					</div>
					<div class="ea-chip">★ <?php echo esc_html( $rating . ' · ' . $count ); ?></div>
				</div>
			</div>
		</div>
	</section>

	<!-- MARQUEE -->
	<div class="ea-marquee" aria-hidden="true">
		<div class="ea-marquee__track">
			<?php
			$notes = array( 'Oud', 'Ambră', 'Mosc alb', 'Șofran', 'Trandafir de Taif', 'Vanilie', 'Tămâie', 'Santal' );
			foreach ( array_merge( $notes, $notes ) as $note ) {
				echo '<span class="ea-marquee__item"><span class="ea-marquee__note">' . esc_html( $note ) . '</span><span class="ea-marquee__diamond"></span></span>';
			}
			?>
		</div>
	</div>

	<!-- BENEFICII -->
	<section class="ea-section ea-benefits">
		<h2 class="ea-h2 ea-h2--center">De ce aleg <em>sute de clienți</em> cutia noastră</h2>
		<div class="ea-benefits__grid">
			<div class="ea-benefits__pair">
				<div class="ea-benefit"><span class="ea-benefit__big">4×</span><span class="ea-benefit__title">4 parfumuri la preț de unul</span><span class="ea-benefit__txt">Un singur parfum arabesc costă adesea cât toată cutia.</span></div>
				<div class="ea-benefit"><span class="ea-benefit__ar">عطر</span><span class="ea-benefit__title">Parfumuri arabești autentice</span><span class="ea-benefit__txt">Oud, ambră, mosc și șofran — arome intense, care țin.</span></div>
			</div>
			<div class="ea-benefits__pair">
				<div class="ea-benefit"><span class="ea-benefit__arch"></span><span class="ea-benefit__title">Gata de oferit cadou</span><span class="ea-benefit__txt">Vine într-o cutie elegantă. Nu mai cauți ambalaj.</span></div>
				<div class="ea-benefit"><span class="ea-benefit__big">0</span><span class="ea-benefit__title">Zero lei plătiți acum</span><span class="ea-benefit__txt">Plătești curierului, numerar sau card, când ai cutia în mână.</span></div>
			</div>
		</div>
	</section>

	<!-- ALEGE CUTIA -->
	<section class="ea-choose">
		<div class="ea-dots ea-dots--choose"></div>
		<div class="ea-choose__inner">
			<div class="ea-choose__head">
				<span class="ea-eyebrow">Același preț pentru oricare</span>
				<h2 class="ea-h2">Pentru cine e <em>cutia</em>?</h2>
			</div>

			<div class="ea-choose__cards">
				<?php
				$cards = array(
					array( 'm', array( '#b07a45', '#b07a45', '#b07a45', '#b07a45' ), '4', 'Pentru el', 'Lemnos · ambrat · intens', false ),
					array( 'x', array( '#b07a45', '#b07a45', '#d79c8f', '#d79c8f' ), '2<span class="ea-gold">+</span>2', 'Pentru amândoi', '2 masculine · 2 feminine', true ),
					array( 'f', array( '#d79c8f', '#d79c8f', '#d79c8f', '#d79c8f' ), '4', 'Pentru ea', 'Floral · oriental · dulce', false ),
				);
				foreach ( $cards as $c ) :
					?>
					<button type="button" class="ea-card<?php echo 'x' === $c[0] ? ' is-on' : ''; ?>" data-ea-choice="<?php echo esc_attr( $c[0] ); ?>">
						<?php if ( $c[5] ) : ?><span class="ea-card__flag">Cea mai aleasă</span><?php endif; ?>
						<div class="ea-card__vials">
							<?php foreach ( $c[1] as $k => $col ) : ?><span class="ea-vial <?php echo $k % 2 ? 'ea-vial--s' : ''; ?>" style="background:<?php echo esc_attr( $col ); ?>"></span><?php endforeach; ?>
						</div>
						<span class="ea-card__num"><?php echo $c[2]; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="ea-card__title"><?php echo esc_html( $c[3] ); ?></span>
						<span class="ea-card__sub"><?php echo esc_html( $c[4] ); ?></span>
						<span class="ea-card__price"><?php echo esc_html( $p1 ); ?></span>
						<span class="ea-card__dot"></span>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="ea-bundles">
				<button type="button" class="ea-bundle is-on" data-ea-bundle="1">
					<span class="ea-bundle__label">1 cutie</span><span class="ea-bundle__price"><?php echo esc_html( $p1 ); ?></span><span class="ea-bundle__sub">4 parfumuri × 100 ml · + livrare</span>
				</button>
				<button type="button" class="ea-bundle" data-ea-bundle="2">
					<span class="ea-bundle__flag ea-bundle__flag--red">Economisești <?php echo esc_html( $save2 ); ?></span>
					<span class="ea-bundle__label">2 cutii</span><span class="ea-bundle__price"><?php echo esc_html( $p2 ); ?></span><span class="ea-bundle__sub ea-bundle__sub--gold">8 parfumuri × 100 ml · livrare gratuită</span>
				</button>
			</div>

			<div class="ea-choose__cta">
				<a href="#ea-order" class="ea-btn ea-btn--lg" data-ea-open>Comandă · <span data-ea="bundleTotal"><?php echo esc_html( $p1 ); ?></span></a>
				<span class="ea-choose__note">Plată la livrare · fără avans · livrare <span data-ea="delivery"><?php echo esc_html( $delivery ); ?></span></span>
			</div>
		</div>
	</section>

	<!-- RECENZII -->
	<section class="ea-section ea-reviews" id="ea-reviews">
		<div class="ea-reviews__head">
			<div class="ea-reviews__title">
				<span class="ea-eyebrow">Recenzii verificate</span>
				<h2 class="ea-h2">Ce spun cei care <em>au deschis cutia</em></h2>
			</div>
			<div class="ea-reviews__summary">
				<div class="ea-reviews__score">
					<span class="ea-reviews__big"><?php echo esc_html( $rating ); ?></span>
					<div class="ea-reviews__scoretxt"><span class="ea-reviews__stars">★★★★★</span><span>din 5 · <?php echo esc_html( $count ); ?></span></div>
				</div>
				<div class="ea-dist">
					<?php foreach ( array( 5, 4, 3, 2 ) as $i => $star ) : ?>
						<div class="ea-dist__row"><span class="ea-dist__n"><?php echo (int) $star; ?></span><div class="ea-dist__bar"><div class="ea-dist__fill" style="width:<?php echo (int) $s['dist'][ $i ]; ?>%"></div></div></div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php if ( $s['ugc_grid'] ) : ?>
			<div class="ea-ugc">
				<?php foreach ( $s['ugc'] as $u ) : ?>
					<div class="ea-ugc__item">
						<?php if ( ! empty( $u['image'] ) ) : ?>
							<img src="<?php echo esc_url( $u['image'] ); ?>" alt="<?php echo esc_attr( $u['label'] ); ?>" loading="lazy">
						<?php else : ?>
							<span class="ea-ugc__label"><?php echo esc_html( $u['label'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="ea-reviews__grid">
			<?php foreach ( $s['reviews'] as $r ) : ?>
				<div class="ea-review">
					<div class="ea-review__top"><span class="ea-review__stars"><?php echo esc_html( $stars( $r['stars'] ) ); ?></span><span class="ea-review__ok">✓ Cumpărător verificat</span></div>
					<p class="ea-review__text">„<?php echo esc_html( $r['text'] ); ?>”</p>
					<span class="ea-review__by"><strong><?php echo esc_html( $r['name'] ); ?></strong> · <?php echo esc_html( $r['city'] ); ?> · <?php echo esc_html( $r['box'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- GARANȚIE -->
	<section class="ea-guarantee">
		<div class="ea-guarantee__inner">
			<div class="ea-guarantee__copy">
				<span class="ea-eyebrow ea-eyebrow--light">Fără niciun risc</span>
				<h2 class="ea-guarantee__title">Plătești doar când ai cutia <em>în mână</em>.</h2>
				<p>Nu-ți cerem cardul și nici avans. Iar dacă nu ești mulțumit, ai 14 zile să returnezi cutia sigilată.</p>
			</div>
			<div class="ea-guarantee__facts">
				<div class="ea-fact"><span class="ea-fact__n">0</span><span class="ea-fact__t">lei avans</span></div>
				<div class="ea-fact"><span class="ea-fact__n">1–3</span><span class="ea-fact__t">zile livrare</span></div>
				<div class="ea-fact"><span class="ea-fact__n">14</span><span class="ea-fact__t">zile retur</span></div>
			</div>
		</div>
	</section>

	<!-- FAQ -->
	<section class="ea-section ea-faq">
		<h2 class="ea-h2 ea-h2--center ea-h2--faq">Întrebări <em>frecvente</em></h2>
		<div class="ea-faq__grid">
			<div class="ea-faq__item"><span class="ea-faq__q">Cât costă în total?</span><span class="ea-faq__a"><?php echo esc_html( $p1 ); ?> cutia cu 4 parfumuri arabești de câte 100 ml, plus livrarea: <?php echo esc_html( $courier ); ?> prin curier sau <?php echo esc_html( $easybox ); ?> la easybox. De la 2 cutii, livrarea e gratuită.</span></div>
			<div class="ea-faq__item"><span class="ea-faq__q">Cum plătesc?</span><span class="ea-faq__a">La curier, numerar sau card. Dacă preferi, poți plăti online cu cardul<?php echo $disc_pct ? ' și primești o reducere de ' . esc_html( $disc_pct ) . '%' : ''; ?>.</span></div>
			<div class="ea-faq__item"><span class="ea-faq__q">Pot alege parfumurile?</span><span class="ea-faq__a">Alegi doar pentru cine e cutia. Parfumurile rămân surpriză.</span></div>
			<div class="ea-faq__item"><span class="ea-faq__q">Primesc două la fel?</span><span class="ea-faq__a">Nu. Cele 4 parfumuri dintr-o cutie sunt diferite.</span></div>
		</div>
	</section>

	<!-- CTA FINAL -->
	<section class="ea-final">
		<div class="ea-final__inner">
			<img class="ea-final__img" src="<?php echo esc_url( $img ); ?>" alt="Mystery Box Elixir Arabesc" loading="lazy">
			<span class="ea-final__ar">صندوق الأسرار</span>
			<h2 class="ea-final__title">Patru parfumuri <em>te așteaptă.</em></h2>
			<div class="ea-final__price"><span class="ea-final__num"><?php echo (int) $s['price1']; ?></span><span class="ea-final__cur">lei</span></div>
			<?php if ( $s['countdown'] || $s['stock_bar'] ) : ?>
				<span class="ea-final__note">
					<?php if ( $s['countdown'] ) : ?>Oferta se încheie în <span class="ea-tab"><span data-ea="hh">00</span>:<span data-ea="mm">00</span>:<span data-ea="ss">00</span></span><?php endif; ?>
					<?php if ( $s['countdown'] && $s['stock_bar'] ) : ?> · <?php endif; ?>
					<?php if ( $s['stock_bar'] ) : ?><?php echo $stock; ?> cutii rămase<?php endif; ?>
				</span>
			<?php endif; ?>
			<a href="#ea-order" class="ea-btn ea-btn--lg" data-ea-open>Comandă cu plata la livrare</a>
		</div>
	</section>

	<?php get_template_part( 'template-parts/footer' ); ?>

	<!-- ELEMENTE FIXE -->
	<div class="ea-toast" data-ea-toast hidden>
		<span class="ea-toast__vial" data-ea="toastColor"></span>
		<div class="ea-toast__body">
			<span><strong data-ea="toastName"></strong> din <span data-ea="toastCity"></span></span>
			<span>a comandat <strong data-ea="toastChoice"></strong></span>
			<span class="ea-toast__ago">acum <span data-ea="toastAgo"></span> min · ✓ verificat</span>
		</div>
	</div>

	<?php if ( $s['whatsapp'] && $wa ) : ?>
		<a class="ea-wa" href="<?php echo esc_url( 'https://wa.me/' . $wa ); ?>" target="_blank" rel="noopener"><span class="ea-wa__dot"></span>WhatsApp</a>
	<?php endif; ?>

	<?php if ( $s['sticky_bar'] ) : ?>
		<div class="ea-sticky">
			<div class="ea-sticky__price">
				<span class="ea-sticky__num"><?php echo esc_html( $p1 ); ?> <span class="ea-sticky__sub">· 4 × 100 ml</span></span>
				<?php if ( $s['countdown'] ) : ?><span class="ea-sticky__timer">Expiră în <span data-ea="hh">00</span>:<span data-ea="mm">00</span>:<span data-ea="ss">00</span></span><?php endif; ?>
			</div>
			<a href="#ea-order" class="ea-btn ea-btn--sticky" data-ea-open>Comandă acum</a>
		</div>
	<?php endif; ?>

	<!-- MODAL COMANDĂ RAPIDĂ -->
	<div class="ea-modal" id="ea-order" role="dialog" aria-modal="true" aria-label="Comandă rapidă" hidden>
		<div class="ea-modal__panel">
			<button type="button" class="ea-modal__close" data-ea-close aria-label="Închide">×</button>

			<form class="ea-form" id="ea-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
				<input type="hidden" name="action" value="ea_mb_order">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( ea_mb_token() ); ?>">
				<input type="hidden" name="choice" value="x" data-ea-input="choice">
				<input type="hidden" name="bundle" value="1" data-ea-input="bundle">
				<input type="hidden" name="ship" value="courier" data-ea-input="ship">
				<input type="hidden" name="pay" value="cod" data-ea-input="pay">
				<div class="ea-hp" aria-hidden="true"><label>Website<input type="text" name="ea_website" tabindex="-1" autocomplete="off"></label></div>

				<div data-ea-view="form" class="ea-form__body">
					<img class="ea-modal__img" src="<?php echo esc_url( $img ); ?>" alt="Mystery Box Elixir Arabesc" loading="lazy">
					<div class="ea-modal__head">
						<span class="ea-modal__eyebrow">Comandă rapidă</span>
						<span class="ea-modal__title">Mystery Box · <em data-ea="choiceLabel">2+2 Mix</em></span>
					</div>


					<div class="ea-spec ea-spec--modal">
						<span class="ea-spec__ar" aria-hidden="true">عطور عربية</span>
						<div class="ea-spec__main">
							<span class="ea-spec__big">4 × 100 ml</span>
							<span class="ea-spec__txt"><strong>Parfumuri arabești</strong> · 100% Elixir Arabesc</span>
						</div>
						<span class="ea-spec__note">Fiecare cutie conține 4 parfumuri arabești diferite, de câte 100 ml.</span>
					</div>

					<?php if ( $err_codes ) : ?>
						<div class="ea-modal__alert">Verifică datele introduse și încearcă din nou.</div>
					<?php endif; ?>

					<?php if ( $s['price_reserved'] ) : ?>
						<div class="ea-reserve"><span>Prețul îți e rezervat încă</span><span class="ea-reserve__time"><span data-ea="resMM">10</span>:<span data-ea="resSS">00</span></span></div>
					<?php endif; ?>

					<div class="ea-mseg">
						<button type="button" class="ea-mseg__btn" data-ea-choice="m">4 Bărbați</button>
						<button type="button" class="ea-mseg__btn" data-ea-choice="f">4 Femei</button>
						<button type="button" class="ea-mseg__btn is-on" data-ea-choice="x">2+2 Mix</button>
					</div>

					<div class="ea-group">
						<span class="ea-group__label">Câte cutii?</span>
						<button type="button" class="ea-opt is-on" data-ea-bundle="1">
							<span class="ea-opt__radio"><span></span></span>
							<span class="ea-opt__txt"><span class="ea-opt__title">1 cutie · 4 parfumuri × 100 ml</span><span class="ea-opt__sub">+ livrare de la <?php echo esc_html( $easybox ); ?></span></span>
							<span class="ea-opt__price"><?php echo esc_html( $p1 ); ?></span>
						</button>
						<button type="button" class="ea-opt" data-ea-bundle="2">
							<span class="ea-opt__radio"><span></span></span>
							<span class="ea-opt__txt"><span class="ea-opt__title">2 cutii · 8 parfumuri × 100 ml</span><span class="ea-opt__sub ea-opt__sub--ok">Livrare gratuită · economisești <?php echo esc_html( $save2 ); ?></span></span>
							<span class="ea-opt__price"><?php echo esc_html( $p2 ); ?></span>
						</button>
						<div class="ea-hint" data-ea-freehint>
							<span>Mai adaugă <strong>o cutie</strong> și livrarea devine <strong>gratuită</strong>.</span>
							<div class="ea-hint__bar"><div class="ea-hint__fill"></div></div>
						</div>
					</div>

					<div class="ea-fields">
						<label class="ea-field">
							<span class="ea-field__label">Nume și prenume</span>
							<input class="ea-input<?php echo in_array( 'name', $err_codes, true ) ? ' is-err' : ''; ?>" name="name" data-ea-field="name" autocomplete="name" placeholder="Ion Popescu">
							<span class="ea-field__err" data-ea-err="name" <?php echo in_array( 'name', $err_codes, true ) ? '' : 'hidden'; ?>>Scrie numele complet.</span>
						</label>
						<label class="ea-field">
							<span class="ea-field__label">Telefon</span>
							<input class="ea-input<?php echo in_array( 'phone', $err_codes, true ) ? ' is-err' : ''; ?>" name="phone" data-ea-field="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="07xx xxx xxx">
							<span class="ea-field__err" data-ea-err="phone" <?php echo in_array( 'phone', $err_codes, true ) ? '' : 'hidden'; ?>>Număr invalid (ex. 0722 123 456).</span>
						</label>
						<label class="ea-field">
							<span class="ea-field__label">E-mail <em class="ea-field__opt">(opțional)</em></span>
							<input class="ea-input<?php echo in_array( 'email', $err_codes, true ) ? ' is-err' : ''; ?>" name="email" data-ea-field="email" type="email" inputmode="email" autocomplete="email" placeholder="nume@exemplu.ro">
							<span class="ea-field__hint">Nu e obligatoriu. Dacă îl completezi, îți trimitem confirmarea comenzii și pe e-mail.</span>
							<span class="ea-field__err" data-ea-err="email" <?php echo in_array( 'email', $err_codes, true ) ? '' : 'hidden'; ?>>Adresa de e-mail nu este validă.</span>
						</label>
						<div class="ea-fields__row">
							<label class="ea-field">
								<span class="ea-field__label">Județ</span>
								<select class="ea-input ea-select<?php echo in_array( 'county', $err_codes, true ) ? ' is-err' : ''; ?>" name="county" data-ea-field="county" autocomplete="address-level1">
										<option value="">Alege județul</option>
										<?php foreach ( (array) WC()->countries->get_states( 'RO' ) as $state_label ) : ?>
											<option value="<?php echo esc_attr( $state_label ); ?>"><?php echo esc_html( $state_label ); ?></option>
										<?php endforeach; ?>
									</select>
							</label>
							<label class="ea-field">
								<span class="ea-field__label">Localitate</span>
								<input class="ea-input<?php echo in_array( 'city', $err_codes, true ) ? ' is-err' : ''; ?>" name="city" data-ea-field="city" autocomplete="address-level2" placeholder="Voluntari">
							</label>
						</div>
						<label class="ea-field">
							<span class="ea-field__label" data-ea="addressLabel">Adresă</span>
							<input class="ea-input<?php echo in_array( 'address', $err_codes, true ) ? ' is-err' : ''; ?>" name="address" data-ea-field="address" autocomplete="street-address" placeholder="Strada, număr, bloc, apartament" data-ea-placeholder>
							<span class="ea-field__err" data-ea-err="address" <?php echo array_intersect( array( 'county', 'city', 'address' ), $err_codes ) ? '' : 'hidden'; ?>>Completează județul, localitatea și adresa.</span>
						</label>
						<?php do_action( 'ea_mb_after_address_field' ); ?>
					</div>

					<div class="ea-group">
						<span class="ea-group__label">Livrare</span>
						<div class="ea-ship">
							<button type="button" class="ea-ship__btn is-on" data-ea-ship="courier"><span class="ea-ship__name">Curier la adresă</span><span class="ea-ship__price" data-ea="courierPrice"><?php echo esc_html( $courier ); ?></span></button>
							<button type="button" class="ea-ship__btn" data-ea-ship="easybox"><span class="ea-ship__name">Locker easybox</span><span class="ea-ship__price" data-ea="easyboxPrice"><?php echo esc_html( $easybox ); ?></span></button>
						</div>
					</div>

					<div class="ea-group">
						<span class="ea-group__label">Plata</span>
						<button type="button" class="ea-opt ea-opt--pay is-on" data-ea-pay="cod">
							<span class="ea-opt__radio"><span></span></span>
							<span class="ea-opt__txt"><span class="ea-opt__title">La livrare (ramburs)</span><span class="ea-opt__sub">Numerar sau card, la curier</span></span>
						</button>
						<?php if ( $card ) : ?>
							<button type="button" class="ea-opt ea-opt--pay" data-ea-pay="card">
								<span class="ea-opt__radio"><span></span></span>
								<span class="ea-opt__txt"><span class="ea-opt__title">Card online<?php echo $disc_pct ? ' · −' . esc_html( $disc_pct ) . '%' : ''; ?></span><?php if ( $disc_pct ) : ?><span class="ea-opt__sub ea-opt__sub--ok">Reducere <?php echo esc_html( $disc_pct ); ?>% la plata online cu cardul</span><?php endif; ?></span>
							</button>
						<?php endif; ?>
					</div>

					<div class="ea-sum">
						<div class="ea-sum__row"><span data-ea="bundleLine">1 cutie · 4 parfumuri × 100 ml</span><strong data-ea="subtotal"><?php echo esc_html( $p1 ); ?></strong></div>
						<div class="ea-sum__row" data-ea-discrow hidden><span data-ea="discLabel">Reducere card</span><strong class="is-free" data-ea="discount">—</strong></div>
						<div class="ea-sum__row"><span>Livrare · <span data-ea="delivery"><?php echo esc_html( $delivery ); ?></span></span><strong data-ea="shipping" data-ea-shipping>—</strong></div>
						<div class="ea-sum__total"><span class="ea-sum__totlabel">Total</span><span class="ea-sum__totnum" data-ea="total"><?php echo esc_html( $p1 ); ?></span></div>
					</div>

					<label class="ea-terms" data-ea-terms>
						<input type="checkbox" name="terms" value="1" data-ea-field="terms">
						<span>Am citit și sunt de acord cu <a href="<?php echo esc_url( ea_legal_url( 'termeni-si-conditii' ) ); ?>" target="_blank" rel="noopener">Termenii și condițiile</a>, <a href="<?php echo esc_url( ea_legal_url( 'politica-de-confidentialitate' ) ); ?>" target="_blank" rel="noopener">Politica de confidențialitate</a> și <a href="<?php echo esc_url( ea_legal_url( 'retur-si-dreptul-de-retragere' ) ); ?>" target="_blank" rel="noopener">politica de retur</a>.</span>
					</label>

					<div class="ea-form__error" data-ea-formerror hidden></div>
					<button type="submit" class="ea-submit" data-ea="submitLabel">Comand și plătesc la livrare</button>
					<span class="ea-form__note" data-ea="submitNote">Nu plătești nimic acum. Te sunăm pentru confirmare.</span>
				</div>

				<div data-ea-view="done" class="ea-done" hidden>
					<span class="ea-done__ar">شكراً</span>
					<span class="ea-done__title">Mulțumim, <em data-ea="firstName"></em>.</span>
					<p>Te sunăm la <span data-ea="phoneVal"></span> ca să confirmăm: <span data-ea="bundleLine"></span> · <span data-ea="choiceLabel"></span>, total <span data-ea="total"></span>, <span data-ea="payLabel"></span>. Livrare estimată <span data-ea="delivery"><?php echo esc_html( $delivery ); ?></span>.</p>
					<button type="button" class="ea-done__close" data-ea-close>Închide</button>
				</div>
			</form>
		</div>
	</div>

</div>

<?php wp_footer(); ?>
</body>
</html>
