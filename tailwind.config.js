/**
 * Tailwind CSS Konfigurationsdatei
 *
 * Tailwind CSS ist ein Utility-First CSS-Framework, das uns hilft,
 * moderne und responsive Webseiten schnell zu erstellen.
 *
 * Diese Datei konfiguriert, wie Tailwind in unserem Projekt funktioniert.
 *
 * Als Anfänger solltest du wissen:
 * - Tailwind verwendet kleine CSS-Klassen wie 'bg-blue-500' oder 'text-center'
 * - Diese Konfiguration sagt Tailwind, welche Dateien es scannen soll
 * - Wir erweitern das Standard-Theme mit eigenen Schriftarten und Größen
 */

/** @type {import('tailwindcss').Config} */
module.exports = {
  /**
   * CONTENT - Welche Dateien soll Tailwind scannen?
   *
   * Tailwind analysiert deine HTML/PHP/JS-Dateien und findet heraus,
   * welche CSS-Klassen du verwendest. Nur diese werden dann in die
   * finale CSS-Datei kompiliert (kleinerer Dateiumfang).
   */
  content: [
    // Alle PHP-Dateien im public-Verzeichnis (unsere HTML-Templates)
    "./public/**/*.php",

    // HTML-Dateien (falls vorhanden)
    "./public/**/*.html",

    // JavaScript-Dateien (können auch Tailwind-Klassen enthalten)
    "./public/**/*.js",

    // Auch die PHP-Dateien im src-Verzeichnis (für Konsistenz)
    "./src/**/*.php",
    "./src/**/*.html",
    "./src/**/*.js"
  ],

  /**
   * THEME - Anpassungen am Design-System
   *
   * Hier können wir die Standardfarben, Schriftarten, Abstände etc. erweitern.
   */
  theme: {
    extend: {
      /**
       * SCHRIFTARTEN
       *
       * Zusätzliche Schriftarten definieren, die wir verwenden können.
       * Beispiel: class="font-inter" für die Inter-Schriftart
       */
      fontFamily: {
        // Inter ist eine moderne, gut lesbare Schriftart
        'inter': ['Inter', 'system-ui', 'sans-serif'],
      },

      /**
       * SCHRIFTGRÖSSEN
       *
       * Eigene Schriftgrößen mit Zeilenhöhe definieren.
       * Beispiel: class="text-base/7"
       */
      fontSize: {
        // Basis-Schriftgröße mit spezifischer Zeilenhöhe
        'base/7': ['1rem', { lineHeight: '1.75rem' }],
      },
    },
  },

  /**
   * CORE PLUGINS - Welche Tailwind-Funktionen aktivieren?
   *
   * Tailwind besteht aus vielen kleinen "Plugins", die verschiedene
   * CSS-Eigenschaften bereitstellen (Farben, Layout, Typografie, etc.)
   */
  corePlugins: {
    // Preflight: CSS-Reset für konsistente Darstellung in allen Browsern
    // Setzt Standard-Styles zurück (z.B. margin: 0 auf body)
    preflight: true,
  },

  /**
   * PLUGINS - Zusätzliche Tailwind-Erweiterungen
   *
   * Hier können wir zusätzliche Plugins hinzufügen, die neue
   * CSS-Klassen oder Funktionen bereitstellen.
   *
   * Aktuell keine zusätzlichen Plugins nötig.
   */
  plugins: [],
}
