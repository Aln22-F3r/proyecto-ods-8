<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de administradores</title>
    @vite('resources/css/app.css')
</head>

<body class="min-h-screen bg-[#363131] text-white">
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md rounded border border-[#4a4141] bg-[#272020] p-6">
            <h1 class="text-2xl font-semibold">Acceso de administradores</h1>

            {{-- Mensajes temporales de la sesión (por ejemplo, el cierre de sesión) --}}
            @session('status')
                <div class="mt-4 rounded border border-green-700 bg-green-900/40 p-3 text-sm">
                    {{ $value }}
                </div>
            @endsession

            {{-- Administrador con sesión iniciada --}}
            @auth('admin')
                <p class="mt-4 text-sm text-white/80">
                    Ya tienes una sesión activa como <strong>{{ auth('admin')->user()->nombre }}</strong>.
                </p>

                <div class="mt-6 flex gap-2">
                    <a href="/dashboard"
                        class="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80">
                        Ir al panel
                    </a>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                            class="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            @endauth

            {{-- Visitante sin sesión --}}
            @guest('admin')
                @if ($errors->any())
                    <div class="mt-4 rounded border border-red-700 bg-red-900/40 p-3 text-sm">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.post') }}" class="mt-6 space-y-4" novalidate>
                    @csrf

                    <div>
                        <label for="usuario" class="mb-1 block text-sm font-medium">Usuario</label>
                        <input id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" maxlength="50"
                            autocomplete="username" autofocus
                            class="w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium">Contraseña</label>
                        <input id="password" name="password" type="password" maxlength="255"
                            autocomplete="current-password"
                            class="w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white">
                    </div>

                    <button type="submit"
                        class="w-full rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80">
                        Iniciar sesión
                    </button>
                </form>
            @endguest
        </div>
    </main>
</body>

</html>
