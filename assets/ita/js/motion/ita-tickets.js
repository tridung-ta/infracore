(() => {
 'use strict';
 if(window.ItaMotion){document.body.dataset.ticketMotion='off';ItaMotion.register('#ticket-feedback','tickets');ItaMotion.register('#ticket-record','tickets');return;}
 document.body.dataset.ticketMotion=window.UI_MOTION_ENABLED===false?'off':'on';
})();
