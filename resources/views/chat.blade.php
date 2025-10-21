@extends('layouts.app')

@section('content')
    <section class="selectors">
        <form action="{{ route('chat.index') }}" method="GET" class="selector-form">
            <div>
                <label for="schema">Esquema</label>
                <select id="schema" name="schema" onchange="this.form.submit()">
                    @foreach($schemas as $schemaOption)
                        <option value="{{ $schemaOption }}" @selected($selectedSchema === $schemaOption)>{{ $schemaOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="table">Tabla</label>
                <select id="table" name="table" @if(empty($tables)) disabled @endif onchange="this.form.submit()">
                    @forelse($tables as $tableOption)
                        <option value="{{ $tableOption }}" @selected($selectedTable === $tableOption)>{{ $tableOption }}</option>
                    @empty
                        <option value="">(Sin tablas en el esquema)</option>
                    @endforelse
                </select>
            </div>
        </form>
        @if($selectedTable)
            <section class="table-overview">
                <h2>Columnas de {{ $selectedTable }}</h2>
                @if(empty($tableColumns))
                    <p>No se encontraron columnas para esta tabla.</p>
                @else
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Columna</th>
                                    <th>Tipo</th>
                                    <th>Nulidad</th>
                                    <th>Defecto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tableColumns as $column)
                                    <tr>
                                        <td>{{ $column['name'] }}</td>
                                        <td>{{ $column['type'] }}</td>
                                        <td>{{ $column['nullable'] ? 'NULL' : 'NOT NULL' }}</td>
                                        <td>{{ $column['default'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </section>
    <section class="messages">
        @forelse($conversation as $message)
            <article class="message {{ $message['role'] }}">
                <strong>{{ $message['role'] === 'user' ? 'Tú' : 'Asistente' }}</strong>
                <div>{{ $message['content'] }}</div>
            </article>
        @empty
            <article class="message assistant">
                <strong>Asistente</strong>
                <div>¡Hola! Soy tu copiloto para bases de datos PostgreSQL. Selecciona el esquema con el que quieres trabajar, describe la consulta que necesitas y me encargaré de generar y ejecutar el SQL por ti.</div>
            </article>
        @endforelse
    </section>
    <form action="{{ route('chat.send') }}" method="POST">
        @csrf
        <input type="hidden" name="schema" value="{{ $selectedSchema }}">
        <label for="message">Mensaje</label>
        <textarea id="message" name="message" placeholder="Describe la información que necesitas obtener de la base de datos" required>{{ old('message') }}</textarea>
        <div class="actions">
            <button class="secondary" type="reset">Limpiar texto</button>
            <button class="primary" type="submit">Enviar</button>
        </div>
    </form>
@endsection
