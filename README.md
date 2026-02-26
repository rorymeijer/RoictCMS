# ROICT CMS

Een modulair Content Management Systeem gebouwd met PHP, MySQL, Bootstrap, en Web Awesome (Shoelace).

## 📦 Functionaliteiten

- **Installer** – Begeleide installatie wizard in `/install/`
- **Admin Paneel** – Volledig admin systeem op `/admin/`
- **Pagina's** – Aanmaken, bewerken, verwijderen van statische pagina's
- **Nieuws** – Nieuwsberichten met categorieën, samenvatting en uitgelichte afbeelding
- **Gebruikers** – Gebruikersbeheer met rollen (Admin, Redacteur, Auteur)
- **Media** – Drag-and-drop media upload manager
- **Thema's** – Meerdere themes in `themes/` subfolders, activeren via admin
- **Modules** – Module beheer met activeer/deactiveer/verwijder
- **Marketplace** – Webshop voor modules en thema's via JSON API
- **Updates** – CMS self-update systeem met versie check
- **Instellingen** – Volledig configureerbaar via admin

## 🚀 Installatie

1. Upload alle bestanden naar uw webserver
2. Navigeer naar `/install/`
3. Volg de installatie wizard (5 stappen)
4. Log in op `/admin/`

## 📁 Directorystructuur

```
cms/
├── admin/                    # Admin paneel
│   ├── includes/             # Header, footer, init
│   ├── pages/                # Pagina beheer
│   ├── news/                 # Nieuws beheer  
│   ├── users/                # Gebruikers beheer
│   ├── media/                # Media upload
│   ├── themes/               # Thema activatie
│   ├── modules/              # Module beheer
│   ├── marketplace/          # Marketplace (modules + thema's)
│   ├── settings/             # CMS instellingen
│   └── updates/              # CMS updates
├── api/
│   └── marketplace.json      # Marketplace definitie
├── core/                     # CMS core classes
│   ├── bootstrap.php         # Class autoloader + helpers
│   ├── config.php            # Configuratie constanten
│   ├── Database.php          # PDO database wrapper
│   ├── Auth.php              # Authenticatie & sessies
│   ├── Settings.php          # CMS instellingen helper
│   ├── ModuleManager.php     # Module installatie & beheer
│   ├── ThemeManager.php      # Thema beheer
│   └── Updater.php           # CMS update systeem
├── install/
│   └── index.php             # Installatie wizard
├── themes/
│   └── roict-basic/          # ROICT Basic theme
│       ├── theme.json        # Theme metadata
│       ├── functions.php     # Theme functies + hooks
│       ├── header.php        # Site header + navigatie
│       ├── footer.php        # Site footer
│       ├── home.php          # Homepage template
│       ├── archive.php       # Nieuws overzicht
│       ├── single.php        # Enkel nieuwsbericht
│       ├── page.php          # Statische pagina
│       ├── 404.php           # 404 pagina
│       └── assets/
│           └── css/theme.css # Volledig CSS systeem
├── modules/                  # Geïnstalleerde modules
├── uploads/                  # Geüploade bestanden
│   └── images/
├── config.php                # Gegenereerd door installer
├── index.php                 # Frontend router
└── .htaccess                 # URL rewriting
```

## 🎨 Eigen Thema Maken

1. Maak een nieuwe map in `themes/mijn-thema/`
2. Voeg een `theme.json` toe:
```json
{
  "name": "Mijn Thema",
  "version": "1.0.0",
  "author": "Uw naam",
  "description": "Beschrijving van het thema"
}
```
3. Maak de template bestanden aan: `header.php`, `footer.php`, `home.php`, `archive.php`, `single.php`, `page.php`, `404.php`
4. Activeer het thema via Admin → Thema's

## 🔌 Marketplace JSON Formaat

De marketplace leest van `api/marketplace.json` of een externe URL:

```json
{
  "modules": [
    {
      "slug": "mijn-module",
      "name": "Mijn Module",
      "description": "Wat doet de module",
      "version": "1.0.0",
      "author": "Auteur",
      "category": "Categorie",
      "icon": "puzzle",
      "downloads": 100,
      "rating": 4.5,
      "price": "free",
      "download_url": "https://example.com/module.zip"
    }
  ],
  "themes": [...]
}
```

## 🔒 Beveiliging

- CSRF beveiliging op alle formulieren
- Wachtwoorden gehasht met bcrypt
- SQL queries via prepared statements (PDO)
- `.htaccess` blokkeert toegang tot `core/` en `config.php`
- Verwijder `install/` na installatie!

## 📋 Vereisten

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache met mod_rewrite (of Nginx equivalent)
- PDO MySQL extensie
- GD Library

## 🛠️ Technische Stack

- **Backend**: PHP 8 (OOP, PDO)
- **Database**: MySQL / MariaDB
- **Frontend Admin**: Bootstrap 5.3 + Shoelace (Web Awesome) + Bootstrap Icons
- **Frontend Theme**: Eigen CSS (geen framework dependency)
- **Typografie**: Bricolage Grotesque + Source Serif 4

---

*ROICT CMS v1.0.0 — Gebouwd door ROICT*
