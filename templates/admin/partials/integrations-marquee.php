<?php
/**
 * Admin Partial Template - Integrations Marquee
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DWM_Features' ) ) {
	require_once DWM_PLUGIN_DIR . 'includes/core/class-dwm-features.php';
}

$integration_groups = DWM_Features::get_all_integrations();
$labels             = DWM_Features::get_labels();
$integration_items  = [];

foreach ( $integration_groups as $group_items ) {
	foreach ( (array) $group_items as $integration ) {
		if ( empty( $integration['docs_page'] ) ) {
			continue;
		}
		$integration_items[] = $integration;
	}
}

if ( empty( $integration_items ) ) {
	return;
}
?>

<section class="dwm-integrations-marquee-section" data-dwm-integrations-marquee>
	<div class="dwm-integrations-marquee-header">
		<span class="dwm-integrations-marquee-eyebrow"><?php esc_html_e( 'Pro Integrations', 'dashboard-widget-manager' ); ?></span>
		<h3><?php esc_html_e( 'Connect Widget Data To Your Full Stack', 'dashboard-widget-manager' ); ?></h3>
		<p><?php esc_html_e( 'Send dashboard insights to analytics, monitoring, collaboration, and automation tools with one consistent integration layer.', 'dashboard-widget-manager' ); ?></p>
	</div>

	<div class="dwm-integrations-marquee" data-marquee-root>
		<button type="button" class="dwm-integrations-marquee-arrow is-left" data-marquee-dir="-1" aria-label="<?php esc_attr_e( 'Scroll integrations left', 'dashboard-widget-manager' ); ?>">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
		</button>
		<div class="dwm-integrations-marquee-viewport" data-marquee-viewport>
			<div class="dwm-integrations-marquee-track" data-marquee-track>
				<?php foreach ( $integration_items as $integration ) : ?>
					<article class="dwm-integrations-marquee-card">
						<div class="dwm-integrations-marquee-card-head">
							<div class="dwm-integrations-marquee-logo">
								<?php if ( ! empty( $integration['image'] ) ) : ?>
									<img src="<?php echo esc_url( $integration['image'] ); ?>" alt="" loading="lazy">
								<?php elseif ( ! empty( $integration['icon'] ) ) : ?>
									<span><?php echo esc_html( $integration['icon'] ); ?></span>
								<?php endif; ?>
							</div>
							<div class="dwm-integrations-marquee-badges">
								<span class="dwm-pro-badge"><?php echo esc_html( $labels['badge_pro'] ?? __( 'Pro', 'dashboard-widget-manager' ) ); ?></span>
								<?php if ( empty( $integration['implemented'] ) ) : ?>
									<span class="dwm-badge dwm-badge-primary"><?php echo esc_html( $labels['badge_coming_soon'] ?? __( 'Coming Soon', 'dashboard-widget-manager' ) ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<h4><?php echo esc_html( $integration['title'] ?? '' ); ?></h4>
						<p><?php echo esc_html( wp_trim_words( $integration['description'] ?? '', 14, '...' ) ); ?></p>
						<button type="button" class="dwm-integrations-marquee-link" data-open-modal="dwm-docs-modal" data-docs-page="<?php echo esc_attr( $integration['docs_page'] ); ?>">
							<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
						</button>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
		<button type="button" class="dwm-integrations-marquee-arrow is-right" data-marquee-dir="1" aria-label="<?php esc_attr_e( 'Scroll integrations right', 'dashboard-widget-manager' ); ?>">
			<span class="dashicons dashicons-arrow-right-alt2"></span>
		</button>
	</div>
</section>

<style>
.dwm-integrations-marquee-section{margin:24px 0;background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:24px;overflow:hidden}
.dwm-integrations-marquee-header{text-align:center;margin-bottom:16px}
.dwm-integrations-marquee-eyebrow{display:inline-block;padding:3px 10px;border-radius:999px;background:rgba(118,75,162,.12);color:#764ba2;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.dwm-integrations-marquee-header h3{margin:10px 0 6px;font-size:24px;line-height:1.25}
.dwm-integrations-marquee-header p{margin:0 auto;max-width:760px;color:#6b7280;font-size:14px;line-height:1.6}
.dwm-integrations-marquee{position:relative}
.dwm-integrations-marquee-viewport{overflow:hidden;mask-image:linear-gradient(to right,transparent 0,#000 5%,#000 95%,transparent 100%)}
.dwm-integrations-marquee-track{display:flex;gap:14px;will-change:transform;padding:4px 0}
.dwm-integrations-marquee-card{flex:0 0 280px;max-width:280px;height:190px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc;padding:12px;display:flex;flex-direction:column;gap:8px}
.dwm-integrations-marquee-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.dwm-integrations-marquee-logo{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #e5e7eb;flex-shrink:0}
.dwm-integrations-marquee-logo img{width:22px;height:22px;object-fit:contain}
.dwm-integrations-marquee-badges{display:flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end}
.dwm-integrations-marquee-card h4{margin:0;font-size:15px;line-height:1.25;color:#111827}
.dwm-integrations-marquee-card p{margin:0;color:#6b7280;font-size:12px;line-height:1.5;flex:1}
.dwm-integrations-marquee-link{align-self:flex-start;background:none;border:none;padding:0;color:#764ba2;font-weight:600;cursor:pointer}
.dwm-integrations-marquee-arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:3;width:34px;height:34px;border-radius:999px;border:1px solid rgba(118,75,162,.35);background:#fff;color:#764ba2;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s ease}
.dwm-integrations-marquee:hover .dwm-integrations-marquee-arrow{opacity:1;pointer-events:auto}
.dwm-integrations-marquee-arrow.is-left{left:6px}
.dwm-integrations-marquee-arrow.is-right{right:6px}
</style>

<script>
(function(){
	const root=document.querySelector('[data-dwm-integrations-marquee]');
	if(!root){return;}
	const viewport=root.querySelector('[data-marquee-viewport]');
	const track=root.querySelector('[data-marquee-track]');
	if(!viewport||!track){return;}
	const originals=Array.from(track.children);
	if(!originals.length){return;}
	originals.forEach(function(card){ track.appendChild(card.cloneNode(true)); });

	let baseSpeed=0.9;
	let pointerSpeed=0;
	let paused=false;
	let rafId=0;

	function getLoopWidth(){ return track.scrollWidth/2; }

	function tick(){
		const loopWidth=getLoopWidth();
		const delta=(paused?0:baseSpeed)+pointerSpeed;
		viewport.scrollLeft += delta;
		if (viewport.scrollLeft >= loopWidth) { viewport.scrollLeft -= loopWidth; }
		if (viewport.scrollLeft < 0) { viewport.scrollLeft += loopWidth; }
		rafId=requestAnimationFrame(tick);
	}

	root.addEventListener('mouseleave',function(){ paused=false; pointerSpeed=0; });
	track.addEventListener('mouseover',function(e){
		const enteredCard = e.target.closest('.dwm-integrations-marquee-card');
		if (!enteredCard) { return; }
		const fromCard = e.relatedTarget && e.relatedTarget.closest ? e.relatedTarget.closest('.dwm-integrations-marquee-card') : null;
		if (fromCard === enteredCard) { return; }
		paused = true;
	});
	track.addEventListener('mouseout',function(e){
		const exitedCard = e.target.closest('.dwm-integrations-marquee-card');
		if (!exitedCard) { return; }
		const toCard = e.relatedTarget && e.relatedTarget.closest ? e.relatedTarget.closest('.dwm-integrations-marquee-card') : null;
		if (toCard === exitedCard) { return; }
		paused = false;
	});
	root.querySelectorAll('[data-marquee-dir]').forEach(function(btn){
		const dir=parseFloat(btn.getAttribute('data-marquee-dir')||'0') || 0;
		btn.addEventListener('mouseenter',function(){ pointerSpeed=dir*2.7; });
		btn.addEventListener('mouseleave',function(){ pointerSpeed=0; });
		btn.addEventListener('mousedown',function(){ pointerSpeed=dir*4.5; });
		btn.addEventListener('mouseup',function(){ pointerSpeed=dir*2.7; });
		btn.addEventListener('click',function(){ pointerSpeed=dir*2.7; });
	});

	if (rafId) { cancelAnimationFrame(rafId); }
	tick();
})();
</script>
