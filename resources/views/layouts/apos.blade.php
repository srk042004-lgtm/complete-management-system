<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | ArtFlow APOS</title>
    <link rel="stylesheet" href="{{ asset('css/apos-pages.css') }}">
</head>
<body class="apos-page">
    <header class="apos-header">
        <div class="apos-shell apos-header-inner">
            <a class="apos-brand" href="{{ route('portal') }}" aria-label="ArtFlow Management System home">
                <span class="apos-brand-mark">AF</span>
                <span>ARTFLOW<small>MANAGEMENT SYSTEM</small></span>
            </a>
            <a class="apos-back" href="{{ route('portal') }}">Back to portal <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <main class="apos-shell apos-main">
        <nav class="apos-page-nav" aria-label="APOS information pages">
            <a href="{{ route('apos.customers') }}">Customers</a>
            <a href="{{ route('apos.products') }}">Products</a>
            <a href="{{ route('apos.invoices') }}">Invoice templates</a>
            <a href="{{ route('apos.softwares') }}">Software</a>
            <a href="{{ route('apos.requirements') }}">Requirements</a>
            <a href="{{ route('apos.payments') }}">Payments &amp; returns</a>
            <a href="{{ route('apos.privacy') }}">Privacy</a>
            <a href="{{ route('apos.terms') }}">Terms</a>
        </nav>

        <section class="apos-hero">
            <span class="apos-eyebrow"><span class="apos-status-dot"></span>{{ $page['eyebrow'] }}</span>
            <div class="apos-page-icon" aria-hidden="true">{{ $page['icon'] }}</div>
            <h1>{{ $page['title'] }}</h1>
            <p>{{ $page['summary'] }}</p>
        </section>

        <section class="apos-content-panel" aria-label="{{ $page['title'] }} information">
            <div class="apos-panel-heading">
                <span>ARTFLOW POS</span>
                <span>Information &amp; support</span>
            </div>
            <div class="apos-cards">
                @foreach($page['sections'] as $section)
                    <article class="apos-card">
                        <span class="apos-card-marker" aria-hidden="true"></span>
                        <h2>{{ $section['title'] }}</h2>
                        <p>{{ $section['text'] }}</p>
                    </article>
                @endforeach
            </div>
            <aside class="apos-notice">
                <span class="apos-notice-icon" aria-hidden="true">i</span>
                <p>Need details for your organization? <a href="{{ route('portal') }}#demo">Contact the ArtFlow team</a> to confirm current features, compatibility, availability, and applicable terms.</p>
            </aside>
        </section>
    </main>

    <footer class="apos-footer">
        <div class="apos-shell">ArtFlow Management System <span aria-hidden="true">·</span> APOS information</div>
    </footer>
</body>
</html>
