<?php declare( strict_types=1 );
/**
 * The authorize button view, allowing the user to authorize their install with
 * the license server via the origin site.
 *
 * @see \StellarWP\Uplink\Components\Authorize_Button_Controller
 *
 * @var \League\Plates\Template\Template $this
 * @var string $link_text The link text, changes based on whether the user is authorized to authorize :)
 * @var string $url The location the link goes to, either the custom origin URL, or a link to the admin.
 * @var string $target The link target.
 */
?>

<div>
	<a href="<?php echo esc_url( $url ) ?>" target="<?php esc_attr( $target ) ?>">
		<?php echo esc_html( $link_text ) ?>
	</a>
</div>

