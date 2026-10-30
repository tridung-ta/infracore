import * as THREE from '/assets/ita/vendor/three/0.180.0/three.module.min.js';

/** Demand-rendered declared topology. No RAF loop or network polling. */
export function createScene(host,view,onSelect,onLost){
 const color=name=>getComputedStyle(document.body).getPropertyValue('--ita-'+name).trim();
 const renderer=new THREE.WebGLRenderer({antialias:true,alpha:false});
 let disposed=false,ready=false,observer=null,pick=null,lost=null,move=null,leave=null,down=null,up=null,cancel=null,drag=null,tap=null,frame=0,yaw=0,pitch=0,hoverX=0,hoverY=0;
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');
 const scene=new THREE.Scene(),camera=new THREE.PerspectiveCamera(48,1,.1,100),group=new THREE.Group(),meshes=[];
 const labels=document.createElement('div'),names=[];labels.className='network-3d-labels';labels.setAttribute('aria-hidden','true');
 function dispose(){if(disposed)return;disposed=true;cancelAnimationFrame(frame);drag=tap=null;observer?.disconnect();renderer.domElement.removeEventListener('pointerdown',down);renderer.domElement.removeEventListener('pointerup',up);renderer.domElement.removeEventListener('pointercancel',cancel);renderer.domElement.removeEventListener('lostpointercapture',cancel);renderer.domElement.removeEventListener('pointermove',move);renderer.domElement.removeEventListener('pointerleave',leave);reduced.removeEventListener('change',leave);removeEventListener('ita:motion',leave);renderer.domElement.removeEventListener('webglcontextlost',lost);group.traverse(object=>{object.geometry?.dispose();if(Array.isArray(object.material))object.material.forEach(m=>m.dispose());else object.material?.dispose();});renderer.dispose();renderer.forceContextLoss();renderer.domElement.remove();labels.remove();}
 try{
 scene.background=new THREE.Color(color('bg'));scene.add(group);camera.position.set(0,8,13);camera.lookAt(0,0,0);
 renderer.setPixelRatio(Math.min(devicePixelRatio,1.5));renderer.domElement.style.cursor='grab';renderer.domElement.setAttribute('role','img');renderer.domElement.setAttribute('aria-label',(document.body.dataset.mode==='live'?'Liên kết khai báo':'MOCK')+' · Sơ đồ mạng 3D; giữ chuột kéo để xoay 360 độ ngang và dọc; dùng nút xoay hoặc danh sách thiết bị bằng bàn phím');host.append(renderer.domElement);
 const palette={healthy:color('success'),warning:color('warning'),offline:color('text-muted')};
 const deviceColors=['accent','success','warning','device-violet','device-blue','device-pink','device-lime','device-orange'];
 function device(n,index){
  const root=new THREE.Group(),kind=String((n.kind||'')+' '+(n.name||'')).normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
  const base=new THREE.Color(color(deviceColors[index%deviceColors.length])).offsetHSL(Math.floor(index/deviceColors.length)*.053,0,0);
  function box(w,h,d,x,y,z,tint=base){const part=new THREE.Mesh(new THREE.BoxGeometry(w,h,d),new THREE.MeshBasicMaterial({color:tint}));part.position.set(x,y,z);part.userData={id:n.id};part.userData.base=tint;root.add(part);}
  if(/printer|may in/.test(kind)){box(1,.45,.8,0,.25,0);box(.72,.08,.5,0,.53,-.1,color('text'));box(.65,.08,.35,0,.1,.5,color('text-muted'));}
  else if(/laptop|xach tay/.test(kind)){box(1,.07,.75,0,.05,0);box(1,.65,.07,0,.4,-.32);box(.84,.48,.02,0,.41,-.27,color('surface-2'));}
  else if(/monitor|man hinh/.test(kind)){box(1.15,.7,.1,0,.65,0);box(.98,.52,.02,0,.65,.06,color('surface-2'));box(.1,.35,.12,0,.2,0);box(.6,.07,.35,0,.03,0);}
  else if(/switch|router|mang|network/.test(kind)){box(1.2,.25,.65,0,.16,0);for(let p=0;p<6;p++)box(.1,.09,.03,-.44+p*.18,.16,.34,color('text'));}
  else if(/server|may chu|storage|luu tru/.test(kind)){box(.65,1.15,.8,0,.58,0);for(let p=0;p<4;p++)box(.5,.1,.03,0,.25+p*.22,.42,color('surface-2'));}
  else if(/desktop|computer|may tinh|workstation/.test(kind)){box(.42,.9,.65,-.43,.45,0);box(.85,.6,.1,.3,.55,0);box(.1,.25,.12,.3,.15,0);box(.5,.06,.3,.3,.03,0);}
  else {box(.7,.7,.7,0,.35,0);box(.45,.13,.03,0,.4,.37,color('surface-2'));}
  root.userData={id:n.id,status:n.status};return root;
 }
 host.append(labels);
 for(const [index,n] of view.nodes.entries()){
  const mesh=device(n,index);
  mesh.position.set((n.x-550)/70,0,(n.y-250)/45);mesh.scale.setScalar(1.4);group.add(mesh);meshes.push(mesh);
  const name=document.createElement('span');name.className='network-3d-name';name.dataset.status=n.status;name.textContent=String(n.name||n.id);name.title=[n.name,n.kind,n.assetStatus||n.status].filter(Boolean).join(' · ');labels.append(name);names.push({mesh,name});
 }
 const byId=new Map(meshes.map(mesh=>[mesh.userData.id,mesh]));
 for(const link of view.links){const a=byId.get(link.source),b=byId.get(link.target);if(!a||!b)continue;
  group.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints([a.position.clone(),b.position.clone()]),new THREE.LineBasicMaterial({color:palette[link.status]})));
 }
 function labelPositions(){
  const width=host.clientWidth,height=host.clientHeight,placed=[];
  const items=names.map(({mesh,name})=>{const bounds=new THREE.Box3().setFromObject(mesh),p=bounds.getCenter(new THREE.Vector3()).project(camera);let top=p.y;for(const x of [bounds.min.x,bounds.max.x])for(const y of [bounds.min.y,bounds.max.y])for(const z of [bounds.min.z,bounds.max.z])top=Math.max(top,new THREE.Vector3(x,y,z).project(camera).y);p.y=top+8/Math.max(1,height);return {name,p,w:name.offsetWidth||140,h:name.offsetHeight||22};});
  for(const {name,p,w,h} of items){
   name.hidden=p.z< -1||p.z>1;if(name.hidden)continue;
   const x=Math.max(w/2+4,Math.min(width-w/2-4,(p.x+1)*width/2));let y=Math.max(h+4,Math.min(height-4,(1-p.y)*height/2));
   while(y>h+4&&placed.some(b=>Math.abs(b.x-x)<(b.w+w)/2+3&&y>b.y-b.h-3&&y-h<b.y+3))y=Math.max(h+4,y-h-4);
   placed.push({x,y,w,h});name.style.left=x+'px';name.style.top=y+'px';
  }
 }
 function draw(){if(disposed)return;try{renderer.render(scene,camera);labelPositions();}catch(error){if(!ready)throw error;dispose();onLost();}}
 function resize(){
  if(disposed)return;const width=Math.max(1,host.clientWidth),height=Math.max(1,host.clientHeight);camera.aspect=width/height;
  const sphere=new THREE.Box3().setFromObject(group).getBoundingSphere(new THREE.Sphere()),distance=(Math.max(1,sphere.radius)+.5)/Math.sin(THREE.MathUtils.degToRad(camera.fov)/2)/Math.min(1,camera.aspect);
  camera.position.copy(sphere.center).add(new THREE.Vector3(0,.52,.85).normalize().multiplyScalar(distance));camera.lookAt(sphere.center);camera.updateProjectionMatrix();renderer.setSize(width,height,false);draw();
 }
 observer=new ResizeObserver(()=>{try{resize();}catch(error){onLost();}});observer.observe(host);
 const raycaster=new THREE.Raycaster(),pointer=new THREE.Vector2();
 pick=event=>{if(disposed)return;const rect=renderer.domElement.getBoundingClientRect();pointer.set((event.clientX-rect.left)/rect.width*2-1,-(event.clientY-rect.top)/rect.height*2+1);raycaster.setFromCamera(pointer,camera);const hit=raycaster.intersectObjects(meshes,true)[0];if(hit)onSelect(hit.object.userData.id);};
 lost=event=>{event.preventDefault();onLost();};
 function orient(){group.rotation.set(pitch+hoverX,yaw+hoverY,0);resize();}
 function requestDraw(){if(!frame)frame=requestAnimationFrame(()=>{frame=0;if(!disposed)orient();});}
 down=event=>{
  if(disposed||event.button!==0||drag||tap)return;
  if(event.pointerType!=='mouse'){if(['touch','pen'].includes(event.pointerType))tap={id:event.pointerId,x:event.clientX,y:event.clientY,moved:false};return;}
  drag={id:event.pointerId,x:event.clientX,y:event.clientY,yaw:yaw+hoverY,pitch:pitch+hoverX,moved:false};
  renderer.domElement.style.cursor='grabbing';
  try{renderer.domElement.setPointerCapture(event.pointerId);}catch(error){/* Synthetic/nonactive pointers cannot be captured. */}
 };
 function release(){const current=drag;drag=null;renderer.domElement.style.cursor='grab';if(current&&renderer.domElement.hasPointerCapture(current.id))renderer.domElement.releasePointerCapture(current.id);}
 up=event=>{if(tap?.id===event.pointerId){const click=!tap.moved&&Math.hypot(event.clientX-tap.x,event.clientY-tap.y)<=4;tap=null;if(click)pick(event);return;}if(!drag||drag.id!==event.pointerId)return;const click=!drag.moved&&Math.hypot(event.clientX-drag.x,event.clientY-drag.y)<=4;release();if(click)pick(event);};
 cancel=event=>{if(tap?.id===event.pointerId)tap=null;if(drag&&event.pointerId===drag.id)release();};
 move=event=>{
  if(disposed)return;
  if(event.pointerType!=='mouse'){if(tap?.id===event.pointerId&&Math.hypot(event.clientX-tap.x,event.clientY-tap.y)>4)tap.moved=true;return;}
  if(drag){
   if(drag.id!==event.pointerId)return;
   const dx=event.clientX-drag.x,dy=event.clientY-drag.y;
   if(Math.hypot(dx,dy)>4)drag.moved=true;
   if(drag.moved){yaw=drag.yaw+dx*.012;pitch=drag.pitch+dy*.012;hoverX=hoverY=0;requestDraw();}return;
  }
  if(reduced.matches||window.ItaMotion?.enabled()===false)return;
  const rect=renderer.domElement.getBoundingClientRect();if(!rect.width||!rect.height)return;
  hoverY=Math.max(-1,Math.min(1,(event.clientX-rect.left)/rect.width*2-1))*.65;
  hoverX=Math.max(-1,Math.min(1,(event.clientY-rect.top)/rect.height*2-1))*.4;
  requestDraw();
 };
 leave=()=>{tap=null;if(disposed||drag)return;cancelAnimationFrame(frame);frame=0;hoverX=hoverY=0;orient();};
 renderer.domElement.addEventListener('pointermove',move);renderer.domElement.addEventListener('pointerleave',leave);reduced.addEventListener('change',leave);addEventListener('ita:motion',leave);
 renderer.domElement.addEventListener('pointerdown',down);renderer.domElement.addEventListener('pointerup',up);renderer.domElement.addEventListener('pointercancel',cancel);renderer.domElement.addEventListener('lostpointercapture',cancel);renderer.domElement.addEventListener('webglcontextlost',lost);resize();
 ready=true;
 return {
  rotate(delta){yaw+=delta;orient();},
  select(id){for(const mesh of meshes)mesh.traverse(part=>{if(part.material)part.material.color.set(mesh.userData.id===id?color('text'):part.userData.base);});draw();},
  dispose
 };
 }catch(error){dispose();throw error;}
}
