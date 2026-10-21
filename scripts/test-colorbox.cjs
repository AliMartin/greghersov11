/**
 * Exercise the installed Colorbox plugin and Drupal behavior with real gallery HTML.
 * Requires jsdom (test-only dependency; see libraries/README.md).
 * Run inside DDEV with NODE_PATH pointing to the temporary jsdom installation.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const https = require('node:https');
const http = require('node:http');
const { JSDOM } = require('jsdom');

async function main() {
  const root = path.resolve(__dirname, '..');
  // Use the canonical site host: requesting localhost can cache absolute image
  // URLs for the wrong host in the gallery's rendered output.
  const url = new URL(process.env.GALLERY_TEST_URL || 'https://greghersov11.ddev.site/gallery');
  const html = await new Promise((resolve, reject) => {
    const client = url.protocol === 'https:' ? https : http;
    const options = { rejectUnauthorized: !url.hostname.endsWith('.ddev.site') };
    client.get(url, options, response => {
      if (response.statusCode !== 200) {
        response.resume();
        reject(new Error(`Gallery returned HTTP ${response.statusCode}`));
        return;
      }
      response.setEncoding('utf8');
      let body = '';
      response.on('data', chunk => { body += chunk; });
      response.on('end', () => resolve(body));
      response.on('error', reject);
    }).on('error', reject);
  });
  const dom = new JSDOM(html, {
    url: url.href,
    runScripts: 'outside-only',
    pretendToBeVisual: true,
  });
  const { window } = dom;
  try {
    window.drupalSettings = JSON.parse(window.document.querySelector('[data-drupal-selector="drupal-settings-json"]').textContent);
    const plugin = process.env.COLORBOX_TEST_PLUGIN || 'libraries/colorbox/jquery.colorbox-min.js';
    for (const file of [
      'core/assets/vendor/jquery/jquery.min.js',
      'core/assets/vendor/once/once.min.js',
      'core/misc/drupal.js',
      'libraries/dompurify/dist/purify.min.js',
      plugin,
      'modules/contrib/colorbox/js/colorbox.js',
      'modules/contrib/colorbox/styles/default/colorbox_style.js',
    ]) {
      window.eval(fs.readFileSync(path.resolve(root, file), 'utf8'));
    }
    const $ = window.jQuery;
    $.fx.off = true;
    assert.match($.fn.jquery, /^4\./, 'Exercise the Drupal 11 jQuery 4 runtime');
    window.Drupal.behaviors.initColorbox.attach(window.document, window.drupalSettings);
    window.Drupal.behaviors.initColorboxDefaultStyle.attach(window.document, window.drupalSettings);
    const links = [...window.document.querySelectorAll('a.colorbox')];
    assert.ok(links.length > 1, 'Use the actual gallery image links');
    assert.ok(links.every(link => new URL(link.href).origin === url.origin), 'Gallery images must use the canonical site origin');
    assert.ok(links.every(link => link.classList.contains('cboxElement')), 'Every gallery image must initialize');
    assert.equal(window.Drupal.colorbox.sanitizeMarkup('<strong>Caption</strong><script>alert(1)</script>'), '<strong>Caption</strong>');

    // Opening the image must prevent normal link navigation, even before it loads.
    const click = new window.MouseEvent('click', { bubbles: true, cancelable: true });
    links[0].dispatchEvent(click);
    assert.equal(click.defaultPrevented, true, 'Thumbnail click must be handled by Colorbox');
    assert.notEqual($('#colorbox').css('display'), 'none', 'The lightbox must open');
    $.colorbox.close();
    assert.equal($('#colorbox').css('display'), 'none', 'The lightbox must close');
    console.log(`PASS: jQuery ${$.fn.jquery}, ${links.length} gallery images initialized, caption sanitized, lightbox opens and closes.`);
  }
  finally {
    window.close();
  }
}

main().catch(error => {
  console.error(error);
  process.exitCode = 1;
});
