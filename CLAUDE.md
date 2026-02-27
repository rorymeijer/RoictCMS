# ROICT CMS – Developer Context voor Claude Code

Dit bestand beschrijft de architectuur, conventies en regels voor het ontwikkelen van **modules** binnen ROICT CMS. Gebruik dit als referentie bij het bouwen of aanpassen van modules.

---

## Projectstructuur

```
RoictCMS/
├── admin/          # Beheerpaneel (templates, pagina's)
├── core/           # CMS-kern: classes, bootstrap, config
├── themes/         # Frontend-thema's
├── modules/        # Geïnstalleerde modules (plug-ins)
├── uploads/        # Door gebruikers geüploade bestanden
├── api/            # Marketplace-definities (JSON)
├── install/        # Installatiewizard
├── index.php       # Frontend-router
├── config.php      # Databaseconfiguratie (aangemaakt door installer)
└── CLAUDE.md       # Dit bestand
```

---

## Kern-klassen (core/)

### Database (`core/Database.php`)
Singleton PDO-wrapper met UTF8MB4. Gebruik altijd prepared statements.

```php
$db = Database::getInstance();
$db->query($sql, $params);          // Voer query uit
$db->fetch($sql, $params);          // Één rij ophalen
$db->fetchAll($sql, $params);       // Meerdere rijen
$db->insert($table, $data);         // INSERT, geeft insert-ID terug
$db->update($table, $data, $where, $whereParams); // UPDATE
$db->delete($table, $where, $params); // DELETE
$db->tableExists($table);           // bool
```

### Auth (`core/Auth.php`)
Sessie-gebaseerde authenticatie met bcrypt.

```php
Auth::init();           // Initialiseer sessie
Auth::isLoggedIn();     // bool
Auth::isAdmin();        // bool (rol: admin of super_admin)
Auth::requireLogin();   // Redirect als niet ingelogd
Auth::requireAdmin();   // Redirect als geen admin
Auth::currentUser();    // Huidig gebruikersobject of null
Auth::hashPassword($pw);// bcrypt-hash
```

### Settings (`core/Settings.php`)
Sleutel-waardeopslag met in-memory cache.

```php
Settings::init();
Settings::get('site_name', 'Standaard');
Settings::set('mijn_instelling', 'waarde');
Settings::setMultiple(['key1' => 'val1', 'key2' => 'val2']);
```

### Hulpfuncties (geladen via `core/bootstrap.php`)

```php
e($string)                   // htmlspecialchars – gebruik altijd bij output
slug($string)                // Zet om naar URL-veilige slug
redirect($url)               // Stuur HTTP-redirect
flash($type, $message)       // Sla flash-melding op ('success'|'error'|'warning'|'info')
getFlash()                   // Haal flash-melding op
renderFlash()                // Geef HTML-alert terug (Shoelace)
csrf_token()                 // Genereer CSRF-token (sessie)
csrf_field()                 // <input type="hidden"> met CSRF-token
csrf_verify()                // Valideer POST-token – gebruik altijd bij forms
paginate($total, $perPage, $page) // Retourneert paginering-array
assetUrl($path)              // /admin/assets/...
themeAssetUrl($path)         // /themes/{actief}/assets/...
```

### Action Hooks (WordPress-stijl)

```php
add_action($hook, $callback, $priority = 10);
do_action($hook, ...$args);
```

---

## Modulestructuur

Elke module staat in een eigen map onder `modules/{slug}/`.
De slug is **altijd lowercase met koppeltekens** (bijv. `contact-form`, `seo-tools`).

### Verplichte bestanden

| Bestand | Doel |
|---------|------|
| `module.json` | Metadata (naam, versie, auteur, beschrijving) |
| `init.php` | Geladen bij elke pagina-aanvraag als de module actief is |

### Optionele bestanden

| Bestand/map | Doel |
|-------------|------|
| `install.php` | Wordt eenmalig uitgevoerd na installatie |
| `uninstall.php` | Wordt uitgevoerd vóór verwijdering |
| `functions.php` | Helperfuncties van de module |
| `admin/` | Beheerpagina's van de module |
| `admin/index.php` | Standaard beheerpagina |
| `assets/css/` | Stylesheet van de module |
| `assets/js/` | JavaScript van de module |

### Volledige mapstructuur

```
modules/{slug}/
├── module.json
├── init.php
├── install.php
├── uninstall.php
├── functions.php
├── admin/
│   ├── index.php
│   └── settings.php
└── assets/
    ├── css/
    │   └── {slug}.css
    └── js/
        └── {slug}.js
```

---

## module.json

```json
{
  "name": "Naam van de module",
  "version": "1.0.0",
  "author": "Auteursnaam",
  "description": "Korte beschrijving van wat de module doet.",
  "category": "Categorie",
  "icon": "bootstrap-icon-naam"
}
```

- `icon`: gebruik een naam uit [Bootstrap Icons](https://icons.getbootstrap.com/), bijv. `"envelope"`, `"image"`, `"graph-up"`.
- `category`: vrije tekst, bijv. `"Communication"`, `"SEO"`, `"E-Commerce"`.

---

## init.php

Wordt geladen bij **elke** pagina-aanvraag zolang de module actief is. Gebruik dit bestand voor:

- Hooks registreren
- Beheerpagina's toevoegen aan de zijbalk

```php
<?php
// Voeg navigatielink toe aan beheerpaneel zijbalk
add_action('admin_sidebar_nav', function($activePage) {
    $isActive = ($activePage ?? '') === 'mijn-module' ? 'active' : '';
    echo '<a href="' . BASE_URL . '/admin/mijn-module/" class="nav-link ' . $isActive . '">'
       . '<i class="bi bi-icon-naam"></i> Mijn Module</a>';
});

// Voeg inhoud toe aan de themafooter
add_action('theme_footer', function() {
    echo '<div class="mijn-module-widget">...</div>';
});

// Verwerk formuliersubmissies (via init.php indien geen eigen router)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mijn_module_action'])) {
    if (!csrf_verify()) {
        flash('error', 'Ongeldige aanvraag.');
        redirect(BASE_URL . '/admin/mijn-module/');
    }
    // Verwerk de invoer...
}
```

### Beschikbare admin-hooks

| Hook | Argument | Beschrijving |
|------|----------|--------------|
| `admin_sidebar_nav` | `$activePage` | Voeg navigatielinks toe aan zijbalk |
| `admin_head` | – | Voeg inhoud toe aan `</head>` in beheerpaneel |
| `admin_footer` | – | Voeg inhoud toe vóór `</body>` in beheerpaneel |
| `theme_footer` | – | Voeg inhoud toe aan themazijde `</body>` |
| `theme_head` | – | Voeg inhoud toe aan thema `<head>` |

---

## install.php

Wordt **eenmalig** uitgevoerd na installatie. Gebruik dit voor:

- Aanmaken van databasetabellen
- Opslaan van standaardinstellingen

```php
<?php
$db = Database::getInstance();

// Maak moduletabel aan
$db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mijn_module_items` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `title`      VARCHAR(255) NOT NULL,
        `content`    TEXT,
        `status`     ENUM('active','inactive') DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Sla standaardinstellingen op
Settings::set('mijn_module_instelling', 'standaardwaarde');
```

---

## uninstall.php

Wordt uitgevoerd **vóór** verwijdering van de module. Gebruik dit voor opruimwerk.

```php
<?php
$db = Database::getInstance();

// Verwijder moduletabellen
$db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "mijn_module_items`");

// Verwijder instellingen (optioneel)
$db->query("DELETE FROM `" . DB_PREFIX . "settings` WHERE `key` LIKE 'mijn_module_%'");
```

---

## admin/index.php (beheerpagina)

Beheerpagina's volgen altijd dit patroon:

```php
<?php
require_once dirname(__DIR__, 3) . '/admin/includes/init.php';
Auth::requireAdmin();

$db     = Database::getInstance();
$pageTitle = 'Mijn Module';
$activePage = 'mijn-module';

// Verwerk acties
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Ongeldige aanvraag.');
        redirect(BASE_URL . '/admin/mijn-module/');
    }
    // ... verwerk formulier ...
    flash('success', 'Opgeslagen.');
    redirect(BASE_URL . '/admin/mijn-module/');
}

// Haal data op
$items = $db->fetchAll(
    "SELECT * FROM `" . DB_PREFIX . "mijn_module_items` ORDER BY created_at DESC"
);

require_once ADMIN_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h1><?= e($pageTitle) ?></h1>
</div>

<?= renderFlash() ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <!-- formuliervelden -->
            <button type="submit" class="btn btn-primary">Opslaan</button>
        </form>
    </div>
</div>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
```

### Regels voor beheerpagina's

1. **Altijd** `Auth::requireAdmin()` bovenaan aanroepen.
2. **Altijd** `csrf_verify()` bij POST-verwerking.
3. **Altijd** uitvoer escapen met `e()`.
4. Gebruik `flash()` + `redirect()` na POST (PRG-patroon).
5. Gebruik Bootstrap 5-klassen voor layout (`card`, `btn`, `table`, etc.).
6. Gebruik Bootstrap Icons (`<i class="bi bi-..."></i>`) voor iconen.
7. Shoelace Web Components zijn beschikbaar voor geavanceerde UI.

---

## Databaseconventies

- Tabelnamen: `DB_PREFIX . 'module_slug_naam'` (bijv. `cms_contact_submissions`)
- Gebruik **altijd** `DB_PREFIX` als prefix
- Primaire sleutel: `id INT AUTO_INCREMENT PRIMARY KEY`
- Tijdstempels: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP`
- Tekenset: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
- Gebruik prepared statements via `Database::getInstance()` – nooit raw SQL met gebruikersinvoer

---

## Naamgevingsconventies

| Element | Conventie | Voorbeeld |
|---------|-----------|---------|
| Module-slug | lowercase-koppeltekens | `contact-form` |
| Mapnaam module | zelfde als slug | `modules/contact-form/` |
| PHP-klassen | PascalCase | `ContactForm` |
| PHP-functies | camelCase | `getContactSubmissions()` |
| Hulpfuncties globaal | snake_case | `csrf_verify()` |
| Databasetabellen | prefix + snake_case | `cms_contact_submissions` |
| CSS-klassen | kebab-case | `.contact-form-widget` |
| JS-variabelen | camelCase | `contactFormData` |
| Settings-sleutels | snake_case met prefix | `contact_form_email` |

---

## Beschikbare constanten

```php
CMS_VERSION    // "1.0.x"
CMS_NAME       // "ROICT CMS"
BASE_PATH      // Absoluut pad naar projectroot
BASE_URL       // URL naar projectroot (geen trailing slash)
DB_PREFIX      // "cms_"
THEMES_PATH    // BASE_PATH . '/themes'
MODULES_PATH   // BASE_PATH . '/modules'
UPLOADS_PATH   // BASE_PATH . '/uploads'
ADMIN_PATH     // BASE_PATH . '/admin'
INSTALLED      // true/false
```

---

## Beveiligingsregels (verplicht)

1. **Escapen**: gebruik altijd `e($var)` bij het afdrukken van gebruikersinvoer in HTML.
2. **CSRF**: gebruik `csrf_field()` in elk formulier en `csrf_verify()` bij POST-verwerking.
3. **Authenticatie**: roep `Auth::requireAdmin()` aan bovenaan elke beheerpagina.
4. **SQL**: gebruik altijd prepared statements – nooit string-interpolatie in queries.
5. **Uploads**: valideer bestandstype en -grootte; sla op in `UPLOADS_PATH`.
6. **Redirects**: valideer URL's bij redirect na POST; gebruik altijd het PRG-patroon.

---

## Compleet voorbeeld: eenvoudige module

### `modules/voorbeeld-module/module.json`
```json
{
  "name": "Voorbeeld Module",
  "version": "1.0.0",
  "author": "ROICT",
  "description": "Een eenvoudige voorbeeldmodule die laat zien hoe modules werken.",
  "category": "Voorbeeld",
  "icon": "star"
}
```

### `modules/voorbeeld-module/install.php`
```php
<?php
$db = Database::getInstance();
$db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "voorbeeld_items` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `naam`       VARCHAR(255) NOT NULL,
        `aangemaakt` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
Settings::set('voorbeeld_module_actief', '1');
```

### `modules/voorbeeld-module/uninstall.php`
```php
<?php
$db = Database::getInstance();
$db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "voorbeeld_items`");
$db->query("DELETE FROM `" . DB_PREFIX . "settings` WHERE `key` LIKE 'voorbeeld_module_%'");
```

### `modules/voorbeeld-module/init.php`
```php
<?php
add_action('admin_sidebar_nav', function($activePage) {
    $actief = ($activePage ?? '') === 'voorbeeld-module' ? 'active' : '';
    echo '<a href="' . BASE_URL . '/admin/voorbeeld-module/" class="nav-link ' . $actief . '">'
       . '<i class="bi bi-star"></i> Voorbeeld</a>';
});
```

### `modules/voorbeeld-module/admin/index.php`
```php
<?php
require_once dirname(__DIR__, 3) . '/admin/includes/init.php';
Auth::requireAdmin();

$db        = Database::getInstance();
$pageTitle = 'Voorbeeld Module';
$activePage = 'voorbeeld-module';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Ongeldige aanvraag.');
        redirect(BASE_URL . '/admin/voorbeeld-module/');
    }
    $naam = trim($_POST['naam'] ?? '');
    if ($naam !== '') {
        $db->insert(DB_PREFIX . 'voorbeeld_items', ['naam' => $naam]);
        flash('success', 'Item toegevoegd.');
    }
    redirect(BASE_URL . '/admin/voorbeeld-module/');
}

$items = $db->fetchAll("SELECT * FROM `" . DB_PREFIX . "voorbeeld_items` ORDER BY aangemaakt DESC");

require_once ADMIN_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h1><?= e($pageTitle) ?></h1>
</div>

<?= renderFlash() ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Naam</label>
                <input type="text" name="naam" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Toevoegen
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Naam</th><th>Aangemaakt</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['naam']) ?></td>
                    <td><?= e($item['aangemaakt']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="2" class="text-center text-muted py-4">Geen items gevonden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
```

---

## Marketplace-registratie (api/marketplace.json)

Om een module beschikbaar te maken in de marketplace, voeg je deze toe aan `api/marketplace.json`:

```json
{
  "modules": [
    {
      "slug": "mijn-module",
      "name": "Mijn Module",
      "description": "Beschrijving van wat de module doet.",
      "version": "1.0.0",
      "author": "Auteursnaam",
      "category": "Categorie",
      "icon": "icon-naam",
      "tags": ["tag1", "tag2"],
      "downloads": 0,
      "rating": 5.0,
      "price": "free",
      "download_url": "https://url-naar-zip-bestand.zip"
    }
  ]
}
```
