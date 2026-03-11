import type { UplinkData } from './uplink-data';

declare global {
    interface Window {
        uplinkData?: UplinkData;
    }
}

// SVG files: default export is a URL string; ReactComponent is the inline SVG.
declare module '*.svg' {
    import type { FC, SVGProps } from 'react';
    export const ReactComponent: FC<SVGProps<SVGSVGElement>>;
    const src: string;
    export default src;
}
