const { chromium } = require('playwright');
(async()=>{const b=await chromium.launch();
for (const u of process.argv.slice(2)){const c=await b.newContext({viewport:{width:390,height:844},userAgent:'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1'});const p=await c.newPage();let bytes=0,reqs=0;p.on('response',async r=>{reqs++;const l=r.headers()['content-length'];if(l)bytes+=+l;});
await p.addInitScript(()=>{window.__lcp=0;window.__cls=0;new PerformanceObserver(l=>{for(const e of l.getEntries())window.__lcp=e.startTime}).observe({type:'largest-contentful-paint',buffered:true});new PerformanceObserver(l=>{for(const e of l.getEntries())if(!e.hadRecentInput)window.__cls+=e.value}).observe({type:'layout-shift',buffered:true});});
const t=Date.now();await p.goto(u,{waitUntil:'load',timeout:90000});await p.waitForTimeout(3000);
const m=await p.evaluate(()=>({lcp:Math.round(window.__lcp),cls:+window.__cls.toFixed(3),dom:document.getElementsByTagName('*').length,ov:document.documentElement.scrollWidth>window.innerWidth}));
console.log(u,JSON.stringify({...m,loadMs:Date.now()-t,reqs,MB:(bytes/1e6).toFixed(1)}));await p.screenshot({path:'shot_'+u.split('/').filter(Boolean).pop()+'.png'});await c.close();}
await b.close();})();
