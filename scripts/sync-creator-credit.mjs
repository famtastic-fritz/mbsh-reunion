import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';

const root = path.resolve(import.meta.dirname, '..');
const check = process.argv.includes('--check');
const fragmentPath = 'frontend/templates/creator-credit.html';
const fragment = fs.readFileSync(path.join(root, fragmentPath), 'utf8').trim();
const marker = /\s*<!-- famtastic-creator-credit:start -->[\s\S]*?<!-- famtastic-creator-credit:end -->\s*/g;
function walk(relative) {
  return fs.readdirSync(path.join(root, relative), {withFileTypes:true}).flatMap(entry => {
    const next = path.join(relative, entry.name);
    if (entry.isSymbolicLink() || ['node_modules', 'vendor', 'uploads'].includes(entry.name)) return [];
    return entry.isDirectory() ? walk(next) : [next];
  });
}
const files = ['frontend', 'backend', 'wordpress'].flatMap(walk);
const changed = [];
let covered = 0;
for (const file of files) {
  if (file === fragmentPath || !/\.(html|php)$/.test(file)) continue;
  const original = fs.readFileSync(path.join(root, file), 'utf8');
  // Only complete HTML documents. Never touch JSON/API, email producers,
  // archived evidence or content fragments. PHP access checks remain unchanged.
  if (!/<html\b/i.test(original) || !/<\/body>/i.test(original)) continue;
  covered++;
  const clean = original.replace(marker, '\n');
  const result = clean.replace(/\s*<\/body>/i, '\n' + fragment + '\n</body>');
  if (result !== original) {
    changed.push(file);
    if (!check) fs.writeFileSync(path.join(root, file), result);
  }
}
const logo = fs.readFileSync(path.join(root, 'frontend/assets/famtastic/famtastic-designs-logo-v1.png'));
const hash = createHash('sha256').update(logo).digest('hex');
const approved = fs.readFileSync(path.join(root, 'config/creator-credit-logo.sha256'), 'utf8').trim();
if (hash !== approved) throw new Error('Creator logo differs from the approved source artwork');
if (check && changed.length) throw new Error('Creator-credit drift: ' + changed.join(', '));
console.log(JSON.stringify({covered, changed, logoSha256: hash, mode:check?'check':'sync'}, null, 2));
