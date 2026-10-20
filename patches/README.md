# Drupal 11 compatibility patches

These are project-maintained patches against the exact stable releases pinned in
`composer.json`. Composer Patches 2 applies them during installation, and
`patches.lock.json` records their SHA-256 hashes. Commit both lockfiles and this
directory. Do not edit installed files under `modules/contrib`.

| Package | Pinned release | Local patch | Changes |
| --- | --- | --- | --- |
| AudioField | 1.14.0 | [audiofield-drupal11.patch](audiofield-drupal11.patch) | Declare Drupal 11 compatibility; replace removed file-extension validation with the `file.validator` service; replace removed remote-file download helper with Guzzle; use argument arrays for Symfony processes and the FileExists enum. |
| Menus Attribute | 1.3.0 | [menus_attribute-drupal11.patch](menus_attribute-drupal11.patch) | Declare Drupal 11 compatibility and remove obsolete Drupal 8 metadata. No removed runtime API was found by the upgrade scan. |
| Simple Comment Notify | 2.0.3 | [scn-drupal11.patch](scn-drupal11.patch) | Declare Drupal 11 compatibility; load role labels through Role entities, preserving exclusion of the anonymous role. |

`mglaman/composer-drupal-lenient` is restricted to these three packages. It lets
Composer resolve their outdated **core requirement metadata**; the patches provide
the runtime changes. It does not make arbitrary modules compatible with Drupal 11.

The AudioField upstream issue is
<https://www.drupal.org/project/audiofield/issues/3494200>. Its attachment #33 is
an incremental patch against different source and did not apply to 1.14.0. The
local patch was written against the installed stable release instead. These
patches do not claim to be upstream-supported releases.

Before updating a pinned package, check upstream releases and issues, remove the
patch if its fixes have been released, and remove that package from the lenient
allow-list. Otherwise regenerate the patch against the new source and run:

```sh
ddev composer patches-relock
ddev composer patches-repatch
ddev composer validate --no-check-publish
ddev drush cache:rebuild
ddev drush php:script scripts/verify-drupal11.php
```

Retest MP3 playback in a browser. The integration script checks server rendering
and editor form construction; it does not play audio or submit notifications.
