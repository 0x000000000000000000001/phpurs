// Check documentation links, anchors and the structural targets that keep the
// docs navigable. Broken links fail; pending splits and language drift warn.
import fs from 'node:fs';
import fsp from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const strict = process.argv.includes('--strict');

const errors = [];
const warnings = [];
const report = (list, file, line, rule, message) => list.push({ file, line, rule, message });

async function walk(directory) {
  const entries = await fsp.readdir(directory, { withFileTypes: true });
  const files = [];
  for (const entry of entries.sort((a, b) => a.name.localeCompare(b.name))) {
    const full = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...(await walk(full)));
    else if (entry.isFile() && entry.name.endsWith('.md')) files.push(full);
  }
  return files;
}

function slug(heading) {
  return heading
    .trim()
    .toLowerCase()
    .replace(/<[^>]*>/g, '')
    .replace(/[`*_~]/g, '')
    .replace(/[^\p{L}\p{N}\s-]/gu, '')
    .replace(/\s+/g, '-');
}

function headingsOf(source) {
  return new Set([...source.matchAll(/^#{1,6}\s+(.+)$/gm)].map((match) => slug(match[1])));
}

function linksOf(source) {
  const links = [];
  for (const match of source.matchAll(/!?\[[^\]]*\]\(([^)\s]+)(?:\s+"[^"]*")?\)/g)) {
    links.push({ target: match[1], index: match.index });
  }
  for (const match of source.matchAll(/^\s*\[[^\]]+\]:\s*(\S+)/gm)) {
    links.push({ target: match[1], index: match.index });
  }
  return links;
}

const languageHints = {
  fr: /\b(le|la|les|des|une|est|pour|avec|dans|sur|par|pas|plus|sans|chaque|être|doit|peut)\b/gi,
  en: /\b(the|and|with|this|that|from|are|is|not|into|each|must|when|which)\b/gi,
};

const files = [...(await walk(path.join(root, 'docs'))), path.join(root, 'README.md')];
for (const file of files) {
  const rel = path.relative(root, file).split(path.sep).join('/');
  const source = await fsp.readFile(file, 'utf8');
  const directory = path.dirname(file);

  for (const { target, index } of linksOf(source)) {
    const line = source.slice(0, index).split('\n').length;
    if (/^(https?:|mailto:|tel:)/.test(target)) continue;
    const [rawPath, anchor] = target.split('#');
    const targetPath = rawPath ? path.resolve(directory, decodeURIComponent(rawPath)) : file;
    if (!fsExists(targetPath)) {
      report(errors, rel, line, 'link', `missing target ${target}`);
      continue;
    }
    if (!anchor || /^L\d+(-L\d+)?$/.test(anchor)) continue;
    let targetSource = source;
    if (targetPath !== file) targetSource = await fsp.readFile(targetPath, 'utf8');
    if (!headingsOf(targetSource).has(anchor.toLowerCase())) {
      report(errors, rel, line, 'anchor', `missing heading #${anchor} in ${rawPath || rel}`);
    }
  }

  // Ignore incidental quotes: only flag a document that genuinely mixes both
  // languages (enough hits on each side and a comparable share).
  const fr = (source.match(languageHints.fr) ?? []).length;
  const en = (source.match(languageHints.en) ?? []).length;
  const smaller = Math.min(fr, en);
  const larger = Math.max(fr, en);
  if (smaller >= 20 && smaller / larger >= 0.2)
    report(warnings, rel, 1, 'language', `mixed French/English hints (${fr} fr, ${en} en)`);
}

for (const file of ['docs/index.md']) {
  if (!fsExists(path.join(root, file)))
    report(warnings, file, 1, 'index', 'missing docs index (created by M5-02)');
}

const compilerDoc = path.join(root, 'docs', 'compiler.md');
if (fsExists(compilerDoc)) {
  const lines = (await fsp.readFile(compilerDoc, 'utf8')).split('\n').length;
  if (lines > 250)
    report(
      warnings,
      'docs/compiler.md',
      1,
      'compiler-size',
      `${lines} lines; split into docs/compiler/ (M5-02)`,
    );
}

for (const list of [errors, warnings])
  for (const entry of list)
    (list === errors ? console.error : console.warn)(
      `${entry.file}:${entry.line}: ${entry.rule}: ${entry.message}`,
    );

if (strict && warnings.length > 0) errors.push(...warnings);
console.log(`${files.length} docs checked: ${errors.length} errors, ${warnings.length} warnings.`);
if (errors.length > 0) process.exitCode = 1;

function fsExists(target) {
  try {
    fs.statSync(target);
    return true;
  } catch {
    return false;
  }
}
