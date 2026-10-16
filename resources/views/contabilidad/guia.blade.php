@extends('layouts.app')

@section('title', 'Guía: retenciones e IVA')

@push('css')
<style>
.gu h2{font-size:1.15rem;font-weight:800;margin-top:1.6rem}
.gu ol li,.gu ul li{margin-bottom:.35rem}
.gu .paso{display:flex;gap:.8rem;margin-bottom:1rem}
.gu .paso b.n{flex:0 0 34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#0f1411;color:#a6f34a}
.gu table{font-size:.88rem}
</style>
@endpush

@section('content')
<div class="lp-dash gu">
    <div class="lp-hello">
        <div><small>Contabilidad</small><h1>Guía de uso: <span>retenciones e IVA</span></h1>
            <p class="text-muted mb-0">Cómo cruzar las facturas de la DIAN con la contabilidad y dejar listo el borrador del 350 y del IVA.</p></div>
        <div class="lp-quick"><a href="{{ route('contabilidad.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Clientes</a></div>
    </div>

    <div class="lp-box">
        <div class="alert alert-info small mb-3"><b>Qué hace y qué no:</b> LocalProo cruza, calcula y arma el borrador. <b>No presenta la declaración</b>: el Formulario 350 y el 300 se presentan en el portal MUISCA de la DIAN con la firma electrónica del declarante. La revisión final y la firma son del contador o del revisor fiscal.</div>

        <h2>Quién la puede usar</h2>
        <ul>
            <li><b>Contadores y analistas contables</b>: al registrarse eligen el tipo de negocio <b>Contador / analista contable</b>.</li>
            <li><b>Cualquier negocio</b>: la activa en <b>Datos del negocio → Módulos</b>.</li>
            <li><b>Asesores de la red LocalPro</b>: con el curso aprobado, la tienen en su panel (menú <b>Red comercial → Contabilidad</b>). Cada asesor tiene su propio espacio con sus clientes, sin crear un negocio aparte; nadie más ve esa información.</li>
        </ul>

        <div class="alert alert-success small mb-3"><b>¿Primera vez?</b> En la pantalla de clientes toque <b>Probar con datos de ejemplo</b>: se crea un cliente de ejemplo con los dos archivos ya cargados. También puede descargar los archivos de muestra para ver el formato: <a href="{{ route('contabilidad.ejemplo.archivo', 'dian') }}">listado de la DIAN</a> · <a href="{{ route('contabilidad.ejemplo.archivo', 'contable') }}">auxiliar contable</a>.</div>

        <h2>1. Prepare los archivos del mes</h2>
        <div class="paso"><b class="n">A</b><div><b>Listado de la DIAN.</b> Entre al portal de facturación electrónica de la DIAN con el usuario del cliente, vaya a la consulta de documentos, filtre el mes (emitidos y recibidos) y descargue el listado en Excel. Súbalo tal cual, sin cambiar los títulos. Trae por cada documento: tipo, CUFE, prefijo y folio, fecha, NIT y nombre de emisor y receptor, IVA, otros impuestos, rete IVA, rete renta, rete ICA y total.</div></div>
        <div class="paso"><b class="n">B</b><div><b>Base contable.</b> En Contapyme (u otro programa: Siigo, World Office, Helisa…) exporte a Excel o CSV el auxiliar de compras y gastos del mes con sus retenciones. Sirve de dos formas: <i>en columnas</i> (una fila por factura con base, IVA y retenciones) o <i>auxiliar por cuenta</i> (una fila por movimiento con cuenta y débito/crédito). Debe traer el NIT del tercero y el número de la factura.</div></div>

        <h2>2. Cree el cliente y abra el mes</h2>
        <ol>
            <li>En <b>Contabilidad</b> toque <b>Nuevo cliente</b>: nombre, NIT, si es persona jurídica o natural, si es agente de retención, si practica autorretención y su periodicidad de IVA.</li>
            <li>Entre al cliente y en <b>Abrir un mes</b> elija mes y año.</li>
        </ol>

        <h2>3. Suba los dos archivos</h2>
        <ol>
            <li>Paso 1: suba el listado de la DIAN.</li>
            <li>Paso 2: suba la base contable. La primera vez le pregunta <b>qué columna es cada dato</b> (ya marca lo que reconoce). La próxima vez, con el mismo programa, lo recuerda.</li>
            <li>El sistema une cada factura de la DIAN con la de la contabilidad por <b>NIT del tercero + número de la factura</b> (sin prefijo ni ceros).</li>
        </ol>

        <h2>4. Revise el cruce</h2>
        <div class="table-responsive"><table class="table table-sm">
            <tr><th>Aviso</th><th>Qué significa</th><th>Qué hacer</th></tr>
            <tr><td><span class="badge" style="background:#fde2e2;color:#a61b1b">Está en la DIAN y no está contabilizada</span></td><td>Le facturaron y no se registró.</td><td>Contabilizarla o verificar si debe rechazarse.</td></tr>
            <tr><td><span class="badge" style="background:#fff1d6;color:#8a5a00">Contabilizada pero no está en la DIAN</span></td><td>No hay factura electrónica de soporte.</td><td>Pedir la factura o el documento soporte; sin soporte el costo y el IVA pueden no ser deducibles/descontables.</td></tr>
            <tr><td><span class="badge" style="background:#e7efff;color:#24489b">El IVA no coincide</span></td><td>El IVA contabilizado es distinto al de la factura.</td><td>Corregir en la contabilidad.</td></tr>
            <tr><td><span class="badge" style="background:#fde2e2;color:#a61b1b">Faltó practicar retención</span></td><td>Según la tabla, la base y el tipo de proveedor, debía retenerse más.</td><td>Revisar el concepto y el proveedor; si aplica, ajustar.</td></tr>
            <tr><td><span class="badge" style="background:#fff1d6;color:#8a5a00">Se retuvo más de lo que da la tabla</span></td><td>Tarifa o concepto distinto.</td><td>Verificar concepto (compras / servicios / honorarios…) y si el proveedor es declarante.</td></tr>
        </table></div>
        <ul>
            <li><b>Concepto:</b> marque facturas y cámbieles el concepto (compras, servicios, honorarios, arrendamientos…). Con "Recordar para ese proveedor" queda para los meses siguientes. Si la contabilidad usa cuentas 2365, el concepto se toma de la subcuenta.</li>
            <li><b>Proveedores:</b> en la ficha del cliente indique para cada tercero si es persona natural o jurídica, si es <b>declarante</b> (cambia la tarifa) y si es <b>autorretenedor</b> (no se le retiene).</li>
            <li><b>Rete IVA:</b> marque en los datos del cliente <b>Practica retención de IVA</b> si es agente de retención de IVA (gran contribuyente, entidad pública o designado por la DIAN, art. 437-2 ET). Entonces el sistema calcula el 15 % del IVA desde la misma base mínima del concepto y avisa si faltó o sobró. Marque como <b>Gran contrib.</b> a los proveedores grandes contribuyentes: entre ellos no se practica.</li>
        </ul>

        <h2>5. Borrador del 350 y del IVA</h2>
        <ul>
            <li><b>Borrador 350:</b> base y retención por concepto, separado en personas jurídicas y naturales, autorretenciones (se escriben en "Estado y notas"), rete IVA y total. Pase los valores a las casillas del formulario vigente en MUISCA.</li>
            <li><b>IVA:</b> IVA generado en ventas menos IVA descontable en compras, por mes y sumado por bimestre o cuatrimestre. Es el punto de partida del Formulario 300.</li>
            <li><b>Rete ICA</b> se muestra aparte: va en la declaración del municipio.</li>
            <li><b>Descargar Excel:</b> papel de trabajo con todas las facturas, diferencias y el borrador.</li>
            <li><b>Certificados de retención:</b> en la ficha del cliente, al lado de cada proveedor (art. 381 ET). Se imprimen o guardan en PDF.</li>
            <li>Marque el mes como <b>Revisado</b> o <b>Presentado en la DIAN</b> para llevar el control.</li>
        </ul>

        <h2>6. Calendario tributario y recordatorios</h2>
        <p>En <b>Calendario</b> ve los vencimientos de la retención en la fuente (350) y del IVA (300) de todos sus clientes, según el último dígito del NIT (sin el dígito de verificación). Aparecen los que vencen en los próximos días y los vencidos sin presentar. Cuando marca el mes como <b>Presentado en la DIAN</b>, el vencimiento queda en verde.</p>
        <p>LocalProo le envía un <b>correo 3 días antes y el mismo día</b> de cada vencimiento pendiente. Se apaga por cliente en sus datos. La retención solo aparece si el cliente es agente de retención o autorretenedor; el IVA según su periodicidad. Las fechas cargadas son las del calendario 2026; el del año siguiente se agrega cuando la DIAN lo publica.</p>

        <h2>7. Tabla de retenciones (manténgala al día)</h2>
        <p>En <b>Tabla de retenciones</b> están las bases en UVT y las tarifas por concepto, con fechas de vigencia. UVT cargadas:
            @foreach ($uvt as $a => $v)<b>{{ $a }}</b>: ${{ number_format($v, 0, ',', '.') }}{{ $loop->last ? '.' : ' · ' }}@endforeach</p>
        <div class="alert alert-warning small">El <b>Decreto 572 de 2025</b> modificó bases y tarifas de retención. El <b>Consejo de Estado suspendió provisionalmente sus artículos 2 al 8</b> (auto del 7 de mayo de 2026) y desde el <b>8 de mayo de 2026</b> volvieron a aplicarse las bases y tarifas anteriores: esas son las que trae la tabla por defecto (compras 27 UVT, servicios 4 UVT…). Para los meses en que rigió el decreto, agregue las filas con sus fechas. Revise la norma vigente antes de cada cierre.</div>

        <h2>Normas y fuentes</h2>
        <ul class="small">
            <li>Estatuto Tributario, Libro II (retención en la fuente), arts. 365 a 419; agentes de retención art. 368 y personas naturales art. 368-2 (30.000 UVT); certificados art. 381. <a href="https://normograma.dian.gov.co/dian/compilacion/docs/estatuto_tributario.htm" target="_blank" rel="noopener">Normograma DIAN – Estatuto Tributario</a></li>
            <li>Retención en la fuente de IVA: arts. 437-1 y 437-2 ET.</li>
            <li>Decreto Único Reglamentario en materia tributaria 1625 de 2016 (bases y tarifas de retención). <a href="https://normograma.dian.gov.co/dian/compilacion/docs/decreto_1625_2016.htm" target="_blank" rel="noopener">Normograma DIAN – DUR 1625</a></li>
            <li>UVT 2026: $52.374, Resolución DIAN 000238 de 2025. <a href="https://incp.org.co/publicaciones/infoincp-publicaciones/impuestos/2025/12/dian-fijo-en-52-374-en-valor-de-la-uvt-para-el-ano-gravable-2026/" target="_blank" rel="noopener">INCP</a></li>
            <li>Suspensión del Decreto 572 de 2025 y ajustes del Formulario 350 desde junio de 2026. <a href="https://siemprealdia.co/colombia/impuestos/formulario-350-declaracion-retencion-fuente/" target="_blank" rel="noopener">Siempre al día</a> · <a href="https://incp.org.co/publicaciones/infoincp-publicaciones/impuestos/2026/07/dian-habilito-el-formulario-350-para-declarar-las-retenciones-en-la-fuente-de-junio/" target="_blank" rel="noopener">INCP</a></li>
            <li>Calendario tributario: Decreto 1625 de 2016, arts. 1.6.1.13.2.30 (IVA bimestral), 1.6.1.13.2.31 (IVA cuatrimestral) y 1.6.1.13.2.33 (retención en la fuente), modificados por el Decreto 2229 de 2023. <a href="https://actualicese.com/plazos-para-declaracion-y-pago-de-retencion-en-la-fuente-2026/" target="_blank" rel="noopener">Actualícese – retención 2026</a> · <a href="https://actualicese.com/plazos-para-la-declaracion-del-iva-2026/" target="_blank" rel="noopener">Actualícese – IVA 2026</a></li>
            <li>Formulario 350 – Declaración de retenciones en la fuente. <a href="https://www.dian.gov.co/Prensa/Paginas/ng-formulario-350-declaracion-retencion-fuente.aspx" target="_blank" rel="noopener">DIAN</a></li>
        </ul>
        <p class="small text-muted mb-0">LocalProo es una herramienta de apoyo; no reemplaza el criterio profesional del contador público ni constituye asesoría tributaria.</p>
    </div>
</div>
@endsection
