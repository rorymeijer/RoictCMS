<?php
require_once dirname(__DIR__, 3) . '/admin/includes/init.php';
Auth::requireAdmin();

$pageTitle  = 'Toegankelijkheid';
$activePage = 'admin-accessibility';

require_once ADMIN_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-1" style="font-size:1.5rem;font-weight:800;">
            <i class="bi bi-universal-access me-2" style="color:var(--primary);"></i>Toegankelijkheid
        </h1>
        <p class="text-muted mb-0" style="font-size:.88rem;">
            Pas het beheerpaneel aan voor een betere gebruikservaring. Instellingen worden lokaal per browser opgeslagen.
        </p>
    </div>
</div>

<?= renderFlash() ?>

<div class="row g-4">

    <!-- Donker thema -->
    <div class="col-md-6">
        <div class="cms-card h-100">
            <div class="cms-card-header">
                <h2 class="cms-card-title mb-0">
                    <i class="bi bi-moon-stars-fill me-2" style="color:#7c3aed;"></i>Donker thema
                </h2>
                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:.72rem;font-weight:700;">Stijl</span>
            </div>
            <div class="cms-card-body">
                <p style="font-size:.88rem;color:var(--text-muted);" class="mb-3">
                    Schakel over naar een donkere achtergrond om de oogbelasting te verminderen,
                    ook nuttig in omgevingen met weinig licht.
                </p>

                <div class="a11y-preview-block">
                    <span><i class="bi bi-sun me-2"></i>Huidig thema:</span>
                    <span id="current-theme-label" style="font-weight:700;">—</span>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-outline-secondary" onclick="setTheme('light')" id="btn-light">
                        <i class="bi bi-sun"></i> Licht
                    </button>
                    <button class="btn btn-primary" onclick="setTheme('dark')" id="btn-dark">
                        <i class="bi bi-moon-stars-fill"></i> Donker
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lettergrootte -->
    <div class="col-md-6">
        <div class="cms-card h-100">
            <div class="cms-card-header">
                <h2 class="cms-card-title mb-0">
                    <i class="bi bi-fonts me-2" style="color:#059669;"></i>Lettergrootte
                </h2>
                <span class="badge bg-success bg-opacity-10 text-success" style="font-size:.72rem;font-weight:700;">Tekst</span>
            </div>
            <div class="cms-card-body">
                <p style="font-size:.88rem;color:var(--text-muted);" class="mb-3">
                    Vergroot of verklein de tekst in het beheerpaneel om leesbaarheid te verbeteren.
                </p>

                <div class="a11y-preview-block mb-3">
                    <span><i class="bi bi-type me-2"></i>Huidige grootte:</span>
                    <span id="current-fontsize-label" style="font-weight:700;">—</span>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-secondary" onclick="setFontSize('small')" id="btn-fs-small">
                        <span style="font-size:.85rem;">A</span> Klein
                    </button>
                    <button class="btn btn-outline-secondary" onclick="setFontSize('normal')" id="btn-fs-normal">
                        <span style="font-size:1rem;">A</span> Normaal
                    </button>
                    <button class="btn btn-outline-secondary" onclick="setFontSize('large')" id="btn-fs-large">
                        <span style="font-size:1.2rem;">A</span> Groot
                    </button>
                    <button class="btn btn-outline-secondary" onclick="setFontSize('xlarge')" id="btn-fs-xlarge">
                        <span style="font-size:1.45rem;">A</span> X-Groot
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tekst-naar-spraak -->
    <div class="col-md-6">
        <div class="cms-card h-100">
            <div class="cms-card-header">
                <h2 class="cms-card-title mb-0">
                    <i class="bi bi-volume-up-fill me-2" style="color:#d97706;"></i>Tekst-naar-spraak
                </h2>
                <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size:.72rem;font-weight:700;">Audio</span>
            </div>
            <div class="cms-card-body">
                <p style="font-size:.88rem;color:var(--text-muted);" class="mb-3">
                    Laat het beheerpaneel tekst voorlezen. Beweeg je muis over knoppen,
                    links, koppen en tekst om deze voor te laten lezen.
                </p>

                <div class="a11y-preview-block mb-3">
                    <span><i class="bi bi-headphones me-2"></i>Voorlezen:</span>
                    <span id="current-tts-label" style="font-weight:700;">—</span>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" onclick="setTTS(false)" id="btn-tts-off">
                        <i class="bi bi-volume-mute"></i> Uit
                    </button>
                    <button class="btn btn-primary" onclick="setTTS(true)" id="btn-tts-on">
                        <i class="bi bi-volume-up-fill"></i> Aan
                    </button>
                </div>

                <div id="tts-browser-warning" class="mt-3" style="display:none;">
                    <sl-alert variant="warning" open>
                        <i class="bi bi-exclamation-triangle" slot="icon"></i>
                        <strong>Niet beschikbaar</strong> – Tekst-naar-spraak wordt niet ondersteund door deze browser.
                        Probeer een moderne browser zoals Chrome of Edge.
                    </sl-alert>
                </div>
            </div>
        </div>
    </div>

    <!-- Alles resetten -->
    <div class="col-md-6">
        <div class="cms-card h-100">
            <div class="cms-card-header">
                <h2 class="cms-card-title mb-0">
                    <i class="bi bi-arrow-counterclockwise me-2" style="color:#dc2626;"></i>Resetten
                </h2>
                <span class="badge bg-danger bg-opacity-10 text-danger" style="font-size:.72rem;font-weight:700;">Beheer</span>
            </div>
            <div class="cms-card-body">
                <p style="font-size:.88rem;color:var(--text-muted);" class="mb-3">
                    Zet alle toegankelijkheidsinstellingen terug naar de standaardwaarden.
                    Dit verwijdert de lokale browseropslag voor deze instellingen.
                </p>
                <button class="btn btn-outline-danger" onclick="resetAll()">
                    <i class="bi bi-trash"></i> Alle instellingen resetten
                </button>
            </div>
        </div>
    </div>

    <!-- Uitleg / Info -->
    <div class="col-12">
        <div class="cms-card">
            <div class="cms-card-header">
                <h2 class="cms-card-title mb-0">
                    <i class="bi bi-info-circle me-2" style="color:var(--primary);"></i>Over deze module
                </h2>
            </div>
            <div class="cms-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="stat-icon" style="background:rgba(37,99,235,.1);color:var(--primary);">
                                <i class="bi bi-device-hdd"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.88rem;">Lokale opslag</div>
                                <div style="font-size:.8rem;color:var(--text-muted);">
                                    Instellingen worden opgeslagen in de browser (localStorage), niet op de server.
                                    Elke gebruiker heeft zijn eigen voorkeur per apparaat.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="stat-icon" style="background:rgba(124,58,237,.1);color:#7c3aed;">
                                <i class="bi bi-palette2"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.88rem;">Donker thema</div>
                                <div style="font-size:.8rem;color:var(--text-muted);">
                                    Het donkere thema overschrijft Bootstrap én Shoelace componenten
                                    voor een consistente donkere weergave.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="stat-icon" style="background:rgba(217,119,6,.1);color:#d97706;">
                                <i class="bi bi-mic-fill"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.88rem;">Web Speech API</div>
                                <div style="font-size:.8rem;color:var(--text-muted);">
                                    Tekst-naar-spraak gebruikt de ingebouwde Web Speech API van de browser.
                                    Ondersteund door Chrome, Edge en Safari.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$extraScript = <<<'JS'
(function() {
    var STORAGE_KEY_THEME    = 'a11y_theme';
    var STORAGE_KEY_FONTSIZE = 'a11y_fontsize';
    var STORAGE_KEY_TTS      = 'a11y_tts';

    var fontSizeNames = { small: 'Klein', normal: 'Normaal', large: 'Groot', xlarge: 'X-Groot' };

    function updateUI() {
        var theme    = localStorage.getItem(STORAGE_KEY_THEME)    || 'light';
        var fontSize = localStorage.getItem(STORAGE_KEY_FONTSIZE) || 'normal';
        var tts      = localStorage.getItem(STORAGE_KEY_TTS)      === 'on';

        // Labels
        document.getElementById('current-theme-label').textContent    = theme === 'dark' ? '🌙 Donker' : '☀️ Licht';
        document.getElementById('current-fontsize-label').textContent  = fontSizeNames[fontSize] || 'Normaal';
        document.getElementById('current-tts-label').textContent       = tts ? '🔊 Aan' : '🔇 Uit';

        // Knopstijlen – thema
        ['light', 'dark'].forEach(function(t) {
            var btn = document.getElementById('btn-' + t);
            if (!btn) return;
            btn.className = t === theme
                ? 'btn btn-primary'
                : 'btn btn-outline-secondary';
        });

        // Knopstijlen – lettergrootte
        ['small', 'normal', 'large', 'xlarge'].forEach(function(s) {
            var btn = document.getElementById('btn-fs-' + s);
            if (!btn) return;
            btn.className = s === fontSize
                ? 'btn btn-primary'
                : 'btn btn-outline-secondary';
        });

        // Knopstijlen – TTS
        var btnOn  = document.getElementById('btn-tts-on');
        var btnOff = document.getElementById('btn-tts-off');
        if (btnOn)  btnOn.className  = tts  ? 'btn btn-primary'           : 'btn btn-outline-secondary';
        if (btnOff) btnOff.className = !tts ? 'btn btn-outline-secondary active' : 'btn btn-outline-secondary';

        // TTS browser-waarschuwing
        var warn = document.getElementById('tts-browser-warning');
        if (warn && !window.speechSynthesis) { warn.style.display = 'block'; }
    }

    window.setTheme = function(theme) {
        localStorage.setItem(STORAGE_KEY_THEME, theme);
        // Herladen zodat de accessibility.js het ophaalt
        location.reload();
    };

    window.setFontSize = function(size) {
        localStorage.setItem(STORAGE_KEY_FONTSIZE, size);
        location.reload();
    };

    window.setTTS = function(enable) {
        localStorage.setItem(STORAGE_KEY_TTS, enable ? 'on' : 'off');
        location.reload();
    };

    window.resetAll = function() {
        localStorage.removeItem(STORAGE_KEY_THEME);
        localStorage.removeItem(STORAGE_KEY_FONTSIZE);
        localStorage.removeItem(STORAGE_KEY_TTS);
        location.reload();
    };

    // Initialiseer UI-labels
    updateUI();
})();
JS;
?>
<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
