(() => {
 'use strict';
 const password=document.getElementById('password');
 const toggle=document.getElementById('password-toggle');
 toggle.addEventListener('click',()=>{
  const visible=password.type==='password';
  password.type=visible?'text':'password';
  toggle.textContent=visible?'Ẩn':'Hiện';
  toggle.setAttribute('aria-pressed',String(visible));
 });
 document.getElementById('login-form').addEventListener('submit',()=>{
  const button=document.querySelector('.login-submit');button.disabled=true;button.textContent='Đang đăng nhập…';
 });
})();
