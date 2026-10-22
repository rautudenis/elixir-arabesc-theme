<?php
/**
 * Template Name: Pagină legală
 *
 * Aceeași identitate vizuală ca landing-ul Mystery Box (fără header/footer Woodmart).
 */
defined( 'ABSPATH' ) || exit;

the_post();
$title   = get_the_title();
$words   = explode( ' ', $title );
$last    = array_pop( $words );
$heading = $words ? esc_html( implode( ' ', $words ) ) . ' <em>' . esc_html( $last ) . '</em>' : '<em>' . esc_html( $last ) . '</em>';
$current = get_post_field( 'post_name' );
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

<div class="ea-page ea-page--legal" id="ea-page">

	<div class="ea-topbar">
		<span>Plata la livrare</span><span class="ea-topbar__dot">·</span><span>Retur 14 zile</span><span class="ea-topbar__dot">·</span><span>Livrare gratuită de la 2 cutii</span>
	</div>

	<header class="ea-header">
		<a class="ea-brand" href="<?php echo esc_url( ea_landing_url() ); ?>">
			<img class="ea-brand__logo" src="<?php echo esc_url( EA_MB_URI . '/assets/img/elixir-arabesc-logo.png' ); ?>" alt="Elixir Arabesc – parfumuri orientale" width="900" height="345">
		</a>
		<a href="<?php echo esc_url( ea_landing_url() ); ?>" class="ea-btn ea-btn--sm">← Înapoi la ofertă</a>
	</header>

	<section class="ea-legal-hero">
		<div class="ea-dots ea-dots--hero"></div>
		<div class="ea-legal-hero__inner">
			<span class="ea-eyebrow">Informații legale</span>
			<h1 class="ea-legal-hero__title"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput -- escapat mai sus. ?></h1>
			<span class="ea-legal-hero__meta">Ultima actualizare: <?php echo esc_html( get_the_modified_date( 'j F Y' ) ); ?></span>
		</div>
	</section>

	<nav class="ea-legal-nav" aria-label="Pagini legale">
		<?php foreach ( ea_legal_pages() as $slug => $def ) : ?>
			<a href="<?php echo esc_url( ea_legal_url( $slug ) ); ?>"<?php echo $slug === $current ? ' class="is-on" aria-current="page"' : ''; ?>><?php echo esc_html( $def[2] ); ?></a>
		<?php endforeach; ?>
	</nav>

	<main class="ea-legal">
		<article class="ea-legal__body">
			<?php
			if ( 'termeni-si-conditii' === $current ) {
				$co = ea_company();
				?>
			<h2>Rolul companiei și entitatea care facturează</h2>
			<ul class="ea-legal__role">
				<li><strong>Rolul companiei în comercializare:</strong> <?php echo esc_html( $co['name'] ); ?> acționează în calitate de <strong>comerciant</strong>: vinde în nume și pe cont propriu produsele afișate pe acest site către consumatori. Nu este marketplace, intermediar, distribuitor sau agent al altor vânzători.</li>
				<li><strong>Entitatea care comercializează produsele și emite factura:</strong> <?php echo esc_html( $co['name'] ); ?>, CUI <?php echo esc_html( $co['cui'] ); ?>, nr. Registrul Comerțului <?php echo esc_html( $co['regcom'] ); ?>, cu sediul în <?php echo esc_html( $co['address'] ); ?>. Factura fiscală pentru fiecare comandă este emisă de această societate, sub denumirea comercială <?php echo esc_html( $co['brand'] ); ?>.</li>
				<li><strong>Contact:</strong> <a href="mailto:<?php echo esc_attr( $co['email'] ); ?>"><?php echo esc_html( $co['email'] ); ?></a>, <?php echo esc_html( $co['phone'] ); ?>.</li>
			</ul>
				<?php
			}
			the_content();
			?>
		</article>
	</main>

	<?php get_template_part( 'template-parts/footer' ); ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
