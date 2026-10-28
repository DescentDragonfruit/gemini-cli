import sys,re,json,html
from html.parser import HTMLParser
p=sys.argv[1]; s=open(p,encoding='utf-8',errors='ignore').read()
def f(rx):
    m=re.search(rx,s,re.I|re.S); return html.unescape(m.group(1).strip()) if m else None
print('bytes',len(s))
print('title:',f(r'<title[^>]*>(.*?)</title>'))
print('desc:',f(r'<meta name="description" content="([^"]*)"'))
print('robots:',f(r'<meta name="robots" content="([^"]*)"'))
print('canonical:',f(r'<link rel="canonical" href="([^"]*)"'))
print('og:image:',f(r'<meta property="og:image" content="([^"]*)"'))
print('h1s:',[re.sub('<[^>]+>','',h).strip()[:80] for h in re.findall(r'<h1[^>]*>(.*?)</h1>',s,re.S|re.I)])
print('h2 count:',len(re.findall(r'<h2[\s>]',s,re.I)),'h3:',len(re.findall(r'<h3[\s>]',s,re.I)))
types=[]
for b in re.findall(r'<script[^>]*application/ld\+json[^>]*>(.*?)</script>',s,re.S):
    try:
        d=json.loads(b); g=d.get('@graph',[d]) if isinstance(d,dict) else d
        for x in g: types.append(x.get('@type'))
        for x in g:
            if x.get('@type') in ('Person',) : print('person:',json.dumps(x)[:400])
            if x.get('@type') in ('Organization','NewsMediaOrganization'): print('org:',json.dumps(x)[:500])
            if 'Article' in str(x.get('@type')) or 'Review' in str(x.get('@type')) or 'Hotel' in str(x.get('@type')): print('art:',json.dumps(x)[:600])
    except Exception as e: types.append('PARSE_ERR')
print('schema:',types)
imgs=re.findall(r'<img\b[^>]*>',s,re.I)
noalt=[i for i in imgs if not re.search(r'alt="[^"]+"',i)]
print('imgs',len(imgs),'no/empty alt',len(noalt),'lazy',sum('loading="lazy"' in i for i in imgs),'webp/avif',sum(bool(re.search(r'\.(webp|avif)',i)) for i in imgs))
body=re.sub(r'<(script|style|noscript)[^>]*>.*?</\1>','',s,flags=re.S|re.I)
m=re.search(r'<main[^>]*>(.*)</main>',body,re.S) or re.search(r'<article[^>]*>(.*)</article>',body,re.S)
txt=re.sub(r'<[^>]+>',' ',m.group(1) if m else body); txt=html.unescape(re.sub(r'\s+',' ',txt))
print('words(main):',len(txt.split()))
print('scripts:',len(re.findall(r'<script',s)),'inline style bytes',sum(len(x) for x in re.findall(r'<style[^>]*>(.*?)</style>',s,re.S)))
print('author mention:',bool(re.search(r'Coco Tran',txt)), 'updated:',bool(re.search(r'(Updated|Last updated)',txt,re.I)))
print('TEXT START:',txt[:700])
