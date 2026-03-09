/**
 * Machine-readable error codes for UplinkError instances.
 *
 * @package StellarWP\Uplink
 */
export enum ErrorCode {
	FeaturesFetchFailed = 'features-fetch-failed',
	FeatureEnableFailed = 'feature-enable-failed',
	FeatureDisableFailed = 'feature-disable-failed',
	LicenseFetchFailed = 'license-fetch-failed',
	LicenseActionInProgress = 'license-action-in-progress',
	LicenseActivateFailed = 'license-activate-failed',
	LicenseDeleteFailed = 'license-delete-failed',
}
