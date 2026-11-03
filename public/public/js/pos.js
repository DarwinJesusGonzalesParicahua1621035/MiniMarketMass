// ---------- Datos de ejemplo (basados en schema.sql) ----------
const SUC={nombre:'Mass Cayma',serieB:'B001',serieF:'F001'};
const IGV_RATE=1.18;
const EMO={Abarrotes:'🌾',Lácteos:'🥛',Bebidas:'🥤',Limpieza:'🧴',Snacks:'🍿'};
const D={
  producto:[[1,'7750182001234','Arroz Costeño 1 kg','Abarrotes','kg',4.50,1],[2,'7750182005678','Leche Gloria 400 g','Lácteos','unidad',4.20,1],[3,'7750182009012','Inca Kola 1.5 L','Bebidas','unidad',7.90,1],[4,'7750182003456','Detergente Ariel 1 kg','Limpieza','unidad',12.50,1],[5,'7750182007890','Papas Lays 150 g','Snacks','unidad',5.50,0],[6,'7750182002468','Aceite Primor 1 L','Abarrotes','unidad',9.80,1]],
  stock:{1:120,2:8,3:64,4:15,5:30,6:42},
  cliente:[[1,'DNI','72845619','Carlos Quispe Mamani'],[2,'RUC','20601234567','Distribuidora Sur SAC'],[3,'DNI','45120987','María Condori Huamán'],[4,'RUC','20559876543','Bodega Los Andes EIRL']],
  ventas:[
    {id:231,tipo:'BOLETA',serie:'B001',corr:231,fecha:'2026-10-26 09:12',cliente:'Carlos Quispe Mamani',doc:'DNI 72845619',subtotal:29.66,igv:5.34,total:35.00,estado:'EMITIDA',motivo:'',sesion:1,
     items:[{id:3,nombre:'Inca Kola 1.5 L',qty:2,pu:7.90,tl:15.80},{id:4,nombre:'Detergente Ariel 1 kg',qty:1,pu:12.50,tl:12.50},{id:2,nombre:'Leche Gloria 400 g',qty:1,pu:4.20,tl:4.20},{id:1,nombre:'Arroz Costeño 1 kg',qty:0.5,pu:4.50,tl:2.50}],pagos:[{m:'EFECTIVO',monto:35,ref:''}]},
    {id:232,tipo:'BOLETA',serie:'B001',corr:232,fecha:'2026-10-26 11:15',cliente:'—',doc:'',subtotal:46.61,igv:8.39,total:55.00,estado:'EMITIDA',motivo:'',sesion:1,
     items:[{id:6,nombre:'Aceite Primor 1 L',qty:3,pu:9.80,tl:29.40},{id:3,nombre:'Inca Kola 1.5 L',qty:2,pu:7.90,tl:15.80},{id:2,nombre:'Leche Gloria 400 g',qty:2,pu:4.20,tl:8.40}].slice(0,3),pagos:[{m:'YAPE',monto:55,ref:'OP-889120'}]}
  ],
  corr:{BOLETA:233,FACTURA:12},
  sesion:{id:1,apertura:'2026-10-26 08:00',inicial:100,estado:'ABIERTA',cierre:'',final:null}
};
(function(){const v=D.ventas[1];v.total=r2(v.items.reduce((a,i)=>a+i.tl,0));v.subtotal=r2(v.total/IGV_RATE);v.igv=r2(v.total-v.subtotal);v.pagos[0].monto=v.total;
  const a=D.ventas[0];a.total=r2(a.items.reduce((x,i)=>x+i.tl,0));a.subtotal=r2(a.total/IGV_RATE);a.igv=r2(a.total-a.subtotal);a.pagos[0].monto=a.total;})();

const $=id=>document.getElementById(id);
const money=n=>'S/ '+Number(n).toFixed(2);
function r2(n){return Math.round((n+Number.EPSILON)*100)/100}
const pad=n=>String(n).padStart(2,'0');
const now=()=>{const d=new Date();return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`};
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const comp=v=>`${v.serie}-${String(v.corr).padStart(6,'0')}`;
const prod=id=>D.producto.find(p=>p[0]===id);
function toast(t,bad){const e=$('toast');e.textContent=t;e.className='on'+(bad?' bad':'');clearTimeout(toast.t);toast.t=setTimeout(()=>e.className='',2400)}
const open_=h=>{$('mbox').innerHTML=h;$('modal').classList.add('on')};
const close_=()=>$('modal').classList.remove('on');
$('modal').onclick=e=>{if(e.target.id==='modal')close_()};
setInterval(()=>$('clock').textContent='🕘 '+now().slice(11),1000);$('clock').textContent='🕘 '+now().slice(11);

let cart=[],comprobante='BOLETA',cat='Todas',q='',view='pos',cli={doc:'',nombre:'',found:false},P=[];
const cajaAbierta=()=>D.sesion.estado==='ABIERTA';
const total=()=>r2(cart.reduce((a,l)=>a+l.tl,0));
const ventasSesion=()=>D.ventas.filter(v=>v.sesion===D.sesion.id);

const NAV=[['pos','🛒','Nueva venta','Operación'],['ventas','🧾','Mis ventas','Operación'],['caja','💵','Mi caja','Operación'],['cliente','👥','Clientes','Consulta']];
function buildNav(){let h='',g='';NAV.forEach(n=>{if(n[3]!==g){g=n[3];h+=`<h6>${g}</h6>`}h+=`<a data-k="${n[0]}" onclick="go('${n[0]}')"><span>${n[1]}</span>${n[2]}</a>`});$('nav').innerHTML=h}

function go(k){
  view=k;
  // ESTA LÍNEA CIERRA LA BARRA LATERAL EN MÓVILES
  document.getElementById('aside').classList.remove('open');
  
  document.querySelectorAll('nav a').forEach(a=>a.classList.toggle('on',a.dataset.k===k));
  const t={pos:['Nueva venta','Escanea el código de barras o elige un producto'],ventas:['Mis ventas','Comprobantes emitidos en tu turno'],caja:['Mi caja','Apertura, resumen y cierre de caja'],cliente:['Clientes','Clientes registrados con DNI o RUC']}[k];
  $('title').innerHTML=`${t[0]}<small>${t[1]}</small>`;
  $('cajaTag').innerHTML=cajaAbierta()?'<span class="tag ok">ABIERTA</span>':'<span class="tag bad">CERRADA</span>';
  ({pos:renderPOS,ventas:()=>renderVentas(''),caja:renderCaja,cliente:()=>renderClientes('')})[k]();
}

function renderPOS(){
  if(!cajaAbierta()){
    $('view').innerHTML=`<div class="card" style="text-align:center;padding:50px 20px"><div style="font-size:42px">🔒</div><h3 style="font-size:20px;margin:10px 0 6px">La caja está cerrada</h3><p style="color:var(--g4);margin-bottom:18px">Abre tu caja para empezar a vender.</p><button class="btn y" onclick="openCaja()">Abrir caja</button></div>`;return}
  const cats=['Todas',...new Set(D.producto.map(p=>p[3]))];
  $('view').innerHTML=`<div class="pos">
   <div>
     <div class="scan"><input class="inp" id="q" placeholder="🔍  Código de barras o nombre del producto…  (F2)" autocomplete="off" oninput="q=this.value;renderGrid()" onkeydown="if(event.key==='Enter')scanEnter()"><button class="btn k" onclick="scanEnter()">Agregar</button></div>
     <div class="cats">${cats.map(c=>`<button class="${c===cat?'on':''}" onclick="cat='${c}';renderPOS()">${c}</button>`).join('')}</div>
     <div class="prods" id="grid"></div>
   </div>
   <div class="card cart" id="cartBox"></div>
  </div>`;
  $('q').value=q;renderGrid();renderCart();$('q').focus();
}
function filtered(){const t=q.trim().toLowerCase();return D.producto.filter(p=>p[6]&&(cat==='Todas'||p[3]===cat)&&(!t||p[1].includes(t)||p[2].toLowerCase().includes(t)))}
function renderGrid(){
  const l=filtered();
  $('grid').innerHTML=l.map(p=>{const s=D.stock[p[0]]||0;return `<button class="prod" ${s<=0?'disabled':''} onclick="add(${p[0]})">
    <div class="ic">${EMO[p[3]]||'📦'}</div><b>${p[2]}</b><span class="pr">${money(p[5])}<small> /${p[4]}</small></span>
    <small>${s<=0?'<span class="tag bad">Sin stock</span>':s<10?`<span class="tag bad">${s} · stock bajo</span>`:`Stock: ${s}`}</small></button>`}).join('')||'<div class="empty" style="grid-column:1/-1">Sin resultados</div>';
}
function scanEnter(){
  const t=q.trim();if(!t)return;
  let p=D.producto.find(p=>p[1]===t);
  if(!p){const l=filtered();if(l.length===1)p=l[0]}
  if(!p)return toast('Producto no encontrado o hay varias coincidencias',true);
  if(!p[6])return toast('Producto inactivo',true);
  add(p[0]);q='';$('q').value='';renderGrid();$('q').focus();
}
function add(id,n){
  const p=prod(id),s=D.stock[id]||0,l=cart.find(x=>x.id===id),step=p[4]==='kg'?0.5:1,cant=n??step,
        ya=l?l.qty:0;
  if(ya+cant>s)return toast(`Stock insuficiente de ${p[2]} (disp. ${s})`,true);
  if(l)l.qty=r2(l.qty+cant),l.tl=r2(l.qty*l.pu);else cart.push({id,nombre:p[2],unidad:p[4],qty:cant,pu:p[5],tl:r2(cant*p[5])});
  renderCart();
}
function setQty(id,v){
  const l=cart.find(x=>x.id===id),s=D.stock[id]||0;v=parseFloat(v);
  if(!(v>0)){cart=cart.filter(x=>x.id!==id)}
  else{if(v>s){toast(`Stock insuficiente (disp. ${s})`,true);v=s}
    if(l.unidad!=='kg')v=Math.floor(v)||1;l.qty=Math.round(v*1000)/1000;l.tl=r2(l.qty*l.pu)}
  renderCart();
}
function renderCart(){
  const T=total(),sub=r2(T/IGV_RATE),igv=r2(T-sub),n=cart.length;
  $('cartBox').innerHTML=`
   <div class="hd"><h3>🧺 Venta actual <span class="tag b">${n}</span></h3><button class="btn g" style="padding:6px 12px" onclick="clearCart()" ${n?'':'disabled'}>Vaciar</button></div>
   <div class="lines">${n?cart.map(l=>`<div class="ln"><b>${l.nombre}</b><span class="t">${money(l.tl)}</span>
     <span class="q">${money(l.pu)} ×<input type="number" min="0" step="${l.unidad==='kg'?'0.001':'1'}" value="${l.qty}" onchange="setQty(${l.id},this.value)"> ${l.unidad}</span><button class="x" onclick="setQty(${l.id},0)">✕</button></div>`).join(''):'<div class="empty">Aún no hay productos.<br>Escanea o toca un producto.</div>'}</div>
   <div class="cfg">
     <div class="seg"><button class="${comprobante==='BOLETA'?'on':''}" onclick="setComp('BOLETA')">Boleta ${SUC.serieB}</button><button class="${comprobante==='FACTURA'?'on':''}" onclick="setComp('FACTURA')">Factura ${SUC.serieF}</button></div>
     <div class="r2"><input class="inp" id="cdoc" placeholder="${comprobante==='FACTURA'?'RUC (11 dígitos)':'DNI / RUC (opcional)'}" inputmode="numeric" maxlength="11" value="${cli.doc}" oninput="lookup(this.value)"><input class="inp" id="cnom" placeholder="Nombre / Razón social" value="${esc(cli.nombre)}" ${cli.found?'readonly':''} oninput="cli.nombre=this.value"></div>
     <small id="cmsg">${cli.found?'✔ Cliente registrado':comprobante==='FACTURA'?'La factura requiere cliente con RUC.':'Sin datos se emite como cliente genérico.'}</small>
   </div>
   <div class="tot"><div><span>Subtotal</span><span>${money(sub)}</span></div><div><span>IGV (18%)</span><span>${money(igv)}</span></div>
     <div class="T"><span>TOTAL</span><span style="color:var(--y);font-size:30px;font-weight:800">${money(T)}</span></div>
     <button class="btn y big" onclick="checkout()" ${n?'':'disabled'}>Cobrar  (F9)</button></div>`;
}
function setComp(t){comprobante=t;renderCart()}
function clearCart(){cart=[];renderCart()}
function lookup(v){
  v=v.replace(/\D/g,'');cli.doc=v;
  const c=D.cliente.find(c=>c[2]===v);
  cli.found=!!c;if(c)cli.nombre=c[3];else if(cli.nombre&&!cli.found&&cli._auto)cli.nombre='';
  cli._auto=!!c;
  const nom=$('cnom');nom.value=cli.nombre;nom.readOnly=cli.found;
  $('cdoc').value=v;
  $('cmsg').textContent=c?'✔ Cliente registrado':(v.length===8||v.length===11)?'Cliente nuevo: escribe el nombre y se registrará al cobrar.':'Escribe el DNI (8) o RUC (11).';
}
function validateClient(){
  const d=cli.doc,nm=cli.nombre.trim();
  if(comprobante==='FACTURA'){
    if(d.length!==11)return 'La factura requiere un RUC de 11 dígitos.';
    if(!nm)return 'Falta la razón social del cliente.';
  }else if(d||nm){
    if(d.length!==8&&d.length!==11)return 'El DNI debe tener 8 dígitos y el RUC 11.';
    if(!nm)return 'Falta el nombre del cliente.';
  }
  return '';
}

function checkout(){
  if(!cart.length)return;
  const err=validateClient();if(err)return toast(err,true);
  P=[];
  open_(`<h3>Cobrar</h3><div class="big">${money(total())}</div><small style="color:var(--g4)">${comprobante} ${comprobante==='BOLETA'?SUC.serieB:SUC.serieF}-${String(D.corr[comprobante]).padStart(6,'0')}</small>
   <div class="mp">${[['EFECTIVO','💵'],['YAPE','🟣'],['PLIN','🟦'],['TARJETA','💳']].map(m=>`<button onclick="addPay('${m[0]}')">${m[1]}<br>${m[0][0]+m[0].slice(1).toLowerCase()}</button>`).join('')}</div>
   <div id="pl"></div><div class="sum" id="sum"></div>
   <div class="row"><button class="btn g" onclick="close_()">Cancelar</button><button class="btn y" id="ok" onclick="confirmPay()">Confirmar venta</button></div>`);
  addPay('EFECTIVO');
}
function paid(){return r2(P.reduce((a,p)=>a+(parseFloat(p.monto)||0),0))}
function addPay(m){
  const falta=Math.max(0,r2(total()-paid()));
  P.push({m,monto:falta>0?falta:'',ref:''});renderPay();
}
function renderPay(){
  $('pl').innerHTML=P.map((p,i)=>`<div class="pl"><b style="font-size:13px">${p.m[0]+p.m.slice(1).toLowerCase()}</b>
   <input class="inp" type="number" step="0.01" min="0" value="${p.monto}" placeholder="Monto" oninput="P[${i}].monto=this.value;sumPay()">
   ${p.m==='EFECTIVO'?'<span style="font-size:12px;color:var(--g3)">Sin referencia</span>':`<input class="inp" maxlength="30" placeholder="N.º operación" value="${esc(p.ref)}" oninput="P[${i}].ref=this.value">`}
   <button class="x" onclick="P.splice(${i},1);renderPay()">✕</button></div>`).join('');
  sumPay();
}
function payState(){
  const T=total(),pd=paid(),cash=r2(P.filter(p=>p.m==='EFECTIVO').reduce((a,p)=>a+(parseFloat(p.monto)||0),0));
  const vuelto=Math.max(0,r2(pd-T)),falta=Math.max(0,r2(T-pd));
  return {T,pd,cash,vuelto,falta,ok:pd>=T&&vuelto<=cash&&P.every(p=>parseFloat(p.monto)>0)&&P.length>0};
}
function sumPay(){
  const s=payState();
  $('sum').innerHTML=`<div><span>Recibido</span><b>${money(s.pd)}</b></div>
   <div><span>Falta por cobrar</span><b class="${s.falta?'bad-t':''}">${money(s.falta)}</b></div>
   <div><span>Vuelto</span><span class="v ${s.vuelto&&s.vuelto>s.cash?'bad-t':'ok-t'}">${money(s.vuelto)}</span></div>
   ${s.vuelto>s.cash?'<small class="bad-t">El vuelto solo puede salir del efectivo recibido.</small>':''}`;
  $('ok').disabled=!s.ok;
}
function confirmPay(){
  const s=payState();if(!s.ok)return;
  let clienteTxt='—',docTxt='';
  if(cli.doc){
    if(!D.cliente.find(c=>c[2]===cli.doc))D.cliente.push([D.cliente.length+1,cli.doc.length===8?'DNI':'RUC',cli.doc,cli.nombre.trim()]);
    clienteTxt=cli.nombre.trim();docTxt=(cli.doc.length===8?'DNI ':'RUC ')+cli.doc;
  }
  let v=s.vuelto;
  const pagos=P.map(p=>({m:p.m,monto:parseFloat(p.monto),ref:p.ref.trim()})).map(p=>{if(p.m==='EFECTIVO'&&v>0){const d=Math.min(v,p.monto);p.monto=r2(p.monto-d);v=r2(v-d)}return p}).filter(p=>p.monto>0);
  const T=s.T,sub=r2(T/IGV_RATE),venta={
    id:D.ventas.length?Math.max(...D.ventas.map(x=>x.id))+1:1,tipo:comprobante,serie:comprobante==='BOLETA'?SUC.serieB:SUC.serieF,corr:D.corr[comprobante]++,
    fecha:now(),cliente:clienteTxt,doc:docTxt,subtotal:sub,igv:r2(T-sub),total:T,estado:'EMITIDA',motivo:'',sesion:D.sesion.id,
    items:cart.map(l=>({id:l.id,nombre:l.nombre,qty:l.qty,pu:l.pu,tl:l.tl})),pagos,vuelto:s.vuelto,recibido:s.pd};
  cart.forEach(l=>D.stock[l.id]-=l.qty);
  D.ventas.unshift(venta);
  cart=[];cli={doc:'',nombre:'',found:false};comprobante='BOLETA';
  showTicket(venta,true);renderPOS();
}
function showTicket(v,nuevo){
  open_(`<h3>${nuevo?'✅ Venta registrada':'Comprobante'}</h3><div id="ticket">
   <div class="c"><b>TIENDAS MASS</b><br>${SUC.nombre} · Av. Cayma 410<br>${v.tipo} ELECTRÓNICA<br><b>${comp(v)}</b>${v.estado==='ANULADA'?'<br><b>*** ANULADA ***</b>':''}</div><hr>
   Fecha: ${v.fecha}<br>Cajero: Jorge Salas · Caja 1<br>Cliente: ${esc(v.cliente)}${v.doc?'<br>'+v.doc:''}<hr>
   ${v.items.map(i=>`${esc(i.nombre)}<div class="f"><span>${i.qty} × ${i.pu.toFixed(2)}</span><span>${i.tl.toFixed(2)}</span></div>`).join('')}<hr>
   <div class="f"><span>Subtotal</span><span>${v.subtotal.toFixed(2)}</span></div><div class="f"><span>IGV 18%</span><span>${v.igv.toFixed(2)}</span></div>
   <div class="f"><b>TOTAL</b><b>${money(v.total)}</b></div><hr>
   ${v.pagos.map(p=>`<div class="f"><span>${p.m}${p.ref?' ('+esc(p.ref)+')':''}</span><span>${p.monto.toFixed(2)}</span></div>`).join('')}
   ${v.vuelto?`<div class="f"><span>Vuelto</span><span>${v.vuelto.toFixed(2)}</span></div>`:''}<hr><div class="c">¡Gracias por su compra!</div></div>
   <div class="row"><button class="btn g" onclick="close_()">Cerrar</button><button class="btn" onclick="window.print()">🖨️ Imprimir</button></div>`);
}

function renderVentas(t){
  const all=ventasSesion(),em=all.filter(v=>v.estado==='EMITIDA');
  const rows=all.filter(v=>(comp(v)+v.cliente+v.tipo).toLowerCase().includes(t.toLowerCase()));
  const sum=em.reduce((a,v)=>a+v.total,0);
  $('view').innerHTML=`<div class="kpis">
    <div class="kpi"><p>Vendido en mi turno</p><h2>${money(sum)}</h2><small>${em.length} comprobantes emitidos</small></div>
    <div class="kpi"><p>Boletas</p><h2>${em.filter(v=>v.tipo==='BOLETA').length}</h2><small>Serie ${SUC.serieB}</small></div>
    <div class="kpi"><p>Facturas</p><h2>${em.filter(v=>v.tipo==='FACTURA').length}</h2><small>Serie ${SUC.serieF}</small></div>
    <div class="kpi"><p>Anuladas</p><h2>${all.length-em.length}</h2><small>En este turno</small></div></div>
   <div class="tw"><div class="tools"><h3>Comprobantes <span class="tag b">${rows.length}</span></h3><input class="inp" id="vq" placeholder="Buscar…" value="${esc(t)}" oninput="renderVentas(this.value);const e=$('vq');e.focus();e.setSelectionRange(99,99)"></div>
   <div class="scroll"><table><thead><tr>${['Comprobante','Tipo','Cliente','Hora','Total','Pago','Estado',''].map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>
   ${rows.map(v=>`<tr><td><b>${comp(v)}</b></td><td><span class="tag ${v.tipo==='FACTURA'?'b':''}">${v.tipo}</span></td><td>${esc(v.cliente)}</td><td>${v.fecha.slice(11)}</td><td>${money(v.total)}</td><td>${v.pagos.map(p=>p.m[0]+p.m.slice(1).toLowerCase()).join(' + ')}</td>
     <td><span class="tag ${v.estado==='EMITIDA'?'ok':'bad'}">${v.estado}</span></td>
     <td><button class="act" title="Ver comprobante" onclick="showTicket(D.ventas.find(x=>x.id===${v.id}))">👁️</button>${v.estado==='EMITIDA'&&cajaAbierta()?`<button class="act" title="Anular" onclick="askAnular(${v.id})">🚫</button>`:''}</td></tr>`).join('')}
   </tbody></table>${rows.length?'':'<div class="empty">Sin ventas</div>'}</div></div>`;
}
function askAnular(id){
  const v=D.ventas.find(x=>x.id===id);
  open_(`<h3>Anular ${comp(v)}</h3><p style="color:var(--g4);font-size:14px">Se devolverán los productos al stock de la sucursal.</p>
   <label>Motivo de anulación</label><input class="inp" id="mot" maxlength="150" placeholder="Ej.: error en el producto">
   <div class="row"><button class="btn g" onclick="close_()">Volver</button><button class="btn d" onclick="anular(${id})">Anular comprobante</button></div>`);
  $('mot').focus();
}
function anular(id){
  const m=$('mot').value.trim();if(!m)return toast('Indica el motivo de anulación',true);
  const v=D.ventas.find(x=>x.id===id);v.estado='ANULADA';v.motivo=m;
  v.items.forEach(i=>D.stock[i.id]=(D.stock[i.id]||0)+i.qty);
  close_();toast(`${comp(v)} anulada`);renderVentas('');
}

function resumenCaja(){
  const em=ventasSesion().filter(v=>v.estado==='EMITIDA'),by={EFECTIVO:0,YAPE:0,PLIN:0,TARJETA:0};
  em.forEach(v=>v.pagos.forEach(p=>by[p.m]+=p.monto));
  return {by,total:r2(Object.values(by).reduce((a,b)=>a+b,0)),n:em.length,esperado:r2(D.sesion.inicial+by.EFECTIVO)};
}
function renderCaja(){
  const s=D.sesion,r=resumenCaja(),ab=cajaAbierta();
  $('view').innerHTML=`<div class="kpis">
    <div class="kpi"><p>Estado de la caja</p><h2>${ab?'Abierta':'Cerrada'}</h2><small>${s.apertura?'Apertura '+s.apertura:''}</small></div>
    <div class="kpi"><p>Monto inicial</p><h2>${money(s.inicial)}</h2><small>Caja 1 · ${SUC.nombre}</small></div>
    <div class="kpi"><p>Ventas del turno</p><h2>${money(r.total)}</h2><small>${r.n} comprobantes</small></div>
    <div class="kpi"><p>Efectivo esperado</p><h2>${money(r.esperado)}</h2><small>Inicial + cobros en efectivo</small></div></div>
   <div class="two">
    <div class="card"><h3>Cobros por medio de pago</h3><small>Solo comprobantes emitidos</small>
      <div class="rows">${Object.entries(r.by).map(([k,v])=>`<div><span>${{EFECTIVO:'💵',YAPE:'🟣',PLIN:'🟦',TARJETA:'💳'}[k]} ${k[0]+k.slice(1).toLowerCase()}</span><b>${money(v)}</b></div>`).join('')}<div><span>Total cobrado</span><span>${money(r.total)}</span></div></div></div>
    <div class="card"><h3>${ab?'Cierre de caja':'Apertura de caja'}</h3><small>${ab?'Cuenta el efectivo físico antes de cerrar':'Registra el efectivo con el que inicias'}</small>
      ${ab?`<label style="display:block;font-size:12px;font-weight:600;color:var(--g4);margin:16px 0 5px;text-transform:uppercase">Efectivo contado (S/)</label><input class="inp" id="fin" type="number" step="0.01" min="0" placeholder="0.00" oninput="difCaja()">
        <div class="sum" id="dif"><div><span>Esperado</span><b>${money(r.esperado)}</b></div><div><span>Diferencia</span><b>—</b></div></div>
        <button class="btn k big" style="margin-top:14px" onclick="cerrarCaja()">Cerrar caja</button>`
      :`<div class="sum" style="margin-top:14px"><div><span>Último cierre</span><b>${s.cierre||'—'}</b></div><div><span>Monto final declarado</span><b>${s.final!=null?money(s.final):'—'}</b></div></div><button class="btn y big" style="margin-top:14px" onclick="openCaja()">Abrir caja</button>`}
    </div></div>`;
}
function difCaja(){
  const r=resumenCaja(),v=parseFloat($('fin').value),d=r2(v-r.esperado);
  $('dif').innerHTML=`<div><span>Esperado</span><b>${money(r.esperado)}</b></div><div><span>Diferencia</span><b class="${isNaN(d)?'':d===0?'ok-t':'bad-t'}">${isNaN(d)?'—':(d>0?'+':'')+money(d)+(d===0?' · cuadra':d>0?' · sobra':' · falta')}</b></div>`;
}
function cerrarCaja(){
  const v=parseFloat($('fin').value);if(isNaN(v)||v<0)return toast('Ingresa el efectivo contado',true);
  if(cart.length&&!confirm('Hay una venta sin cobrar. Se descartará. ¿Cerrar la caja?'))return;
  cart=[];Object.assign(D.sesion,{estado:'CERRADA',cierre:now(),final:v});
  toast('Caja cerrada');go('caja');
}
function openCaja(){
  open_(`<h3>Abrir caja</h3><p style="color:var(--g4);font-size:14px">Caja 1 · ${SUC.nombre}</p>
   <label>Monto inicial en efectivo (S/)</label><input class="inp" id="ini" type="number" step="0.01" min="0" value="100">
   <div class="row"><button class="btn g" onclick="close_()">Cancelar</button><button class="btn y" onclick="doOpen()">Abrir caja</button></div>`);
  $('ini').focus();
}
function doOpen(){
  const m=parseFloat($('ini').value);if(isNaN(m)||m<0)return toast('Monto inicial inválido',true);
  D.sesion={id:D.sesion.id+1,apertura:now(),inicial:m,estado:'ABIERTA',cierre:'',final:null};
  close_();toast('Caja abierta');go('pos');
}

function renderClientes(t){
  const rows=D.cliente.filter(c=>c.join(' ').toLowerCase().includes(t.toLowerCase()));
  $('view').innerHTML=`<div class="tw"><div class="tools"><h3>Clientes <span class="tag b">${rows.length}</span></h3><input class="inp" id="cq" placeholder="Buscar por DNI, RUC o nombre…" value="${esc(t)}" oninput="renderClientes(this.value);const e=$('cq');e.focus();e.setSelectionRange(99,99)"></div>
   <div class="scroll"><table><thead><tr>${['#','Tipo doc.','N.º documento','Nombre'].map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>
   ${rows.map(c=>`<tr><td>${c[0]}</td><td><span class="tag ${c[1]==='RUC'?'b':''}">${c[1]}</span></td><td>${c[2]}</td><td>${esc(c[3])}</td></tr>`).join('')}</tbody></table>${rows.length?'':'<div class="empty">Sin resultados</div>'}</div></div>`;
}

document.addEventListener('keydown',e=>{
  if(e.key==='F2'){e.preventDefault();if(view!=='pos')go('pos');setTimeout(()=>$('q')&&$('q').focus(),0)}
  if(e.key==='F9'){e.preventDefault();if(view==='pos'&&cajaAbierta()&&!$('modal').classList.contains('on'))checkout()}
  if(e.key==='Escape')close_();
});
buildNav();go('pos');