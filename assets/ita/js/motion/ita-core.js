(() => {
 'use strict';
 const reduced=matchMedia('(prefers-reduced-motion: reduce)'),registrations=[],animations=new Set();
 let preference=true;
 try{preference=localStorage.getItem('ita.motion')!=='off';}catch(error){}
 const enabled=()=>window.UI_MOTION_ENABLED!==false&&preference&&!reduced.matches&&!document.hidden;
 function cancel(){for(const animation of animations)animation.cancel();animations.clear();for(const record of registrations){cancelAnimationFrame(record.frame);record.queued=false;}}
 function play(target){
  if(!enabled()||!target?.isConnected||!target.getClientRects().length||typeof target.animate!=='function')return;
  for(const animation of animations)if(animation.effect?.target===target){animation.cancel();animations.delete(animation);}
  const animation=target.animate([{opacity:.65,transform:'translateY(5px)'},{opacity:1,transform:'translateY(0)'}],{duration:180,easing:'ease-out'});
  animations.add(animation);animation.finished.catch(()=>{}).finally(()=>animations.delete(animation));
 }
 function active(record){return !record.view||location.hash==='#'+record.view||(!location.hash&&record.view==='overview');}
 function refresh(){
  cancel();document.body.dataset.itaMotion=enabled()?'on':'off';
  document.querySelectorAll('[data-motion-toggle]').forEach(button=>{
   button.textContent=reduced.matches?'Hiệu ứng: Giảm chuyển động':window.UI_MOTION_ENABLED===false?'Hiệu ứng: Đã tắt cấu hình':preference?'Hiệu ứng: Bật':'Hiệu ứng: Tắt';
   button.setAttribute('aria-pressed',String(enabled()));button.disabled=reduced.matches||window.UI_MOTION_ENABLED===false;
  });
  for(const record of registrations){record.observer.disconnect();if(enabled())record.observer.observe(record.target,{childList:true,attributes:true,attributeFilter:['hidden','open']});}
  dispatchEvent(new CustomEvent('ita:motion',{detail:{enabled:enabled()}}));
 }
 function register(selector,view){
  const target=document.querySelector(selector);if(!target)return;
  const record={target,view,queued:false};
  record.observer=new MutationObserver(()=>{
   if(record.queued||!active(record))return;record.queued=true;
   record.frame=requestAnimationFrame(()=>{record.queued=false;if(active(record))play(target);});
  });
  registrations.push(record);if(enabled())record.observer.observe(target,{childList:true,attributes:true,attributeFilter:['hidden','open']});
  if(active(record))play(target);
 }
 function setEnabled(value){preference=Boolean(value);try{localStorage.setItem('ita.motion',preference?'on':'off');}catch(error){}refresh();}
 window.ItaMotion={register,play,cancel,setEnabled,enabled,refresh};
 document.querySelectorAll('[data-motion-toggle]').forEach(button=>button.addEventListener('click',()=>setEnabled(!preference)));
 reduced.addEventListener('change',refresh);
 document.addEventListener('visibilitychange',refresh);
 addEventListener('pagehide',()=>{cancel();registrations.forEach(record=>record.observer.disconnect());});
 addEventListener('pageshow',event=>{if(event.persisted)refresh();});
 addEventListener('ita:view',()=>{cancel();for(const record of registrations)if(active(record))play(record.target);});
 refresh();
})();
