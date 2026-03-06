/**
 * Type guard utilities for narrowing Feature union types.
 *
 * @package StellarWP\Uplink
 */
import type {
	Feature,
	PluginFeature,
	ThemeFeature,
	FlagFeature,
	InstallableFeature,
} from '@/types/api';

export function isPluginFeature( feature: Feature ): feature is PluginFeature {
	return feature.type === 'plugin';
}

export function isThemeFeature( feature: Feature ): feature is ThemeFeature {
	return feature.type === 'theme';
}

export function isFlagFeature( feature: Feature ): feature is FlagFeature {
	return feature.type === 'flag';
}

export function isInstallableFeature( feature: Feature ): feature is InstallableFeature {
	return feature.type === 'plugin' || feature.type === 'theme';
}
