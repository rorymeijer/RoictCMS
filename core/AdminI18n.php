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

function admin_lang(): string {
    $fallback = Settings::get('language', 'nl');
    $lang = Settings::get('admin_language', $fallback);
    $supported = admin_available_languages();
    return isset($supported[$lang]) ? $lang : 'nl';
}

function admin_i18n_base_dictionary(): array {
    return [
        'en' => [
            'Dashboard' => 'Dashboard',
            'Inhoud' => 'Content',
            "Pagina's" => 'Pages',
            'Nieuws' => 'News',
            'Beheer' => 'Management',
            'Gebruikers' => 'Users',
            "Thema's" => 'Themes',
            'Uitbreidingen' => 'Extensions',
            'Instellingen' => 'Settings',
            'Updates' => 'Updates',
            'Bekijk website' => 'View website',
            'Uitloggen' => 'Logout',
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
            'pagina\'s totaal' => 'total pages',
            'berichten totaal' => 'total posts',
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
    if ($lang === 'nl') {
        return $html;
    }

    $dict = admin_i18n_dictionary($lang);
    if (!$dict) {
        return $html;
    }

    return strtr($html, $dict);
}
