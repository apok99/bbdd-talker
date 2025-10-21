# BBDD Talker

Aplicación Laravel pensada como copiloto para bases de datos PostgreSQL usando un modelo local de Ollama. La interfaz permite conversar en español y obtener consultas SQL generadas y ejecutadas automáticamente sobre la base conectada.

## Requisitos

* PHP 8.2+
* Composer
* Servidor de PostgreSQL accesible desde la aplicación
* Servidor local de Ollama con un modelo conversacional compatible con el endpoint `/api/chat`

## Puesta en marcha

```bash
cp .env.example .env
php -r "echo base64_encode(random_bytes(32));" # genera APP_KEY
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

La aplicación estará disponible en `http://localhost:8000`.

## Configuración de Ollama

Edita el archivo `.env` para indicar el endpoint y el nombre del modelo local:

```
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3
```

Puedes ajustar `OLLAMA_DATABASE_SCHEMA` para limitar el análisis del esquema a un `schema` concreto.

## Uso

1. Escribe en español la información que necesitas de tu base de datos.
2. El asistente generará una consulta SQL, la ejecutará y mostrará los resultados.
3. Usa el botón **Limpiar conversación** para reiniciar el contexto.

> **Nota:** Solo se ejecutan consultas de lectura (SELECT/WITH) por motivos de seguridad.
