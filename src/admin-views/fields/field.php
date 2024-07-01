<?php declare( strict_types=1 );
/**
 * @var Field $field The Field object.
 */

use StellarWP\Uplink\Admin\Fields\Field;
use StellarWP\Uplink\Config;
?>

<?php do_action( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/license_field_before_field', $field->get_slug() ) ?>
<?php if ( $field->should_show_label() ) : ?>
	<table class="form-table" role="presentation">
		<tr class="stellarwp-uplink-license-key-field">
			<th scope="row">
				<label for="<?php echo esc_attr( $field->get_id() ); ?>"><?php echo esc_html( $field->get_label() ); ?></label>
			</th>
			<td>
				<?php endif; ?>
				<div class="stellarwp-uplink__license-field">
					<div
						class="<?php echo esc_attr( $field->get_classes() ); ?>"
						id="<?php echo esc_attr( $field->get_field_id() ); ?>"
						data-slug="<?php echo esc_attr( $field->get_slug() ); ?>"
						data-plugin="<?php echo esc_attr( $field->get_product() ); ?>"
						data-plugin-slug="<?php echo esc_attr( $field->get_product_slug() ); ?>"
						data-action="<?php echo esc_attr( $field->get_action() ); ?>"
					>
						<fieldset class="stellarwp-uplink__settings-group">
							<input
								type="text"
								name="<?php echo esc_attr( $field->get_field_name() ); ?>"
								value="<?php echo esc_attr( $field->get_field_value() ); ?>"
								placeholder="<?php echo esc_attr( $field->get_placeholder() ); ?>"
								class="regular-text stellarwp-uplink__settings-field"
							/>
							<?php echo $field->get_key_status_html(); ?>
						</fieldset>
						<?php echo $field->get_nonce_field(); ?>
					</div>
				</div>
				<?php if ( $field->should_show_label() ) : ?>
			</td>
		</tr>
	</table>
<?php endif; ?>
<?php do_action( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/license_field_after_field', $field->get_slug() ) ?>
