<!DOCTYPE html>
<html lang="es">
<head>
@include('lpchat._head')
<title>Condiciones de uso · LP Chat</title>
<style>
body{background:var(--fondo)}
.doc{max-width:760px;margin:0 auto;background:var(--panel);padding:28px 22px 40px;line-height:1.55;min-height:100dvh}
h1{font-size:1.5rem;margin:.2rem 0}
h2{font-size:1.08rem;margin:1.6rem 0 .4rem;color:var(--verde2)}
.f{color:var(--txt2);font-size:.88rem}
li{margin:.25rem 0}
.box{background:var(--chip);border-radius:10px;padding:12px 14px;margin:14px 0}
</style>
</head>
<body>
<div class="doc">
    <a href="{{ route('lpchat.index') }}" style="text-decoration:none">← Volver a LP Chat</a>
    <h1>Condiciones de uso de LP Chat</h1>
    <div class="f">LP Chat (LocalProChat), la red social nariñense · Vigentes desde el 17 de noviembre de 2026</div>

    <div class="box"><b>En pocas palabras:</b> LocalProo pone la aplicación a tu disposición, como lo hace WhatsApp; <b>cada persona es responsable de lo que escribe, publica y comparte</b>. Los chats guardan solo los últimos 7 días y los estados duran 24 horas.</div>

    <h2>1. Qué es LP Chat</h2>
    <p>LP Chat es una aplicación de mensajería y red social de <b>LocalProo</b> (localproo.com) para chatear, crear grupos, publicar estados, conocer gente y escribirle a empresas. Une a las comunidades de LocalProo, Pasto Fight Night (PFN), Nariño ExpoShow (NES) y otros sistemas conectados. Es gratis.</p>

    <h2>2. Quién puede usarla</h2>
    <ul>
        <li>Solo <b>mayores de 18 años</b>. Al crear la cuenta declaras que lo eres. Las cuentas de NES o PFN con inscripciones de menores de edad las maneja su padre, madre o acudiente: no salen en "Conocer gente" y solo pueden chatear con el equipo del evento.</li>
        <li>Te registras con tu <b>correo o tu número de celular</b>, que es como te encuentran tus amigos. Eres responsable de tu clave y de lo que se haga con tu cuenta.</li>
        <li>Si te registraste en LocalProo, PFN o NES, al aceptar sus condiciones también aceptaste <b>hacer parte de LP Chat, la red social nariñense</b>. Puedes salir de "Conocer gente" o pedir que borremos tu cuenta cuando quieras.</li>
    </ul>

    <h2>3. LocalProo presta la herramienta; tú respondes por tu uso</h2>
    <ul>
        <li>LocalProo pone la aplicación disponible "tal como está". <b>No revisa ni aprueba</b> los mensajes, fotos, videos, audios ni estados antes de que se envíen, y no responde por lo que los usuarios digan, compartan, acuerden, vendan o compren entre ellos.</li>
        <li>Los negocios que aparecen en <b>Empresas</b> responden por sus productos, precios, servicios y por la atención que dan por el chat.</li>
        <li>Lo que pase cuando conoces a alguien (citas, negocios, pagos, encuentros) es decisión y responsabilidad de cada persona. <b>Cuídate:</b> no envíes dinero ni datos de tu banco a desconocidos y, si te vas a ver con alguien, hazlo en un lugar público.</li>
    </ul>

    <h2>4. Lo que no se permite</h2>
    <ul>
        <li>Contenido sexual con menores de edad (se reporta a las autoridades), contenido sexual sin consentimiento, acoso, amenazas, discriminación o incitación a la violencia.</li>
        <li>Estafas, suplantación de personas o empresas, spam, cadenas o mensajes masivos.</li>
        <li>Publicar datos personales, fotos o videos de otras personas sin su autorización.</li>
        <li>Vender o promover drogas, armas o cualquier cosa ilegal.</li>
        <li>Intentar copiar, descargar o recolectar los perfiles, fotos o datos de otros usuarios (con programas, robots o a mano) o dañar el servicio.</li>
    </ul>
    <p>Si incumples, podemos borrar el contenido, limitar o cerrar tu cuenta sin aviso y entregar la información que pidan las autoridades competentes.</p>

    <h2>5. Conocer gente, solicitudes y empresas</h2>
    <ul>
        <li>En <b>Conocer gente</b> salen tu nombre, foto, ciudad, tu descripción y de qué comunidad vienes (por ejemplo, "Peleador PFN"). <b>Nunca</b> se muestran tu correo ni tu celular. Puedes apagarlo en Mi perfil.</li>
        <li>Para cuidar a todos, cada persona ve un número limitado de perfiles por día y puede saludar a máximo <b>5 personas nuevas al día</b>. A quien saludas le llega como <b>solicitud</b>: puede aceptarla, descartarla (ya no le llegan tus mensajes) o bloquearte.</li>
        <li>Los chats con quienes te pasaron su link o tienen tu correo/celular quedan en <b>Mis chats</b>; los de Conocer gente y Empresas, en <b>General</b>.</li>
    </ul>

    <h2>6. Cuánto dura lo que envías</h2>
    <ul>
        <li>Para no saturar el servidor, LP Chat guarda los <b>últimos 3 meses</b> de cada chat: después se borran solos los mensajes, fotos, videos y audios.</li>
        <li>Los <b>estados</b> los pueden ver todos los usuarios de LP Chat y se borran a las <b>24 horas</b>.</li>
        <li>Lo que envías para <b>ver una sola vez</b> se borra apenas se abre. Ojo: nadie puede impedir que la otra persona le tome una foto o una captura a su pantalla.</li>
        <li>LP Chat no es un lugar para guardar información importante: haz tu propia copia de lo que necesites.</li>
    </ul>

    <h2>7. Datos personales</h2>
    <p>Tratamos tus datos según la Ley 1581 de 2012 y nuestra <a href="{{ url('/privacidad') }}">política de privacidad</a>, solo para prestar LP Chat: crear tu cuenta, mostrar tu perfil, entregar tus mensajes y mantener la seguridad. No vendemos tus datos. Puedes conocer, corregir o pedir que borremos tus datos escribiendo a <a href="mailto:{{ \App\Models\Ajuste::valor('correo_legal') ?: config('mail.from.address') }}">{{ \App\Models\Ajuste::valor('correo_legal') ?: config('mail.from.address') }}</a> o desde <a href="{{ url('/eliminar-datos') }}">eliminar mis datos</a>.</p>

    <h2>8. Denuncias</h2>
    <p>En cualquier chat puedes tocar <b>⋮ → Denunciar</b>: la persona queda bloqueada para ti y revisamos el caso.</p>

    <h2>9. Cambios y ley aplicable</h2>
    <p>Podemos mejorar LP Chat y cambiar estas condiciones; los cambios importantes los avisamos en la app. Se rigen por las leyes de Colombia. Las <a href="{{ url('/terminos') }}">condiciones generales de LocalProo</a> también aplican.</p>
</div>
</body>
</html>
