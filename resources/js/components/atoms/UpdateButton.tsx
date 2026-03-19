/**
 * Icon button that triggers a feature update.
 *
 * When upgradeLabel is provided the button is rendered disabled with an
 * upsell tooltip. The Tooltip component handles the span wrapper needed to
 * keep hover events working when pointer-events are disabled on the button.
 *
 * @package StellarWP\Uplink
 */
import { __, sprintf } from '@wordpress/i18n';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';

interface UpdateButtonProps {
	featureName:   string;
	disabled?:     boolean;
	onClick?:      () => void;
	/** When set, the button is disabled and this text is shown as an upsell tooltip. */
	upgradeLabel?: string;
}

/**
 * @since 3.0.0
 */
export function UpdateButton( { featureName, disabled = false, onClick, upgradeLabel }: UpdateButtonProps ) {
	const button = (
		<Button
			variant="default"
			size="icon-xs"
			className="rounded-full"
			disabled={ !! upgradeLabel || disabled }
			onClick={ onClick }
			aria-label={ sprintf( __( 'Update %s', '%TEXTDOMAIN%' ), featureName ) }
		>
			<Download className="w-3.5 h-3.5" />
		</Button>
	);

	return upgradeLabel
		? <Tooltip label={ upgradeLabel }>{ button }</Tooltip>
		: button;
}
