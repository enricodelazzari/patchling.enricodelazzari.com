const MONTH_S = 5, TOTAL = 36, PHP_MONTH = 18, MAX_E = 10;
const $ = id => document.getElementById(id);
const clamp = (v,a=0,b=100) => Math.max(a, Math.min(b, v));
const rnd = Math.random;
let S, last = 0, raf = null;

const ACTIONS = [
  {id:"feat", icon:"✨", name:"New feature", cost:()=>4, hint:"Happier users, more debt"},
  {id:"test", icon:"🧪", name:"Write tests",    cost:()=>3, hint:"Fewer new bugs"},
  {id:"upd",  icon:"🔄", name:"Update",       cost:()=>Math.min(MAX_E, 2+Math.floor(S.age/4)), hint:()=>S.age<1?"Already up to date":`Site offline for ${updDur().toFixed(0)}s`},
  {id:"ref",  icon:"🧹", name:"Clean up code", cost:()=>3, hint:"Less tech debt"}
];
const updDur = () => 1.5 + S.age*0.45;

function newGame(){
  S = {m:0, coins:0, energy:6, happy:70, health:100, debt:15, test:20, age:1, feat:3,
       bugs:[], bugId:0, busy:null, event:null, nextEventAt:14, phpDone:false, php:"", breaches:0,
       infected:false, eol:false, stats:{feat:0,test:0,upd:0,ref:0,squash:0}, maxAge:1, diary:[], paused:false, over:false};
  $("bugs").innerHTML=""; $("eventBox").innerHTML=""; $("diary").innerHTML="";
  buildActions();
  note("The site is live. Three features, a few bugs, everything to look after.");
}

/* ---------- diario e pop ---------- */
function note(t, cls=""){
  S.diary.unshift([`Month ${Math.floor(S.m)+1}: ${t}`, cls]);
  S.diary = S.diary.slice(0,6);
  $("diary").innerHTML = S.diary.map(d=>`<li class="${d[1]}">${d[0]}</li>`).join("");
}
function pop(text, cls, x, y){
  const st = $("stage"), el = document.createElement("div");
  el.className = "pop "+cls; el.textContent = text;
  el.style.left = (x ?? st.clientWidth/2-20)+"px"; el.style.top = (y ?? 110)+"px";
  st.appendChild(el); setTimeout(()=>el.remove(),1100);
}

/* ---------- insetti ---------- */
function spawnBug(n=1){
  const st = $("stage");
  for(let i=0;i<n;i++){
    if(S.bugs.length>=14) return;
    const b = {id:++S.bugId, x:20+rnd()*(st.clientWidth-64), y:60+rnd()*170, vx:(rnd()-.5)*60, vy:(rnd()-.5)*50};
    const el = document.createElement("button");
    el.className="bug"; el.textContent="🐛"; el.setAttribute("aria-label","Squash a bug");
    el.onclick = ()=>squash(b.id, true);
    b.el = el; $("bugs").appendChild(el); S.bugs.push(b);
  }
}
function squash(id, byHand){
  const i = S.bugs.findIndex(b=>b.id===id); if(i<0) return;
  const b = S.bugs[i]; S.bugs.splice(i,1);
  b.el.classList.add("squash"); setTimeout(()=>b.el.remove(),300);
  if(byHand){ S.stats.squash++; S.happy = clamp(S.happy+1.5); pop("+1","pos",b.x,b.y); }
}
function squashSome(n){ for(let i=0;i<n && S.bugs.length;i++) squash(S.bugs[0].id,false); }

/* ---------- azioni ---------- */
function buildActions(){
  $("actions").innerHTML = ACTIONS.map(a=>`<button id="a-${a.id}"><span class="cost"></span><b>${a.icon} ${a.name}</b><small></small></button>`).join("") + `<div class="busy" id="busy" hidden></div>`;
  ACTIONS.forEach(a=> $("a-"+a.id).onclick = ()=>doAction(a));
  $("cells").innerHTML = Array.from({length:MAX_E},()=>"<i></i>").join("");
}
function doAction(a){
  const c = a.cost(); if(S.energy<c || S.busy || S.over) return;
  S.energy -= c; S.stats[a.id]++;
  if(a.id==="feat"){
    const slow = S.debt>65;
    S.feat++; S.happy=clamp(S.happy+(slow?12:22)); S.debt=clamp(S.debt+12); S.test=clamp(S.test-10);
    const nb = Math.round(2.4*(1-S.test/100)); spawnBug(nb);
    pop("+ feature","pos");
    note(slow ? "New feature shipped, but tech debt made it slow to build and less polished." :
         `New feature live${nb?`, with ${nb} new bug${nb>1?"s":""}`:""}.`, slow?"neg":"pos");
  }
  if(a.id==="test"){
    S.test=clamp(S.test+30); const n=Math.min(3,S.bugs.length); squashSome(n);
    pop("+ tests","pos"); note(`Tests caught ${n} bug${n===1?"":"s"} before users did.`,"pos");
  }
  if(a.id==="ref"){
    S.debt=clamp(S.debt-25); squashSome(1); pop("− debt","pos");
    note("Code cleaned up: the next changes will take less effort.","pos");
  }
  if(a.id==="upd"){
    const d = updDur(); S.busy = {t:0, d, fromAge:S.age};
    note(S.age>6 ? `Long update: components were ${Math.round(S.age)} months old and other parts need adapting. Site offline.` : "Quick update in progress.", S.age>6?"neg":"");
  }
}
function finishUpdate(){
  const from = S.busy.fromAge; S.busy=null; S.age=0; S.eol=false;
  if(S.infected){ S.infected=false; note("Update complete: the hole is closed and the site is clean again.","pos"); }
  if(S.test<40){ spawnBug(3); note("Updated, but without enough tests something broke: 3 new bugs.","neg"); }
  else note(`Components updated${from>6?" (finally)":""}.`,"pos");
  if(S.event && S.event.security) resolveEvent(true);
}

/* ---------- eventi ---------- */
function showEvent(ev){
  S.event = {...ev, t:0};
  $("stage").classList.toggle("alarm", !!ev.security);
  const box = $("eventBox");
  box.innerHTML = `<div class="event ${ev.security?"danger":""}"><h3>${ev.title}</h3><p>${ev.text}</p>
    ${ev.deadline?`<div class="countdown"><div id="cd"></div></div>`:""}
    <div class="btns">${(ev.buttons||[]).map((b,i)=>`<button data-i="${i}">${b.label}</button>`).join("")}</div></div>`;
  box.querySelectorAll(".btns button").forEach(b=> b.onclick = ()=>{ ev.buttons[b.dataset.i].fn(); closeEvent(); });
}
function closeEvent(){ S.event=null; $("eventBox").innerHTML=""; $("stage").classList.remove("alarm"); S.nextEventAt = S.m + 3 + rnd()*3; }
function resolveEvent(ok){ const e=S.event; closeEvent(); ok ? e.onSuccess?.() : e.onFail?.(); }

function phpEvent(){
  S.phpDone = true;
  const fresh = S.age<=3;
  showEvent({ security:true, deadline:14,
    title:"🚨 Critical flaw in the site’s engine",
    text: `Two critical vulnerabilities in the component that runs your site. Update before time runs out! ${fresh?"Your components are recent: this will be quick.":`Your components are ${Math.round(S.age)} months old: the update will take a while.`}`,
    buttons:[],
    onSuccess:()=>{ S.php = fresh?"rapido":"lento"; },
    onFail:()=>{ S.php="violato"; S.infected=true; S.breaches++; S.health=clamp(S.health-35); S.happy=clamp(S.happy-30);
      note("Attack succeeded: user data exposed and pages defaced. The site stays infected until you update.","neg"); pop("HACKED","neg"); }
  });
  if(S.busy) S.php = "in corso";
}
const POOL = [
  ()=> S.age<=3 ? (note("Vulnerability in a third-party library: your components were up to date, no harm done.","pos"), null) : ({
    security:true, deadline:10, title:"Vulnerable library",
    text:"The payments component has a security hole. Update before the timer runs out, or risk an attack.", buttons:[],
    onFail:()=>{ if(rnd()<.6){ S.breaches++; S.health=clamp(S.health-25); S.happy=clamp(S.happy-15); note("Someone exploited the library vulnerability.","neg"); pop("hacked","neg"); } else note("Nobody exploited the hole. This time.",""); }
  }),
  ()=>{ const slow = S.debt>50; if(slow){ S.health=clamp(S.health-15); S.happy=clamp(S.happy-10); note("Traffic spike: code full of shortcuts can’t cope and the site slows down.","neg"); pop("slow!","neg"); }
        else { S.coins+=15; S.happy=clamp(S.happy+6); note("Traffic spike handled smoothly: +€15k.","pos"); pop("+€15k","pos"); } return null; },
  ()=>{ if(S.age>6){ spawnBug(4); note("Browsers updated: old components start showing weird bugs.","neg"); }
        else note("Browsers updated. Your components are recent: no effect.","pos"); return null; },
  ()=>{ const hard = S.test<40 || S.debt>50; if(hard){ S.energy=0; note("A developer leaves. With no tests and messy code, the newcomer struggles to understand it: energy drained.","neg"); }
        else note("A developer leaves, but clean code and tests help the newcomer get up to speed.","pos"); return null; },
  ()=>({ title:"A client wants a feature yesterday", text:"It can be done right away, but only by cutting corners.", deadline:12,
    buttons:[
      {label:"Rush it", fn:()=>{ S.feat++; S.coins+=12; S.debt=clamp(S.debt+25); spawnBug(2); note("Feature rushed out: +€12k, but lots of tech debt.",""); }},
      {label:"Explain the timeline", fn:()=>{ S.happy=clamp(S.happy-8); note("Unhappy client, but the code stays clean.",""); }}
    ], onFail:()=>{ S.happy=clamp(S.happy-8); note("No answer to the client: they complained.","neg"); } }),
  ()=>{ if(S.age>=8 && !S.eol){ S.eol=true; note("End of support: the vendor will no longer fix the version you’re using.","neg"); } else return POOL[1](); return null; }
];

/* ---------- loop ---------- */
function tick(dt){
  if(S.paused || S.over) return;
  const prevMonth = Math.floor(S.m);
  S.m += dt/MONTH_S; S.age += dt/MONTH_S; S.maxAge = Math.max(S.maxAge, S.age);
  if(Math.floor(S.m)!==prevMonth && S.m>=TOTAL) return finish();
  S.energy = Math.min(MAX_E, S.energy + dt*0.85);

  const offline = !!S.busy, nb = S.bugs.length;
  S.happy = clamp(S.happy - dt*(0.9 + nb*0.22 + (offline?1.6:0) + (S.infected?1:0)));
  S.test  = clamp(S.test - dt*0.25);
  S.debt  = clamp(S.debt + dt*0.12);
  const spawnRate = (0.07 + S.debt/100*0.22 + (S.age>8?0.08:0)) * (1 - S.test/130);
  if(rnd() < spawnRate*dt) spawnBug(1);
  let dh = 0;
  if(nb>4) dh -= (nb-4)*0.55;
  if(S.debt>70) dh -= 0.7;
  if(S.infected) dh -= 1.6;
  if(S.eol) dh -= 0.3;
  if(dh===0 && nb<=2 && S.debt<60) dh = 0.8;
  S.health = clamp(S.health + dh*dt);
  if(!offline) S.coins += dt * S.feat * 0.22 * (S.happy/100);

  if(S.busy){ S.busy.t += dt; if(S.busy.t>=S.busy.d) finishUpdate(); }
  if(S.event && S.event.deadline){ S.event.t += dt; if(S.event.t>=S.event.deadline) resolveEvent(false); }
  if(!S.event){
    if(!S.phpDone && S.m>=PHP_MONTH) phpEvent();
    else if(S.m>=S.nextEventAt && S.m<TOTAL-1){
      const ev = POOL[Math.floor(rnd()*POOL.length)]();
      if(ev) showEvent(ev); else S.nextEventAt = S.m + 3 + rnd()*3;
    }
  }
  moveBugs(dt);
  if(S.health<=0) return finish("crash");
  if(S.happy<=0) return finish("utenti");
  if(S.m>=TOTAL) return finish();
}
function moveBugs(dt){
  const st=$("stage"), W=st.clientWidth-44, H=250;
  S.bugs.forEach(b=>{
    b.vx += (rnd()-.5)*40*dt; b.vy += (rnd()-.5)*40*dt;
    b.vx = Math.max(-70,Math.min(70,b.vx)); b.vy = Math.max(-55,Math.min(55,b.vy));
    b.x += b.vx*dt; b.y += b.vy*dt;
    if(b.x<4||b.x>W){ b.vx*=-1; b.x=Math.max(4,Math.min(W,b.x)); }
    if(b.y<50||b.y>H){ b.vy*=-1; b.y=Math.max(50,Math.min(H,b.y)); }
    b.el.style.transform = `translate(${b.x}px,${b.y}px) scaleX(${b.vx<0?-1:1})`;
  });
}

/* ---------- disegno ---------- */
function meter(id,v,label,invert){
  const m=$(id), d=m.querySelector(".bar div");
  d.style.width=clamp(v)+"%";
  const bad = invert ? v>=65 : v<30, good = invert ? v<35 : v>=60;
  d.style.background = bad?"var(--danger)":good?"var(--ok)":"var(--signal)";
  m.querySelector(".v").textContent = label;
}
function draw(){
  if(!S || S.over) return;
  const mo = Math.min(TOTAL, Math.floor(S.m)+1);
  $("month").textContent = `Month ${mo}`;
  $("tl").style.width = (S.m/TOTAL*100)+"%";
  $("coins").textContent = "€"+Math.round(S.coins)+"k";
  meter("m-happy",S.happy,Math.round(S.happy)+"%");
  meter("m-health",S.health,Math.round(S.health)+"%");
  meter("m-debt",S.debt,Math.round(S.debt)+"%",true);
  meter("m-test",S.test,Math.round(S.test)+"%");
  meter("m-age",S.age/12*100, S.age<1?"up to date":`${Math.floor(S.age)} months${S.eol?", unsupported":""}`,true);

  $("cells").querySelectorAll("i").forEach((c,i)=>c.classList.toggle("on", i<Math.floor(S.energy)));
  ACTIONS.forEach(a=>{
    const b=$("a-"+a.id), c=a.cost();
    b.querySelector(".cost").textContent = "⚡"+c;
    b.querySelector("small").textContent = typeof a.hint==="function"?a.hint():a.hint;
    b.disabled = S.energy<c || !!S.busy;
    b.hidden = !!S.busy;
  });
  const busy=$("busy");
  if(S.busy){ busy.hidden=false; busy.innerHTML=`🔄 Updating, site offline…<div class="bar"><div style="width:${S.busy.t/S.busy.d*100}%"></div></div>`; }
  else busy.hidden=true;
  if(S.event && S.event.deadline && $("cd")) $("cd").style.width = (100 - S.event.t/S.event.deadline*100)+"%";

  // aspetto del personaggio
  const pet=$("pet"), nb=S.bugs.length;
  const mood = S.busy?"sleep": S.infected?"infected": (S.health<35||nb>6)?"sick": (S.happy<35)?"sad":"ok";
  pet.className = "pet "+({sleep:"sleep",infected:"sick infected",sick:"sick",sad:"sad",ok:""}[mood]);
  $("dust").setAttribute("opacity", (S.debt/100).toFixed(2));
  $("crack").setAttribute("opacity", S.health<50 ? ((50-S.health)/50).toFixed(2) : 0);
  $("web").setAttribute("opacity", Math.min(1, Math.max(0,(S.age-4)/6)).toFixed(2));
  $("zzz").setAttribute("opacity", S.busy?1:0);
  $("led").setAttribute("fill", S.infected?"#9A6FB0": S.event?.security ? (Math.floor(performance.now()/300)%2?"#C8402F":"#F2C230") : S.health<35?"#C8402F":"#2F7D5B");
  const eyeRy = S.busy?1.5 : mood==="sick"?3 : 8;
  $("eyeL").setAttribute("ry",eyeRy); $("eyeR").setAttribute("ry",eyeRy);
  const mouth = {ok:"M60 88 Q75 98 90 88", sad:"M60 94 Q75 84 90 94", sick:"M58 92 Q67 86 75 92 Q83 98 92 92", infected:"M58 92 Q67 86 75 92 Q83 98 92 92", sleep:"M66 90 L84 90"}[mood];
  $("mouth").setAttribute("d",mouth);

  // cosa dice
  let say="";
  if(S.busy) say="Updating, back in a sec…";
  else if(S.infected) say="Someone broke in! Update me!";
  else if(S.event?.security) say="Quick, update me!";
  else if(nb>=6) say="Itchy! Too many bugs on me";
  else if(S.health<35) say="I don’t feel well…";
  else if(S.debt>65) say="I’m covered in dust, clean me up";
  else if(S.age>9) say="I feel old and creaky";
  else if(S.happy<35) say="Users are bored, I need something new";
  else if(S.test<15 && S.feat>4) say="Nobody checks if I break…";
  $("bubble").textContent = say;
}
function loop(ts){
  const dt = Math.min(0.1,(ts-last)/1000 || 0); last = ts;
  tick(dt); draw();
  if(!S.over) raf = requestAnimationFrame(loop);
}

/* ---------- fine ---------- */
function finish(reason){
  if(S.over) return; S.over=true; cancelAnimationFrame(raf);
  $("game").hidden=true; $("end").hidden=false; window.scrollTo(0,0);
  const st=S.stats, tot=st.feat+st.test+st.upd+st.ref || 1;
  const maint=Math.round((st.test+st.upd+st.ref)/tot*100);
  const mo = Math.min(TOTAL, Math.floor(S.m)+1);
  let v,vt;
  if(reason==="crash"){ v="The site went down"; vt=`In month ${mo} the site gave up: too many bugs, too much debt, or one attack too many.`; }
  else if(reason==="utenti"){ v="Users are gone"; vt=`By month ${mo} nobody uses it anymore.`; }
  else if(S.health>=60 && S.happy>=50 && S.age<=6 && S.breaches===0){ v="Healthy site"; vt="Three years on, the site keeps growing, holds up, and updates without drama."; }
  else if(S.age>8 || S.debt>=70){ v="Nearing end of life"; vt="It still works, but it’s fragile and expensive to change. Soon someone will suggest rebuilding it from scratch."; }
  else { v="Survived"; vt="The site is still standing, a little battered."; }
  $("verdict").textContent=v; $("verdictText").textContent=vt;
  const box=(l,val)=>`<div class="stat"><b>${val}</b><span>${l}</span></div>`;
  $("endStats").innerHTML = box("Revenue","€"+Math.round(S.coins)+"k")+box("Features",S.feat)+box("Bugs squashed by hand",st.squash)+box("Attacks suffered",S.breaches);
  const L=[];
  L.push(`You spent ${maint}% of your actions on tests, updates and cleanup. In real life, maintenance often accounts for more than half the total cost of software: it isn’t an extra, it’s the main job.`);
  if(S.php==="rapido") L.push("When the critical flaw hit, your components were recent and the update was quick. That’s the difference between the sites that are safe the day after a security alert and the ones that have to go offline.");
  if(S.php==="lento") L.push("The critical flaw caught you with old components: you made it, but with a long update and the site offline. Putting updates off doesn’t save money, it moves the cost to the worst possible moment.");
  if(S.php==="violato") L.push("During the critical flaw your site was attacked. In real life that means exposed user data, legal notification duties and reputational damage that cost far more than an update.");
  if(st.squash>15) L.push(`You squashed ${st.squash} bugs by hand. That’s firefighting: it works, but it never ends. Tests stop bugs from hatching in the first place.`);
  if(S.debt>=50) L.push("Tech debt ended up high: every shortcut makes all later changes slower and riskier, like interest on a loan.");
  if(st.feat<4) L.push("You shipped few new features: a site that’s only maintained stays safe but loses users. Balancing new things and care is the real craft.");
  $("lessons").innerHTML=L.map(x=>`<p>${x}</p>`).join("");
  submitResult(reason, mo);
}

/* ---------- classifica e condivisione ---------- */
const NAME_KEY = "patchling:name";
const esc = t => String(t).replace(/[&<>"']/g, c=>`&#${c.charCodeAt(0)};`);
let result = null;

async function api(method, path, body){
  const r = await fetch("/api/"+path, {method, headers:{"Accept":"application/json","Content-Type":"application/json"}, body: body && JSON.stringify(body)});
  const data = await r.json().catch(()=>({}));
  if(!r.ok) throw Object.assign(new Error(data.message || r.statusText), {data});
  return data;
}
async function submitResult(reason, months){
  const game = S, sc = $("scorecard");
  const id = await game.id;
  if(S!==game) return;
  if(!id){ sc.hidden=true; return; }
  sc.hidden=false; sc.innerHTML=`<p class="muted">Saving your score…</p>`;
  try{
    const r = await api("POST", `games/${id}/result`, {
      outcome: reason==="crash"?"crash": reason==="utenti"?"users":"completed", months,
      revenue: Math.round(S.coins), features: S.feat, squashed: S.stats.squash, breaches: S.breaches,
      health: Math.round(S.health), happy: Math.round(S.happy), debt: Math.round(S.debt), age: Math.round(S.age*10)/10,
      name: localStorage.getItem(NAME_KEY)
    });
    if(S!==game) return;
    result = {...r.data, claim: r.claim_token};
    renderScore(); loadBoard();
  }catch{ if(S===game) sc.innerHTML=`<p class="muted">Your score couldn’t be saved this time.</p>`; }
}
function shareText(r){
  const pts = r.score.toLocaleString("en");
  return ["crashed","users_gone"].includes(r.verdict)
    ? `My website lasted ${r.months} month${r.months===1?"":"s"} in Patchling (${pts} points). Can you keep yours alive longer?`
    : `${r.verdict_label}: my website survived three years in Patchling with ${pts} points. Can you keep yours alive?`;
}
function renderScore(){
  const r = result, url = encodeURIComponent(r.share_url), text = encodeURIComponent(shareText(r));
  $("scorecard").innerHTML = `
    <div class="score"><b>${r.score.toLocaleString("en")} points</b><span>${r.name ? `#${r.rank} on the leaderboard` : `Good for #${r.rank} on the leaderboard`}</span></div>
    ${r.name ? "" : `<form class="nameform" id="nameForm"><label for="nick">Put your name on the leaderboard</label>
      <div><input id="nick" maxlength="20" minlength="2" required autocomplete="nickname" placeholder="Your name"><button>Save</button></div><p class="err" id="nameErr"></p></form>`}
    <div class="share">
      ${navigator.share ? `<button class="primary" id="shareBtn">Share your result</button>` : `<b>Share your result</b>`}
      <div class="links">
        <a href="https://x.com/intent/post?text=${text}&url=${url}" target="_blank" rel="noopener">X</a>
        <a href="https://bsky.app/intent/compose?text=${text}%20${url}" target="_blank" rel="noopener">Bluesky</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=${url}" target="_blank" rel="noopener">LinkedIn</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=${url}" target="_blank" rel="noopener">Facebook</a>
        <a href="https://wa.me/?text=${text}%20${url}" target="_blank" rel="noopener">WhatsApp</a>
        <button id="copyBtn">Copy link</button>
      </div>
    </div>`;
  $("shareBtn")?.addEventListener("click", ()=> navigator.share({title:"Patchling", text:shareText(r), url:r.share_url}).catch(()=>{}));
  $("copyBtn").onclick = async ()=>{ await navigator.clipboard.writeText(r.share_url); $("copyBtn").textContent="Copied!"; };
  $("nameForm")?.addEventListener("submit", saveName);
}
async function saveName(e){
  e.preventDefault();
  const name = $("nick").value.trim();
  try{
    const r = await api("PATCH", `results/${result.id}`, {name, claim_token: result.claim});
    localStorage.setItem(NAME_KEY, name);
    result = {...r.data, claim: result.claim};
    renderScore(); loadBoard();
  }catch(err){ $("nameErr").textContent = err.data?.errors?.name?.[0] || "Couldn’t save your name, try again."; }
}
async function loadBoard(){
  try{
    const {data} = await api("GET", "leaderboard");
    const html = data.length ? data.map(r=>`<li${r.id===result?.id?' class="me"':""}><span class="pos">${r.rank}</span>
      <a href="${esc(r.share_url)}">${esc(r.name)}<small>${esc(r.verdict_label)}</small></a><b>${r.score.toLocaleString("en")}</b></li>`).join("")
      : `<li class="empty">No scores yet. Be the first!</li>`;
    document.querySelectorAll(".board").forEach(b=>{ b.hidden=false; b.querySelector("ol").innerHTML=html; });
  }catch{ document.querySelectorAll(".board").forEach(b=>b.hidden=true); }
}

/* ---------- avvio ---------- */
function startGame(){ newGame(); S.id = api("POST","games").then(g=>g.id, ()=>null); $("scorecard").hidden=true; result=null; $("intro").hidden=true; $("end").hidden=true; $("game").hidden=false; window.scrollTo(0,0); last=performance.now(); raf=requestAnimationFrame(loop); }
$("start").onclick = startGame;
$("restart").onclick = startGame;
$("pause").onclick = ()=>{ S.paused=!S.paused; $("pause").textContent=S.paused?"▶":"II"; $("pause").setAttribute("aria-label",S.paused?"Resume":"Pause"); };
document.addEventListener("visibilitychange",()=>{ if(document.hidden && S && !S.over){ S.paused=true; $("pause").textContent="▶"; } });
loadBoard();
