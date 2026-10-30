/** Apply the presentation preference before styles paint; no auth/session data. */
(() => {
 'use strict';let command=true;
 try{command=localStorage.getItem('ita.motion')!=='off';}catch(error){}
 const root=document.documentElement;root.dataset.uiTheme=command?'command':'focus';root.dataset.bsTheme=command?'dark':'light';root.style.colorScheme=command?'dark':'light';
})();
