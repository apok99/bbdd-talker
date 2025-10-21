<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <style>
        :root {
            color-scheme: light dark;
            font-family: 'Figtree', sans-serif;
        }
        body {
            margin: 0;
            background: #f5f5f5;
            color: #1f2933;
        }
        body.dark {
            background: #0f172a;
            color: #e2e8f0;
        }
        .container {
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
            max-width: 960px;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        header h1 {
            font-size: 1.75rem;
            margin: 0;
        }
        main {
            background: rgba(255,255,255,0.85);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(15,23,42,0.1);
            padding: 1.5rem;
            display: grid;
            gap: 2rem;
        }
        body.dark main {
            background: rgba(15,23,42,0.7);
        }
        .messages {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-height: 60vh;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        .messages .message code,
        .messages .message pre {
            background: rgba(15,23,42,0.05);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            display: block;
            overflow-x: auto;
        }
        body.dark .messages .message code,
        body.dark .messages .message pre {
            background: rgba(15,23,42,0.45);
        }
        .message {
            padding: 1rem;
            border-radius: 1rem;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .message.user {
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #fff;
            margin-left: auto;
            max-width: 75%;
        }
        .message.assistant {
            background: rgba(15,23,42,0.05);
            border: 1px solid rgba(15,23,42,0.05);
            max-width: 80%;
        }
        body.dark .message.assistant {
            background: rgba(15,23,42,0.6);
            border-color: rgba(148,163,184,0.2);
        }
        form {
            margin-top: 2rem;
            display: grid;
            gap: 0.75rem;
        }
        .selector-form {
            margin-top: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .selectors__header {
            display: grid;
            gap: 0.5rem;
        }
        .selectors__header h2 {
            margin: 0;
            font-size: 1.25rem;
        }
        .selector {
            display: grid;
            gap: 0.5rem;
        }
        .selectors {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .selectors label {
            display: block;
            margin-bottom: 0.25rem;
        }
        .selector__hint {
            font-size: 0.875rem;
            margin: 0;
            color: rgba(15,23,42,0.6);
        }
        body.dark .selector__hint {
            color: rgba(226,232,240,0.7);
        }
        label {
            font-weight: 600;
        }
        textarea {
            width: 100%;
            min-height: 120px;
            border-radius: 0.75rem;
            border: 1px solid rgba(15,23,42,0.1);
            padding: 1rem;
            font: inherit;
            resize: vertical;
        }
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }
        select {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid rgba(15,23,42,0.1);
            padding: 0.75rem 1rem;
            font: inherit;
            background: #fff;
        }
        body.dark select {
            background: rgba(15,23,42,0.7);
            color: inherit;
            border-color: rgba(148,163,184,0.2);
        }
        select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }
        button {
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font: inherit;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        button.primary {
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: white;
            box-shadow: 0 10px 20px rgba(37,99,235,0.3);
        }
        button.secondary {
            background: transparent;
            border: 1px solid rgba(15,23,42,0.15);
            color: inherit;
        }
        button:active {
            transform: scale(0.98);
            box-shadow: none;
        }
        button[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .metadata {
            display: grid;
            gap: 1rem;
        }
        .metadata__summary {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(37,99,235,0.1);
            color: #1f2933;
            font-size: 0.875rem;
        }
        body.dark .pill {
            background: rgba(148,163,184,0.2);
            color: inherit;
        }
        .table-overview {
            background: rgba(15,23,42,0.04);
            border: 1px solid rgba(15,23,42,0.08);
            border-radius: 1rem;
            padding: 1rem;
        }
        .table-overview header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .table-overview__meta {
            margin: 0;
            font-size: 0.875rem;
            color: rgba(15,23,42,0.6);
        }
        body.dark .table-overview__meta {
            color: rgba(226,232,240,0.7);
        }
        body.dark .table-overview {
            background: rgba(15,23,42,0.6);
            border-color: rgba(148,163,184,0.2);
        }
        .table-overview h2 {
            margin-top: 0;
            margin-bottom: 0.25rem;
        }
        .table-scroll {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }
        th, td {
            text-align: left;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid rgba(15,23,42,0.1);
        }
        body.dark th, body.dark td {
            border-color: rgba(148,163,184,0.2);
        }
        .error {
            color: #dc2626;
        }
        .loading-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15,23,42,0.35);
            backdrop-filter: blur(4px);
            z-index: 1000;
        }
        .loading-overlay[data-visible="true"] {
            display: flex;
        }
        .loading-dialog {
            background: rgba(255,255,255,0.95);
            color: #1f2933;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(15,23,42,0.18);
            display: grid;
            gap: 1rem;
            justify-items: center;
            text-align: center;
            min-width: 260px;
        }
        body.dark .loading-dialog {
            background: rgba(15,23,42,0.85);
            color: #e2e8f0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            border: 0.35rem solid rgba(37,99,235,0.2);
            border-top-color: #2563eb;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 640px) {
            main {
                padding: 1rem;
            }
            header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>{{ config('app.name') }}</h1>
        <form action="{{ route('chat.reset') }}" method="POST">
            @csrf
            <button class="secondary" type="submit">Limpiar conversación</button>
        </form>
    </header>
    <main>
        @if ($errors->any())
            <p class="error">{{ $errors->first('message') }}</p>
        @endif
        @yield('content')
    </main>
</div>
<div class="loading-overlay" id="loading-overlay" data-visible="false">
    <div class="loading-dialog" role="status" aria-live="assertive">
        <div class="loading-spinner" aria-hidden="true"></div>
        <p>Generando respuesta con la IA…</p>
    </div>
</div>
<script>
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    if (prefersDark.matches) {
        document.body.classList.add('dark');
    }
    prefersDark.addEventListener('change', (event) => {
        document.body.classList.toggle('dark', event.matches);
    });

    const overlay = document.getElementById('loading-overlay');
    document.querySelectorAll('form[data-loading-overlay="true"]').forEach((form) => {
        form.addEventListener('submit', () => {
            if (! overlay) {
                return;
            }

            overlay.setAttribute('data-visible', 'true');

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.setAttribute('disabled', 'disabled');
            }
        });
    });
</script>
</body>
</html>
