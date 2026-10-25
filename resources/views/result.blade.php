@php
    $who = $result->name ?? 'Someone';
    $title = $result->verdict->label().' · '.number_format($result->score).' points in Patchling';
    $description = $result->verdict->completed()
        ? "{$who} kept a website alive for three years. Can you do better?"
        : "{$who}’s website lasted {$result->months} ".str('month')->plural($result->months).'. Can you keep yours alive longer?';
    $image = route('results.image', ['result' => $result, 'v' => $result->updated_at->timestamp]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Patchling">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ route('results.show', $result) }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $result->verdict->label() }}: {{ number_format($result->score) }} points">
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&display=swap" rel="stylesheet">
@vite('resources/css/app.css')
</head>
<body>
<main>
  <div class="tape"></div>
  <h2 class="verdict">{{ $result->verdict->label() }}</h2>
  <p>{{ $who }} scored <b>{{ number_format($result->score) }} points</b>{{ $result->name ? ", #{$rank} on the leaderboard" : '' }}.</p>
  <img class="card" src="{{ $image }}" width="1200" height="630" alt="{{ $result->verdict->label() }}: {{ number_format($result->score) }} points">
  <p>Patchling is a small game about software maintenance: your company’s website is alive, and you have three years to keep it healthy.</p>
  <a class="primary" href="{{ route('home') }}">Play Patchling</a>
</main>
</body>
</html>
