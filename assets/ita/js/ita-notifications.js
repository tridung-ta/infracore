/** Session-only reminder state. Active alerts are never deleted or resolved here. */
(() => {
 'use strict';
 const $=id=>document.getElementById(id);let key='',signature='',count=0,pendingSeen=false,seen='';
 function identity(){const user=window.itaLiveContext?.user;if(!user)return false;const next='ita.alert-reminder.'+user.id+'.'+(user.capabilities_enabled?[...(user.permissions||[])].sort().join(','):user.role);if(next!==key){key=next;signature='';pendingSeen=false;try{seen=sessionStorage.getItem(key)||'';}catch{seen='';}}return true;}
 function remember(){seen=signature;try{sessionStorage.setItem(key,seen);}catch{/* In-memory fallback; no auth/storage dependency. */}}
 function render(){
  if(!signature){$('dash-bell-label').textContent='Đang kiểm tra cảnh báo…';return;}
  const viewed=signature!==''&&signature===seen,badge=$('nav-alert-count'),button=$('notification-button');
  badge.textContent=String(count);badge.hidden=count===0||viewed;badge.title='Cảnh báo cần kiểm tra; không phải số tin chưa đọc';
  button.querySelector('.notification-dot').hidden=count===0||viewed;
  button.setAttribute('aria-label',`${count} cảnh báo cần xử lý${viewed?', đã xem trong phiên này':''}`);
  button.title=viewed?'Đã xem trong phiên này; cảnh báo vẫn cần xử lý':'Mở trung tâm cảnh báo';
  $('dash-bell-label').textContent=(viewed?'Đã xem · ':'')+count+' cảnh báo cần xử lý';
 }
 function update(data){
  if(!identity()||!Number.isSafeInteger(data.active_total)||data.active_total<0||!Array.isArray(data.rows))return false;
  count=data.active_total;
  // Compact presentation fingerprint, not a security hash or individual read receipt.
  const text=JSON.stringify([count,data.rows.map(row=>[row.reason,row.code,row.level,row.title])]);let hash=2166136261;
  for(let i=0;i<text.length;i++)hash=Math.imul(hash^text.charCodeAt(i),16777619);
  signature=String(hash>>>0);if(pendingSeen){remember();pendingSeen=false;}render();return true;
 }
 function viewed(){if(!identity())return;if(signature)remember();else pendingSeen=true;render();}
 window.ItaNotifications=Object.freeze({update,viewed});
})();
