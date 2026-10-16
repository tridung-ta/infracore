/* Development-only server. Serves approved static directories, never PHP/config. */
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../../..');
const mime = {'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.json':'application/json; charset=utf-8','.woff2':'font/woff2'};
http.createServer((req, res) => {
 let name;
 try { name = decodeURIComponent(new URL(req.url, 'http://localhost').pathname); }
 catch { res.writeHead(400).end(); return; }
 if (name === '/') name = '/assets/ita/preview/index.html';
 const allowed = name === '/assets/ita/preview/index.html' || /^\/assets\/ita\/(css|js|mock|vendor)\//.test(name) || name === '/assets/fonts/inter/InterVariable.woff2';
 const file = path.resolve(root, '.' + name);
 if (!allowed || !file.startsWith(root + path.sep) || !mime[path.extname(file)] || !['GET','HEAD'].includes(req.method)) { res.writeHead(404).end(); return; }
 fs.readFile(file, (error, data) => {
  if (error) { res.writeHead(404).end(); return; }
  res.writeHead(200, {'Content-Type':mime[path.extname(file)],'Cache-Control':'no-store','X-Content-Type-Options':'nosniff'});
  res.end(req.method === 'HEAD' ? undefined : data);
 });
}).listen(18766, '127.0.0.1', () => console.log('InfraCore MOCK preview: http://127.0.0.1:18766'));
