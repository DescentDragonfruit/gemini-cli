const { chromium } = require('playwright');
(async()=>{const b=await chromium.launch();const p=await b.newPage({viewport:{width:390,height:844}});const L=[];
p.on('response',r=>{const l=+(r.headers()['content-length']||0);L.push([l,r.request().resourceType(),r.url().slice(0,140)])});
await p.goto(process.argv[2],{waitUntil:'load',timeout:90000});await p.waitForTimeout(3000);
L.sort((a,b)=>b[0]-a[0]);L.slice(0,10).forEach(x=>console.log((x[0]/1e6).toFixed(2)+'MB',x[1],x[2]));await b.close();})();
