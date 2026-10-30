async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const c=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await c.newPage();
 try{
  await p.goto('http://127.0.0.1:18766/#network');await p.waitForFunction(()=>window.ItaMotion);
  for(const theme of ['command','focus']){
   await p.evaluate(theme=>ItaMotion.setEnabled(theme==='command'),theme);
   await p.evaluate(()=>Promise.all(document.getAnimations().map(a=>a.finished.catch(()=>{}))));
   const result=await p.evaluate(()=>{
    const luminance=rgb=>rgb.map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4;}).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);
    const rgb=s=>s.match(/[\d.]+/g).slice(0,3).map(Number);
    const contrast=n=>{let background=n,cs=getComputedStyle(n);while(background&&getComputedStyle(background).backgroundColor==='rgba(0, 0, 0, 0)')background=background.parentElement;const a=luminance(rgb(cs.color)),b=luminance(rgb(getComputedStyle(background||document.body).backgroundColor));return (Math.max(a,b)+.05)/(Math.min(a,b)+.05);};
    const input=document.getElementById('network-search'),select=document.getElementById('network-department');
    const alert=document.createElement('p');alert.className='admin-message';alert.setAttribute('role','alert');alert.textContent='QA error';document.querySelector('main').append(alert);
    window.__qaContrast=contrast;const values={body:contrast(document.body),input:contrast(input),select:contrast(select),error:contrast(alert),arrow:getComputedStyle(select).backgroundImage,background:getComputedStyle(document.body).backgroundColor};alert.remove();return values;
   });
   for(const key of ['body','input','select','error'])check(result[key]>=4.5,theme+' '+key+' contrast >=4.5');check(result.arrow!=='none',theme+' native select arrow retained');
   await p.evaluate(()=>{void ItaUi.confirm({title:'MOCK dialog',message:'No write',confirmLabel:'Confirm'});});await p.locator('#confirm-dialog').waitFor({state:'visible'});check(await p.locator('#confirm-dialog').evaluate(n=>window.__qaContrast(n))>=4.5,theme+' dialog contrast >=4.5');await p.locator('#confirm-cancel').click();
  }
 }finally{await c.close();}
 return {checks:checks.length,results:checks,scope:'Computed styles only, local MOCK, no DB'};
}
