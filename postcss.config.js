/**
 * Scope all Tailwind-generated CSS rules under .uplink-ui.
 *
 * Tailwind v4's @config compatibility layer does not support the selector
 * strategy (important: '.selector'), so scoping is handled here instead.
 * Running after @tailwindcss/postcss (which expands all utilities) and before
 * autoprefixer (so vendor prefixes are added to the already-scoped selectors).
 *
 * Rules excluded from scoping:
 *   - :root   — CSS variables must remain global to be reachable by var()
 *   - @keyframes content — animation keyframes don't use ancestor selectors
 *   - Rules already containing .uplink-ui — written that way intentionally
 *     (e.g. the base resets in globals.css)
 */
function scopeToUplinkUI() {
    const plugin = () => ( {
        postcssPlugin: 'postcss-scope-to-uplink-ui',
        Rule( rule ) {
            if (
                rule.selector.includes( '.uplink-ui' ) ||
                /^:root\b/.test( rule.selector.trim() ) ||
                /^keyframes$/i.test( rule.parent?.name ?? '' )
            ) {
                return;
            }
            rule.selector = rule.selectors
                .map( ( s ) => `.uplink-ui ${ s }` )
                .join( ',\n' );
        },
    } );
    plugin.postcss = true;
    return plugin;
}

module.exports = {
    plugins: [
        require( '@tailwindcss/postcss' ),
        scopeToUplinkUI(),
        require( 'autoprefixer' ),
    ],
};
