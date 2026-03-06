/**
 * Active license key card.
 *
 * Shows the stored unified key and provides a Remove button wired to the
 * stellarwp/uplink @wordpress/data store.
 *
 * @package StellarWP\\Uplink
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Key, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { store as uplinkStore } from '@/store';
import { UplinkError } from '@/errors';
import { useToast } from '@/context/toast-context';

interface LicenseCardProps {
	licenseKey: string;
}

/**
 * @since 3.0.0
 */
export function LicenseCard({ licenseKey }: LicenseCardProps) {
	const { deleteLicense } = useDispatch(uplinkStore);
	const { addToast } = useToast();

	// TODO: Refactor error display to use an error modal instead of
	// toasts. The modal will show safe, user-facing messages from the
	// UplinkError chain.

	const { isDeleting, canModifyLicense } = useSelect(
		(select) => ({
			isDeleting: select(uplinkStore).isLicenseDeleting(),
			canModifyLicense: select(uplinkStore).canModifyLicense(),
		}),
		[]
	);

	const handleDelete = async () => {
		const result = await deleteLicense();
		if (result instanceof UplinkError) {
			addToast(result.message, 'error');
		} else {
			addToast(
				__('License removed successfully.', '%TEXTDOMAIN%'),
				'success'
			);
		}
	};

	return (
		<div className="flex items-center justify-between px-4 py-3">
			<div className="flex items-center gap-3 min-w-0">
				<Key className="w-4 h-4 text-slate-400 shrink-0" />
				<span className="font-mono text-sm text-slate-700 truncate">
					{licenseKey}
				</span>
			</div>
			<Button
				variant="destructive"
				size="sm"
				onClick={handleDelete}
				disabled={!canModifyLicense}
				className="shrink-0 ml-4"
			>
				{isDeleting ? (
					<>
						<Loader2 className="w-3 h-3 animate-spin" />
						{__('Removing…', '%TEXTDOMAIN%')}
					</>
				) : (
					__('Remove', '%TEXTDOMAIN%')
				)}
			</Button>
		</div>
	);
}
