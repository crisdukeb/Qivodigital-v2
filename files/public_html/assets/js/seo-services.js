(function(){
  // Mapa: carpeta => {title, desc, emoji}
  const MAP = {
    "whatsapp": {
      title: "WhatsApp Marketing / API — QivoDigital",
      desc: "Campañas, disparadores, flujos y API de WhatsApp con métricas y automatización end-to-end para vender más.",
      emoji: "📲"
    },
    "automatizacion": {
      title: "Automatización de Procesos — QivoDigital",
      desc: "Elimina tareas repetitivas con flujos, bots y orquestación de sistemas para eficiencia real.",
      emoji: "⚙️"
    },
    "ecommerce": {
      title: "Tiendas Online / Ecommerce — QivoDigital",
      desc: "Ecommerce de alto rendimiento: catálogo, pagos, logística, analítica y growth integrado.",
      emoji: "🛒"
    },
    "software-medida": {
      title: "Software a Medida — QivoDigital",
      desc: "Sistemas internos, paneles, SaaS y microservicios diseñados exactamente a tu operación.",
      emoji: "🧩"
    },
    "integraciones": {
      title: "Integraciones / APIs — QivoDigital",
      desc: "Conecta ERPs, CRMs, pasarelas de pago y proveedores con APIs robustas y seguras.",
      emoji: "🔗"
    },
    "web-apps": {
      title: "Diseño y Desarrollo Web — QivoDigital",
      desc: "Sitios y apps web rápidos, SEO-first, mobile-first y listos para escalar.",
      emoji: "💻"
    },
    "apps": {
      title: "Apps Móviles — QivoDigital",
      desc: "Apps nativas/híbridas con push, pagos, geolocalización y analítica.",
      emoji: "📱"
    },
    "chatbots": {
      title: "Chatbots e IA — QivoDigital",
      desc: "Bots con IA para ventas y soporte, integrados a tus canales y datos.",
      emoji: "🤖"
    },
    "crm": {
      title: "CRM a Medida — QivoDigital",
      desc: "Pipelines, automatización y reporting a la medida de tu proceso comercial.",
      emoji: "📊"
    }
  };

  // Detectar carpeta /servicios/<slug>/
  const parts = location.pathname.split("/").filter(Boolean);
  const idx = parts.indexOf("servicios");
  if (idx === -1 || !parts[idx+1]) return; // salir si no estamos en /servicios/*
  const slug = parts[idx+1];
  const data = MAP[slug] || {title: document.title, desc: "", emoji:""};

  // ---- <title> y <meta name="description">
  document.title = data.title;
  let mdesc = document.querySelector('meta[name="description"]');
  if(!mdesc){ mdesc = document.createElement("meta"); mdesc.setAttribute("name","description"); document.head.appendChild(mdesc); }
  mdesc.setAttribute("content", data.desc);

  // ---- canonical
  const canonicalURL = location.origin + location.pathname;
  let linkCanon = document.querySelector('link[rel="canonical"]');
  if(!linkCanon){ linkCanon = document.createElement("link"); linkCanon.rel = "canonical"; document.head.appendChild(linkCanon); }
  linkCanon.href = canonicalURL;

  // ---- OpenGraph / Twitter
  function setMeta(p, c){ let m = document.querySelector(`meta[property="${p}"],meta[name="${p}"]`);
    if(!m){ m = document.createElement("meta"); if(p.startsWith("og:")) m.setAttribute("property",p); else m.setAttribute("name",p); document.head.appendChild(m); }
    m.setAttribute("content", c);
  }
  setMeta("og:title", data.title);
  setMeta("og:description", data.desc);
  setMeta("og:type", "website");
  setMeta("og:url", canonicalURL);
  setMeta("twitter:card","summary_large_image");
  setMeta("twitter:title", data.title);
  setMeta("twitter:description", data.desc);

  // ---- H1 y lead inicial
  const main = document.querySelector("main.container") || document.querySelector("main") || document.body;
  const h1 = main.querySelector("h1") || (function(){const el=document.createElement("h1"); el.style.marginTop="100px"; main.prepend(el); return el;})();
  h1.textContent = `${data.emoji} ${data.title.replace(" — QivoDigital","")}`;
  const lead = main.querySelector(".lead") || main.querySelector("p");
  if (lead && data.desc) lead.textContent = data.desc;

  // ---- JSON-LD Organization (simple)
  function addJSONLD(obj){
    const s = document.createElement("script");
    s.type = "application/ld+json";
    s.text = JSON.stringify(obj);
    document.head.appendChild(s);
  }
  addJSONLD({
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "QivoDigital",
    "url": location.origin,
    "logo": location.origin + "/assets/img/logo-qivo.png",
    "sameAs": []
  });

  // ---- JSON-LD Service
  addJSONLD({
    "@context": "https://schema.org",
    "@type": "Service",
    "name": data.title.replace(" — QivoDigital",""),
    "provider": { "@type": "Organization", "name": "QivoDigital", "url": location.origin },
    "areaServed": ["Colombia","LatAm"],
    "url": canonicalURL,
    "description": data.desc
  });

  // ---- JSON-LD FAQ (plantilla mínima; ajústala luego)
  addJSONLD({
    "@context":"https://schema.org",
    "@type":"FAQPage",
    "mainEntity":[
      {"@type":"Question","name":"¿Qué incluye este servicio?","acceptedAnswer":{"@type":"Answer","text":"Análisis, diseño, implementación y soporte continuo, con integraciones según tu stack."}},
      {"@type":"Question","name":"¿En cuánto tiempo se implementa?","acceptedAnswer":{"@type":"Answer","text":"Dependiendo del alcance, de 2 a 8 semanas. Entregamos por fases con hitos claros."}}
    ]
  });
})();
