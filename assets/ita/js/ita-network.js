(() => {
 'use strict';
 const $=id=>document.getElementById(id),live=document.body.dataset.mode==='live',ns='http://www.w3.org/2000/svg';
 if(live)return;
 const labels={healthy:'Bình thường (mẫu)',warning:'Cần theo dõi (mẫu)',offline:'Mất kết nối (mẫu)'};
 let source=null,visible={nodes:[],links:[]},selected='',loading=false;
 const node=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
 function svg(tag,attributes={}){const n=document.createElementNS(ns,tag);for(const [k,v]of Object.entries(attributes))n.setAttribute(k,String(v));return n;}
 function select(id){
  const item=visible.nodes.find(n=>n.id===id);if(!item)return;selected=id;
  $('network-detail').hidden=false;$('network-detail-title').textContent=item.name+' · MOCK';$('network-detail-values').replaceChildren();
  for(const [key,value]of [['Loại',item.kind],['Bộ phận',item.department],['Trạng thái',labels[item.status]],['Liên kết đang xem',visible.links.filter(l=>l.source===id||l.target===id).length]])$('network-detail-values').append(node('dt',key),node('dd',String(value)));
  document.querySelectorAll('[data-network-node]').forEach(n=>n.setAttribute('aria-pressed',String(n.dataset.networkNode===id)));
  dispatchEvent(new CustomEvent('ita:network-select',{detail:{id}}));
 }
 function render(){
  const query=$('network-search').value.trim().toLocaleLowerCase('vi'),department=$('network-department').value;
  const nodes=source.nodes.filter(n=>(!query||(n.name+' '+n.kind).toLocaleLowerCase('vi').includes(query))&&(!department||n.department===department)),ids=new Set(nodes.map(n=>n.id));
  visible={nodes,links:source.links.filter(l=>ids.has(l.source)&&ids.has(l.target))};
  $('network-count').textContent=`MOCK · ${nodes.length}/${source.nodes.length} thiết bị · ${visible.links.length} liên kết trong bộ lọc`;
  $('network-links').replaceChildren();$('network-nodes').replaceChildren();$('network-node-list').replaceChildren();
  const byId=new Map(nodes.map(n=>[n.id,n]));
  for(const link of visible.links){const a=byId.get(link.source),b=byId.get(link.target),line=svg('path',{d:`M ${a.x} ${a.y} L ${(a.x+b.x)/2} ${a.y} L ${(a.x+b.x)/2} ${b.y} L ${b.x} ${b.y}`,class:'network-edge','data-state':link.status});$('network-links').append(line);}
  for(const item of nodes){
   const group=svg('g',{class:'network-node',transform:`translate(${item.x},${item.y})`,role:'button',tabindex:0,'aria-label':item.name+' · '+labels[item.status],'aria-pressed':'false','data-network-node':item.id});
   const title=svg('title');title.textContent=item.name;const text=svg('text',{'text-anchor':'middle',y:5});text.textContent=item.kind;
   group.append(title,svg('rect',{x:-66,y:-25,width:132,height:50,rx:8}),text);group.addEventListener('click',()=>select(item.id));group.addEventListener('keydown',event=>{if(['Enter',' '].includes(event.key)){event.preventDefault();select(item.id);}});$('network-nodes').append(group);
   const button=node('button',item.name);button.type='button';button.className='secondary-button';button.dataset.networkNode=item.id;button.setAttribute('aria-pressed','false');button.addEventListener('click',()=>select(item.id));$('network-node-list').append(button);
  }
  $('network-link-rows').replaceChildren(...visible.links.map(l=>{const tr=node('tr','');for(const value of [byId.get(l.source).name,byId.get(l.target).name,l.kind,l.capacity,labels[l.status]])tr.append(node('td',value));return tr;}));
  if(!visible.links.length){const tr=node('tr',''),td=node('td','Không có liên kết trong bộ lọc này.');td.colSpan=5;tr.append(td);$('network-link-rows').append(tr);}
  $('network-detail').hidden=true;if(ids.has(selected))select(selected);else selected='';
  dispatchEvent(new CustomEvent('ita:network-render'));
 }
 function validate(data){
  if(data.mock!==true||!Array.isArray(data.nodes)||!Array.isArray(data.links)||data.nodes.length>200||data.links.length>400)throw new Error('Mô hình mẫu không hợp lệ.');
  const ids=new Set();for(const n of data.nodes){if(typeof n.id!=='string'||!n.id||n.id.length>100||ids.has(n.id)||!['name','kind','department'].every(k=>typeof n[k]==='string'&&n[k].length<=100)||!Object.hasOwn(labels,n.status)||![n.x,n.y].every(Number.isFinite)||n.x<70||n.x>1030||n.y<40||n.y>460)throw new Error('Thiết bị mẫu không hợp lệ.');ids.add(n.id);}
  for(const l of data.links)if(!ids.has(l.source)||!ids.has(l.target)||l.source===l.target||!Object.hasOwn(labels,l.status)||!['kind','capacity'].every(k=>typeof l[k]==='string'&&l[k].length<=100))throw new Error('Liên kết mẫu không hợp lệ.');
 }
 async function load(){
  if(live||loading)return;loading=true;$('network-message').textContent='Đang tải mô hình MOCK…';$('network-retry').hidden=true;
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),15000);
  try{const response=await fetch('/assets/ita/mock/network.json',{cache:'no-store',signal:controller.signal});if(!response.ok)throw new Error('Chưa tải được mô hình mẫu.');const text=await response.text();if(text.length>250000)throw new Error('Mô hình mẫu vượt giới hạn.');const data=JSON.parse(text);validate(data);source=data;
   $('network-department').replaceChildren(new Option('Tất cả bộ phận',''),...[...new Set(data.nodes.map(n=>n.department))].sort().map(name=>new Option(name,name)));
   $('network-content').hidden=false;render();$('network-message').textContent='MOCK · Chỉ minh họa topology, chưa có dữ liệu mạng thật.';
  }catch(error){$('network-content').hidden=true;$('network-message').textContent=error.name==='AbortError'?'Tải mô hình quá thời gian. Hãy thử lại.':error.message;$('network-retry').hidden=false;}finally{clearTimeout(timer);loading=false;}
 }
 function activate(){
  $('network-nav').hidden=live;if(location.hash!=='#network')return;
  if(live){$('network-content').hidden=true;$('network-message').textContent='Mạng thật chưa được mở BE. Không tải dữ liệu MOCK trong chế độ hệ thống.';return;}
  if(!source)load();
 }
 $('network-filters').addEventListener('submit',event=>{event.preventDefault();if(source)render();});$('network-reset').addEventListener('click',()=>{$('network-filters').reset();if(source)render();});$('network-retry').addEventListener('click',load);
 window.ItaNetwork={view:()=>visible,select};addEventListener('ita:view',activate);activate();
})();
