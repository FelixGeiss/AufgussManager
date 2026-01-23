const overviewTabStorageKey = 'dbOverviewActiveTab';

function getStoredOverviewTab() {
    try {
        return localStorage.getItem(overviewTabStorageKey);
    } catch (error) {
        return null;
    }
}

function setStoredOverviewTab(tabName) {
    try {
        localStorage.setItem(overviewTabStorageKey, tabName);
    } catch (error) {
        // Ignore storage failures.
    }
}

function getCurrentOverviewTab() {
    const activeButton = document.querySelector('.tab-button.border-indigo-500');
    if (!activeButton || !activeButton.id) return null;
    return activeButton.id.replace(/^tab-/, '');
}

// Datenbank-Uebersicht: Tabs und Loeschen
function showTab(tabName) {
    // Verstecke alle Tab-Inhalte
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Verstecke alle Upload-Bereiche
    document.querySelectorAll('.tab-upload').forEach(upload => {
        upload.classList.add('hidden');
    });

    // Entferne aktive Tab-Stile
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-indigo-500', 'text-indigo-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    // Zeige ausgewaehlten Tab-Inhalt
    const activeContent = document.getElementById('content-' + tabName);
    if (!activeContent) {
        return;
    }
    activeContent.classList.remove('hidden');

    // Setze aktiven Tab-Stil
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
    const activeButton = document.getElementById('tab-' + tabName);
    if (activeButton) {
        activeButton.classList.add('border-indigo-500', 'text-indigo-600');
    }

    setStoredOverviewTab(tabName);

    // Zeige Upload-Bereich fuer den aktiven Tab, falls vorhanden
    const activeUpload = document.querySelector(`.tab-upload[data-tab="${tabName}"]`);
    if (activeUpload) {
        activeUpload.classList.remove('hidden');
    }
}

// Umfragen-Tabelle filtern
function initUmfragenSearch() {
    const input = document.getElementById('umfragen-search');
    const table = document.querySelector('#content-umfragen table');
    if (!input || !table) return;
    const rows = Array.from(table.querySelectorAll('tbody tr'));

    const filterRows = () => {
        const query = input.value.trim().toLowerCase();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.classList.toggle('hidden', query !== '' && !text.includes(query));
        });
    };

    input.addEventListener('input', filterRows);
}

document.addEventListener('DOMContentLoaded', initUmfragenSearch);
document.addEventListener('DOMContentLoaded', () => {
    const stored = getStoredOverviewTab();
    if (stored && document.getElementById('content-' + stored)) {
        showTab(stored);
        return;
    }
    showTab('aufguesse');
});

async function uploadOverviewFile(type, file, button) {
    if (!file) {
        return;
    }
    const originalText = button.dataset.originalText || button.textContent;
    button.dataset.originalText = originalText;
    button.disabled = true;
    button.textContent = 'Lädt...';

    const formData = new FormData();
    formData.append('type', type);
    formData.append('file', file);

    try {
        const response = await fetch('../uploads/upload_misc_image.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Upload fehlgeschlagen');
        }
        location.reload();
    } catch (error) {
        alert(error.message || 'Upload fehlgeschlagen');
    } finally {
        button.disabled = false;
        button.textContent = button.dataset.originalText || 'Hochladen';
    }
}

function handleOverviewUpload(event, type) {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form) {
        return;
    }
    const input = form.querySelector('input[type="file"]');
    const button = form.querySelector('button[type="submit"]');
    if (!input || !input.files || input.files.length === 0) {
        alert('Bitte eine Datei auswählen.');
        return;
    }
    if (!button) {
        return;
    }
    uploadOverviewFile(type, input.files[0], button);
}

window.handleOverviewUpload = handleOverviewUpload;

// Loeschfunktion fuer Datenbank-Eintraege
function deleteDatenbankEintrag(type, id, name) {
    const currentTab = getCurrentOverviewTab();
    if (currentTab) {
        setStoredOverviewTab(currentTab);
    }
    // Erstelle ein Formular fuer den DELETE-Request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../deletes/delete-entry.php';

    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'type';
    typeInput.value = type;

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = id;

    form.appendChild(typeInput);
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
}
