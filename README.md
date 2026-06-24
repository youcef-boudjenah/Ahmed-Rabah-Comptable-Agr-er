# Cabinet Comptable — Plateforme de gestion (Algérie)

PHP vanilla + MySQL + Tailwind. Automatisation CNAS, CACOBATPH, G50, G12 avec OCR.

## Prérequis

- PHP 8.1+
- MySQL 8+
- Composer
- (Optionnel) Tesseract OCR + poppler (`pdftotext`) pour OCR avancé

## Installation

```bash
composer install
php scripts/migrate.php
php scripts/seed.php
php scripts/test-calc.php
```

## Lancer le serveur

```bash
php -S localhost:8080 -t public
```

Ouvrir http://localhost:8080 — **admin@cabinet.dz** / **admin123**

## Worker OCR (arrière-plan)

```bash
php workers/ocr-worker.php
```

## Configuration (.env)

```
APP_URL=http://localhost:8080
APP_KEY=your-32-char-secret-key
DB_HOST=127.0.0.1
DB_DATABASE=cabinet_comptable
DB_USERNAME=root
DB_PASSWORD=
OPENROUTER_API_KEY=sk-or-...
```

## Flux démo

1. Connexion → Tableau de bord
2. **Saisie paie** → Client BOUALAM MOHAMED → masse 173781.80 → CNAS ~61049.55 DA
3. **Déclarations** → Revue → Approuver → Marquer déposée
4. **Documents OCR** → Upload `fiche de paie.pdf` → Valider → déclarations auto

## Structure

- `public/` — point d'entrée
- `src/Modules/` — auth, clients, entries, declarations, documents, automation, ai, alerts
- `migrations/` — schéma MySQL
- `storage/uploads/` — documents uploadés
- `AHMED RABAH MOKHTAR/` — échantillons officiels (CNAS, CACOBATPH, G50, G12)
