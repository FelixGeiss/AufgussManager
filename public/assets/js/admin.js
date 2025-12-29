/**
 * GEMEINSAME ADMIN-FUNKTIONEN
 *
 * Dieses Script enthält alle wiederverwendbaren Funktionen für den Admin-Bereich:
 * - AJAX-Requests mit Sicherheit
 * - Modal-Fenster Management
 * - Drag & Drop für Datei-Uploads
 * - Toast-Benachrichtigungen
 * - Formular-Validierung
 * - Datum/Zeit-Helfer
 *
 * Als Anfänger solltest du wissen:
 * - Dies ist eine "Utility"-Bibliothek für Admin-Funktionen
 * - window.AdminUtils macht Funktionen global verfügbar
 * - AJAX = Asynchronous JavaScript And XML (ohne Seitenreload)
 * - CSRF = Cross-Site Request Forgery (Sicherheit gegen Angriffe)
 *
 * Architektur: Mehrere Admin-Seiten verwenden diese gemeinsamen Funktionen
 */

// Warten bis DOM bereit ist
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Interface geladen');

    /**
     * CSRF-SCHUTZ FÜR AJAX-REQUESTS
     *
     * Cross-Site Request Forgery Schutz:
     * - Token im HTML-Head suchen
     * - Bei jedem AJAX-Request mitsenden
     * - Server prüft Token für Sicherheit
     */
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        // jQuery AJAX-Setup (falls jQuery verwendet wird)
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content')
            }
        });
    }

    /**
     * LOGOUT-FUNKTIONALITÄT
     *
     * Sicherer Logout mit Bestätigung:
     * - Klick auf Logout-Link abfangen
     * - Bestätigungsdialog anzeigen
     * - Bei "Ja": Zu logout.php weiterleiten
     */
    const logoutBtn = document.querySelector('a[href*="logout"]');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            // Standard-Link-Verhalten verhindern
            e.preventDefault();

            // Bestätigung vom Benutzer einholen
            if (confirm('Wirklich abmelden?')) {
                // Zu Logout-Seite weiterleiten (beendet Session)
                window.location.href = 'logout.php';
            }
        });
    }
});

/**
 * ============================================================================
 * MODAL-FENSTER FUNKTIONEN
 * ============================================================================
 *
 * Modals sind Overlay-Fenster für Formulare und Dialoge.
 * Diese Funktionen zeigen/verstecken Modals mit CSS-Klassen.
 */

/**
 * Modal-Fenster anzeigen
 *
 * @param {string} modalId - ID des Modal-Elements
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Versteckt entfernen, Flex-Layout aktivieren
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

/**
 * Modal-Fenster verstecken
 *
 * @param {string} modalId - ID des Modal-Elements
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Versteckt aktivieren, Flex-Layout entfernen
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

/**
 * ============================================================================
 * AJAX-HILFSFUNKTIONEN
 * ============================================================================
 *
 * AJAX ermöglicht Datenübertragung ohne Seitenreload.
 * Diese Funktionen kapseln XMLHttpRequest für einfachere Verwendung.
 */

/**
 * AJAX-Request senden (Promise-basiert)
 *
 * Beispiel:
 * ajaxRequest('api/users.php', 'POST', {name: 'Max'})
 *   .then(data => console.log(data))
 *   .catch(error => console.error(error));
 *
 * @param {string} url - API-Endpunkt
 * @param {string} method - HTTP-Methode (GET, POST, PUT, DELETE)
 * @param {Object} data - Zu sendende Daten (werden zu JSON)
 * @returns {Promise} - Promise mit Response oder Error
 */
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        // XMLHttpRequest-Objekt erstellen
        const xhr = new XMLHttpRequest();

        // Request konfigurieren
        xhr.open(method, url, true);

        // Headers für JSON und AJAX setzen
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        // ERFOLG: Response verarbeiten
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    // JSON parsen falls möglich
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    // Reine Text-Response
                    resolve(xhr.responseText);
                }
            } else {
                // HTTP-Fehler (404, 500, etc.)
                reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
            }
        };

        // NETZWERKFEHLER behandeln
        xhr.onerror = function() {
            reject(new Error('Netzwerkfehler'));
        };

        // Request senden
        if (data) {
            xhr.send(JSON.stringify(data)); // Daten als JSON
        } else {
            xhr.send(); // Leerer Request
        }
    });
}

/**
 * ============================================================================
 * TOAST-BENACHRICHTIGUNGEN
 * ============================================================================
 *
 * Kleine Pop-up-Nachrichten für Erfolg/Fehler.
 * Erscheinen oben rechts und verschwinden automatisch.
 */

/**
 * Toast-Nachricht anzeigen
 *
 * @param {string} message - Nachrichtentext
 * @param {string} type - Typ: 'success', 'error', 'warning', 'info'
 */
function showToast(message, type = 'info') {
    // Toast-Element erstellen
    const toast = document.createElement('div');

    // CSS-Klassen basierend auf Typ
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    }`;

    // Nachricht einfügen
    toast.textContent = message;

    // Toast zum Body hinzufügen
    document.body.appendChild(toast);

    // Nach 3 Sekunden automatisch entfernen
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Bestätigungsdialog
function confirmAction(message) {
    return new Promise((resolve) => {
        if (confirm(message)) {
            resolve(true);
        } else {
            resolve(false);
        }
    });
}

// Form-Daten zu Object konvertieren
function formToObject(form) {
    const data = new FormData(form);
    const result = {};

    for (let [key, value] of data.entries()) {
        result[key] = value;
    }

    return result;
}

// Loading-Spinner anzeigen/verstecken
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="spinner"></div>';
    }
}

function hideLoading(elementId, content = '') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = content;
    }
}

// Drag & Drop Funktionalität
function initDragAndDrop(dropZoneId, callback) {
    const dropZone = document.getElementById(dropZoneId);
    if (!dropZone) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('dragover');
    }

    function unhighlight(e) {
        dropZone.classList.remove('dragover');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (callback && typeof callback === 'function') {
            callback(files);
        }
    }
}

// Date-Helper-Funktionen
function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString('de-DE');
}

function formatTime(time) {
    return time.substring(0, 5); // HH:MM format
}

function getCurrentDate() {
    const today = new Date();
    return today.toISOString().split('T')[0];
}

// Input-Validierung
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateRequired(value) {
    return value && value.trim() !== '';
}

/**
 * ============================================================================
 * EXPORT FÜR ANDERE MODULE
 * ============================================================================
 *
 * Macht alle Hilfsfunktionen global verfügbar.
 * Andere JavaScript-Dateien können diese Funktionen verwenden:
 *
 * Beispiel in mitarbeiter.js:
 * AdminUtils.showToast('Erfolgreich gespeichert!', 'success');
 * AdminUtils.ajaxRequest('api/save.php', 'POST', data);
 */

// Alle wichtigen Funktionen in window.AdminUtils exportieren
window.AdminUtils = {
    showModal,           // Modal ein/ausblenden
    hideModal,           // Modal ein/ausblenden
    ajaxRequest,         // AJAX-Requests senden
    showToast,           // Toast-Nachrichten
    confirmAction,       // Bestätigungsdialoge
    formToObject,        // Formular → Objekt konvertieren
    showLoading,         // Lade-Spinner anzeigen
    hideLoading,         // Lade-Spinner verstecken
    initDragAndDrop,     // Drag & Drop initialisieren
    formatDate,          // Datum formatieren
    formatTime,          // Zeit formatieren
    getCurrentDate,      // Aktuelles Datum
    validateEmail,       // E-Mail validieren
    validateRequired     // Pflichtfelder prüfen
};
