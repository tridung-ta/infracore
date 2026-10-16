/* Presentation-only confirmation primitive; callers must handle server writes. */
(() => {
 'use strict';
 const dialog=document.getElementById('confirm-dialog');
 document.getElementById('confirm-cancel').addEventListener('click',()=>dialog.close('cancel'));
 document.getElementById('confirm-accept').addEventListener('click',()=>dialog.close('confirm'));
 window.ItaUi=Object.freeze({
  confirm({title='Xác nhận thao tác',message='',confirmLabel='Xác nhận'}={}) {
   if(dialog.open)return Promise.resolve(false);
   const trigger=document.activeElement;
   document.getElementById('confirm-title').textContent=String(title);
   document.getElementById('confirm-message').textContent=String(message);
   document.getElementById('confirm-accept').textContent=String(confirmLabel);
   dialog.returnValue='';
   return new Promise((resolve)=>{
    dialog.addEventListener('close',()=>{
     if(trigger?.isConnected)trigger.focus();
     resolve(dialog.returnValue==='confirm');
    },{once:true});
    dialog.showModal();
   });
  }
 });
})();
