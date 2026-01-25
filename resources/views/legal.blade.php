<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title }}</title>
    <style>
      :root {
        color-scheme: light;
        font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
        line-height: 1.55;
        color: #111827;
        background: #f8fafc;
      }
      body {
        margin: 0;
        padding: 40px 20px;
      }
      main {
        max-width: 760px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08);
      }
      h1, h2, h3 {
        margin-top: 0;
      }
      a {
        color: #2563eb;
      }
      ul {
        padding-left: 20px;
      }
      .back-link {
        display: inline-block;
        margin-bottom: 16px;
        font-size: 0.9rem;
      }
    </style>
  </head>
  <body>
    <main>
      <div class="back-link">
        <a href="/login">← Back to login</a>
      </div>
      {!! $content !!}
    </main>
  </body>
</html>
