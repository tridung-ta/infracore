(() => {
 'use strict';
 if(window.ItaMotion){document.body.dataset.ticketMotion='off';return;}
 document.body.dataset.ticketMotion=window.UI_MOTION_ENABLED===false?'off':'on';
})();
