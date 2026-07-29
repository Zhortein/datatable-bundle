import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import { describe, expect, it } from 'vitest';

const readWorkflow = (name) => readFileSync(
    resolve(process.cwd(), '.github', 'workflows', name),
    'utf8',
);

describe('Release workflow', () => {
    it('validates release candidates before promotion to main', () => {
        const ciWorkflow = readWorkflow('ci.yaml');

        expect(ciWorkflow).toContain("github.base_ref == 'main'");
        expect(ciWorkflow).toContain('Verify changelog fragments were consumed');
        expect(ciWorkflow).toContain('extract-release-notes.php');
    });

    it('protects tagged releases with integrity and quality gates', () => {
        const releaseWorkflow = readWorkflow('release.yaml');

        expect(releaseWorkflow).toContain('git merge-base --is-ancestor');
        expect(releaseWorkflow).toContain('assets/package.json');
        expect(releaseWorkflow).toContain('Verify changelog fragments were consumed');
        expect(releaseWorkflow).toContain('needs: quality');
        expect(releaseWorkflow).toContain('Run PHPUnit');
        expect(releaseWorkflow).toContain('Run frontend tests');
        expect(releaseWorkflow).toContain('tools/smoke-test/external-theme/templates');
        expect(releaseWorkflow).toContain('--prerelease');
        expect(releaseWorkflow).toContain('--verify-tag');
    });
});
