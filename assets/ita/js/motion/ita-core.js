(() => {
 'use strict';
 const reduced=matchMedia('(prefers-reduced-motion: reduce)'),registrations=[],animations=new Set();
 let preference=true,lastTheme='';
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
  const theme=preference?'command':'focus';document.body.dataset.uiTheme=theme;document.documentElement.dataset.uiTheme=theme;document.documentElement.dataset.bsTheme=preference?'dark':'light';document.documentElement.style.colorScheme=preference?'dark':'light';
  document.querySelectorAll('[data-motion-toggle]').forEach(button=>{
   button.textContent=preference?'Hiệu ứng: Bật · Command':'Hiệu ứng: Tắt · Focus';
   if(reduced.matches||window.UI_MOTION_ENABLED===false)button.textContent=(preference?'Command':'Focus')+' · Chuyển động đã giảm';
   button.title=preference?'Chuyển sang Focus: giao diện sáng, không hiệu ứng tự động':'Chuyển sang Command: giao diện tối, hiệu ứng theo cài đặt';
   button.setAttribute('aria-label',button.title);button.setAttribute('aria-pressed',String(preference));button.disabled=false;
  });
  for(const record of registrations){record.observer.disconnect();if(enabled())record.observer.observe(record.target,{childList:true,attributes:true,attributeFilter:['hidden','open']});}
  dispatchEvent(new CustomEvent('ita:motion',{detail:{enabled:enabled()}}));
  if(lastTheme!==theme){lastTheme=theme;dispatchEvent(new CustomEvent('ita:theme',{detail:{theme}}));}
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
 function setEnabled(value){preference=Boolean(value);try{localStorage.setItem('ita.motion',preference?'on':'off');}catch(error){}refresh();
  if(!reduced.matches&&window.UI_MOTION_ENABLED!==false){for(const button of document.querySelectorAll('[data-motion-toggle]')){const animation=button.animate(preference?[{transform:'scale(.96)',opacity:.65},{transform:'scale(1)',opacity:1}]:[{transform:'translateX(0)',opacity:1},{transform:'translateX(-5px)',opacity:.7},{transform:'translateX(0)',opacity:1}],{duration:preference?260:160,easing:'ease-out'});animations.add(animation);animation.finished.catch(()=>{}).finally(()=>animations.delete(animation));}}
 }
 window.ItaMotion={register,play,cancel,setEnabled,enabled,refresh};
 document.querySelectorAll('[data-motion-toggle]').forEach(button=>button.addEventListener('click',()=>setEnabled(!preference)));
 reduced.addEventListener('change',refresh);
 document.addEventListener('visibilitychange',refresh);
 addEventListener('pagehide',()=>{cancel();registrations.forEach(record=>record.observer.disconnect());});
 addEventListener('pageshow',event=>{if(event.persisted)refresh();});
 addEventListener('ita:view',()=>{cancel();for(const record of registrations)if(active(record))play(record.target);});
 refresh();
})();
