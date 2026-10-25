<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Patchling</title>
<meta name="description" content="A small game about software maintenance: keep your company’s website alive for three years.">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Patchling">
<meta property="og:title" content="Patchling">
<meta property="og:description" content="A small game about software maintenance: keep your company’s website alive for three years.">
<meta property="og:url" content="{{ route('home') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/game.js'])
</head>
<body>
<main>

<section id="intro">
  <div class="tape"></div>
  <div class="hero">
    <h1>Patchling</h1>
    <p>Your company’s website is alive: it’s hungry for new things, it catches bugs, it gets old. You have three years (three minutes) to keep it healthy.</p>
  </div>
  <h2>The life of a piece of software</h2>
  <ol class="cycle">
    <li><b>Analysis</b><span>Deciding what it should do, and for whom.</span></li>
    <li><b>Development</b><span>Writing the code, often on top of components built by others, like PHP.</span></li>
    <li><b>Testing</b><span>Checking that it works, and that changes don’t break everything else.</span></li>
    <li><b>Release</b><span>The site goes live.</span></li>
    <li class="here"><b>Maintenance — you are here</b><span>The longest phase: bugs, updates, cleanup, new features.</span></li>
    <li><b>End of life</b><span>The site gets rebuilt, or stays online with nobody looking after it.</span></li>
  </ol>
  <div class="rules">
    <ul>
      <li>Tap the bugs 🐛 to squash them.</li>
      <li>Spend your team’s energy on the four actions below. It recharges on its own.</li>
      <li>Listen to your site: it tells you when something’s wrong.</li>
      <li>Halfway through, a critical vulnerability hits. Be ready.</li>
    </ul>
  </div>
  <button class="primary" id="start">Switch on the site</button>
  <div class="board">
    <h2>Leaderboard</h2>
    <ol></ol>
  </div>
</section>

<section id="game" hidden>
  <div class="hud">
    <span class="month" id="month">Month 1</span>
    <div class="timeline"><div class="fill" id="tl"></div><span class="php" title="Critical vulnerability"></span></div>
    <span class="coins" id="coins">€0k</span>
    <button class="pause" id="pause" aria-label="Pause">II</button>
  </div>

  <div class="stage" id="stage">
    <div class="bubble" id="bubble"></div>
    <div class="floor"></div>
    <div class="pet" id="pet">
      <svg viewBox="0 0 150 170" aria-hidden="true">
        <line x1="75" y1="4" x2="75" y2="26" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
        <circle id="led" cx="75" cy="6" r="7" fill="#2F7D5B"/>
        <rect class="bodyfill" x="12" y="24" width="126" height="132" rx="30" fill="var(--body-c)" stroke="currentColor" stroke-width="4"/>
        <rect x="30" y="42" width="90" height="62" rx="14" fill="var(--screen)" stroke="currentColor" stroke-width="3"/>
        <g id="eyes" fill="#1E2A32">
          <ellipse id="eyeL" cx="56" cy="68" rx="6" ry="8"/>
          <ellipse id="eyeR" cx="94" cy="68" rx="6" ry="8"/>
        </g>
        <path id="mouth" d="M60 88 Q75 98 90 88" fill="none" stroke="#1E2A32" stroke-width="4" stroke-linecap="round"/>
        <g fill="currentColor" opacity=".55"><rect x="34" y="118" width="40" height="6" rx="3"/><rect x="34" y="130" width="28" height="6" rx="3"/><circle cx="112" cy="127" r="7"/></g>
        <g id="dust" opacity="0" fill="#6B5A44">
          <circle cx="24" cy="60" r="5"/><circle cx="128" cy="96" r="6"/><circle cx="40" cy="146" r="4"/><circle cx="118" cy="40" r="4"/><circle cx="20" cy="112" r="6"/><circle cx="98" cy="148" r="5"/><circle cx="132" cy="136" r="4"/>
        </g>
        <path id="crack" opacity="0" d="M120 30 L108 52 L118 60 L104 84 M40 156 L50 138 L42 128" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
        <g id="web" opacity="0" stroke="currentColor" stroke-width="1.5" fill="none">
          <path d="M12 54 Q30 40 42 24 M12 54 L42 24 M12 40 Q22 34 28 24 M12 46 Q26 38 36 24"/>
        </g>
        <text id="zzz" x="128" y="20" font-size="20" font-weight="800" fill="currentColor" opacity="0" font-family="sans-serif">z z</text>
      </svg>
    </div>
    <div id="bugs"></div>
    <div id="eventBox"></div>
  </div>

  <div class="meters">
    <div class="meter" id="m-happy"><div class="row"><span>Happy users</span><span class="v"></span></div><div class="bar"><div></div></div></div>
    <div class="meter" id="m-health"><div class="row"><span>Site health</span><span class="v"></span></div><div class="bar"><div></div></div></div>
    <div class="meter" id="m-debt"><div class="row"><span>Tech debt</span><span class="v"></span></div><div class="bar"><div></div></div></div>
    <div class="meter" id="m-test"><div class="row"><span>Test coverage</span><span class="v"></span></div><div class="bar"><div></div></div></div>
    <div class="meter" id="m-age" style="grid-column:1/-1"><div class="row"><span>Component age</span><span class="v"></span></div><div class="bar"><div></div></div></div>
  </div>

  <div class="energy"><span>Team energy</span><div class="cells" id="cells"></div></div>
  <div class="actions" id="actions"></div>

  <div class="diary">
    <h2>Log</h2>
    <ul id="diary"></ul>
  </div>
</section>

<section id="end" hidden>
  <div class="tape"></div>
  <h2 class="verdict" id="verdict"></h2>
  <p id="verdictText"></p>
  <div class="endstats" id="endStats"></div>
  <div class="scorecard" id="scorecard" hidden aria-live="polite"></div>
  <h2>What it teaches</h2>
  <div class="lessons" id="lessons"></div>
  <button class="primary" id="restart" style="margin-top:16px">Play again</button>
  <div class="board">
    <h2>Leaderboard</h2>
    <ol></ol>
  </div>
</section>
</main>

</body>
</html>
