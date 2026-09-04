<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Importador') · {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #f6f7f9;
            --panel: #ffffff;
            --borde: #dfe3e8;
            --texto: #1c2430;
            --suave: #6b7684;
            --acento: #1f6feb;
            --acento-suave: #e8f0fe;
            --ok: #15803d;
            --ok-bg: #e8f6ec;
            --aviso: #a45c00;
            --aviso-bg: #fdf3e2;
            --error: #b42318;
            --error-bg: #fdeceb;
            --radio: 8px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #14181d;
                --panel: #1c2128;
                --borde: #2d333b;
                --texto: #e6edf3;
                --suave: #93a1b1;
                --acento: #4c8dff;
                --acento-suave: #16233a;
                --ok: #4ade80;
                --ok-bg: #14251a;
                --aviso: #fbbf24;
                --aviso-bg: #2a2213;
                --error: #f87171;
                --error-bg: #2c1618;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--texto);
            font: 14px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        a { color: var(--acento); }

        header.barra {
            background: var(--panel);
            border-bottom: 1px solid var(--borde);
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            height: 54px;
        }

        header.barra .marca { font-weight: 650; letter-spacing: -0.01em; }
        header.barra nav { display: flex; gap: 16px; margin-left: auto; align-items: center; }
        header.barra nav a { text-decoration: none; color: var(--suave); }
        header.barra nav a:hover, header.barra nav a.activo { color: var(--texto); }

        main { max-width: 1180px; margin: 0 auto; padding: 24px 20px 80px; }

        h1 { font-size: 22px; margin: 0 0 4px; letter-spacing: -0.02em; }
        h2 { font-size: 16px; margin: 28px 0 10px; letter-spacing: -0.01em; }
        .sub { color: var(--suave); margin: 0 0 22px; }

        .panel {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: var(--radio);
            padding: 18px;
            margin-bottom: 18px;
        }

        .rejilla { display: grid; gap: 14px; }
        .rejilla.dos { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

        label.campo { display: block; margin-bottom: 14px; }
        label.campo > span { display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px; }
        label.campo > small { display: block; color: var(--suave); font-weight: 400; margin-top: 4px; }

        input[type=text], input[type=number], input[type=password], input[type=email], input[type=file], select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--borde);
            border-radius: 6px;
            background: var(--bg);
            color: var(--texto);
            font: inherit;
        }

        input:focus, select:focus { outline: 2px solid var(--acento); outline-offset: -1px; }

        label.check { display: flex; gap: 9px; align-items: flex-start; margin-bottom: 10px; cursor: pointer; }
        label.check input { margin-top: 3px; flex: none; }
        label.check small { display: block; color: var(--suave); }

        button, .boton {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 6px;
            border: 1px solid transparent;
            background: var(--acento);
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        button:hover, .boton:hover { filter: brightness(1.08); }
        button.neutro, .boton.neutro { background: var(--panel); color: var(--texto); border-color: var(--borde); }
        button.peligro, .boton.peligro { background: var(--error); }
        button[disabled] { opacity: .5; cursor: not-allowed; filter: none; }

        .acciones { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

        .aviso, .exito, .error {
            border-radius: var(--radio);
            padding: 12px 14px;
            margin-bottom: 14px;
            border: 1px solid;
        }

        .exito { background: var(--ok-bg); border-color: var(--ok); color: var(--ok); }
        .aviso { background: var(--aviso-bg); border-color: var(--aviso); color: var(--aviso); }
        .error { background: var(--error-bg); border-color: var(--error); color: var(--error); }
        .aviso ul, .error ul { margin: 6px 0 0; padding-left: 18px; }

        .tarjetas { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }

        .tarjeta {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: var(--radio);
            padding: 13px 15px;
        }

        .tarjeta .n { font-size: 25px; font-weight: 680; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; }
        .tarjeta .e { color: var(--suave); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .tarjeta.ok .n { color: var(--ok); }
        .tarjeta.aviso-n .n { color: var(--aviso); }
        .tarjeta.error-n .n { color: var(--error); }

        .tabla-scroll { overflow-x: auto; border: 1px solid var(--borde); border-radius: var(--radio); background: var(--panel); }

        table { border-collapse: collapse; width: 100%; font-size: 13px; }
        th, td { padding: 7px 10px; text-align: left; border-bottom: 1px solid var(--borde); white-space: nowrap; }
        th { background: var(--bg); font-weight: 600; position: sticky; top: 0; font-size: 12px; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--acento-suave); }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        td.envuelve { white-space: normal; min-width: 260px; }

        /* Las tablas de resumen viven dentro de tarjetas angostas: la etiqueta puede
           partirse en dos renglones, pero el importe nunca —y se pega a la derecha
           para que no se salga del recuadro. */
        .panel table { table-layout: auto; }
        .panel table td, .panel table th { white-space: normal; }
        .panel table td:last-child, .panel table th:last-child {
            white-space: nowrap;
            text-align: right;
            width: 1%;
            font-variant-numeric: tabular-nums;
        }

        .etiqueta {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 650;
            border: 1px solid var(--borde);
            color: var(--suave);
            text-transform: lowercase;
        }

        .etiqueta.crear, .etiqueta.creado { background: var(--ok-bg); color: var(--ok); border-color: transparent; }
        .etiqueta.reutilizar, .etiqueta.reutilizado { background: var(--acento-suave); color: var(--acento); border-color: transparent; }
        .etiqueta.actualizar, .etiqueta.actualizado { background: var(--aviso-bg); color: var(--aviso); border-color: transparent; }
        .etiqueta.aplicado { background: var(--ok-bg); color: var(--ok); border-color: transparent; }
        .etiqueta.previsualizado { background: var(--acento-suave); color: var(--acento); border-color: transparent; }
        .etiqueta.revertido { background: var(--error-bg); color: var(--error); border-color: transparent; }

        .mini { color: var(--suave); font-size: 12px; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; }
        .lista-limpia { list-style: none; margin: 0; padding: 0; }
        .lista-limpia li { padding: 3px 0; }

        details.filas > summary { cursor: pointer; font-weight: 600; padding: 6px 0; }
        .paginacion { margin-top: 14px; }
        .paginacion svg { width: 16px; height: 16px; }
    </style>
</head>
<body>
    <header class="barra">
        <span class="marca">{{ config('app.name') }} · Importador</span>
        <nav>
            @auth
                <a href="{{ route('imports.padron.index') }}" @class(['activo' => request()->routeIs('imports.padron.index')])>Lotes</a>
                <a href="{{ route('imports.padron.create') }}" @class(['activo' => request()->routeIs('imports.padron.create')])>Nueva importación</a>
                <span class="mini">{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('imports.logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="neutro" style="padding:5px 11px">Salir</button>
                </form>
            @endauth
        </nav>
    </header>

    <main>
        @if (session('exito'))
            <div class="exito">{{ session('exito') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">
                <strong>No se pudo continuar</strong>
                <ul>
                    @foreach ($errors->all() as $mensaje)
                        <li>{{ $mensaje }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('contenido')
    </main>
</body>
</html>
