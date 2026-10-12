@extends('legal._base')
@section('titulo', 'Eliminación de datos')
@section('cuerpo')
<p>Usted puede pedir que eliminemos sus datos de LocalProo en cualquier momento.</p>
<div class="box">
<h2 style="margin-top:0">Si conectó Facebook o Instagram</h2>
<ol>
    <li>Entre a LocalProo → <b>Mis redes → Cuentas</b> y toque <b>Desconectar</b>. Dejamos de recibir sus mensajes y se invalida el acceso.</li>
    <li>También puede quitar el acceso desde Facebook: <b>Configuración → Seguridad e inicio de sesión → Integraciones comerciales</b> (o "Apps y sitios web") y eliminar <b>LocalProo</b>.</li>
    <li>Para borrar además los mensajes y datos guardados, escríbanos a <a href="mailto:{{ $correo }}">{{ $correo }}</a> con el asunto <b>"Eliminar mis datos"</b> e indique el nombre de su negocio o su página. Los eliminamos en un máximo de 15 días hábiles y le confirmamos por correo.</li>
</ol>
</div>
<h2>Si es cliente de un negocio que usa LocalProo</h2>
<p>Pídale al negocio que elimine sus datos, o escríbanos a <a href="mailto:{{ $correo }}">{{ $correo }}</a> y lo gestionamos con él.</p>
@endsection
