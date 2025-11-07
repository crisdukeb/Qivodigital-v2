/* QivoDigital — Router mínimo sin romper el diseño */
(function(){
  const toggle = document.getElementById('qvToggle');

  function closeSheet(){
    if (toggle) toggle.checked = false;
  }

  function go(where){
    if (!where) return;
    // Cerrar menú siempre antes de navegar
    closeSheet();

    // Rutas canónicas
    if (where === 'home'){ window.location.assign('/'); return; }
    if (where.startsWith('home#')){ window.location.assign('/#' + where.split('#')[1]); return; }
    if (where === 'servicios'){ window.location.assign('/servicios/'); return; }

    // Fallback
    window.location.assign(where);
  }

  // Delegación para items del header/sheet con data-goto
  document.addEventListener('click', (e)=>{
    const trg = e.target.closest('[data-goto]');
    if (!trg) return;
    e.preventDefault();
    go(trg.getAttribute('data-goto'));
  }, {passive:false});

  // Smooth scroll solo si el destino existe en ESTA página
  document.addEventListener('click', (e)=>{
    const a = e.target.closest('a[href^="#"]');
    if(!a) return;
    const id = a.getAttribute('href');
    const el = document.querySelector(id);
    if(el){
      e.preventDefault();
      closeSheet();
      el.scrollIntoView({behavior:'smooth', block:'start'});
    }
  });

  // Header: ocultar al bajar / mostrar al subir (suave)
  (function(){
    const hdr = document.getElementById('qv-header');
    if(!hdr) return;
    let lastY = window.scrollY, ticking = false;
    function onScroll(){
      const y = window.scrollY;
      const hide = (y > lastY && y > 80);
      hdr.style.transform = hide ? 'translateY(-100%)' : 'translateY(0)';
      lastY = y; ticking = false;
    }
    window.addEventListener('scroll', ()=>{
      if(!ticking){ window.requestAnimationFrame(onScroll); ticking = true; }
    }, {passive:true});
  })();

  // Cerrar sheet si hacen tap fuera (por si tu HTML no tenía el label/backdrop)
  document.querySelectorAll('[data-close-sheet]').forEach(el=>{
    el.addEventListener('click', closeSheet);
  });
})();
