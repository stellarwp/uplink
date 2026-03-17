import type { UplinkData } from './uplink-data';

declare global {
    interface Window {
        uplinkData?: UplinkData;
        uplink?: {
            legacyLicenses?: Array<{ key: string; slug: string; name: string; status: string }>;
        };
    }
}
