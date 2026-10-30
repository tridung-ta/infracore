(() => {
 'use strict';
 if(window.ItaMotion){document.body.dataset.ticketMotion='off';ItaMotion.register('#ticket-message','tickets');ItaMotion.register('#ticket-details','tickets');return;}
 document.body.dataset.ticketMotion=window.UI_MOTION_ENABLED===false?'off':'on';
})();
