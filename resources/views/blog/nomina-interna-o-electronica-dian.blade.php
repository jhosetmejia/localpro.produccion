@extends('blog._base')
@section('titulo', $a['titulo'])
@section('descripcion', $a['resumen'])
@section('volver')<a class="v" href="{{ route('blog.index') }}">← Blog</a>@endsection
@section('cuerpo')
<h1>{{ $a['titulo'] }}</h1>
<div class="f">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>

<p>Si usted tiene empleados, cada quincena o cada mes le toca hacer cuentas: salario, auxilio de transporte, horas extra, salud, pensión, descuentos… y entregarle a cada uno lo que le corresponde. En LocalProo ahora puede hacer todo eso desde el mismo sistema donde vende y controla su inventario.</p>

<p>Y lo mejor: <b>usted decide si la nómina se queda solo dentro de su negocio o si también la envía a la DIAN.</b></p>

<h2>Tres opciones, usted escoge</h2>
<p>La primera vez que entra a <b>Nómina</b>, el sistema le pregunta cómo la quiere manejar:</p>
<div class="tw"><table class="tabla">
<tr><th>Opción</th><th>Para quién es</th></tr>
<tr><td><b>No tengo empleados</b></td><td>Negocios que trabajan solos. El módulo no le estorba.</td></tr>
<tr><td><b>Nómina sin DIAN (interna)</b></td><td>Quiere liquidar y llevar el control de lo que paga, sin enviar nada a la DIAN.</td></tr>
<tr><td><b>Nómina electrónica DIAN</b></td><td>Además de liquidar, necesita transmitir la nómina a la DIAN.</td></tr>
</table></div>
<p>Puede cambiar de opción cuando quiera desde la configuración de Nómina.</p>

<h2>Nómina interna: sin DIAN y sin enredos</h2>
<p>Si escoge <b>Nómina sin DIAN</b>, el sistema funciona completo, pero solo para usted:</p>
<ul>
    <li>Registra a sus empleados con su salario, cargo y forma de pago.</li>
    <li>Liquida el mes o la quincena: salario, auxilio de transporte, horas extra y recargos, incapacidades, vacaciones, prima, salud, pensión, préstamos y otros descuentos.</li>
    <li>Le muestra cuánto le cuesta cada empleado (aportes y prestaciones) para que no se lleve sorpresas.</li>
    <li>Saca el <b>desprendible de pago</b> de cada empleado, listo para imprimir.</li>
    <li>Guarda el historial de todo lo que ha pagado.</li>
</ul>
<p>No aparece nada de la DIAN: ni envíos, ni documentos electrónicos, ni plazos.</p>

<h2>¿Paga más o menos del mínimo? También se puede</h2>
<p>No todos los negocios pagan exactamente el salario mínimo: hay quien paga más, quien paga por medio tiempo o quien tiene un acuerdo propio con su trabajador. Para eso está la <b>liquidación personalizada</b>:</p>
<ul>
    <li>Usted pone el valor que realmente paga.</li>
    <li>Decide si se le aplica salud y pensión, prestaciones y auxilio de transporte.</li>
    <li>Si el valor queda por debajo del mínimo, el sistema le avisa, pero no lo bloquea.</li>
</ul>

<h2>¿Y si quiero enviarla a la DIAN?</h2>
<p>Escoja <b>Nómina electrónica DIAN</b>. LocalProo arma el documento de cada empleado al mes, le muestra el plazo para transmitirlo y permite hacer notas de ajuste si hay que corregir algo. Usa la misma conexión de la facturación electrónica.</p>
<p>Además, cada empleado tiene el interruptor <b>"Reportar a la DIAN"</b>. Así puede reportar a unos y llevar a otros solo de forma interna.</p>

<div class="ojo">
    <b>Lo que debe saber antes de decidir</b>
    <ul style="margin:8px 0 0">
        <li>La DIAN exige el documento soporte de nómina electrónica para aceptar los pagos laborales como <b>costo o deducción en la declaración de renta</b>. Si maneja la nómina solo de forma interna, esos pagos no le sirven para descontar en renta.</li>
        <li>La nómina interna <b>no reemplaza la planilla de seguridad social (PILA)</b>: los aportes de sus empleados se siguen pagando por el operador de PILA que usted use.</li>
        <li>Pagar menos del mínimo o no afiliar a un trabajador puede traer obligaciones laborales. Si tiene dudas, consulte con su contador o un abogado laboral.</li>
    </ul>
</div>

<h2>En resumen</h2>
<div class="box">
    <ul style="margin:0">
        <li><b>Sí</b> puede manejar la nómina solo de forma interna, sin la DIAN.</li>
        <li><b>Sí</b> puede liquidar salarios por encima o por debajo del mínimo.</li>
        <li><b>Sí</b> puede pasarse a nómina electrónica cuando lo necesite, o reportar solo a algunos empleados.</li>
    </ul>
</div>

<a class="cta" href="{{ route('registro') }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Entre a su cuenta y busque <b>Nómina</b> en el menú.</p>
@endsection
