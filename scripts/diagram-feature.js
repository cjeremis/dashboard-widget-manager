#!/usr/bin/env node
/**
 * Generate dependency diagram for a specific feature module.
 * Traces JS imports, SCSS partials, and PHP templates related to the feature.
 *
 * Usage: node scripts/diagram-feature.js <feature-name> [entry-file]
 *
 * Examples:
 *   node scripts/diagram-feature.js features-modal
 *   node scripts/diagram-feature.js features-modal assets/js/components/admin.js
 */

const madge = require('madge');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');

const pluginName = 'dashboard-widget-manager';
const root = path.resolve(__dirname, '..');

const featureName = process.argv[2] || 'features-modal';
const entryArg = process.argv[3] || 'assets/js/components/admin.js';

const entryFile = path.resolve(root, entryArg);
const docsDir = path.resolve(root, '../../../docs/diagrams', pluginName);
const outputFile = path.join(docsDir, `${featureName}.md`);

fs.mkdirSync(docsDir, { recursive: true });

function grepRelated(keyword, ext, baseDir) {
  try {
    const result = execSync(
      `grep -r "${keyword}" "${baseDir}" --include="*.${ext}" -l 2>/dev/null`,
      { encoding: 'utf8' }
    ).trim();
    return result ? result.split('\n').map(f => f.replace(root + '/', '')) : [];
  } catch {
    return [];
  }
}

async function run() {
  console.log(`\nAnalyzing JS entry: ${entryArg}`);
  console.log(`Feature keyword:    ${featureName}\n`);

  // --- JS dependency graph ---
  const result = await madge(entryFile, {
    baseDir: root,
    fileExtensions: ['js'],
  });

  const allDeps = result.obj();

  // Filter to only files related to the feature
  const featureKey = featureName.replace(/-/g, '[_-]');
  const featureRegex = new RegExp(featureKey, 'i');
  const relatedJs = Object.entries(allDeps).reduce((acc, [file, deps]) => {
    if (featureRegex.test(file)) {
      acc[file] = deps;
    }
    return acc;
  }, {});

  // Also find which entry points import the feature file
  const importedBy = Object.entries(allDeps)
    .filter(([, deps]) => deps.some(d => featureRegex.test(d)))
    .map(([file]) => file);

  // --- Related SCSS ---
  const scssFiles = grepRelated(featureName, 'scss', path.join(root, 'assets/scss'));

  // --- Related PHP templates ---
  const phpFiles = grepRelated(featureName, 'php', path.join(root, 'templates'));

  // --- Related PHP includes ---
  const phpIncludes = grepRelated(featureName, 'php', path.join(root, 'includes'));

  // --- Build markdown output ---
  const jsDepsBlock = Object.entries(relatedJs)
    .map(([file, deps]) => `  ${file}\n${deps.map(d => `    -> ${d}`).join('\n') || '    (no imports)'}`)
    .join('\n') || '  none';

  const circular = result.circular();
  const circularBlock = circular.length
    ? '\n## Circular Dependencies\n\n```\n' + circular.map(c => c.join(' -> ')).join('\n') + '\n```\n'
    : '';

  const md = `# ${featureName} — Dependency Diagram
_Plugin: ${pluginName} | Generated: ${new Date().toISOString()}_

## JS

\`\`\`
Entry: ${entryArg}

Imported by:
${importedBy.map(f => `  ${f}`).join('\n') || '  none'}

Feature files:
${jsDepsBlock}
\`\`\`

## SCSS

\`\`\`
${scssFiles.join('\n') || 'none'}
\`\`\`

## PHP Templates

\`\`\`
${phpFiles.join('\n') || 'none'}
\`\`\`

## PHP Includes

\`\`\`
${phpIncludes.join('\n') || 'none'}
\`\`\`
${circularBlock}`;

  fs.writeFileSync(outputFile, md);
  console.log(`\nDiagram: ${outputFile}`);

  // --- Summary ---
  console.log('\n--- Summary ---');
  console.log(`Imported by:   ${importedBy.join(', ') || 'none'}`);
  console.log(`JS files:      ${Object.keys(relatedJs).join(', ') || 'none'}`);
  console.log(`SCSS files:    ${scssFiles.join(', ') || 'none'}`);
  console.log(`PHP templates: ${phpFiles.join(', ') || 'none'}`);
  console.log(`PHP includes:  ${phpIncludes.join(', ') || 'none'}`);
  if (circular.length) {
    console.warn('\nCircular dependencies:', circular);
  }
}

run().catch(err => {
  console.error(err.message);
  process.exit(1);
});
