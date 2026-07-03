# Space Quota

A Drupal module that manages and restricts disk space usage on node forms,
enforcing configurable quotas either globally or per user.

## Features

- Configurable maximum quota in megabytes.
- Two calculation modes: **global** (all users share a single quota) or
  **per-user** (each user has an individual quota).
- Configurable warning threshold: displays a warning message when a user
  reaches a defined percentage of their quota.
- Selectively disables file/image upload widgets when the quota is exceeded,
  while keeping the delete buttons active so users can free up space.
- Optional support for the **body** field: disables the CKEditor editor when
  the quota is exceeded.
- Optional exclusion of **orphaned files** (files with no recorded usages)
  from the storage calculation, without waiting for a Cron run.
- Two permissions: bypass all quota checks, or view the quota status page.
- Dedicated quota status page at `/quota/status`.
- Refined cache invalidation via a custom cache tag
  (`space_quota:calculated_size`), triggered on file entity insert/delete.

## Requirements

- Drupal 10.3 or Drupal 11.
- The core **File** module (provides `\Drupal\file\FileInterface`).

## Installation

### Via Composer (Recommended)

1. Since this module is hosted on GitHub, add the repository to your project's composer.json first:

```bash
composer config repositories.space_quota vcs https://github.com/francoud/space_quota
```

2. Then, install the module:

```bash
composer require francoud/space_quota
```

3. Enable the module via **Extend** (`/admin/modules`) or with Drush:

   ```bash
   drush en space_quota
   ```
4. Grant the appropriate permissions at `/admin/people/permissions`.

### Manual Installation

1. Place the `space_quota` folder in your `modules/custom/` directory.
2. Enable the module via **Extend** (`/admin/modules`) or with Drush:

   ```bash
   drush en space_quota
   ```

3. Grant the appropriate permissions at `/admin/people/permissions`.

## Configuration

Navigate to **Administration → Configuration → Content authoring → Space Quota
Settings** (`/admin/config/content/quota`).

### General Quota Settings

| Setting | Description | Default |
|---|---|---|
| Maximum disk quota (MB) | Hard limit in megabytes. | 10 |
| Calculation mode | *Global* or *Per user*. | Global |
| Warning threshold (%) | Percentage at which a warning message is shown. | 75 |
| Ignore orphaned files | Exclude files with no content references from the count. | Off |

### Content and Field Restrictions

| Setting | Description |
|---|---|
| Content Types to Restrict | Select which node types are subject to quota enforcement. |
| File Fields to Monitor | Select which file/image fields (and optionally body) are blocked when the quota is exceeded. |

## Permissions

| Permission | Description |
|---|---|
| `bypass space quota checks` | User can upload files regardless of quota. Restricted access. |
| `view space quota status` | User can access the quota status page at `/quota/status`. |

## Status Page

The page at `/quota/status` displays:

- The configured maximum quota and calculation mode.
- The current used storage (formatted as B/KB/MB/GB/TB/PB).
- The usage percentage, with a CSS class indicating status:
  - `quota-status-ok` (below 70%)
  - `quota-status-warning` (70–90%)
  - `quota-status-critical` (above 90%)
- A notice when the quota is global.

The page bypasses the render cache (`max-age: 0`) so values are always current.

## Planned Improvements (Phase 2)

The following changes are identified for a future release to align the module
more closely with Drupal.org publishing standards:

- Rename procedural functions in `space_quota.module` to follow Drupal hook
  naming conventions consistently.
- Add a `config/schema/space_quota.schema.yml` file for configuration schema
  validation.
- Add a `config/install/space_quota.settings.yml` file for default
  configuration.
- Introduce a dedicated service class for storage calculation logic instead of
  standalone procedural functions.
- Add automated tests (Kernel or Functional).

## Maintainers

- Initial development: private use, with assistance from Claude (Anthropic)
  and Gemini (Google).
- repository:  https://github.com/francoud/space_quota 
