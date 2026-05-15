<?php
/**
 * Template part: Agenda calendar tab in Mi Espacio.
 * The JS function initAgendaCalendar() populates the grid.
 */

defined( 'ABSPATH' ) || exit;

$now   = current_time( 'timestamp' );
$year  = (int) date( 'Y', $now );
$month = (int) date( 'm', $now );
?>

<div class="cal-month" id="cal-month"
     data-year="<?php echo esc_attr( $year ); ?>"
     data-month="<?php echo esc_attr( $month ); ?>">

	<nav class="cal-month__nav" aria-label="Navegar por mes">
		<button type="button" class="btn-ghost cal-month__nav-btn" id="cal-prev" aria-label="Mes anterior">
			<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
				<path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</button>
		<h2 class="cal-month__heading" id="cal-heading"></h2>
		<button type="button" class="btn-ghost cal-month__nav-btn" id="cal-next" aria-label="Mes siguiente">
			<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
				<path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</button>
	</nav>

	<div class="cal-month__weekdays" aria-hidden="true">
		<span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span>
		<span>Vi</span><span>Sa</span><span>Do</span>
	</div>

	<div class="cal-month__grid" id="cal-grid" aria-live="polite"></div>

	<div class="cal-event-popup" id="cal-event-popup" hidden role="dialog" aria-modal="false" aria-label="Detalles del evento">
		<button type="button" class="cal-event-popup__close" id="cal-popup-close" aria-label="Cerrar">×</button>
		<h3 class="cal-event-popup__title" id="cal-popup-title"></h3>
		<p  class="cal-event-popup__date"  id="cal-popup-date"></p>
		<p  class="cal-event-popup__desc"  id="cal-popup-desc"></p>
	</div>
</div>
