#!/usr/bin/env bash
set -euo pipefail

DOMAIN="https://qivodigital.com"
OG_DEFAULT="/assets/img/og-default.jpg"  # pon tu imagen OG aquí si quieres
TIME="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

# Recorre todos los .html (home y subcarpetas)
mapfile -t FILES < <(find . -type f -name "*.html" | sort)

for FILE in "${FILES[@]}"; do
  # Lee contenido
  HTML="$(cat "$FILE")"

  # Detecta <title> y <meta name="description">
  TITLE="$(printf '%s' "$HTML" | sed -n 's/.*<title>\(.*\)<\/title>.*/\1/p' | head -n1)"
  DESC="$(printf '%s' "$HTML" | sed -n 's/.*<meta[^>]*name=["'\'']description["'\''][^>]*content=["'\'']\([^"'\''>]*\)["'\''][^>]*>.*/\1/p' | head -n1)"

  # Si faltan, pon valores seguros
  [[ -z "$TITLE" ]] && TITLE="QivoDigital — Tecnología que transforma negocios"
  [[ -z "$DESC"  ]] && DESC="Software a medida, automatización con IA, ecommerce, integraciones, chatbots y CRM. Operación ágil, escalable y precisa."

  # Calcula canonical desde la ruta del archivo
  # ./index.html -> /
  # ./servicios/web-apps/index.html -> /servicios/web-apps/
  REL="${FILE#./}"
  if [[ "$REL" == "index.html" ]]; then
    PATH_CANON="/"
  else
    PATH_CANON="/${REL%index.html}"
  fi
  CANON="$DOMAIN${PATH_CANON}"

  # Bloque SEO a inyectar
  read -r -d '' SEO <<EOF
  <!-- SEO-BLOCK START (auto) -->
  <link rel="canonical" href="${CANON}"/>
  <meta name="description" content="${DESC}"/>
  <meta name="theme-color" content="#0C1B2A"/>

  <!-- Open Graph -->
  <meta property="og:type" content="website"/>
  <meta property="og:site_name" content="QivoDigital"/>
  <meta property="og:title" content="${TITLE}"/>
  <meta property="og:description" content="${DESC}"/>
  <meta property="og:url" content="${CANON}"/>
  <meta property="og:image" content="${OG_DEFAULT}"/>

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image"/>
  <meta name="twitter:title" content="${TITLE}"/>
  <meta name="twitter:description" content="${DESC}"/>
  <meta name="twitter:image" content="${OG_DEFAULT}"/>

  <!-- JSON-LD: Website + Organization -->
  <script type="application/ld+json">{
    "@context":"https://schema.org",
    "@graph":[
      {
        "@type":"WebSite",
        "url":"${DOMAIN}",
        "name":"QivoDigital",
        "inLanguage":"es",
        "potentialAction":{
          "@type":"SearchAction",
          "target":"${DOMAIN}/?q={search_term_string}",
          "query-input":"required name=search_term_string"
        }
      },
      {
        "@type":"Organization",
        "name":"QivoDigital",
        "url":"${DOMAIN}",
        "logo":"${DOMAIN}/assets/img/og-default.jpg",
        "sameAs":[]
      }
    ]
  }</script>
  <!-- SEO-BLOCK END (auto) -->
EOF

  # 1) Elimina bloque anterior si existe
  HTML="$(printf '%s' "$HTML" | awk '
    BEGIN{skip=0}
    /<!-- SEO-BLOCK START \(auto\) -->/{skip=1}
    !skip{print}
    /<!-- SEO-BLOCK END \(auto\) -->/{skip=0}
  ')"

  # 2) Inserta justo antes de </head>
  if grep -qi '</head>' <<<"$HTML"; then
    HTML="$(printf '%s' "$HTML" | sed "0,/<\/head>/s//${SEO//$'\n'/\\n}\n<\/head>/")"
  else
    # si por alguna razón no tiene </head>, no tocamos ese archivo
    echo "⚠  Sin </head>: $FILE — omitido"
    continue
  fi

  # 3) Escribe de vuelta (sin backups múltiples)
  printf '%s' "$HTML" > "$FILE"
  echo "✓ SEO aplicado: $FILE"
done

echo "Hecho ${TIME}"
