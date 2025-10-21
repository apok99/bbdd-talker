@extends('layouts.app')

@section('content')
    <section class="messages">
        @forelse($conversation as $message)
            <article class="message {{ $message['role'] }}">
                <strong>{{ $message['role'] === 'user' ? 'Tú' : 'Asistente' }}</strong>
                <div>{{ $message['content'] }}</div>
            </article>
        @empty
            <article class="message assistant">
                <strong>Asistente</strong>
                <div>¡Hola! Soy tu copiloto para bases de datos PostgreSQL. Describe la consulta que necesitas y me encargaré de generar y ejecutar el SQL por ti.</div>
            </article>
        @endforelse
    </section>
    <form action="{{ route('chat.send') }}" method="POST">
        @csrf
        <label for="message">Mensaje</label>
        <textarea id="message" name="message" placeholder="Describe la información que necesitas obtener de la base de datos" required>{{ old('message') }}</textarea>
        <div class="actions">
            <button class="secondary" type="reset">Limpiar texto</button>
            <button class="primary" type="submit">Enviar</button>
        </div>
    </form>
@endsection
