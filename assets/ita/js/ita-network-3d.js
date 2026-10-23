(() => {
 'use strict';
 const $=id=>document.getElementById(id);let mode='2d',scene=null,module=null,sequence=0;
 function controls(){const three=mode==='3d';$('network-graph').hidden=three;$('network-3d').hidden=!three;$('network-show-2d').setAttribute('aria-pressed',String(!three));$('network-show-3d').setAttribute('aria-pressed',String(three));$('network-show-3d').disabled=mode==='loading';$('network-rotate-left').disabled=!three;$('network-rotate-right').disabled=!three;dispatchEvent(new CustomEvent('ita:network-mode',{detail:{mode}}));}
 function two(message='MOCK · Sơ đồ 2D đang hiển thị.'){sequence++;scene?.dispose();scene=null;mode='2d';controls();$('network-3d-status').textContent=message;}
 function build(){scene?.dispose();scene=null;scene=module.createScene($('network-3d'),ItaNetwork.view(),ItaNetwork.select,()=>two('WebGL bị gián đoạn. Đã chuyển về 2D.'));scene.select(document.querySelector('#network-node-list [aria-pressed=true]')?.dataset.networkNode);}
 async function three(){
  if(document.body.dataset.mode==='live'||!ItaNetwork.view().nodes.length)return;
  const current=++sequence;mode='loading';controls();$('network-3d-status').textContent='Đang tải mô hình 3D MOCK…';
  try{module=module||await import('/assets/ita/js/ita-network-scene.js');if(current!==sequence||location.hash!=='#network')return;
   mode='3d';controls();build();if(mode==='3d'&&scene)$('network-3d-status').textContent='MOCK · 3D theo thao tác; không có giám sát mạng thật. Chọn thiết bị bằng danh sách hoặc bấm khối.';
  }catch(error){if(current===sequence)two('3D chưa khả dụng. Sơ đồ 2D và bảng đối chiếu vẫn dùng được.');}
 }
 $('network-show-2d').addEventListener('click',()=>two());$('network-show-3d').addEventListener('click',three);
 $('network-rotate-left').addEventListener('click',()=>scene?.rotate(-.2));$('network-rotate-right').addEventListener('click',()=>scene?.rotate(.2));
 addEventListener('ita:network-select',event=>scene?.select(event.detail.id));
 addEventListener('ita:network-render',()=>{if(mode==='3d')try{build();}catch(error){two('Không dựng được scene mới. Đang dùng 2D.');}});
 addEventListener('ita:view',()=>{if(location.hash!=='#network')two();});addEventListener('pagehide',()=>two());controls();
})();
