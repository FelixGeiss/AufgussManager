const screensApiUrl = '../api/bildschirme.php';
const plansApiUrl = '../api/plaene.php';
const uploadUrl = 'upload_screen_media.php';
const globalAdUploadUrl = 'upload_global_ad.php';
const screenCount = 5;
const mediaOptions = window.ScreenMediaOptions || { screens: [], backgrounds: [], ads: [] };
let globalAd = { path: '', type: '' };

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function notify(message, type = 'info') {
    if (window.AdminUtils && typeof window.AdminUtils.showToast === 'function') {
        window.AdminUtils.showToast(message, type);
        return;
    }
    alert(message);
}

function fetchJson(url, options) {
    return fetch(url, options).then(response => response.ok ? response.json() : null);
}

function extractPlans(payload) {
    if (payload && payload.data && Array.isArray(payload.data.plaene)) {
        return payload.data.plaene;
    }
    if (payload && Array.isArray(payload.plaene)) {
        return payload.plaene;
    }
    return [];
}

function extractScreens(payload) {
    const screens = {};
    const list = payload && payload.data && Array.isArray(payload.data.screens)
        ? payload.data.screens
        : [];
    list.forEach(screen => {
        if (screen && screen.id) {
            screens[String(screen.id)] = screen;
        }
    });
    return screens;
}

function buildPlanOptions(plans, selectedId) {
    if (!plans.length) {
        return '<option value="">Keine Plaene</option>';
    }
    return [
        '<option value="">Plan waehlen</option>',
        ...plans.map(plan => {
            const id = String(plan.id);
            const label = escapeHtml(plan.name || `Plan ${id}`);
            const selected = String(selectedId) === id ? ' selected' : '';
            return `<option value="${id}"${selected}>${label}</option>`;
        })
    ].join('');
}

function isVideoPath(path) {
    if (!path) return false;
    const clean = String(path).split('?')[0].split('#')[0];
    return /\.(mp4|webm|ogg)$/i.test(clean);
}

function buildFileOptions(paths, selectedPath, placeholder) {
    const list = Array.isArray(paths) ? [...paths] : [];
    if (selectedPath && !list.includes(selectedPath)) {
        list.unshift(selectedPath);
    }
    const options = [
        `<option value="">${escapeHtml(placeholder)}</option>`,
        ...list.map(path => {
            const selected = selectedPath === path ? ' selected' : '';
            const label = escapeHtml(path.split('/').pop() || path);
            return `<option value="${escapeHtml(path)}"${selected}>${label}</option>`;
        })
    ];
    return options.join('');
}

function buildPreview(path) {
    if (!path) {
        return '<div class="text-xs text-gray-500">Kein Bild hinterlegt.</div>';
    }
    const safe = escapeHtml(`../uploads/${path}`);
    return `<img src="${safe}" alt="Preview" class="w-full max-h-40 object-contain rounded border">`;
}

function buildMediaPreview(path) {
    if (!path) {
        return '<div class="text-xs text-gray-500">Keine Datei hinterlegt.</div>';
    }
    const safe = escapeHtml(`../uploads/${path}`);
    if (isVideoPath(path)) {
        return `<video src="${safe}" class="w-full max-h-40 object-contain rounded border" muted autoplay loop playsinline></video>`;
    }
    return `<img src="${safe}" alt="Preview" class="w-full max-h-40 object-contain rounded border">`;
}

function buildGlobalAdCard() {
    const options = buildFileOptions(mediaOptions.ads, globalAd.path, '-- Werbung waehlen --');
    return `
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" data-global-ad-card>
            <div class="text-lg font-semibold mb-3">Globale Werbung</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Werbung auswaehlen</label>
                    <select name="global_ad_select" class="w-full border rounded px-3 py-2">
                        ${options}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Werbung hochladen</label>
                    <input type="file" name="global_ad_upload" data-kind="global-ad" class="w-full text-sm" accept="image/*,video/*">
                    <div class="mt-2" data-preview="global-ad">
                        ${buildMediaPreview(globalAd.path)}
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button type="button" data-action="save-global-ad" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Speichern</button>
                <span class="text-xs text-gray-500">Gilt fuer alle Bildschirme.</span>
            </div>
        </div>
    `;
}

function buildScreenCard(screenId, screen, plans) {
    const mode = screen && screen.mode === 'image' ? 'image' : 'plan';
    const planId = screen && screen.plan_id ? String(screen.plan_id) : '';
    const imagePath = screen && screen.image_path ? String(screen.image_path) : '';
    const backgroundPath = screen && screen.background_path ? String(screen.background_path) : '';
    const imageOptions = buildFileOptions(mediaOptions.screens, imagePath, '-- Bild waehlen --');
    const backgroundOptions = buildFileOptions(mediaOptions.backgrounds, backgroundPath, '-- Hintergrund waehlen --');

    return `
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" data-screen-card data-screen-id="${screenId}" data-image-path="${escapeHtml(imagePath)}" data-background-path="${escapeHtml(backgroundPath)}">
            <div class="flex items-center justify-between mb-3">
                <div class="text-lg font-semibold">Bildschirm ${screenId}</div>
                <a href="../bildschirm_${screenId}.php" class="text-sm text-blue-600 hover:underline" target="_blank" rel="noopener">Oeffnen</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Anzeige</label>
                    <select name="mode" class="w-full border rounded px-3 py-2">
                        <option value="plan"${mode === 'plan' ? ' selected' : ''}>Plan</option>
                        <option value="image"${mode === 'image' ? ' selected' : ''}>Bild</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Plan</label>
                    <select name="plan_id" class="w-full border rounded px-3 py-2">
                        ${buildPlanOptions(plans, planId)}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Bild (Anzeige)</label>
                    <select name="image_select" class="w-full border rounded px-3 py-2 mb-2">
                        ${imageOptions}
                    </select>
                    <input type="file" name="image_upload" data-kind="image" class="w-full text-sm">
                    <div class="mt-2" data-preview="image">
                        ${buildPreview(imagePath)}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Hintergrund</label>
                    <select name="background_select" class="w-full border rounded px-3 py-2 mb-2">
                        ${backgroundOptions}
                    </select>
                    <input type="file" name="background_upload" data-kind="background" class="w-full text-sm">
                    <div class="mt-2" data-preview="background">
                        ${buildPreview(backgroundPath)}
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button type="button" data-action="save" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Speichern</button>
                <span class="text-xs text-gray-500">Aenderungen am Modus oder Plan bitte speichern.</span>
            </div>
        </div>
    `;
}

function updateCardState(card) {
    const mode = card.querySelector('[name="mode"]')?.value || 'plan';
    const planSelect = card.querySelector('[name="plan_id"]');
    const imageInput = card.querySelector('input[data-kind="image"]');
    const imageSelect = card.querySelector('[name="image_select"]');

    const planDisabled = mode !== 'plan';
    const imageDisabled = mode !== 'image';

    if (planSelect) {
        planSelect.disabled = planDisabled;
        planSelect.classList.toggle('opacity-60', planDisabled);
    }
    if (imageSelect) {
        imageSelect.disabled = imageDisabled;
        imageSelect.classList.toggle('opacity-60', imageDisabled);
    }
    if (imageInput) {
        imageInput.disabled = imageDisabled;
        imageInput.classList.toggle('opacity-60', imageDisabled);
    }
}

function renderScreens(plans, screens) {
    const root = document.getElementById('screen-list');
    if (!root) return;

    const cards = [];
    for (let i = 1; i <= screenCount; i++) {
        const screen = screens[String(i)] || { id: i };
        cards.push(buildScreenCard(i, screen, plans));
    }
    root.innerHTML = cards.join('');

    root.querySelectorAll('[data-screen-card]').forEach(card => {
        updateCardState(card);
    });
}

function renderGlobalAd() {
    const root = document.getElementById('global-ad-card');
    if (!root) return;
    root.innerHTML = buildGlobalAdCard();
}

function getCardConfig(card) {
    const mode = card.querySelector('[name="mode"]')?.value || 'plan';
    const planId = card.querySelector('[name="plan_id"]')?.value || '';
    const imagePath = card.dataset.imagePath || '';
    const backgroundPath = card.dataset.backgroundPath || '';

    return {
        mode,
        planId,
        imagePath,
        backgroundPath
    };
}

function updatePreview(card, kind, path) {
    const preview = card.querySelector(`[data-preview="${kind}"]`);
    if (!preview) return;
    preview.innerHTML = buildPreview(path);
}

function updateGlobalAdPreview(path) {
    const root = document.getElementById('global-ad-card');
    if (!root) return;
    const preview = root.querySelector('[data-preview="global-ad"]');
    if (!preview) return;
    preview.innerHTML = buildMediaPreview(path);
}

function updateGlobalAdType(path) {
    if (!path) {
        globalAd.type = '';
        return;
    }
    globalAd.type = isVideoPath(path) ? 'video' : 'image';
}

function handleSave(card) {
    const screenId = Number(card.dataset.screenId || 0);
    if (!screenId) return;

    const config = getCardConfig(card);
    const payload = {
        screen_id: screenId,
        mode: config.mode,
        plan_id: config.mode === 'plan' ? config.planId : null,
        image_path: config.mode === 'image' ? config.imagePath : null,
        background_path: config.backgroundPath || null
    };

    fetchJson(screensApiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(result => {
            if (result && result.success) {
                notify('Bildschirm gespeichert.', 'success');
            } else {
                notify(result && result.message ? result.message : 'Speichern fehlgeschlagen.', 'error');
            }
        })
        .catch(() => notify('Netzwerkfehler beim Speichern.', 'error'));
}

function handleUpload(card, input) {
    const file = input.files && input.files[0];
    if (!file) return;

    const screenId = Number(card.dataset.screenId || 0);
    const kind = input.dataset.kind || 'image';

    const formData = new FormData();
    formData.append('screen_id', String(screenId));
    formData.append('kind', kind);
    formData.append('bild', file);

    fetchJson(uploadUrl, {
        method: 'POST',
        body: formData
    })
        .then(result => {
            if (!result || !result.success || !result.data) {
                notify(result && result.error ? result.error : 'Upload fehlgeschlagen.', 'error');
                return;
            }
            const path = result.data.path || '';
            if (kind === 'background') {
                card.dataset.backgroundPath = path;
                if (!mediaOptions.backgrounds.includes(path)) {
                    mediaOptions.backgrounds.push(path);
                }
            } else {
                card.dataset.imagePath = path;
                if (!mediaOptions.screens.includes(path)) {
                    mediaOptions.screens.push(path);
                }
            }
            updatePreview(card, kind === 'background' ? 'background' : 'image', path);
            const selectName = kind === 'background' ? 'background_select' : 'image_select';
            const select = card.querySelector(`[name="${selectName}"]`);
            if (select) {
                const options = buildFileOptions(
                    kind === 'background' ? mediaOptions.backgrounds : mediaOptions.screens,
                    path,
                    kind === 'background' ? '-- Hintergrund waehlen --' : '-- Bild waehlen --'
                );
                select.innerHTML = options;
                select.value = path;
            }
            notify('Bild hochgeladen.', 'success');
        })
        .catch(() => notify('Netzwerkfehler beim Upload.', 'error'))
        .finally(() => {
            input.value = '';
        });
}

function handleGlobalAdUpload(input) {
    const file = input.files && input.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('werbung', file);

    fetchJson(globalAdUploadUrl, {
        method: 'POST',
        body: formData
    })
        .then(result => {
            if (!result || !result.success || !result.data) {
                notify(result && result.error ? result.error : 'Upload fehlgeschlagen.', 'error');
                return;
            }
            const path = result.data.path || '';
            globalAd.path = path;
            globalAd.type = result.data.type || (isVideoPath(path) ? 'video' : 'image');
            if (path && !mediaOptions.ads.includes(path)) {
                mediaOptions.ads.push(path);
            }
            renderGlobalAd();
            notify('Werbung hochgeladen.', 'success');
        })
        .catch(() => notify('Netzwerkfehler beim Upload.', 'error'))
        .finally(() => {
            input.value = '';
        });
}

function handleGlobalAdSave() {
    const payload = {
        global_ad_path: globalAd.path || null,
        global_ad_type: globalAd.path ? (globalAd.type || null) : null
    };

    fetchJson(screensApiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(result => {
            if (result && result.success && result.data && result.data.global_ad) {
                globalAd = {
                    path: result.data.global_ad.path || '',
                    type: result.data.global_ad.type || ''
                };
                renderGlobalAd();
                notify('Globale Werbung gespeichert.', 'success');
            } else {
                notify(result && result.message ? result.message : 'Speichern fehlgeschlagen.', 'error');
            }
        })
        .catch(() => notify('Netzwerkfehler beim Speichern.', 'error'));
}

function bindEvents() {
    const root = document.getElementById('screen-list');
    if (!root) return;

    root.addEventListener('change', event => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const card = target.closest('[data-screen-card]');
        if (!card) return;

        if (target.name === 'mode') {
            updateCardState(card);
        }
        if (target.name === 'image_select') {
            const path = target.value || '';
            card.dataset.imagePath = path;
            updatePreview(card, 'image', path);
        }
        if (target.name === 'background_select') {
            const path = target.value || '';
            card.dataset.backgroundPath = path;
            updatePreview(card, 'background', path);
        }
        if (target.type === 'file') {
            handleUpload(card, target);
        }
    });

    root.addEventListener('click', event => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (!target.matches('[data-action="save"]')) return;
        const card = target.closest('[data-screen-card]');
        if (!card) return;
        handleSave(card);
    });
}

function bindGlobalAdEvents() {
    const root = document.getElementById('global-ad-card');
    if (!root) return;

    root.addEventListener('change', event => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.name === 'global_ad_select') {
            const path = target.value || '';
            globalAd.path = path;
            updateGlobalAdType(path);
            updateGlobalAdPreview(path);
        }
        if (target.type === 'file') {
            handleGlobalAdUpload(target);
        }
    });

    root.addEventListener('click', event => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (!target.matches('[data-action="save-global-ad"]')) return;
        handleGlobalAdSave();
    });
}

function initScreens() {
    Promise.all([fetchJson(plansApiUrl), fetchJson(screensApiUrl)])
        .then(([plansPayload, screensPayload]) => {
            const plans = extractPlans(plansPayload);
            const screens = extractScreens(screensPayload);
            const global = extractGlobalAd(screensPayload);
            if (global) {
                globalAd = {
                    path: global.path || '',
                    type: global.type || ''
                };
            }
            renderScreens(plans, screens);
            renderGlobalAd();
            bindEvents();
            bindGlobalAdEvents();
        })
        .catch(() => {
            notify('Fehler beim Laden der Bildschirme.', 'error');
        });
}

document.addEventListener('DOMContentLoaded', () => {
    initScreens();
});

function extractGlobalAd(payload) {
    if (payload && payload.data && payload.data.global_ad) {
        return payload.data.global_ad;
    }
    if (payload && payload.global_ad) {
        return payload.global_ad;
    }
    return null;
}
