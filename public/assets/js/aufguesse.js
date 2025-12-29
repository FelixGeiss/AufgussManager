/**
 * JavaScript für Aufguss-Verwaltung mit Plan-Unterstützung
 *
 * Diese Datei verwaltet die komplette Aufguss-Planung einschließlich:
 * - Plan-Auswahl und -Erstellung
 * - Aufguss-Filterung nach Plänen
 * - AJAX-Kommunikation mit der API
 */

// Globale Variablen
let currentDate = new Date().toISOString().split('T')[0];
let currentPlanFilter = '';

/**
 * Initialisierung beim Laden der Seite
 */
document.addEventListener('DOMContentLoaded', function() {
    // Event-Listener für Datumsauswahl
    document.getElementById('selectedDate').addEventListener('change', function(e) {
        currentDate = e.target.value;
        loadAufgüsse();
    });

    // Event-Listener für Plan-Filter
    document.getElementById('planFilter').addEventListener('change', function(e) {
        currentPlanFilter = e.target.value;
        loadAufgüsse();
    });

    // Event-Listener für Plan-Auswahl (Hauptbereich)
    document.getElementById('planSelect').addEventListener('change', function(e) {
        togglePlanFields(e.target.value === '');
    });

    // Event-Listener für Plan-Auswahl im Modal
    document.getElementById('modalPlanSelect').addEventListener('change', function(e) {
        toggleModalPlanFields(e.target.value === '');
    });

    // Initiale Daten laden
    loadPlans();
    loadAufgüsse();

    // Modal-Plan-Felder initial verstecken
    toggleModalPlanFields(false);
});

/**
 * Lädt alle verfügbaren Pläne und füllt die Select-Felder
 */
function loadPlans() {
    fetch('../api/plaene.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populatePlanSelects(data.plaene);
            } else {
                console.error('Fehler beim Laden der Pläne:', data.error);
            }
        })
        .catch(error => {
            console.error('Netzwerkfehler beim Laden der Pläne:', error);
        });
}

/**
 * Füllt die Plan-Select-Felder mit den geladenen Plänen
 */
function populatePlanSelects(plaene) {
    const planSelect = document.getElementById('planSelect');
    const planFilter = document.getElementById('planFilter');

    // Bestehende Optionen (außer der ersten) entfernen
    while (planSelect.children.length > 1) {
        planSelect.removeChild(planSelect.lastChild);
    }
    while (planFilter.children.length > 1) {
        planFilter.removeChild(planFilter.lastChild);
    }

    // Pläne hinzufügen
    plaene.forEach(plan => {
        // Option für Plan-Auswahl
        const option1 = document.createElement('option');
        option1.value = plan.id;
        option1.textContent = plan.name;
        planSelect.appendChild(option1);

        // Option für Plan-Filter
        const option2 = document.createElement('option');
        option2.value = plan.id;
        option2.textContent = plan.name;
        planFilter.appendChild(option2);
    });
}

/**
 * Zeigt/Versteckt die Felder für neuen Plan basierend auf der Auswahl
 */
function togglePlanFields(show) {
    const planName = document.getElementById('planName');
    const planBeschreibung = document.getElementById('planBeschreibung');

    if (show) {
        planName.style.display = 'block';
        planBeschreibung.style.display = 'block';
        planName.required = true;
    } else {
        planName.style.display = 'none';
        planBeschreibung.style.display = 'none';
        planName.required = false;
        planName.value = '';
        planBeschreibung.value = '';
    }
}

/**
 * Zeigt/Versteckt die Felder für neuen Plan im Modal basierend auf der Auswahl
 */
function toggleModalPlanFields(show) {
    const planName = document.getElementById('modalPlanName');
    const planBeschreibung = document.getElementById('modalPlanBeschreibung');

    if (show) {
        planName.style.display = 'block';
        planBeschreibung.style.display = 'block';
        planName.required = true;
    } else {
        planName.style.display = 'none';
        planBeschreibung.style.display = 'none';
        planName.required = false;
        planName.value = '';
        planBeschreibung.value = '';
    }
}

/**
 * Lädt Pläne für das Modal-Select-Feld
 */
function loadModalPlans() {
    fetch('../api/plaene.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateModalPlanSelect(data.plaene);
            } else {
                console.error('Fehler beim Laden der Pläne für Modal:', data.error);
            }
        })
        .catch(error => {
            console.error('Netzwerkfehler beim Laden der Pläne für Modal:', error);
        });
}

/**
 * Füllt das Modal-Plan-Select-Feld mit den geladenen Plänen
 */
function populateModalPlanSelect(plaene) {
    const planSelect = document.getElementById('modalPlanSelect');

    // Bestehende Optionen (außer der ersten) entfernen
    while (planSelect.children.length > 1) {
        planSelect.removeChild(planSelect.lastChild);
    }

    // Pläne hinzufügen
    plaene.forEach(plan => {
        const option = document.createElement('option');
        option.value = plan.id;
        option.textContent = plan.name;
        planSelect.appendChild(option);
    });
}

/**
 * Lädt Aufgüsse für das aktuelle Datum und Plan-Filter
 */
function loadAufgüsse() {
    const params = new URLSearchParams({
        datum: currentDate,
        plan_id: currentPlanFilter || ''
    });

    fetch(`../api/aufguesse.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAufgüsse(data.aufguesse);
            } else {
                console.error('Fehler beim Laden der Aufgüsse:', data.error);
                document.getElementById('aufgussContainer').innerHTML =
                    '<p class="text-red-500">Fehler beim Laden der Aufgüsse.</p>';
            }
        })
        .catch(error => {
            console.error('Netzwerkfehler beim Laden der Aufgüsse:', error);
            document.getElementById('aufgussContainer').innerHTML =
                '<p class="text-red-500">Netzwerkfehler beim Laden der Aufgüsse.</p>';
        });
}

/**
 * Zeigt die geladenen Aufgüsse im Container an
 */
function displayAufgüsse(aufguesse) {
    const container = document.getElementById('aufgussContainer');

    if (!aufguesse || aufgüsse.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-8">Keine Aufgüsse für dieses Datum gefunden.</p>';
        return;
    }

    let html = '';
    aufgüsse.forEach(aufguss => {
        const planInfo = aufguss.plan_name ? `<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">${aufguss.plan_name}</span>` : '';

        html += `
            <div class="bg-gray-50 rounded-lg p-4 border">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="font-bold text-lg">${aufguss.zeit} - ${aufguss.name || 'Aufguss'}</h3>
                            ${planInfo}
                        </div>
                        <div class="text-sm text-gray-600 space-y-1">
                            ${aufguss.mitarbeiter_name ? `<div>Mitarbeiter: ${aufguss.mitarbeiter_name}</div>` : ''}
                            ${aufguss.sauna_name ? `<div>Sauna: ${aufguss.sauna_name}</div>` : ''}
                            ${aufguss.duftmittel_name ? `<div>Duftmittel: ${aufguss.duftmittel_name}</div>` : ''}
                            ${aufguss.beschreibung ? `<div>Beschreibung: ${aufguss.beschreibung}</div>` : ''}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editAufguss(${aufguss.id})" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">
                            Bearbeiten
                        </button>
                        <button onclick="deleteAufguss(${aufguss.id})" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                            Löschen
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Öffnet das Modal für einen neuen Aufguss
 */
function addAufguss() {
    // Modal zurücksetzen
    document.getElementById('aufgussId').value = '';
    document.getElementById('aufgussZeit').value = '';
    document.getElementById('aufgussMitarbeiter').value = '';
    document.getElementById('aufgussBeschreibung').value = '';

    // Plan-Auswahl im Modal zurücksetzen
    document.getElementById('modalPlanSelect').value = '';
    document.getElementById('modalPlanName').value = '';
    document.getElementById('modalPlanBeschreibung').value = '';
    toggleModalPlanFields(false);

    document.getElementById('modalTitle').textContent = 'Aufguss hinzufügen';
    document.getElementById('aufgussModal').classList.remove('hidden');

    // Mitarbeiter und Pläne laden
    loadMitarbeiter();
    loadModalPlans();
}

/**
 * Öffnet das Modal zum Bearbeiten eines Aufgusses
 */
function editAufguss(id) {
    // Hier würde die Logik zum Laden der Aufguss-Daten stehen
    // Für jetzt einfach das Modal öffnen
    addAufguss();
}

/**
 * Löscht einen Aufguss
 */
function deleteAufguss(id) {
    if (confirm('Aufguss wirklich löschen?')) {
        // Hier würde die Lösch-Logik stehen
        console.log('Aufguss löschen:', id);
    }
}

/**
 * Lädt Mitarbeiter für das Select-Feld
 */
function loadMitarbeiter() {
    fetch('../api/mitarbeiter.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('aufgussMitarbeiter');
                select.innerHTML = '<option value="">Mitarbeiter auswählen...</option>';

                data.mitarbeiter.forEach(mitarbeiter => {
                    const option = document.createElement('option');
                    option.value = mitarbeiter.id;
                    option.textContent = mitarbeiter.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Fehler beim Laden der Mitarbeiter:', error);
        });
}

/**
 * Schließt das Aufguss-Modal
 */
function closeAufgussModal() {
    document.getElementById('aufgussModal').classList.add('hidden');
}

/**
 * Sendet das Aufguss-Formular
 */
document.getElementById('aufgussForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Plan-Daten hinzufügen
    const planSelect = document.getElementById('modalPlanSelect');
    const planName = document.getElementById('modalPlanName');
    const planBeschreibung = document.getElementById('modalPlanBeschreibung');

    if (planSelect.value) {
        formData.append('plan_id', planSelect.value);
    } else if (planName.value) {
        formData.append('plan_name', planName.value);
        formData.append('plan_beschreibung', planBeschreibung.value);
    }

    // Datum hinzufügen
    formData.append('datum', currentDate);

    fetch('../api/aufguesse.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAufgussModal();
            loadAufgüsse(); // Liste aktualisieren
            loadPlans(); // Pläne neu laden (falls neuer Plan erstellt wurde)
            alert('Aufguss erfolgreich gespeichert!');
        } else {
            alert('Fehler beim Speichern: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Fehler beim Speichern:', error);
        alert('Netzwerkfehler beim Speichern.');
    });
});

/**
 * Zeigt die Aufgüsse eines bestimmten Plans an
 */
function viewPlanAufgüsse(planId) {
    // Hier könnte man zu einer Detailansicht wechseln oder ein Modal öffnen
    alert(`Aufgüsse für Plan ${planId} anzeigen - Funktion noch nicht implementiert`);
    // window.location.href = `aufguesse.php?plan=${planId}`;
}

/**
 * Löscht einen Plan
 */
function deletePlan(planId, planName) {
    if (confirm(`Plan "${planName}" wirklich löschen?\n\nAlle zugehörigen Aufgüsse werden ebenfalls entfernt!`)) {
        fetch(`../api/plaene.php?id=${planId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Plan erfolgreich gelöscht!');
                location.reload(); // Seite neu laden um Tabelle zu aktualisieren
            } else {
                alert('Fehler beim Löschen: ' + (data.error || 'Unbekannter Fehler'));
            }
        })
        .catch(error => {
            console.error('Fehler beim Löschen:', error);
            alert('Netzwerkfehler beim Löschen.');
        });
    }
}