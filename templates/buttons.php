<?php
/**
 * Theme override: savethedate/buttons.php.
 *
 * @package SaveTheDate
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="stdgf-buttons stdgf-buttons--<?php echo esc_attr( $style ); ?> stdgf-buttons--horizontal" role="group" aria-label="<?php esc_attr_e( 'Add to calendar', 'savethedate-for-gravity-forms' ); ?>" style="--stdgf-accent:<?php echo esc_attr( $accent ); ?>;--stdgf-text:<?php echo esc_attr( $text ); ?>">
	<p class="stdgf-buttons__heading"><?php esc_html_e( 'Add this event to your calendar', 'savethedate-for-gravity-forms' ); ?></p>
	<?php foreach ( $urls as $stdgf_provider => $stdgf_url ) : ?>
		<a class="stdgf-buttons__item stdgf-buttons__item--<?php echo esc_attr( $stdgf_provider ); ?>" href="<?php echo esc_url( $stdgf_url ); ?>"
		<?php
		if ( 'ics' === $stdgf_provider ) :
			?>
			download="event.ics"
			<?php
else :
	?>
			target="_blank" rel="noopener noreferrer"<?php endif; ?>>
			<span><?php echo esc_html( $labels[ $stdgf_provider ] ); ?></span>
			<?php
			if ( 'ics' !== $stdgf_provider ) :
				?>
				<span class="stdgf-sr-only"><?php esc_html_e( '(opens in a new tab)', 'savethedate-for-gravity-forms' ); ?></span><?php endif; ?>
		</a>
	<?php endforeach; ?>
</div>
