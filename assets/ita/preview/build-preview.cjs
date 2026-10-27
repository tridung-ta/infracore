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
const login = expand('admin/ita/login.tpl.html')
 .replace(/\{if \$itaError\}[\s\S]*?\{\/if\}/g, '')
 .replace(/\{\$itaCsrf\|escape:'html'\}/g, 'mock-csrf-no-session')
 .replace('<section class="login-card"', '<p class="mock-notice">MOCK · Giao diện đăng nhập, không xác thực hoặc gửi dữ liệu.</p><section class="login-card"')
 .replace('</body>', '<script>document.body.dataset.mode="mock";document.getElementById("login-form").addEventListener("submit",event=>{event.preventDefault();event.stopImmediatePropagation();let note=document.getElementById("mock-login-result");if(!note){note=document.createElement("p");note.id="mock-login-result";note.className="admin-message";note.setAttribute("role","status");document.getElementById("login-form").after(note);}note.textContent="MOCK · Chỉ xem giao diện, không gửi tài khoản hoặc xác thực.";},true);</script></body>');
fs.writeFileSync(path.join(root, 'assets/ita/mock/login-preview.html'), login);
console.log('Preview generated from Smarty presentation templates.');
