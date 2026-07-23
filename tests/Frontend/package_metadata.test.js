import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import { describe, expect, it } from 'vitest';

const packageMetadata = JSON.parse(
    readFileSync(resolve(process.cwd(), 'assets/package.json'), 'utf8'),
);

describe('Stimulus package metadata', () => {
    it('loads the datatable controller lazily by default', () => {
        expect(packageMetadata.symfony.controllers.datatable).toMatchObject({
            enabled: true,
            fetch: 'lazy',
            main: 'controllers/datatable_controller.js',
        });
    });
});
