@extends('imports.layout')

@section('titulo', 'Entrar')

@section('contenido')
    <div style="max-width:380px;margin:6vh auto">
        <h1>Importador de padrones</h1>
        <p class="sub">Entra con tu usuario de la API.</p>

        <form method="post" action="{{ route('imports.login') }}" class="panel">
            @csrf

            <label class="campo">
                <span>Correo o usuario</span>
                <input type="text" name="email" value="{{ old('email') }}" autofocus required autocomplete="username">
            </label>

            <label class="campo">
                <span>Contraseña</span>
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <label class="check">
                <input type="checkbox" name="recordar" value="1">
                <span>Mantener la sesión abierta</span>
            </label>

            <button type="submit" style="width:100%">Entrar</button>
        </form>
    </div>
@endsection
