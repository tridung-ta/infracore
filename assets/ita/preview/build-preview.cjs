/* Development only: expand presentation includes without PHP/DB bootstrap. */
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../../..');
function expand(file) {
 const source = fs.readFileSync(path.join(root, 'templates', file), 'utf8');
 return source.replace(/\{include file="([^"]+)"\}/g, (_, include) => expand(include));
}
const output = expand('admin/ita/layout.tpl.html').replace(/\{\$\w+\|default:'([^']*)'\|escape:'html'\}/g, (_, value) => value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'));
if (/\{include\s/.test(output)) throw new Error('Unresolved include');
fs.writeFileSync(path.join(__dirname, 'index.html'), output);
console.log('Preview generated from Smarty presentation templates.');
