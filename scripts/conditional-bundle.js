/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * Conditionally runs the bundle step during the `prepare` lifecycle hook.
 *
 * When installing globally (`npm install -g`), npm does not support workspace
 * commands, so the bundle step is skipped. In that case the pre-built
 * `bundle/gemini.js` (produced during local development or included in the
 * published tarball) is used as-is.
 *
 * In all other contexts (local dev install, `npm publish`) the full bundle
 * is (re)built as normal.
 */

import { execSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');

const isGlobalInstall = process.env.npm_config_global === 'true';
const bundleExists = existsSync(join(root, 'bundle', 'gemini.js'));

if (isGlobalInstall) {
  if (bundleExists) {
    console.log(
      'Global install detected: using pre-built bundle (skipping bundle step).',
    );
  } else {
    console.error(
      'Error: Global install detected but bundle/gemini.js is missing.\n' +
        'Please run `npm run bundle` in the repository root before installing globally.',
    );
    process.exit(1);
  }
} else {
  execSync('npm run bundle', { stdio: 'inherit', cwd: root });
}
