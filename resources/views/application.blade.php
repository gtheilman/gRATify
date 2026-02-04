<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="team-based learning, team based learning, TBL, gRAT, tRAT, readiness assurance, readiness assurance test, readiness assurance process, team assessment, group readiness assurance, individual readiness assurance, quiz, formative assessment, active learning, collaborative learning, flipped classroom, assessment platform" />
  <meta name="description" content="gRAT is a team-based learning assessment platform for readiness assurance tests, team quizzes, and active learning in the classroom." />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="color-scheme" content="only light" />
  <!-- Favicons -->
  <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
  <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
  <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('android-chrome-512x512.png') }}">
  <link rel="preload" as="image" href="{{ app(\Illuminate\Foundation\Vite::class)->asset('resources/assets/images/TBL_Process.webp') }}">
  <title>gRATify - TBL Group Assessments</title>
  <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
  @vite(['resources/js/main.js'])
</head>

<body>
  <div id="app">
    <div id="loading-bg">
      <div class="loading-logo">
        <img src="{{ app(\Illuminate\Foundation\Vite::class)->asset('resources/assets/images/gratify-logo-300x90.webp') }}" alt="gRATify" height="90" />
      </div>
      <div class=" loading">
        <div class="effect-1 effects"></div>
        <div class="effect-2 effects"></div>
        <div class="effect-3 effects"></div>
      </div>
    </div>
  </div>
  
  <script>
    const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#FFFFFF'
    const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#7367F0'

    if (loaderColor)
      document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
    if (loaderColor)
      document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

    if (primaryColor)
      document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
    </script>
  </body>
</html>
