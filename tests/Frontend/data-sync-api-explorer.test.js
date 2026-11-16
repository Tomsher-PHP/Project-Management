import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
    EXPLORER_SECTIONS,
    buildExplorerUrl,
    getExplorerSearchMatches,
    normalizeExplorerSearch,
    resolveExplorerState,
} from '../../resources/js/settings/data-sync/api-explorer-tab.js';

const modules = {
    technologies: {
        name: 'Technologies',
        description: 'Technology master data',
        operations: {
            list: { name: 'List Technologies', method: 'GET', endpoint: '/api/integrations/settings/technologies' },
            create: { name: 'Create Technology', method: 'POST', endpoint: '/api/integrations/settings/technologies' },
        },
    },
    projects: {
        name: 'Projects',
        description: 'Project operations',
        operations: {
            show: { name: 'Get Project', method: 'GET', endpoint: '/api/integrations/projects/{code}' },
            update: { name: 'Update Project', method: 'PUT', endpoint: '/api/integrations/projects/{code}' },
        },
    },
};

const explorerSource = readFileSync(
    new URL('../../resources/js/settings/data-sync/api-explorer-tab.js', import.meta.url),
    'utf8',
);

test('normalizes search text and unnecessary whitespace', () => {
    assert.equal(normalizeExplorerSearch('  GET   Projects  '), 'get projects');
    assert.equal(normalizeExplorerSearch(null), '');
});

test('empty search restores configured module and operation order', () => {
    const matches = getExplorerSearchMatches(modules, '   ');

    assert.deepEqual(Object.keys(matches), ['technologies', 'projects']);
    assert.deepEqual(matches.technologies, ['list', 'create']);
    assert.deepEqual(matches.projects, ['show', 'update']);
});

test('module matches retain all of that module operations', () => {
    assert.deepEqual(getExplorerSearchMatches(modules, 'technology'), {
        technologies: ['list', 'create'],
    });
});

test('operation searches match names, methods, and endpoint paths while retaining groups', () => {
    assert.deepEqual(getExplorerSearchMatches(modules, 'update'), {
        projects: ['update'],
    });
    assert.deepEqual(getExplorerSearchMatches(modules, 'POST'), {
        technologies: ['create'],
    });
    assert.deepEqual(getExplorerSearchMatches(modules, '{code}'), {
        projects: ['show', 'update'],
    });
    assert.deepEqual(getExplorerSearchMatches(modules, 'does-not-exist'), {});
});

test('valid URL state selects the requested module, operation, and section', () => {
    const params = new URLSearchParams('module=projects&operation=update&section=responses&filter=active');

    assert.deepEqual(resolveExplorerState(modules, params), {
        moduleSlug: 'projects',
        operationKey: 'update',
        section: 'responses',
    });
});

test('invalid URL state falls back safely to configured ordering', () => {
    const params = new URLSearchParams('module=missing&operation=delete&section=unknown');

    assert.deepEqual(resolveExplorerState(modules, params), {
        moduleSlug: 'technologies',
        operationKey: 'list',
        section: 'headers',
    });
    assert.deepEqual(EXPLORER_SECTIONS, ['headers', 'params', 'payload', 'responses', 'curl']);
});

test('an invalid operation falls back within a valid requested module', () => {
    const params = new URLSearchParams('module=projects&operation=missing&section=payload');

    assert.deepEqual(resolveExplorerState(modules, params), {
        moduleSlug: 'projects',
        operationKey: 'show',
        section: 'payload',
    });
});

test('building a deep link preserves its route and unrelated query parameters', () => {
    const url = buildExplorerUrl(
        'https://pms.example/settings/data-sync/explorer?filter=active&page=2',
        { moduleSlug: 'projects', operationKey: 'update', section: 'responses' },
    );

    assert.equal(url.pathname, '/settings/data-sync/explorer');
    assert.equal(url.searchParams.get('filter'), 'active');
    assert.equal(url.searchParams.get('page'), '2');
    assert.equal(url.searchParams.get('module'), 'projects');
    assert.equal(url.searchParams.get('operation'), 'update');
    assert.equal(url.searchParams.get('section'), 'responses');
});

test('dynamic Explorer content uses safe DOM APIs instead of HTML injection', () => {
    assert.doesNotMatch(explorerSource, /\.innerHTML\s*=/);
    assert.doesNotMatch(explorerSource, /insertAdjacentHTML\s*\(/);
    assert.match(explorerSource, /\.textContent\s*=/);
    assert.match(explorerSource, /document\.createElement\s*\(/);
});
