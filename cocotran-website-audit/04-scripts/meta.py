import sys,re,html,subprocess,csv,time
from concurrent.futures import ThreadPoolExecutor
def get(u):
    for _ in range(3):
        r=subprocess.run(['curl','-sS','-A','Mozilla/5.0','--max-time','40',u],capture_output=True,text=True)
        s=r.stdout
        if len(s)>5000: break
        time.sleep(2)
    def f(rx):
        m=re.search(rx,s,re.I|re.S); return html.unescape(m.group(1).strip()) if m else ''
    b=re.sub(r'<(script|style|noscript|header|footer|nav)[^>]*>.*?</\1>','',s,flags=re.S|re.I)
    t=html.unescape(re.sub(r'\s+',' ',re.sub(r'<[^>]+>',' ',b))).split('sign up for')[0]
    h1=' | '.join(re.sub('<[^>]+>','',h).strip() for h in re.findall(r'<h1[^>]*>(.*?)</h1>',s,re.S|re.I))
    return [u,f(r'<title[^>]*>(.*?)</title>'),f(r'<meta name="description" content="([^"]*)"'),h1,len(t.split()),int('FOR MEMBERS ONLY' in s),len(re.findall(r'<img\b',s)),len([i for i in re.findall(r'<img\b[^>]*>',s) if not re.search(r'alt="[^"]+"',i)])]
urls=[l.strip() for l in open(sys.argv[1]) if l.strip()]
with ThreadPoolExecutor(6) as ex: rows=list(ex.map(get,urls))
w=csv.writer(open(sys.argv[2],'w',newline='')); w.writerow(['url','title','meta_description','h1','visible_words','paywalled','images','images_missing_alt']); w.writerows(rows)
