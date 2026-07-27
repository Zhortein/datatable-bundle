import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import { describe, expect, it } from 'vitest';

const packageMetadata = JSON.parse(
    readFileSync(resolve(process.cwd(), 'assets/package.json'), 'utf8'),
);
const changelog = readFileSync(
    resolve(process.cwd(), 'CHANGELOG.md'),
    'utf8',
);
const readme = readFileSync(
    resolve(process.cwd(), 'README.md'),
    'utf8',
);

describe('Stimulus package metadata', () => {
    it('loads the datatable controller lazily by default', () => {
        expect(packageMetadata.symfony.controllers.datatable).toMatchObject({
            enabled: true,
            fetch: 'lazy',
            main: 'controllers/datatable_controller.js',
        });
    });

    it('declares its Stimulus and Bootstrap module dependencies', () => {
        expect(packageMetadata.peerDependencies).toMatchObject({
            '@hotwired/stimulus': '^3.0',
            bootstrap: '^5.3',
        });
        expect(packageMetadata.importmap).toMatchObject({
            '@hotwired/stimulus': '^3.0',
            bootstrap: '^5.3',
        });
    });

    it('matches the documented stable release', () => {
        expect(packageMetadata.version).toMatch(/^\d+\.\d+\.\d+$/);
        expect(changelog).toContain(`## [${packageMetadata.version}]`);
        expect(readme).toContain('**Stable 1.x**');
        expect(readme).not.toContain('**Alpha Stage**');
    });
});
