/**
 * Machine-readable error codes for UplinkError instances.
 *
 * @package StellarWP\Uplink
 */
export enum ErrorCode {
	FeaturesFetchFailed = 'features-fetch-failed',
	FeatureEnableFailed = 'feature-enable-failed',
	FeatureDisableFailed = 'feature-disable-failed',
	FeatureUpdateFailed = 'feature-update-failed',
	LicenseFetchFailed = 'license-fetch-failed',
	LicenseActionInProgress = 'license-action-in-progress',
	LicenseStoreFailed = 'license-store-failed',
	LicenseDeleteFailed = 'license-delete-failed',
	LicenseValidateFailed = 'license-validate-failed',
	CatalogFetchFailed = 'catalog-fetch-failed',
}
