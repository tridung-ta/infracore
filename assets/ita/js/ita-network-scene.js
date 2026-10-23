import * as THREE from '/assets/ita/vendor/three/0.180.0/three.module.min.js';

/** Demand-rendered declared topology. No RAF loop or network polling. */
export function createScene(host,view,onSelect,onLost){
 const color=name=>getComputedStyle(document.body).getPropertyValue('--ita-'+name).trim();
 const renderer=new THREE.WebGLRenderer({antialias:true,alpha:false});
 let disposed=false,ready=false,observer=null,pick=null,lost=null;
 const scene=new THREE.Scene(),camera=new THREE.PerspectiveCamera(48,1,.1,100),group=new THREE.Group(),meshes=[];
 function dispose(){if(disposed)return;disposed=true;observer?.disconnect();renderer.domElement.removeEventListener('pointerdown',pick);renderer.domElement.removeEventListener('webglcontextlost',lost);group.traverse(object=>{object.geometry?.dispose();if(Array.isArray(object.material))object.material.forEach(m=>m.dispose());else object.material?.dispose();});renderer.dispose();renderer.forceContextLoss();renderer.domElement.remove();}
 try{
 scene.background=new THREE.Color(color('bg'));scene.add(group);camera.position.set(0,8,13);camera.lookAt(0,0,0);
 renderer.setPixelRatio(Math.min(devicePixelRatio,1.5));renderer.domElement.setAttribute('role','img');renderer.domElement.setAttribute('aria-label',(document.body.dataset.mode==='live'?'Liên kết khai báo':'MOCK')+' · Sơ đồ mạng 3D; dùng danh sách thiết bị để chọn bằng bàn phím');host.append(renderer.domElement);
 const palette={healthy:color('success'),warning:color('warning'),offline:color('text-muted')};
 for(const n of view.nodes){
  const mesh=new THREE.Mesh(new THREE.BoxGeometry(.9,.5,.65),new THREE.MeshBasicMaterial({color:palette[n.status]}));
  mesh.position.set((n.x-550)/90,.25,(n.y-250)/70);mesh.userData={id:n.id,status:n.status};group.add(mesh);meshes.push(mesh);
 }
 const byId=new Map(meshes.map(mesh=>[mesh.userData.id,mesh]));
 for(const link of view.links){const a=byId.get(link.source),b=byId.get(link.target);if(!a||!b)continue;
  group.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints([a.position.clone(),b.position.clone()]),new THREE.LineBasicMaterial({color:palette[link.status]})));
 }
 function draw(){if(disposed)return;try{renderer.render(scene,camera);}catch(error){if(!ready)throw error;dispose();onLost();}}
 function resize(){
  if(disposed)return;const width=Math.max(1,host.clientWidth),height=Math.max(1,host.clientHeight);camera.aspect=width/height;
  const sphere=new THREE.Box3().setFromObject(group).getBoundingSphere(new THREE.Sphere()),distance=(Math.max(1,sphere.radius)+.5)/Math.sin(THREE.MathUtils.degToRad(camera.fov)/2)/Math.min(1,camera.aspect);
  camera.position.copy(sphere.center).add(new THREE.Vector3(0,.52,.85).normalize().multiplyScalar(distance));camera.lookAt(sphere.center);camera.updateProjectionMatrix();renderer.setSize(width,height,false);draw();
 }
 observer=new ResizeObserver(()=>{try{resize();}catch(error){onLost();}});observer.observe(host);
 const raycaster=new THREE.Raycaster(),pointer=new THREE.Vector2();
 pick=event=>{if(disposed)return;const rect=renderer.domElement.getBoundingClientRect();pointer.set((event.clientX-rect.left)/rect.width*2-1,-(event.clientY-rect.top)/rect.height*2+1);raycaster.setFromCamera(pointer,camera);const hit=raycaster.intersectObjects(meshes)[0];if(hit)onSelect(hit.object.userData.id);};
 lost=event=>{event.preventDefault();onLost();};
 renderer.domElement.addEventListener('pointerdown',pick);renderer.domElement.addEventListener('webglcontextlost',lost);resize();
 ready=true;
 return {
  rotate(delta){group.rotation.y+=delta;resize();},
  select(id){for(const mesh of meshes)mesh.material.color.set(mesh.userData.id===id?color('accent'):palette[mesh.userData.status]);draw();},
  dispose
 };
 }catch(error){dispose();throw error;}
}
