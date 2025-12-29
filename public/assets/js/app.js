/**
 * HAUPT-JAVASCRIPT FÜR DIE ÖFFENTLICHE AUFGUSSPLAN-ANZEIGE
 *
 * Dieses Script läuft auf der öffentlichen index.php-Seite und ist zuständig für:
 * - Automatisches Laden von Aufgussdaten über AJAX
 * - Regelmäßige Aktualisierung (alle 30 Sekunden)
 * - Darstellung der Daten im TV-freundlichen Format
 * - Vollbild-Modus für TV-Bildschirme
 * - Timer-Funktionalität für laufende Aufgüsse
 *
 * Als Anfänger solltest du wissen:
 * - Dies ist modernes ES6+ JavaScript mit Promises und Fetch API
 * - Die Daten kommen über AJAX (Asynchronous JavaScript And XML)
 * - setInterval() führt Code regelmäßig aus
 * - Event Listener reagieren auf Benutzeraktionen
 * - Arrow Functions (=>) sind eine moderne Schreibweise
 *
 * Architektur: Browser ? AJAX ? PHP-API ? Datenbank
 */

// Warten bis das DOM (HTML) vollständig geladen ist
document.addEventListener('DOMContentLoaded', function() {
    console.log('Aufgussplan App geladen');

    /**
     * AUTOMATISCHE AKTUALISIERUNG
     *
     * Alle 30 Sekunden werden die Daten neu geladen.
     * Das ist wichtig für TV-Bildschirme, die 24/7 laufen.
     * setInterval führt die Funktion wiederholt aus.
     */
    setInterval(loadAufgussplan, 30000); // 30 Sekunden = 30000 Millisekunden

    /**
     * ANFANGSLADUNG
     *
     * Beim ersten Laden der Seite Daten sofort laden (nicht warten).
     */
    loadAufgussplan();

    /**
     * VOLLBILD-MODUS FÜR TV-ANZEIGE
     *
     * Tastenkombination F11 oder Strg+F für Vollbild.
     * Wichtig für TV-Bildschirme ohne Maus/Tastatur.
     */
    document.addEventListener('keydown', function(e) {
        // Prüfen auf F11 oder Strg+F
        if (e.key === 'F11' || (e.ctrlKey && e.key === 'f')) {
            // Standard-Verhalten verhindern (Browser-Vollbild)
            e.preventDefault();
            // Eigenen Vollbild-Modus aktivieren
            toggleFullscreen();
        }
    });
});

/**
 * AUFGUSSDATEN LADEN
 *
 * Lädt die aktuellen Aufgussdaten von der PHP-API.
 * Verwendet moderne Fetch API statt altem XMLHttpRequest.
 *
 * API-Endpunkt: api/aufguesse.php (gibt JSON zurück)
 */
function loadAufgussplan() {
    // HTTP-Request an die API senden
    fetch('api/aufguesse.php')
        // Antwort als JSON parsen
        .then(response => response.json())
        // Erfolgreiche Daten verarbeiten
        .then(data => {
            displayAufgussplan(data);
        })
        // Fehler behandeln
        .catch(error => {
            console.error('Fehler beim Laden des Aufgussplans:', error);
            showError('Fehler beim Laden der Daten');
        });
}

/**
 * AUFGUSSPLAN DARSTELLEN
 *
 * Wandelt die JSON-Daten vom Server in HTML um.
 * Findet den aktuell laufenden Aufguss und zeigt alle Aufgüsse an.
 *
 * @param {Array} aufgüsse - Array mit Aufguss-Objekten aus der API
 */
function displayAufgussplan(aufgüsse) {
    // HTML-Container für den Aufgussplan finden
    const container = document.getElementById('aufgussplan');

    // LEERE LISTE: Keine Aufgüsse vorhanden
    if (!aufgüsse || aufgüsse.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8">Keine Aufgüsse geplant für heute.</p>';
        return; // Funktion beenden
    }

    // AKTUELLE ZEIT für Vergleiche
    const now = new Date();
    let currentAufguss = null;

    /**
     * AKTUELLEN AUFGUSS FINDEN
     *
     * Durchsucht alle Aufgüsse und findet heraus, welcher gerade läuft.
     * Ein Aufguss "läuft" von startZeit bis startZeit + Dauer.
     */
    for (let aufguss of aufgüsse) {
        // Startzeit des Aufgusses als Date-Objekt
        const aufgussTime = new Date(aufguss.datum + ' ' + aufguss.zeit);

        // Endzeit = Startzeit + Dauer (Standard: 15 Minuten)
        const nextAufgussTime = new Date(aufgussTime.getTime() + (aufguss.dauer || 15) * 60000);

        // Prüfen, ob aktuelle Zeit zwischen Start und Ende liegt
        if (now >= aufgussTime && now <= nextAufgussTime) {
            currentAufguss = aufguss;
            break; // Ersten passenden Aufguss nehmen
        }
    }

    /**
     * HTML GENERIEREN
     *
     * Erstellt für jeden Aufguss eine Karte mit Zeit, Mitarbeiter, etc.
     * Der aktuelle Aufguss bekommt eine spezielle CSS-Klasse.
     */
    let html = '<div class="space-y-4">'; // Container mit Abständen

    aufgüsse.forEach((aufguss, index) => {
        // Prüfen, ob dies der aktuelle Aufguss ist
        const isCurrent = currentAufguss && currentAufguss.id === aufguss.id;

        // CSS-Klassen: Normal oder hervorgehoben
        const classes = isCurrent ? 'aufguss-card current' : 'aufguss-card';

        // HTML für eine Aufguss-Karte generieren
        html += `
            <div class="${classes}">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold">${aufguss.zeit}</h3>
                        <p class="text-lg">${aufguss.mitarbeiter_name}</p>
                        ${aufguss.beschreibung ? `<p class="text-sm opacity-90 mt-2">${aufguss.beschreibung}</p>` : ''}
                    </div>
                    ${isCurrent ? '<div class="timer" id="timer">Läuft...</div>' : ''}
                </div>
            </div>
        `;
    });

    html += '</div>';

    // Generiertes HTML in den Container einfügen
    container.innerHTML = html;

    /**
     * TIMER STARTEN
     *
     * Wenn ein Aufguss läuft, wird ein Countdown-Timer gestartet.
     */
    if (currentAufguss) {
        startTimer(currentAufguss);
    }
}

/**
 * TIMER FÜR LAUFENDEN AUFGUSS STARTEN
 *
 * Zeigt einen Live-Countdown für den aktuell laufenden Aufguss an.
 * Aktualisiert sich jede Sekunde und ändert die Farbe bei wenig Zeit.
 *
 * @param {Object} aufguss - Das Aufguss-Objekt mit datum, zeit, dauer
 */
function startTimer(aufguss) {
    // Start- und Endzeit berechnen
    const aufgussTime = new Date(aufguss.datum + ' ' + aufguss.zeit);
    const endTime = new Date(aufgussTime.getTime() + (aufguss.dauer || 15) * 60000);
    const timerElement = document.getElementById('timer');

    /**
     * TIMER UPDATE FUNKTION
     *
     * Wird jede Sekunde aufgerufen, um die Anzeige zu aktualisieren.
     */
    function updateTimer() {
        const now = new Date();
        const remaining = Math.max(0, endTime - now); // Verbleibende Millisekunden

        // AUFGUSS BEENDET
        if (remaining === 0) {
            timerElement.textContent = 'Beendet';
            timerElement.className = 'timer'; // CSS-Klassen zurücksetzen
            return; // Timer stoppen
        }

        // ZEIT BERECHNEN
        const minutes = Math.floor(remaining / 60000);           // Ganzzahlige Minuten
        const seconds = Math.floor((remaining % 60000) / 1000);  // Sekunden im Bereich 0-59

        // FORMAT mm:ss mit führenden Nullen
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        /**
         * FARBKODIERUNG NACH VERBLEIBENDER ZEIT
         *
         * warning: Unter 5 Minuten (gelb)
         * danger: Unter 1 Minute (rot) + blinken
         */
        if (remaining < 300000) { // 5 Minuten = 300.000 Millisekunden
            timerElement.classList.add('warning');
        }
        if (remaining < 60000) { // 1 Minute = 60.000 Millisekunden
            timerElement.classList.add('danger');
        }
    }

    // Timer sofort starten
    updateTimer();

    // Alle 1000ms (1 Sekunde) aktualisieren
    setInterval(updateTimer, 1000);
}

/**
 * VOLLBILD-MODUS EIN-/AUSSCHALTEN
 *
 * Für TV-Bildschirme: Vollbild aktivieren/deaktivieren.
 * Verwendet die Fullscreen API des Browsers.
 */
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        // Nicht im Vollbild: Vollbild aktivieren
        document.documentElement.requestFullscreen();
    } else {
        // Im Vollbild: Vollbild beenden
        document.exitFullscreen();
    }
}

/**
 * FEHLERMELDUNG ANZEIGEN
 *
 * Zeigt eine rote Fehlermeldung anstelle des Aufgussplans an.
 * Wird verwendet, wenn die API nicht erreichbar ist.
 *
 * @param {string} message - Die anzuzeigende Fehlermeldung
 */
function showError(message) {
    const container = document.getElementById('aufgussplan');

    // Fehler-HTML generieren (rot, mit Rahmen)
    container.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <p>${message}</p>
    </div>`;
}
