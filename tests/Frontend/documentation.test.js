import {
    existsSync,
    readFileSync,
    readdirSync,
} from 'node:fs';
import {
    dirname,
    relative,
    resolve,
} from 'node:path';

import { describe, expect, it } from 'vitest';

const projectRoot = process.cwd();
const documentationRoot = resolve(projectRoot, 'docs');

const collectMarkdownFiles = (directory) => readdirSync(directory, { withFileTypes: true })
    .flatMap((entry) => {
        if (entry.name === 'archive') {
            return [];
        }

        const path = resolve(directory, entry.name);

        if (entry.isDirectory()) {
            return collectMarkdownFiles(path);
        }

        return path.endsWith('.md') ? [path] : [];
    });

const documentationFiles = [
    resolve(projectRoot, 'README.md'),
    ...collectMarkdownFiles(documentationRoot),
];

const resolveLinkTarget = (rawTarget) => {
    const trimmedTarget = rawTarget.trim();

    if (trimmedTarget.startsWith('<')) {
        return trimmedTarget.slice(1, trimmedTarget.indexOf('>'));
    }

    return trimmedTarget.split(/\s+/)[0];
};

describe('Documentation', () => {
    it('contains no broken local links in active documentation', () => {
        const brokenLinks = [];

        for (const file of documentationFiles) {
            const contents = readFileSync(file, 'utf8');

            for (const match of contents.matchAll(/!?\[[^\]]*]\(([^)]+)\)/g)) {
                const target = resolveLinkTarget(match[1]);

                if (
                    target === ''
                    || target.startsWith('#')
                    || /^(?:https?:|mailto:)/.test(target)
                ) {
                    continue;
                }

                const localTarget = decodeURIComponent(target.split('#')[0]);
                const resolvedTarget = resolve(dirname(file), localTarget);

                if (!existsSync(resolvedTarget)) {
                    brokenLinks.push(
                        `${relative(projectRoot, file)} -> ${localTarget}`,
                    );
                }
            }
        }

        expect(brokenLinks).toEqual([]);
    });

    it('documents the critical AssetMapper integration contract', () => {
        const installation = readFileSync(
            resolve(documentationRoot, 'installation.md'),
            'utf8',
        );

        expect(installation).toContain('@zhortein/datatable-bundle');
        expect(installation).toContain('"fetch": "lazy"');
        expect(installation).toContain("import './stimulus_bootstrap.js';");
        expect(installation).toContain('bootstrap-icons/font/bootstrap-icons.min.css');
        expect(installation).toContain('asset-map:compile');
        expect(installation).not.toContain('php bin/console asset-mapper:compile');
    });

    it('runs the documented integration in a fresh Symfony application', () => {
        const ciWorkflow = readFileSync(
            resolve(projectRoot, '.github/workflows/ci.yaml'),
            'utf8',
        );
        const smokeTest = readFileSync(
            resolve(projectRoot, 'tools/smoke-test/fresh-symfony-app.sh'),
            'utf8',
        );

        expect(ciWorkflow).toContain('tools/smoke-test/fresh-symfony-app.sh');
        expect(smokeTest).toContain('composer create-project');
        expect(smokeTest).toContain('php bin/console cache:clear');
        expect(smokeTest).toContain('debug:router app_smoke');
        expect(smokeTest).toContain('debug:router zhortein_datatable_fragments');
        expect(smokeTest).toContain('debug:router zhortein_datatable_export');
        expect(smokeTest).toContain("debug:container 'App\\Datatable\\SmokeUserDatatable'");
        expect(smokeTest).toContain("debug:asset-map '@zhortein/datatable-bundle'");
        expect(smokeTest).toContain('asset-map:compile');
        expect(smokeTest).toContain('php -S 127.0.0.1:8000');
        expect(smokeTest).toContain('curl');
        expect(smokeTest).toContain('php smoke.php');
    });

    it('documents every bundle route', () => {
        const routes = readFileSync(
            resolve(documentationRoot, 'routes.md'),
            'utf8',
        );

        expect(routes).toContain('zhortein_datatable_fragments');
        expect(routes).toContain('zhortein_datatable_export');
        expect(routes).toContain('debug:router zhortein_datatable_fragments');
        expect(routes).toContain('debug:router zhortein_datatable_export');
        expect(routes).not.toContain('debug:router zhortein_datatable\n');
    });
});
