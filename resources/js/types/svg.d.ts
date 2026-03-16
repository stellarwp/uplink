// SVG files imported by webpack (@svgr/webpack + url-loader):
// - default export: URL string (for use in <img src>)
// - ReactComponent: inline SVG React component
declare module '*.svg' {
    import type { FC, SVGProps } from 'react';
    export const ReactComponent: FC<SVGProps<SVGSVGElement>>;
    const src: string;
    export default src;
}
