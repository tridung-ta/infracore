import * as THREE from '/assets/ita/vendor/three/0.180.0/three.module.min.js';

/** Demand-rendered declared topology. No RAF loop or network polling. */
export function createScene(host,view,onSelect,onLost){
 const color=name=>getComputedStyle(document.body).getPropertyValue('--ita-'+name).trim();
 const renderer=new THREE.WebGLRenderer({antialias:true,alpha:false});
 let disposed=false,ready=false,observer=null,pick=null,lost=null,move=null,leave=null,frame=0,yaw=0,hoverX=0,hoverY=0;
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');
 const scene=new THREE.Scene(),camera=new THREE.PerspectiveCamera(48,1,.1,100),group=new THREE.Group(),meshes=[];
 const labels=document.createElement('div'),names=[];labels.className='network-3d-labels';labels.setAttribute('aria-hidden','true');
 function dispose(){if(disposed)return;disposed=true;cancelAnimationFrame(frame);observer?.disconnect();renderer.domElement.removeEventListener('pointerdown',pick);renderer.domElement.removeEventListener('pointermove',move);renderer.domElement.removeEventListener('pointerleave',leave);reduced.removeEventListener('change',leave);renderer.domElement.removeEventListener('webglcontextlost',lost);group.traverse(object=>{object.geometry?.dispose();if(Array.isArray(object.material))object.material.forEach(m=>m.dispose());else object.material?.dispose();});renderer.dispose();renderer.forceContextLoss();renderer.domElement.remove();labels.remove();}
 try{
 scene.background=new THREE.Color(color('bg'));scene.add(group);camera.position.set(0,8,13);camera.lookAt(0,0,0);
 renderer.setPixelRatio(Math.min(devicePixelRatio,1.5));renderer.domElement.setAttribute('role','img');renderer.domElement.setAttribute('aria-label',(document.body.dataset.mode==='live'?'Liên kết khai báo':'MOCK')+' · Sơ đồ mạng 3D; rê chuột để xoay ngang và nghiêng dọc, dùng nút xoay hoặc danh sách thiết bị bằng bàn phím');host.append(renderer.domElement);
 const palette={healthy:color('success'),warning:color('warning'),offline:color('text-muted')};
 host.append(labels);
 for(const n of view.nodes){
  const mesh=new THREE.Mesh(new THREE.BoxGeometry(.9,.5,.65),new THREE.MeshBasicMaterial({color:palette[n.status]}));
  mesh.position.set((n.x-550)/90,.25,(n.y-250)/70);mesh.userData={id:n.id,status:n.status};group.add(mesh);meshes.push(mesh);
  const name=document.createElement('span');name.className='network-3d-name';name.textContent=String(n.name||n.id);labels.append(name);names.push({mesh,name});
 }
 const byId=new Map(meshes.map(mesh=>[mesh.userData.id,mesh]));
 for(const link of view.links){const a=byId.get(link.source),b=byId.get(link.target);if(!a||!b)continue;
  group.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints([a.position.clone(),b.position.clone()]),new THREE.LineBasicMaterial({color:palette[link.status]})));
 }
 function labelPositions(){
  const width=host.clientWidth,height=host.clientHeight,placed=[];
  const items=names.map(({mesh,name})=>{const p=mesh.localToWorld(new THREE.Vector3(0,.6,0)).project(camera);return {name,p,w:name.offsetWidth||140,h:name.offsetHeight||22};});
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
 pick=event=>{if(disposed)return;const rect=renderer.domElement.getBoundingClientRect();pointer.set((event.clientX-rect.left)/rect.width*2-1,-(event.clientY-rect.top)/rect.height*2+1);raycaster.setFromCamera(pointer,camera);const hit=raycaster.intersectObjects(meshes)[0];if(hit)onSelect(hit.object.userData.id);};
 lost=event=>{event.preventDefault();onLost();};
 function orient(){group.rotation.set(hoverX,yaw+hoverY,0);resize();}
 move=event=>{
  if(disposed||reduced.matches||event.pointerType!=='mouse')return;
  const rect=renderer.domElement.getBoundingClientRect();if(!rect.width||!rect.height)return;
  hoverY=Math.max(-1,Math.min(1,(event.clientX-rect.left)/rect.width*2-1))*.65;
  hoverX=Math.max(-1,Math.min(1,(event.clientY-rect.top)/rect.height*2-1))*.4;
  if(!frame)frame=requestAnimationFrame(()=>{frame=0;if(!disposed)orient();});
 };
 leave=()=>{if(disposed)return;cancelAnimationFrame(frame);frame=0;hoverX=hoverY=0;orient();};
 renderer.domElement.addEventListener('pointermove',move);renderer.domElement.addEventListener('pointerleave',leave);reduced.addEventListener('change',leave);
 renderer.domElement.addEventListener('pointerdown',pick);renderer.domElement.addEventListener('webglcontextlost',lost);resize();
 ready=true;
 return {
  rotate(delta){yaw+=delta;orient();},
  select(id){for(const mesh of meshes)mesh.material.color.set(mesh.userData.id===id?color('accent'):palette[mesh.userData.status]);draw();},
  dispose
 };
 }catch(error){dispose();throw error;}
}
