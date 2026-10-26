(() => {
 'use strict';
 const $=id=>document.getElementById(id),prefix=document.body.dataset.mode==='live'?'Liên kết khai báo · ':'MOCK · ';let mode='2d',scene=null,module=null,sequence=0;
 function controls(){const three=mode==='3d';$('network-graph').hidden=three;$('network-3d').hidden=!three;$('network-show-2d').setAttribute('aria-pressed',String(!three));$('network-show-3d').setAttribute('aria-pressed',String(three));$('network-show-3d').disabled=mode==='loading';$('network-rotate-left').disabled=!three;$('network-rotate-right').disabled=!three;$('network-fullscreen').disabled=!three||!document.fullscreenEnabled;dispatchEvent(new CustomEvent('ita:network-mode',{detail:{mode}}));}
 function two(message=prefix+'Sơ đồ 2D đang hiển thị.'){sequence++;if(document.fullscreenElement===$('network-3d'))document.exitFullscreen().catch(()=>{});scene?.dispose();scene=null;mode='2d';controls();$('network-3d-status').textContent=message;}
 function build(){scene?.dispose();scene=null;const view=ItaNetwork.view();$('network-fullscreen-device').replaceChildren(new Option('Chọn thiết bị',''),...view.nodes.map(n=>new Option(n.name||n.id,String(n.id))));scene=module.createScene($('network-3d'),view,ItaNetwork.select,()=>two('WebGL bị gián đoạn. Đã chuyển về 2D.'));const id=document.querySelector('#network-node-list [aria-pressed=true]')?.dataset.networkNode;scene.select(id);$('network-fullscreen-device').value=id||'';}
 async function three(){
  if(!window.ItaNetwork?.view().nodes.length)return;
  const current=++sequence;mode='loading';controls();$('network-3d-status').textContent=prefix+'Đang tải mô hình 3D…';
  try{module=module||await import('/assets/ita/js/ita-network-scene.js?v=20261026-orbit');if(current!==sequence||location.hash!=='#network')return;
   mode='3d';controls();build();if(mode==='3d'&&scene)$('network-3d-status').textContent=prefix+'Giữ chuột trái và kéo để xoay 360° ngang/dọc, kể cả xem từ dưới lên. Rê nhẹ để nghiêng; chế độ giảm chuyển động vẫn kéo hoặc dùng nút xoay. Bấm khối hoặc chọn trong danh sách để xem thiết bị. Không có giám sát mạng thật.';
  }catch(error){if(current===sequence)two('3D chưa khả dụng. Sơ đồ 2D và bảng đối chiếu vẫn dùng được.');}
 }
 $('network-show-2d').addEventListener('click',()=>two());$('network-show-3d').addEventListener('click',three);
 $('network-rotate-left').addEventListener('click',()=>scene?.rotate(-.2));$('network-rotate-right').addEventListener('click',()=>scene?.rotate(.2));
 async function fullscreen(){try{if(document.fullscreenElement===$('network-3d'))await document.exitFullscreen();else if(mode==='3d'){await $('network-3d').requestFullscreen();if(mode!=='3d'&&document.fullscreenElement===$('network-3d'))await document.exitFullscreen();}}catch(error){$('network-3d-status').textContent='Trình duyệt chưa cho phép toàn màn hình. Bạn vẫn có thể xem 3D trong khung.';}}
 $('network-fullscreen').addEventListener('click',fullscreen);$('network-fullscreen-exit').addEventListener('click',fullscreen);
 document.addEventListener('fullscreenchange',()=>{const active=document.fullscreenElement===$('network-3d');$('network-fullscreen-controls').hidden=!active;$('network-fullscreen').setAttribute('aria-pressed',String(active));if(active)$('network-fullscreen-exit').focus();else if(mode==='3d')$('network-fullscreen').focus();});
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.fullscreenElement===$('network-3d'))document.exitFullscreen().catch(()=>{});});
 $('network-fullscreen-left').addEventListener('click',()=>scene?.rotate(-.2));$('network-fullscreen-right').addEventListener('click',()=>scene?.rotate(.2));$('network-fullscreen-device').addEventListener('change',event=>ItaNetwork.select(event.target.value));
 addEventListener('ita:network-select',event=>{scene?.select(event.detail.id);$('network-fullscreen-device').value=String(event.detail.id);});
 addEventListener('ita:network-render',()=>{if(mode==='3d')try{build();}catch(error){two('Không dựng được scene mới. Đang dùng 2D.');}});
 addEventListener('ita:view',()=>{if(location.hash!=='#network')two();});addEventListener('pagehide',()=>two());controls();
})();
