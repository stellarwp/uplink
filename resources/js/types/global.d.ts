import type { UplinkData } from './uplink-data';

declare global {
    interface Window {
        uplinkData?: UplinkData;
    }
}
