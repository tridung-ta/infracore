(() => {
 'use strict';
 const $=id=>document.getElementById(id),active=new Set(),layer=document.createElementNS('http://www.w3.org/2000/svg','g');
 layer.id='network-flow-layer';layer.setAttribute('aria-hidden','true');$('network-svg').append(layer);
 const allowed=()=>location.hash==='#network'&&window.ItaMotion?.enabled()&&!$('network-graph').hidden&&typeof $('network-svg').animate==='function';
 function controls(){$('network-flow-play').disabled=!allowed()||!window.ItaNetwork?.view().links.length||active.size>0;$('network-flow-stop').disabled=active.size===0;}
 function stop(){for(const animation of active)animation.cancel();active.clear();layer.replaceChildren();controls();}
 function track(animation,cleanup=()=>{}){active.add(animation);animation.finished.catch(()=>{}).finally(()=>{active.delete(animation);cleanup();controls();});}
 function play(){
  stop();if(!allowed())return;const view=ItaNetwork.view(),nodes=new Map(view.nodes.map(n=>[n.id,n]));
  for(const link of view.links.filter(l=>l.status!=='offline')){
   const a=nodes.get(link.source),b=nodes.get(link.target),middle=(a.x+b.x)/2,dot=document.createElementNS('http://www.w3.org/2000/svg','circle');dot.setAttribute('r','4');dot.setAttribute('class','network-flow-dot');layer.append(dot);
   track(dot.animate([{transform:`translate(${a.x}px,${a.y}px)`,opacity:0},{transform:`translate(${middle}px,${a.y}px)`,opacity:1},{transform:`translate(${middle}px,${b.y}px)`,opacity:1},{transform:`translate(${b.x}px,${b.y}px)`,opacity:0}],{duration:900,easing:'linear'}),()=>dot.remove());
  }
  document.querySelectorAll('#network-nodes rect').forEach(n=>track(n.animate([{opacity:1},{opacity:.55},{opacity:1}],{duration:600,easing:'ease-out'})));
  controls();
 }
 $('network-flow-play').addEventListener('click',play);$('network-flow-stop').addEventListener('click',stop);
 for(const event of ['ita:view','ita:network-render','ita:network-mode','ita:motion'])addEventListener(event,stop);
 document.addEventListener('visibilitychange',stop);addEventListener('pagehide',stop);controls();
})();
