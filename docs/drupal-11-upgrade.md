# Greg Hersov: Drupal 10 to Drupal 11

Upgrade work performed on 20 October 2026, with a gallery fix on 21 October 2026,
against the local DDEV project
`greghersov11`. Production has not been changed.

## Result and scope

The local populated site was upgraded from Drupal 10.6.15 to **11.4.10**.
It boots successfully with PHP 8.4.18 and MariaDB 11.8. Drupal's database updates
completed. The existing root-level Drupal layout is retained.

The user confirmed the upgraded site looks good. The final repository includes
a fresh export of 416 configuration files, six Composer-managed browser libraries,
and a cleaned project `tmp/` directory. At the final configuration check, the
database and `sync/` matched. Production deployment is still outstanding.

Book remains installed and uses **contributed Book 2.0.4**. It was never
uninstalled. All 32 Book nodes (31 published, one unpublished) and all 30 book
outline records were retained. Some Book nodes are intentionally outside the
outline. The custom theme, Book content type, permissions, MP3 fields, path
aliases, hierarchy, and custom navigation template are retained.

Tour was uninstalled while Drupal 10 code was still available. Its configuration,
entity view mode, editor permission, and module dependency were removed from
`sync/` by comparing the active configuration before and after the upgrade.

## Code and configuration changes

- Core packages now require `^11.4`, with 11.4.10 locked. PHP requires `^8.3`;
  the tested local runtime is PHP 8.4.18.
- Added `drupal/book:^2.0`, using the maintenance branch that supports the Drupal
  10-to-11 transition. Book 3 is a separate feature upgrade and was not needed.
- Updated Views Tree from 2.0.0-rc1 to 3.0.3. Its existing custom Book home view is
  disabled; that setting is preserved.
- Updated Composer Installers to 2.3.0, Composer Patches to 2.0.0, Drush to
  13.8.0, and transitive dependencies. Webform resolved to stable 6.3.1;
  Footnotes remains a release candidate and Better Formats remains a locked
  development revision, as required by the existing project constraints.
- Default package stability is now stable, with existing explicit exceptions.
  Removed the old `audit.block-insecure: false` setting.
- Added three version-pinned local compatibility patches and a restricted
  Composer Drupal Lenient allow-list; see [patches/README.md](../patches/README.md).
  These require project maintenance until compatible upstream releases exist.
- The custom `greghersov` theme now declares Drupal 11 support. Its PHP file has
  no executable custom logic; the compatibility scan found no removed APIs.
- Saved core/Book update-hook configuration changes, including new block
  settings, search configuration, editor settings, and updated Views settings.
- Added six pinned browser library packages to Composer, with immutable upstream
  commits, archive checksums, and explicit installation directories. SoundManager
  includes the player assets AudioField needs. DOMPurify was updated to 3.4.17
  and installed at the lowercase path Colorbox expects. See
  [library versions and maintenance](../libraries/README.md).
- Explicitly enabled `content.book_navigation` on
  `core.entity_view_display.node.book.default`. Contributed Book defaults its new
  navigation display component to hidden. Without this setting, the custom
  previous/next/parent navigation disappeared even though the content survived.
- Changed the local, Git-ignored `.ddev/config.yaml` project type to `drupal11`.
  Existing PHP 8.4 and MariaDB 11.8 settings were retained.
- Excluded local database snapshots and diagnostics in
  `tmp/drupal11-backup/` from Git while working, then moved the final artifacts
  outside the public web root to
  `/private/tmp/greghersov11-drupal11-backup-20261020/`. The README is excluded
  from Composer scaffold replacement so this documentation link survives future
  installs.

During the initial upgrade, four pre-existing active-versus-sync differences were
preserved:
`backup_migrate.backup_migrate_schedule.daily_schedule`,
`filter.format.basic_html`, `filter.format.full_html`, and `system.performance`.
For performance configuration, only the core update's `gzip` to `compress` key
rename was applied to the repository version. Local runtime values were not
exported over these four files during that upgrade.

After visual acceptance, the user requested a complete fresh export. The existing
`sync/` directory was backed up to
`/private/tmp/greghersov11-sync-before-export-etgejoyh/sync`, purged, and rebuilt
using `ddev drush config:export -y` (416 YAML files). The Apache `.htaccess`
access protection was restored separately. `drush config:status` confirmed no
differences between the database and `sync/`. This fresh export includes the active
local values for the four files above; review these against production before
deployment.

## Validation

- `drush status`: Drupal 11.4.10, connected database, successful bootstrap.
- `drush updatedb:status`: no pending database updates.
- `composer check-platform-reqs`: all installed package requirements passed.
- `composer audit`: no security vulnerability advisories reported.
- `composer install --dry-run`: nothing to install, update, or remove.
- `composer patches-doctor` and `composer patches-repatch`: all three local
  patches applied successfully with usable Git patchers.
- Clean installation and reinstallation of all six browser libraries passed.
  `scripts/verify-libraries.php` found all 27 module-declared assets and confirmed
  they were served intact over HTTP. No existing Composer package versions changed
  during the library conversion.
- Read-only snapshots from `scripts/book-snapshot.php` compared exactly before
  and after upgrading: row counts and SHA-256 fingerprints match for the Book
  table, all node tables (including fields and revisions), and URL alias tables.
  There are 37 total nodes, 32 of type Book, and 13 MP3 field rows.
- `scripts/verify-drupal11.php` checks anonymous HTTP rendering of the homepage
  and every published Book node, the custom navigation on outlined pages,
  SoundManager/MP3 markup, Book listing controller output, the existing Book edit
  form, notification role options, contrib Book discovery, Tour removal, and
  absence of orphan outlines.
- Upgrade Status scanned the custom theme and the four initial blocking modules.
  Removed Drupal 11 API calls were resolved. When run on Drupal 11, it now targets
  Drupal 12 and reports future deprecations/compatibility declarations; this is
  not a clean Drupal 12 readiness report.

Commands to repeat checks locally:

```sh
ddev drush updatedb:status
ddev composer validate --no-check-publish
ddev composer check-platform-reqs
ddev composer audit
ddev composer verify:libraries
ddev exec env LIBRARY_CHECK_ORIGIN=https://greghersov11.ddev.site php scripts/verify-libraries.php
ddev drush config:status
ddev drush php:script scripts/verify-drupal11.php
ddev drush php:script scripts/book-snapshot.php
```

Composer validation warns about deliberate exact versions for patched modules,
Composer-managed browser libraries, and the pre-existing Better Formats development
constraint. Library installation and maintenance are documented in
[libraries/README.md](../libraries/README.md). All six browser libraries are now
installed by Composer; SoundManager needs no standalone Git checkout. Composer's
audit result does not provide comprehensive JavaScript vulnerability coverage for
these project-defined library packages; monitor their upstream advisories too.

## Existing issues and remaining acceptance checks

The gallery lightbox was repaired on 21 October 2026. The original Composer
definition installed Jack Moore Colorbox 1.6.4, whose `jQuery.isFunction()` calls
fail against Drupal 11.4's jQuery 4. Composer now installs the module-recommended
Turbojet Technologies Colorbox 1.7.0 fork, pinned to commit
`b8c18673b64e917f7bd2eb8697e77adc2acf319b` with an archive checksum, at the same
`libraries/colorbox` path. No gallery configuration or content changed. See the
[module's migration instructions](https://www.drupal.org/project/colorbox/releases/2.2.0)
and the [library regression check](../libraries/README.md#colorbox-javascript-regression-check).
Chrome verification confirmed that a gallery thumbnail opens the full image and
caption in the lightbox and that Close returns to the gallery. The JavaScript
regression test fails with the old library and passes with the fork. The earlier
27-asset checks verified file delivery, but did not establish runtime compatibility.

The existing URL alias `/book` targets
`/something-rich-and-strange/something-rich-and-strange`, rather than a node
system path. It shadows Book's `/book` listing route and returns 404. The alias
is present in the unchanged pre-upgrade fingerprint. It was preserved; public
Book pages and their navigation are tested through `/node/{id}`. Correcting this
alias should be a separate editorial decision.

The local status report has no severity-error requirements. Warnings include
local settings-file protection, HTML5 validation, deprecated History and Stable 9,
and unavailable update-status data. AudioField's optional `audiowaveform` binary
is absent; this site uses SoundManager. Responsive Menu 4.4.5 emits a PHP 8.4
nullable-parameter deprecation while rebuilding caches. These are follow-up
maintenance items, not reasons to remove existing functionality.

The user accepted the site's appearance before the library conversion. Browser
checks were unavailable during the initial upgrade; the follow-up above verified
the desktop gallery in Chrome. Remaining acceptance checks: review mobile menus, other lightbox uses, and
embedded videos; play and seek representative MP3s; follow previous/next/up links;
verify printer-friendly output; edit/reorder a test Book page in staging; and
check comment notification delivery. Form construction and
server responses do not prove browser audio playback or email delivery.

## Local environment

`.ddev/` and site-specific settings are Git-ignored. A fresh checkout must recreate
the local environment: project name `greghersov11`, project type `drupal11`,
document root at the repository root, PHP 8.4, and MariaDB 11.8. Use `ddev start`
and `ddev composer install`, restore a copy of the site's database and uploaded
files, and configure local settings/private file paths before running the checks.
Use a database from the upgraded site with this Drupal 11 checkout. If starting
from a Drupal 10 backup, follow the Tour removal and Book bridge sequence below
before switching its code to Drupal 11. The exported configuration does not
contain Book content or media. Do not run a new site installation in place of
restoring the existing site's database.

## Deployment sequence

Use a staging clone of the **current production database and files** first.
Do not deploy the local database or its environment-specific settings.

1. Check production PHP, Composer, extensions, and database versions against
   Drupal 11.4 requirements and the committed lockfile. PHP 8.3+ is required;
   PHP 8.4 matches the tested environment. Check both web and CLI runtimes.
2. Back up the entire database, uploaded/private files, settings, and deployed
   code. Stop editorial writes and put the site into maintenance mode using the
   currently deployed Drupal 10 code. Keep backups outside the public web root.
3. While still on Drupal 10.3 or later, run pending updates, then uninstall Tour:

   ```sh
   vendor/bin/drush updatedb -y
   vendor/bin/drush pm:uninstall tour -y
   ```

4. On that Drupal 10 release, install the contrib Book bridge without
   uninstalling Book. Run this against the old Drupal 10 manifest, **before**
   deploying this repository's Drupal 11 manifest and lockfile:

   ```sh
   composer require 'drupal/book:^2.0' --with-all-dependencies --no-interaction
   vendor/bin/drush cache:rebuild
   vendor/bin/drush updatedb -y
   vendor/bin/drush php:script scripts/book-snapshot.php > /safe/path/content-before.json
   ```

   Make the snapshot script available from this branch first. Verify discovery
   points to `modules/contrib/book`, and take a further database backup after
   this bridge step. Never run `pm:uninstall book`: uninstalling it removes the
   outlines. The local upgrade followed this bridge sequence.
5. Deploy the complete Drupal 11 source, `composer.json`, `composer.lock`,
   `patches.lock.json`, patches, custom theme, and `sync/`. Composer now installs
   the six browser libraries
   (see [library deployment notes](../libraries/README.md) for clearing old manual
   copies). Install exactly the locked packages:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   composer check-platform-reqs --no-dev
   composer verify:libraries
   vendor/bin/drush updatedb -y
   vendor/bin/drush cache:rebuild
   ```

6. Review `vendor/bin/drush config:status` and the proposed configuration diff.
   Reconcile live configuration changes with `sync/` before importing. The fresh
   export includes local active values for the four previously differing files
   listed above. In particular, review production text formats, backup schedules,
   and performance settings before replacing them. Once the diff is reviewed:

   ```sh
   vendor/bin/drush config:import -y
   vendor/bin/drush cache:rebuild
   vendor/bin/drush updatedb:status
   ```

   The import must include the explicit Book navigation display component and
   must not re-enable Tour. Do not export local runtime settings over production.
7. Compare `scripts/book-snapshot.php` output with the snapshot from step 4 and
   review the error log. On staging, disable maintenance mode before running the
   anonymous HTTP integration checks and browser acceptance checks. Set Drush's
   `--uri` to the staging site's URL when running the scripts. Complete staging
   acceptance before the production cutover.
8. During the production cutover, repeat the content comparison and update/config
   checks while maintenance mode is enabled. Then disable maintenance mode and
   rebuild caches, run the anonymous HTTP checks against the production URI, and
   reopen editorial access once they pass. Run tests that create or reorder
   content on staging. The integration scripts themselves do not save content.

The existing `.cpanel.yml` only copies non-hidden files. It does not install
Composer dependencies, update the database, or import configuration; core,
contributed modules, browser libraries, and vendor files are Git-ignored. That copy task alone is
not a Drupal 11 deployment. Build the complete release artifact or run Composer
on the host, and include required scaffold files such as `.htaccess`.

## Backups and rollback

Local original artifacts were moved outside the web root to
`/private/tmp/greghersov11-drupal11-backup-20261020/`:
`before-upgrade.sql.gz`, original `composer.json`/`composer.lock`, active config
exports, content fingerprints, enabled modules, Composer/update logs, and
integration results. Treat the directory as private: it contains database data.
It is a local recovery aid, not a replacement for independent production backups.
The backup paths recorded here are specific to this workstation. `/private/tmp`
is temporary storage: copy any recovery artifacts you need to durable, private
storage before system cleanup or relying on them for a later deployment.

The pre-export configuration and pre-library-conversion backups are recorded in
the export section above and [libraries/README.md](../libraries/README.md).

The project `tmp/` folder was subsequently cleaned: the generated Upgrade Status
cache was removed, and the pre-existing `config.tar.gz` was moved to
`/private/tmp/greghersov11-tmp-cleanup-dq2x9s28/config.tar.gz`. Only the existing
`.htaccess` protection remains. Runtime temporary files are now Git-ignored.

For rollback, keep maintenance mode on, restore the **matching old code and old
lockfile together with the pre-upgrade database**, and rebuild caches using that
old release. Restore files/settings from the same backup if changed. Core schema
updates are not reversible by merely downgrading Composer packages. Restore the
old exported configuration with the old release as well. Retest the book and
only then reopen access.

## References

- [Drupal 11.4.10 release](https://www.drupal.org/project/drupal/releases/11.4.10)
- [Book replacement instructions and supported branches](https://www.drupal.org/project/book)
- [Tour removal from core](https://www.drupal.org/node/3421972)
- [System requirements](https://www.drupal.org/docs/getting-started/system-requirements)
- [AudioField Drupal 11 compatibility issue](https://www.drupal.org/project/audiofield/issues/3494200)
