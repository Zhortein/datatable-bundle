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

    it('documents both icon providers and the accessibility contract', () => {
        const icons = readFileSync(
            resolve(documentationRoot, 'icons.md'),
            'utf8',
        );
        const configuration = readFileSync(
            resolve(documentationRoot, 'configuration.md'),
            'utf8',
        );

        expect(configuration).toContain('icon_provider: css');
        expect(icons).toContain('icon_provider: ux_icons');
        expect(icons).toContain('composer require symfony/ux-icons');
        expect(icons).toContain('IconRendererInterface');
        expect(icons).toContain('aria-hidden="true"');
        expect(icons).toContain('aria-label');
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
        expect(smokeTest.indexOf('fixtures/zhortein_datatable.yaml'))
            .toBeLessThan(smokeTest.indexOf('composer require'));
        expect(smokeTest.indexOf('fixtures/SmokeUserDatatable.php'))
            .toBeLessThan(smokeTest.indexOf('composer require'));
        expect(smokeTest).toContain('php bin/console cache:clear');
        expect(smokeTest).toContain('debug:router app_smoke');
        expect(smokeTest).toContain('debug:router zhortein_datatable_fragments');
        expect(smokeTest).toContain('debug:router zhortein_datatable_child');
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
        expect(routes).toContain('zhortein_datatable_child');
        expect(routes).toContain('zhortein_datatable_export');
        expect(routes).toContain('debug:router zhortein_datatable_fragments');
        expect(routes).toContain('debug:router zhortein_datatable_child');
        expect(routes).toContain('debug:router zhortein_datatable_export');
        expect(routes).not.toContain('debug:router zhortein_datatable\n');
    });

    it('defines the stable public API and implementation boundary', () => {
        const index = readFileSync(
            resolve(documentationRoot, 'index.md'),
            'utf8',
        );
        const publicApi = readFileSync(
            resolve(documentationRoot, 'public-api.md'),
            'utf8',
        );
        const releaseChecklist = readFileSync(
            resolve(documentationRoot, 'release-checklist.md'),
            'utf8',
        );

        expect(index).toContain('(public-api.md)');
        expect(publicApi).toContain('Contract\\DatatableInterface');
        expect(publicApi).toContain('Contract\\CellValueResolverInterface');
        expect(publicApi).toContain('Contract\\DataProviderInterface');
        expect(publicApi).toContain('Contract\\ExportWriterInterface');
        expect(publicApi).toContain('Contract\\EnumPresentationResolverInterface');
        expect(publicApi).toContain('Definition\\DatatableDefinition');
        expect(publicApi).toContain('Implementation boundary');
        expect(publicApi).toContain('zhortein--datatable-bundle--datatable');
        expect(publicApi).toContain('zhortein_datatable.cell_value_resolver');
        expect(publicApi).toContain('The 1.x surface is stable');
        expect(publicApi).toContain('2.0 prerelease contract');
        expect(publicApi).toContain('Migrating from 1.x to 2.0');
        expect(releaseChecklist).toContain('# Release checklist');
        expect(releaseChecklist).not.toContain('# First pre-release checklist');
    });

    it('documents rich enum presentation across cells, filters and exports', () => {
        const enumPresentation = readFileSync(
            resolve(documentationRoot, 'enum-presentation.md'),
            'utf8',
        );

        expect(enumPresentation).toContain('EnumPresentationResolverInterface');
        expect(enumPresentation).toContain('enumPresentations');
        expect(enumPresentation).toContain('enum_presentation');
        expect(enumPresentation).toContain('CSV and XLSX');
        expect(enumPresentation).toContain('Array provider');
    });

    it('documents rich cells from quick start through public API and examples', () => {
        const quickStart = readFileSync(
            resolve(documentationRoot, 'quick-start.md'),
            'utf8',
        );
        const cellContext = readFileSync(
            resolve(documentationRoot, 'cell-context.md'),
            'utf8',
        );
        const computedExample = readFileSync(
            resolve(documentationRoot, 'examples/computed-cell.md'),
            'utf8',
        );

        expect(quickStart).toContain('row_identifier');
        expect(quickStart).toContain('(cell-context.md)');
        expect(cellContext).toContain('CellValueResolverInterface');
        expect(cellContext).toContain('addComputedColumn');
        expect(cellContext).toContain('never serializes');
        expect(cellContext).toContain('N+1');
        expect(computedExample).toContain('account_summary');
        expect(computedExample).toContain('exportFormats');
    });

    it('documents hierarchical datatables across providers and trust boundaries', () => {
        const hierarchy = readFileSync(
            resolve(documentationRoot, 'hierarchical-datatables.md'),
            'utf8',
        );

        expect(hierarchy).toContain('ChildContextValue');
        expect(hierarchy).toContain('ContextFilterValue');
        expect(hierarchy).toContain('ChildDatatableAuthorizationCheckerInterface');
        expect(hierarchy).toContain('integrity, not encryption');
        expect(hierarchy).toContain('`1` to `3`');
        expect(hierarchy).toContain('at-most-once');
        expect(hierarchy).toContain('Array, Doctrine or a');
        expect(hierarchy).toContain('zhortein_datatable_child');
    });

    it('documents the bounded multi-column sorting contract', () => {
        const sorting = readFileSync(
            resolve(documentationRoot, 'sorting.md'),
            'utf8',
        );

        expect(sorting).toContain('Sorting\\SortCriterion');
        expect(sorting).toContain('getSorts()');
        expect(sorting).toContain('at most eight unique criteria');
        expect(sorting).toContain('sorts[0][field]');
        expect(sorting).toContain('Version 1 URLs and saved views');
        expect(sorting).toContain('aria-sort');
        expect(sorting).toContain('Hold `Shift`');
    });
});
