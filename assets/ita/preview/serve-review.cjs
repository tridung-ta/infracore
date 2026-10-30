/* Development-only manual review. Binds loopback, never executes PHP or connects to a database. */
const http=require('node:http'),fs=require('node:fs'),path=require('node:path'),{execFileSync,spawn}=require('node:child_process');
const root=path.resolve(__dirname,'../../..'),model=require('./review-model.cjs'),port=18767;
execFileSync(process.execPath,[path.join(__dirname,'build-preview.cjs')],{stdio:'inherit'});
const types={'.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.html':'text/html; charset=utf-8','.json':'application/json; charset=utf-8','.woff2':'font/woff2'};
function json(res,data,status=200,message=''){res.writeHead(status,{'Content-Type':'application/json; charset=utf-8'});res.end(JSON.stringify({success:status<400,data,message}));}
function safeFile(name){const file=path.resolve(root,'.'+name);if(!file.startsWith(root+path.sep))return null;let current=root;for(const part of path.relative(root,file).split(path.sep)){current=path.join(current,part);try{if(fs.lstatSync(current).isSymbolicLink())return null;}catch{return null;}}return file;}
function page(){return fs.readFileSync(path.join(__dirname,'index.html'),'utf8').replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="local-review-only"').replace('</head>','<link rel="stylesheet" href="/review/review.css"><script src="/review/review.js" defer></script></head>');}
function bytes(req){return new Promise((resolve,reject)=>{let length=0;const parts=[];req.on('data',part=>{length+=part.length;if(length>6*1024*1024){reject(Object.assign(new Error('LOCAL MOCK: body too large'),{status:413}));req.destroy();}else parts.push(part);});req.on('end',()=>resolve(Buffer.concat(parts)));req.on('error',reject);});}
const server=http.createServer(async(req,res)=>{
 res.setHeader('Cache-Control','no-store');res.setHeader('X-Content-Type-Options','nosniff');res.setHeader('X-InfraCore-Review','LOCAL-MOCK-NO-DB');
 res.setHeader('Content-Security-Policy',"default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
 try{
  if(!['127.0.0.1:'+port,'localhost:'+port].includes(req.headers.host)){res.writeHead(403).end();return;}
  const url=new URL(req.url,'http://127.0.0.1:'+port),name=decodeURIComponent(url.pathname);
  if(req.method==='POST'&&req.headers.origin&&req.headers.origin!=='http://127.0.0.1:'+port&&req.headers.origin!=='http://localhost:'+port){json(res,null,403,'LOCAL MOCK: origin denied');return;}
  if(name==='/review/status'&&req.method==='GET'){json(res,model.status());return;}
  if(name==='/review/control'&&req.method==='POST'){if(req.headers['x-local-review']!=='mock-only'){json(res,null,403,'Missing local review marker');return;}json(res,model.control(JSON.parse((await bytes(req)).toString('utf8'))));return;}
  if(name==='/admin.php'){
   const q=Object.fromEntries(url.searchParams),action=q.act||'';if(!action){if(!['GET','HEAD'].includes(req.method)){res.writeHead(405).end();return;}res.writeHead(200,{'Content-Type':'text/html; charset=utf-8'});res.end(req.method==='HEAD'?undefined:page());return;}
   let result;
   if(req.method==='GET')result=model.read(action,q);
   else if(req.method==='POST'){
    if(req.headers['x-csrf-token']!=='local-review-only'){json(res,null,403,'LOCAL MOCK: CSRF fixture mismatch');return;}
    const body=await bytes(req),input=(req.headers['content-type']||'').startsWith('application/json')?JSON.parse(body.toString('utf8')):{};result=model.write(action,input);
   }else{res.writeHead(405).end();return;}
   if(result?.download){res.writeHead(200,{'Content-Type':result.type,'Content-Disposition':'attachment; filename="'+result.name+'"'});res.end(result.body);}else json(res,result);return;
  }
  if(!['GET','HEAD'].includes(req.method)){res.writeHead(405).end();return;}
  if(name==='/'){res.writeHead(200,{'Content-Type':'text/html; charset=utf-8'});res.end(req.method==='HEAD'?undefined:page());return;}
  if(name==='/review/review.js'||name==='/review/review.css'){const file=path.join(__dirname,name.endsWith('.js')?'review-client.js':'review.css');res.writeHead(200,{'Content-Type':types[path.extname(name)]});res.end(req.method==='HEAD'?undefined:fs.readFileSync(file));return;}
  const allowed=/^\/assets\/ita\/(css|js|mock|vendor)\//.test(name)||name==='/assets/fonts/inter/InterVariable.woff2';
  const file=allowed?safeFile(name):null;if(!file||!types[path.extname(file)]){res.writeHead(404).end();return;}
  res.writeHead(200,{'Content-Type':types[path.extname(file)]});res.end(req.method==='HEAD'?undefined:fs.readFileSync(file));
 }catch(error){if(!res.headersSent&&!res.destroyed)json(res,null,error.status||400,error.status?error.message:'LOCAL MOCK: request không được mô phỏng hoặc không hợp lệ.');else if(!res.destroyed)res.end();}
});
server.on('error',error=>{console.error(error.code==='EADDRINUSE'?'Cổng18767 đang dùng. Mở http://127.0.0.1:18767/ nếu đúng bản LOCAL MOCK; không tắt process lạ.':'Local review server stopped: '+error.code);process.exitCode=1;});
server.listen(port,'127.0.0.1',()=>{console.log('LOCAL MOCK - NO PHP/DB/HOST: http://127.0.0.1:'+port+'/');if(process.argv.includes('--open')&&process.platform==='win32')spawn('cmd.exe',['/c','start','','http://127.0.0.1:'+port+'/'],{windowsHide:true,stdio:'ignore'});});
