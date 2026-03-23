# blog.philipnewborough.co.uk

A personal blog application built with [CodeIgniter 4](https://codeigniter.com/) (PHP 8.2+). It renders Markdown content, supports tagged posts with an RSS feed, and includes a password-protected admin panel for managing posts and media.

## Features

### Public site
- **Home page** — displays the most recent published post prominently, followed by all other published posts
- **Post pages** — full post view with tag list, featured image, optional embedded video, and a "similar posts" sidebar derived from shared tags
- **Tag archive** — lists all posts associated with a given tag
- **Search** — full-text search across published posts
- **RSS feed** — available at `/feed/rss`
- **JSON / Markdown endpoints** — every post exposes `/posts/{slug}/json` and `/posts/{slug}/markdown` for raw access

### Admin panel (`/admin`)
- DataTable-based post list with bulk delete
- Post editor with Markdown source, live preview, and slug auto-generation
- Featured image upload, removal, and media library browser
- Body image upload (inserted into post content)
- Video upload and removal
- Publish / save-as-draft workflow with explicit `published_at` control
- Stats dashboard

### Authentication
- Session-based auth backed by an external auth service
- `AdminFilter` protects all `/admin` routes
- `OptionalAuthFilter` hydrates the session on public routes when a cookie is present

### Data model
| Table | Description |
|-------|-------------|
| `posts` | UUID-keyed posts with soft deletes, status (`draft` / `published` / `revision` / `trashed`), and visibility (`public` / `private`) |
| `tags` | One row per tag per post; queried in bulk to avoid N+1 |
| `meta` | Key/value pairs attached to a post (e.g. `post_video`) |

### CLI commands
| Command | Description |
|---------|-------------|
| `php spark import:posts` | Import posts, tags, and meta from the most recent JSON export in `imports/` |
| `php spark import:posts <file>` | Import from a specific JSON file |
| `php spark import:posts --truncate` | Clear all posts, tags, meta, and media before importing |

The import command downloads and renames featured images (`og-{uuid}.ext`) and body images (`{uuid}.ext`) into `public/media/`, and updates all in-body image URLs automatically.

## Tech stack

| Layer | Technology |
|-------|------------|
| Framework | CodeIgniter 4 |
| PHP | 8.2+ |
| CSS | Bootstrap 5 (BEM naming) |
| JavaScript | Airbnb style guide, ESLint |
| PHP style | PSR-12 |
| UUID generation | `ramsey/uuid` |
| Admin tables | `hermawan/codeigniter4-datatables` |
| Markdown rendering | External Markdown API (via `App\Libraries\Markdown`) |
| Testing | PHPUnit 10 |

## Requirements

- PHP 8.2+
- Composer
- A web server (Apache / Nginx / `php spark serve`) pointing to `public/`
- A configured database (see `app/Config/Database.php`)

## Getting started

```bash
# Install PHP dependencies
composer install

# Install JS dev dependencies (linting only)
npm install

# Copy the environment file and configure it
cp env .env
# Edit .env: set CI_ENVIRONMENT, database credentials, baseURL, etc.

# Run database migrations
php spark migrate

# (Optional) Import an existing post export
php spark import:posts
```

## Project structure

```
app/
  Commands/       # Spark CLI commands (import:posts)
  Config/         # Application configuration
  Controllers/    # Route controllers (Admin/, Api/, CLI/, Debug/)
  Database/       # Migrations and seeds
  Filters/        # HTTP filters (auth, admin, API guards)
  Libraries/      # Markdown, Notification, Sendmail
  Models/         # PostModel, TagModel, MetaModel
  Views/          # Blade-style PHP views (admin/, templates/, feed/, …)
imports/          # Drop JSON export files here for import:posts
public/
  assets/         # Compiled CSS, JS, images
  media/          # Uploaded post images and videos
tests/            # PHPUnit test suite
```

## Running tests

```bash
composer test
```

## Linting

```bash
npx eslint public/assets/js/
```
