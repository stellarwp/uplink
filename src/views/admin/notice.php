<?php declare( strict_types=1 );
/**
 * Render a WordPress dashboard notice.
 *
 * @see \StellarWP\Uplink\Notice\Notice_Controller
 *
 * @var \League\Plates\Template\Template $this
 * @var string $message The message to display.
 * @var string $classes The CSS classes for the notice.
 */
?>
<div class="<?php echo esc_attr( $classes ) ?>">
	<p><?php echo esc_html( $message ) ?></p>
</div>
