async(page)=>{
 const context=await page.context().browser().newContext(),p=await context.newPage();let checks=0;const ck=(ok,label)=>{if(!ok)throw Error(label);checks++;};
 try{
  await p.goto('http://127.0.0.1:18767/#users');await p.getByRole('button',{name:'Phân quyền MOCK-C',exact:true}).click();await p.locator('#permission-live-editor .permission-modules').waitFor();
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:1000});
   const result=await p.locator('#permission-live-editor .permission-modules').evaluate(grid=>{
    const groups=[...grid.querySelectorAll('details')],selected=()=>[...grid.querySelectorAll('input:checked')].map(n=>n.value).sort().join('|'),before=selected();
    groups.forEach(n=>n.open=false);const baseline=groups.map(n=>n.getBoundingClientRect().height);let independent=true,fits=true;
    groups.forEach((group,index)=>{group.open=true;groups.forEach((other,i)=>{if(i!==index&&(other.open||Math.abs(other.getBoundingClientRect().height-baseline[i])>1))independent=false;const box=other.getBoundingClientRect();if(box.left<0||box.right>innerWidth+1)fits=false;});group.open=false;});
    return {independent,fits,unchanged:before===selected(),count:groups.length,align:getComputedStyle(grid).alignItems};
   });
   ck(result.count>5,'Multiple permission groups inspected');ck(result.independent,'Opening each group preserves every other group height/state '+width);ck(result.unchanged,'Opening does not alter granted permissions '+width);ck(result.fits,'Groups fit viewport '+width);ck(result.align==='start','Grid does not stretch cards '+width);
  }
  return `${checks} independent permission layout checks passed; real CSS/DOM, mock data, no DB.`;
 }finally{await context.close();}
}
