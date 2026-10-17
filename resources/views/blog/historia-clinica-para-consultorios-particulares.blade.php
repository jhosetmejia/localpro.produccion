@extends('blog._base')
@section('titulo', $a['titulo'])
@section('descripcion', $a['resumen'])
@section('volver')@endsection
@section('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $a['titulo'], 'description' => $a['resumen'], 'datePublished' => $a['publicado'], 'dateModified' => $a['publicado'], 'inLanguage' => 'es-CO', 'mainEntityOfPage' => url()->current(), 'author' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')], 'publisher' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
@section('cuerpo')
<h1>{{ $a['titulo'] }}</h1>
<div class="f">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>

<p>Muchos consultorios particulares todavía trabajan con carpetas de papel, una agenda en cuaderno y fórmulas escritas a mano. Se pierden citas, cuesta encontrar la historia de un paciente y nadie sabe bien quién la ha visto.</p>
<p>Con el módulo <b>Consultorio</b> de LocalProo, el psicólogo, el odontólogo, la esteticista, el fisioterapeuta o la nutricionista llevan todo en el celular o el computador: <b>pacientes, agenda e historia clínica</b>, y además cobran la consulta en la misma caja.</p>

<h2>Agenda de citas con recordatorio por WhatsApp</h2>
<ul>
    <li>Vea la semana de un vistazo y toque un día para ver sus citas.</li>
    <li>Busque el paciente, ponga hora, duración y motivo. El sistema <b>no deja cruzar</b> dos citas del mismo profesional.</li>
    <li>Con el botón <b>Recordar</b> le escribe al paciente por WhatsApp con el mensaje listo.</li>
    <li>Marque si confirmó, si no asistió o si se canceló, y vea cuántos atendió en el día.</li>
</ul>

<h2>Historia clínica por especialidad</h2>
<p>Cada atención trae los campos de su profesión:</p>
<ul>
    <li><b>Medicina general:</b> revisión por sistemas y examen físico, con signos vitales e IMC automático.</li>
    <li><b>Odontología:</b> examen extra e intraoral, odontograma por piezas, índice de placa y procedimiento del día.</li>
    <li><b>Psicología:</b> examen mental, historia personal y familiar, pruebas aplicadas, intervención y tareas.</li>
    <li><b>Medicina estética:</b> fototipo, zona a tratar, procedimiento con producto y lote, consentimiento y cuidados.</li>
    <li><b>Fisioterapia:</b> dolor (EVA), evaluación, pruebas especiales, objetivos y sesión realizada.</li>
    <li><b>Nutrición:</b> medidas, recordatorio de 24 horas, laboratorios y plan alimentario.</li>
</ul>
<p>Los <b>diagnósticos CIE-10</b> se buscan por palabra ("lumbago", "caries", "ansiedad") y se eligen de la lista. Mientras escribe, la atención se guarda sola.</p>

<div class="box">
    <b>Cerrada es cerrada:</b> al tocar <b>Cerrar y firmar</b>, la atención queda con el nombre, la especialidad y el registro del profesional, y una huella de integridad. Ya no se puede cambiar: si hay un error se agrega una <b>nota aclaratoria</b> con fecha y autor, como pide la Resolución 1995 de 1999.
</div>

<h2>Fórmulas, incapacidades e historia completa</h2>
<p>En la misma atención escribe la fórmula y los días de incapacidad. Al cerrarla, imprime o guarda en PDF la <b>fórmula médica</b>, el <b>certificado de incapacidad</b> o la <b>historia completa</b> del paciente, con el logo del consultorio y su firma.</p>

<h2>Su información, protegida</h2>
<ul>
    <li><b>Reservada:</b> la recepcionista maneja la agenda y los datos de contacto, pero no ve la historia clínica.</li>
    <li><b>Cifrada:</b> motivo de consulta, diagnósticos, fórmulas, alergias y antecedentes se guardan cifrados.</li>
    <li><b>Con registro:</b> el dueño ve quién abrió, escribió o imprimió cada historia.</li>
    <li><b>Se conserva:</b> un paciente con historia clínica no se puede borrar.</li>
    <li><b>Datos personales:</b> se registra la autorización del paciente según la Ley 1581 de 2012.</li>
</ul>

<h2>Y cobra en la misma caja</h2>
<p>La consulta de primera vez, el control, la valoración o el paquete de sesiones se cobran en la caja de LocalProo como cualquier venta, con tiquete por WhatsApp y reportes de lo que entra cada día.</p>

<h2>En resumen</h2>
<ul>
    <li>Pacientes y agenda con recordatorio por WhatsApp.</li>
    <li>Historia clínica por especialidad, con CIE-10, firmada y sin cambios después de cerrada.</li>
    <li>Fórmulas, incapacidades e historia completa para imprimir.</li>
    <li>Datos cifrados, reservados y con registro de accesos.</li>
</ul>
<p style="color:#6b7570;font-size:.9rem">Pensado para consulta particular. La facturación a EPS (RIPS) y la interoperabilidad con el Ministerio de Salud vienen en una siguiente fase.</p>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis en su consultorio</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Actívelo en <b>Datos del negocio → Módulos → Consultorio</b>.</p>
@endsection
