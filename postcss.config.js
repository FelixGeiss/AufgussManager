/**
 * PostCSS Konfigurationsdatei
 *
 * PostCSS ist ein Tool, das CSS verarbeitet und transformiert.
 * Es ist wie ein "CSS-Compiler" mit vielen Plugins.
 *
 * In unserem Projekt verwenden wir PostCSS für:
 * 1. Tailwind CSS: Wandelt Tailwind-Klassen in echtes CSS um
 * 2. Autoprefixer: Fügt automatisch Vendor-Prefixes hinzu (-webkit-, -moz-, etc.)
 *
 * Als Anfänger solltest du wissen:
 * - PostCSS nimmt unsere input.css und erzeugt daraus style.css
 * - Der Build-Prozess: input.css → PostCSS → style.css
 * - npm run build führt diesen Prozess aus
 */

module.exports = {
  plugins: {
    /**
     * TAILWINDCSS PLUGIN
     *
     * Das wichtigste Plugin: Verarbeitet Tailwind CSS
     * Nimmt die @tailwind Direktiven aus input.css und
     * ersetzt sie durch das komplette Tailwind CSS
     */
    tailwindcss: {},

    /**
     * AUTOPREFIXER PLUGIN
     *
     * Fügt automatisch Vendor-Prefixes für verschiedene Browser hinzu.
     * Beispiel:
     *   transform: scale(1.1);
     * Wird zu:
     *   -webkit-transform: scale(1.1);
     *   -moz-transform: scale(1.1);
     *   transform: scale(1.1);
     *
     * Das stellt sicher, dass moderne CSS-Eigenschaften in älteren
     * Browsern funktionieren.
     */
    autoprefixer: {},
  },
}
