# Composer-managed browser libraries

Run `composer install` from the project root to populate this directory. The
library files are Git-ignored; commit `composer.json` and `composer.lock` instead.
No separate JavaScript build, library download command, or Git submodule is needed.

| Library | Composer package | Version | Installation directory |
| --- | --- | --- | --- |
| Colorbox | `jackmoore/colorbox` | 1.6.4 | `libraries/colorbox` |
| DOMPurify | `cure53/dompurify` | 3.4.17 | `libraries/dompurify` |
| FitVids | `jquery/fitvids` | 1.2.0 | `libraries/fitvids` |
| mmenu | `frdh/mmenu-js` | 8.5.20 | `libraries/mmenu` |
| SoundManager2 | `scottschiller/soundmanager2` | 2.97.20170601 | `libraries/soundmanager` |
| Superfish | `joeldbirch/superfish` | 1.7.10 | `libraries/superfish` |

## Installation design

The root manifest defines these as `drupal-library` packages, using upstream
release commits, HTTPS ZIP archives, archive checksums, and explicit installer
names. The existing Composer Installers plugin places them in the paths expected
by the installed Drupal modules. Git source references are also available through
Composer; ordinary deployment uses `--prefer-dist` and needs no nested Git repos.
No additional Composer plugin or asset registry is required.

SoundManager uses upstream tag `V2.97a.20170601`, commit
`f432aa9ed96a0f9022e72fc4a1b977ba0643ed71`. The full archive includes `script/`,
`demo/bar-ui/`, and the other bundled players that AudioField references. Its
active main script, bar UI JavaScript, and bar UI CSS match the previous local
copies after normalizing line endings. A standalone Git checkout would duplicate
version management without improving this installation.

DOMPurify was upgraded from 3.0.1 to 3.4.17 and its directory changed from
`DOMPurify` to lowercase `dompurify`, which is the path Colorbox declares on
case-sensitive servers. Its built distribution is included in the archive.
See the [upstream release](https://github.com/cure53/DOMPurify/releases/tag/3.4.17).

FitVids 1.2.0 is identical to the previous file after normalizing line endings;
its source header still says “FitVids 1.1”. mmenu and Superfish assets matched the
old files exactly. Colorbox's active minified JavaScript is unchanged; the tagged
source differs in comments and some unused translations from the previous copy.
Original licenses remain in each downloaded archive; mmenu retains the existing
CC-BY-NC-4.0 license.

## Checks

```sh
ddev composer verify:libraries
ddev exec env LIBRARY_CHECK_ORIGIN=https://greghersov11.ddev.site php scripts/verify-libraries.php
ddev drush cache:rebuild
ddev drush php:script scripts/verify-drupal11.php
```

The standalone library check requires Composer-installed modules but no database.
It checks installation paths and discovers required assets from AudioField,
Colorbox, FitVids, and Responsive Menu library declarations. The optional HTTP
check confirms each file is served with its expected content. TLS verification
is relaxed only for local `.ddev.site` hosts.

The initial installation began with all six manual library directories removed,
after backing them up. A second `composer reinstall` of all six locked packages
also passed. All **27 module-declared assets** were present and served intact;
the site integration checks passed, and configuration remained synchronized.
These checks do not operate browser controls. Review mobile menus, lightboxes,
embedded videos, and audio playback in a browser after deployment.

## Updates and deployment

These are deliberately explicit package definitions. To upgrade a library, update
its version, release commit, archive URL, checksum, and root requirement together,
then run `composer update vendor/package --prefer-dist`, repeat the checks above,
and commit both Composer files. The Git source reference must match the archive.
Review the relevant module's supported library version before changing major
versions, particularly mmenu and SoundManager.

`composer audit` does not provide comprehensive JavaScript vulnerability coverage
for these project-defined packages. Monitor the upstream projects and advisories,
especially DOMPurify, alongside Drupal/PHP updates.

Deploy with `composer install --no-dev --prefer-dist --optimize-autoloader` or
ship a release artifact built with that command. Copying Git-tracked files alone
will not install these libraries. For an existing deployment, first move the old
manually copied six library directories (including uppercase `DOMPurify`) outside
the web root, then install. Composer may reject nonempty unmanaged destinations;
keep the originals until acceptance checks pass.

Before this conversion, all libraries and the previous Composer files were backed
up outside the web root to
`/private/tmp/greghersov11-libraries-before-composer-i_k72uw4/`. For rollback,
restore the previous manifest/lockfile and run `composer install` first, allowing
Composer to remove the six managed packages from its installed-package records.
Then restore the saved manual library copies, including the original `DOMPurify`
directory casing, and rebuild Drupal caches. Restoring manual copies before
Composer removes the packages could cause those copies to be deleted again.
This conversion made no database/configuration changes. The backup path is local
temporary storage; copy it to durable, private storage if needed for later recovery.
