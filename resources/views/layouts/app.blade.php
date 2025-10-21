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
        .actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .error {
            color: #dc2626;
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
<script>
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    if (prefersDark.matches) {
        document.body.classList.add('dark');
    }
    prefersDark.addEventListener('change', (event) => {
        document.body.classList.toggle('dark', event.matches);
    });
</script>
</body>
</html>
