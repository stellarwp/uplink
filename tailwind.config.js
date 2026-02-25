/**
 * Tailwind CSS configuration.
 *
 * important: true adds !important to every utility declaration.
 *
 * WHY: Tailwind v4 outputs all utilities inside @layer. Per the CSS cascade spec,
 * any unlayered stylesheet (e.g. WordPress admin's load-styles) beats named layers
 * regardless of specificity. !important inside a named layer reverses this: it beats
 * normal unlayered declarations, restoring the expected utility-first behaviour.
 *
 * Scoping to .uplink-ui is handled by the PostCSS plugin in postcss.config.js,
 * which prefixes all generated selectors with .uplink-ui after Tailwind runs.
 * Tailwind v4 does not support the selector strategy via @config compatibility.
 *
 * @see resources/css/globals.css  for the @config reference.
 * @see postcss.config.js          for the scoping plugin.
 */
/** @type {import('tailwindcss').Config} */
module.exports = {
    important: true,
};
