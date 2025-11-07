document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('#testimonios blockquote');
  cards.forEach(card => {
    const img = card.querySelector('.avatar img');
    const name = (card.querySelector('.meta b')?.textContent || 'Qivo').trim();
    const initials = name.split(/\s+/).slice(0,2).map(s=>s[0]).join('').toUpperCase();

    const svg = encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96">
        <defs>
          <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#27335A"/>
            <stop offset="100%" stop-color="#0f172a"/>
          </linearGradient>
        </defs>
        <rect width="96" height="96" rx="48" fill="url(#g)"/>
        <text x="50%" y="58%" text-anchor="middle" font-size="34" font-family="system-ui,Segoe UI,Roboto" fill="#ffffff" font-weight="700">${initials}</text>
      </svg>
    `);
    const fallback = `data:image/svg+xml;charset=utf8,${svg}`;

    // si falla, usa SVG con iniciales
    img.onerror = () => { img.src = fallback; };
    // si ya viene rota o vacía, forzar fallback
    if (!img.getAttribute('src') || (img.complete && img.naturalWidth === 0)) {
      img.src = fallback;
    }
  });
});
