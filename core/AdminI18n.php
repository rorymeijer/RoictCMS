<?php

function admin_available_languages(): array {
    return [
        'nl' => 'Nederlands',
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'es' => 'Español',
    ];
}

function normalize_language_code(?string $language): string {
    $language = strtolower(trim((string)$language));
    if ($language === '') {
        return '';
    }

    // Ondersteun ook locale-vormen zoals en-US of nl_NL.
    $base = preg_split('/[-_]/', $language, 2)[0] ?? '';
    return preg_replace('/[^a-z]/', '', $base) ?: '';
}

function site_lang(): string {
    $supported = admin_available_languages();
    $configured = normalize_language_code(Settings::get('language', 'nl'));
    return isset($supported[$configured]) ? $configured : 'nl';
}

function admin_lang(): string {
    $fallback = site_lang();
    $lang = normalize_language_code(Settings::get('admin_language', $fallback));
    $supported = admin_available_languages();
    return isset($supported[$lang]) ? $lang : $fallback;
}

function apply_site_locale(): void {
    $lang = site_lang();
    $localeMap = [
        'nl' => ['nl_NL.UTF-8', 'nl_NL', 'nl'],
        'en' => ['en_US.UTF-8', 'en_US', 'en_GB.UTF-8', 'en_GB', 'en'],
        'fr' => ['fr_FR.UTF-8', 'fr_FR', 'fr'],
        'de' => ['de_DE.UTF-8', 'de_DE', 'de'],
        'es' => ['es_ES.UTF-8', 'es_ES', 'es'],
    ];
    $locales = $localeMap[$lang] ?? $localeMap['nl'];

    setlocale(LC_ALL, ...$locales);

    if (class_exists('Locale')) {
        Locale::setDefault(str_replace('.UTF-8', '', $locales[0]));
    }
}

function admin_i18n_base_dictionary(): array {
    return [
        'en' => [
            // Navigation
            'Dashboard' => 'Dashboard',
            'Inhoud' => 'Content',
            "Pagina's" => 'Pages',
            'Nieuws' => 'News',
            'Media' => 'Media',
            'Beheer' => 'Management',
            'Gebruikers' => 'Users',
            "Thema's" => 'Themes',
            'Uitbreidingen' => 'Extensions',
            'Instellingen' => 'Settings',
            'Updates' => 'Updates',
            'Bekijk website' => 'View website',
            'Uitloggen' => 'Logout',
            // Module sidebar items
            'Reacties' => 'Comments',
            'Statistieken' => 'Statistics',
            'Contact Formulier' => 'Contact Form',
            'Twee-Factor Auth' => 'Two-Factor Auth',
            'Rollen &amp; Rechten' => 'Roles &amp; Permissions',
            'Galerijen' => 'Galleries',
            'Klantenpaneel' => 'Customer Portal',
            'Klanten' => 'Customers',
            'Offertes' => 'Quotes',
            'Facturen' => 'Invoices',
            // Dashboard
            'Recente berichten' => 'Recent posts',
            'Alles bekijken' => 'View all',
            'Titel' => 'Title',
            'Auteur' => 'Author',
            'Status' => 'Status',
            'Datum' => 'Date',
            'Nog geen berichten' => 'No posts yet',
            'Beheren' => 'Manage',
            'Nieuwe Pagina' => 'New page',
            'Nieuwsbericht' => 'News post',
            'Gebruiker' => 'User',
            'Media Upload' => 'Media upload',
            'Nieuwsberichten' => 'News posts',
            'Actieve Modules' => 'Active modules',
            // Profile page
            'Mijn Profiel' => 'My Profile',
            'Profielgegevens' => 'Profile information',
            'Gebruikersnaam *' => 'Username *',
            'Gebruikersnaam' => 'Username',
            'E-mailadres *' => 'Email address *',
            'E-mailadres' => 'Email address',
            'Wachtwoord wijzigen' => 'Change password',
            'Nieuw wachtwoord' => 'New password',
            'laat leeg om niet te wijzigen' => 'leave empty to not change',
            'Wachtwoord bevestigen' => 'Confirm password',
            'Wijzigingen opslaan' => 'Save changes',
            'Accountinformatie' => 'Account information',
            'Rol' => 'Role',
            'Laatste login' => 'Last login',
            'Lid sinds' => 'Member since',
            // Common actions & labels
            'Weet u het zeker?' => 'Are you sure?',
            'Toevoegen' => 'Add',
            'Bewerken' => 'Edit',
            'Verwijderen' => 'Delete',
            'Opslaan' => 'Save',
            'Annuleren' => 'Cancel',
            'Zoeken' => 'Search',
            'Filteren' => 'Filter',
            'Actief' => 'Active',
            'Inactief' => 'Inactive',
            'Gepubliceerd' => 'Published',
            'Concept' => 'Draft',
            'Ja' => 'Yes',
            'Nee' => 'No',
            'Sluiten' => 'Close',
            'Opgeslagen' => 'Saved',
            'Verwijderd' => 'Deleted',
            'Admin' => 'Admin',
            'Beheerder' => 'Administrator',
            'Lid' => 'Member',
            // Pages & News
            'Pagina toevoegen' => 'Add page',
            'Pagina bewerken' => 'Edit page',
            'Nieuwsbericht toevoegen' => 'Add news post',
            'Nieuwsbericht bewerken' => 'Edit news post',
            'Slug' => 'Slug',
            'Inhoud' => 'Content',
            'Samenvatting' => 'Summary',
            'Uitgelichte afbeelding' => 'Featured image',
            'Publiceren' => 'Publish',
            'Opslaan als concept' => 'Save as draft',
            'Gepubliceerd op' => 'Published on',
            'Aangemaakt op' => 'Created on',
            'Bijgewerkt op' => 'Updated on',
            // Users
            'Gebruiker toevoegen' => 'Add user',
            'Gebruiker bewerken' => 'Edit user',
            'Wachtwoord' => 'Password',
            'Bevestig wachtwoord' => 'Confirm password',
            'Rollen' => 'Roles',
            // Media
            'Afbeelding uploaden' => 'Upload image',
            'Bestand selecteren' => 'Select file',
            'Uploaden' => 'Upload',
            'Bestandsnaam' => 'Filename',
            'Bestandsgrootte' => 'File size',
            'Alt-tekst' => 'Alt text',
            // Settings
            'Algemene Instellingen' => 'General settings',
            'Sitenaam *' => 'Site name *',
            'Tagline' => 'Tagline',
            'Contact Email' => 'Contact email',
            'Footer Tekst' => 'Footer text',
            'Lees & Weergave' => 'Reading & display',
            'Berichten per pagina' => 'Posts per page',
            'Datumformaat' => 'Date format',
            'Tijdzone' => 'Timezone',
            'Taal' => 'Language',
            'Beheertaal' => 'Admin language',
            'Homepage instelling' => 'Homepage setting',
            'Homepage type' => 'Homepage type',
            'Standaard homepage' => 'Default homepage',
            'Statische pagina' => 'Static page',
            'Kies pagina' => 'Choose page',
            '— Selecteer een pagina —' => '— Select a page —',
            "Geen gepubliceerde pagina's beschikbaar." => 'No published pages available.',
            'Kies welke modulestatus zichtbaar is in de marketplace.' => 'Choose which module status is visible in the marketplace.',
            'Stabiel' => 'Stable',
            'Manuale upload' => 'Manual upload',
            'Cache flushen & ZIP opnieuw ophalen' => 'Flush cache & redownload ZIP',
            'Forceert direct een nieuwe controle van ZIP-bestanden in de module marketplace.' => 'Forces an immediate new check of ZIP files in the module marketplace.',
            'Site Status' => 'Site status',
            'Onderhoudsmodus' => 'Maintenance mode',
            'Onderhoudsbericht' => 'Maintenance message',
            'Bijv. We zijn even bezig. Kom later terug!' => 'E.g. We are doing maintenance. Please come back later!',
            'Dit bericht wordt getoond aan bezoekers tijdens de onderhoudsmodus.' => 'This message is shown to visitors during maintenance mode.',
            'CMS Info' => 'CMS info',
            'Versie' => 'Version',
            'Database' => 'Database',
            'Thema' => 'Theme',
            'Instellingen opslaan' => 'Save settings',
            'Instellingen opgeslagen.' => 'Settings saved.',
            "pagina's totaal" => 'total pages',
            'berichten totaal' => 'total posts',
            // Flash / error messages
            'Onjuiste gebruikersnaam of wachtwoord.' => 'Incorrect username or password.',
            'Gebruikersnaam is verplicht.' => 'Username is required.',
            'Ongeldige aanvraag.' => 'Invalid request.',
            'Niet gevonden.' => 'Not found.',
            'Toegang geweigerd.' => 'Access denied.',
            'Er is een fout opgetreden.' => 'An error occurred.',
            'Wijzigingen opgeslagen.' => 'Changes saved.',
            'Gebruiker opgeslagen.' => 'User saved.',
            'Pagina opgeslagen.' => 'Page saved.',
            'Bericht opgeslagen.' => 'Post saved.',
            'Succesvol verwijderd.' => 'Successfully deleted.',
        ],
        'fr' => [
            'Dashboard' => 'Tableau de bord',
            'Inhoud' => 'Contenu',
            "Pagina's" => 'Pages',
            'Nieuws' => 'Actualités',
            'Beheer' => 'Administration',
            'Gebruikers' => 'Utilisateurs',
            "Thema's" => 'Thèmes',
            'Uitbreidingen' => 'Extensions',
            'Instellingen' => 'Paramètres',
            'Updates' => 'Mises à jour',
            'Bekijk website' => 'Voir le site',
            'Uitloggen' => 'Déconnexion',
            'Nieuwsberichten' => 'Actualités',
            'Actieve Modules' => 'Modules actifs',
            'Weet u het zeker?' => 'Êtes-vous sûr ?',
            'Beheertaal' => "Langue d'administration",
            'Instellingen opgeslagen.' => 'Paramètres enregistrés.',
            'Instellingen opslaan' => 'Enregistrer les paramètres',
        ],
        'de' => [
            'Dashboard' => 'Dashboard',
            'Inhoud' => 'Inhalt',
            "Pagina's" => 'Seiten',
            'Nieuws' => 'Neuigkeiten',
            'Beheer' => 'Verwaltung',
            'Gebruikers' => 'Benutzer',
            "Thema's" => 'Themes',
            'Uitbreidingen' => 'Erweiterungen',
            'Instellingen' => 'Einstellungen',
            'Updates' => 'Updates',
            'Bekijk website' => 'Website ansehen',
            'Uitloggen' => 'Abmelden',
            'Nieuwsberichten' => 'Neuigkeiten',
            'Actieve Modules' => 'Aktive Module',
            'Weet u het zeker?' => 'Sind Sie sicher?',
            'Beheertaal' => 'Admin-Sprache',
            'Instellingen opgeslagen.' => 'Einstellungen gespeichert.',
            'Instellingen opslaan' => 'Einstellungen speichern',
        ],
        'es' => [
            'Dashboard' => 'Panel',
            'Inhoud' => 'Contenido',
            "Pagina's" => 'Páginas',
            'Nieuws' => 'Noticias',
            'Beheer' => 'Administración',
            'Gebruikers' => 'Usuarios',
            "Thema's" => 'Temas',
            'Uitbreidingen' => 'Extensiones',
            'Instellingen' => 'Configuración',
            'Updates' => 'Actualizaciones',
            'Bekijk website' => 'Ver sitio web',
            'Uitloggen' => 'Cerrar sesión',
            'Recente berichten' => 'Noticias recientes',
            'Alles bekijken' => 'Ver todo',
            'Titel' => 'Título',
            'Auteur' => 'Autor',
            'Status' => 'Estado',
            'Datum' => 'Fecha',
            'Nog geen berichten' => 'Aún no hay noticias',
            'Beheren' => 'Gestionar',
            'Nieuwe Pagina' => 'Nueva página',
            'Nieuwsbericht' => 'Noticia',
            'Gebruiker' => 'Usuario',
            'Media Upload' => 'Subir medios',
            'Nieuwsberichten' => 'Noticias',
            'Actieve Modules' => 'Módulos activos',
            'Weet u het zeker?' => '¿Estás seguro?',
            'Beheertaal' => 'Idioma del panel',
            'Instellingen opgeslagen.' => 'Configuración guardada.',
            'Instellingen opslaan' => 'Guardar configuración',
        ],
    ];
}

function admin_load_module_dictionary_file(string $file): array {
    if (!is_file($file) || !is_readable($file)) {
        return [];
    }

    $dict = require $file;
    return is_array($dict) ? $dict : [];
}

function admin_module_dictionary(string $lang): array {
    static $cache = [];
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }

    $dictionary = [];

    if (!is_dir(MODULES_PATH)) {
        return $cache[$lang] = $dictionary;
    }

    $slugs = [];
    $dirs = glob(MODULES_PATH . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($dirs as $dir) {
        $slugs[] = basename($dir);
    }

    foreach (array_unique($slugs) as $slug) {
        $base = MODULES_PATH . '/' . $slug . '/lang';
        $candidates = [
            $base . '/admin.' . $lang . '.php',
            $base . '/' . $lang . '.php',
        ];

        foreach ($candidates as $file) {
            $moduleDict = admin_load_module_dictionary_file($file);
            if (!empty($moduleDict)) {
                $dictionary = array_merge($dictionary, $moduleDict);
                break;
            }
        }
    }

    return $cache[$lang] = $dictionary;
}

function admin_i18n_dictionary(string $lang): array {
    static $cache = [];
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }

    $base = admin_i18n_base_dictionary()[$lang] ?? [];
    $modules = admin_module_dictionary($lang);

    // Modules mogen bestaande vertalingen overschrijven.
    $dictionary = array_merge($base, $modules);

    return $cache[$lang] = apply_filters('admin_i18n_dictionary', $dictionary, $lang);
}

function admin_translate_html(string $html): string {
    $lang = admin_lang();

    // Always sync the <html lang="..."> attribute with the actual admin language.
    $html = preg_replace('/<html([^>]*)\blang="[^"]*"/', '<html$1 lang="' . $lang . '"', $html, 1);

    if ($lang === 'nl') {
        return $html;
    }

    $dict = admin_i18n_dictionary($lang);
    if (!$dict) {
        return $html;
    }

    return strtr($html, $dict);
}
