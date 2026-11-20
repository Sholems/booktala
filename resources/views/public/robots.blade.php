# BookTala Robots Directives
# Luxury Stays, Travels & Better Living by G-Briel Property Ltd

User-agent: *
Allow: /
Disallow: /admin/
Disallow: /customer/
Disallow: /staff/
Disallow: /front-desk/
Disallow: /booking/webhook
Disallow: /booking/*/step
Disallow: /storage/*.pdf
Disallow: /login
Disallow: /register
Disallow: /password/

# Welcoming Major Search Engines
User-agent: Googlebot
Allow: /
Disallow: /admin/
Disallow: /customer/

User-agent: Bingbot
Allow: /
Disallow: /admin/
Disallow: /customer/

# Welcoming Generative AI & Answer Engines (GEO & AEO Discovery)
User-agent: GPTBot
Allow: /
Disallow: /admin/
Disallow: /customer/

User-agent: PerplexityBot
Allow: /
Disallow: /admin/
Disallow: /customer/

User-agent: ClaudeBot
Allow: /
Disallow: /admin/
Disallow: /customer/

User-agent: Google-Extended
Allow: /
Disallow: /admin/
Disallow: /customer/

User-agent: Applebot-Extended
Allow: /
Disallow: /admin/
Disallow: /customer/

# Dynamic XML Sitemap
Sitemap: {{ route('sitemap') }}
