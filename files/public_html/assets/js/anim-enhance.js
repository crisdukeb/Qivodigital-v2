(()=>{"use strict";
/* ===== Animaciones PRO (suaves) — NO tocan #qv-wrap (header) ni #qvSheet (menú) ===== */
const prefersReduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

/* Inyecta CSS escopado a .ae-* (transiciones suaves) */
(function injectCss(){
  if(document.querySelector('style[data-ae]')) return;
  const css = `
@media (prefers-reduced-motion:no-preference){
  .ae-fx{opacity:0;transform:translate3d(0,12px,0) scale(.995);filter:blur(.6px);will-change:transform,opacity}
  .ae-up{transform:translate3d(0,20px,0) scale(.995)}
  .ae-left{transform:translate3d(-22px,0,0) scale(.995)}
  .ae-right{transform:translate3d(22px,0,0) scale(.995)}
  .ae-zoom{transform:translate3d(0,8px,0) scale(.96);filter:blur(.8px)}
  .ae-in{
    opacity:1;transform:translate3d(0,0,0) scale(1);filter:blur(0);
    transition:
      transform .42s cubic-bezier(.20,.8,.20,1),
      opacity  .42s cubic-bezier(.20,.8,.20,1),
      filter   .42s cubic-bezier(.20,.8,.20,1);
  }
}
@media (prefers-reduced-motion:reduce){
  .ae-fx{opacity:1!important;transform:none!important;filter:none!important}
}`;
  const s=document.createElement('style');
  s.setAttribute('data-ae','1');
  s.textContent=css;
  document.head.appendChild(s);
})();

/* Helpers */
const isExcluded = (el)=> !!(el.closest('#qv-wrap') || el.closest('#qvSheet') || el.closest('[data-no-anim]'));
const mark = (el, ...cls)=>{ if(!el || isExcluded(el) || el.classList.contains('ae-fx')) return; el.classList.add('ae-fx', ...cls); };

/* Autotag sólo en contenido, nunca en header/menú */
function autotag(){
  // HERO columnas
  document.querySelectorAll('.hero .grid > div').forEach((el,i)=> mark(el, i===0?'ae-left':'ae-right'));

  // h2 de secciones
  document.querySelectorAll('section h2').forEach(el=> mark(el,'ae-up'));

  // Tarjetas
  const cards = document.querySelectorAll('.qv-card');
  cards.forEach((el,i)=> mark(el, (i%2)?'ae-left':'ae-right'));

  // Listas con stagger (sólo hijos directos)
  document.querySelectorAll('.qv-list').forEach(ul=>{
    if(isExcluded(ul)) return;
    if(!ul.hasAttribute('data-ae-stagger')) ul.setAttribute('data-ae-stagger','60');
    ul.querySelectorAll(':scope > li').forEach(li=> mark(li,'ae-up'));
  });

  // Formularios
  document.querySelectorAll('.qv-form').forEach(el=> mark(el,'ae-zoom'));

  // Stack (testimonios)
  document.querySelectorAll('.qv-stack > *').forEach((el,i)=>{
    if(isExcluded(el)) return;
    mark(el, (i%3===0?'ae-zoom': i%3===1?'ae-left':'ae-right'));
  });
}

/* Observer con umbral suave y poco trabajo en main thread */
function runObserver(){
  const targets=[...document.querySelectorAll('.ae-fx')].filter(el=>!isExcluded(el));
  if(!targets.length) return;

  if(typeof IntersectionObserver!=='function'){
    targets.forEach(n=>n.classList.add('ae-in')); return;
  }

  const io=new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(!entry.isIntersecting) return;
      const el=entry.target;
      let delay=0;
      const p=el.parentElement;
      if(p && p.hasAttribute('data-ae-stagger')){
        const step=Number(p.getAttribute('data-ae-stagger'))||60;
        const sib=[...p.children].filter(n=>n.classList?.contains('ae-fx'));
        const idx=sib.indexOf(el);
        delay = Math.max(0, idx*step);
      }
      el.style.transitionDelay = `${delay}ms`;
      el.classList.add('ae-in');
      io.unobserve(el);
    });
  },{threshold:.20, rootMargin:'0px 0px -10%'});

  targets.forEach(n=>io.observe(n));
}

/* Init */
function onReady(){
  autotag();
  if(!prefersReduce) runObserver();
}
if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', onReady, {once:true}); }
else{ onReady(); }

/* Reintento por contenidos inyectados por fetch (header/footer ya están excluidos) */
setTimeout(()=>{ try{autotag(); if(!prefersReduce) runObserver();}catch{} }, 500);
})();
