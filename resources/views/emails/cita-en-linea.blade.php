<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="margin:0;background:#ecfeff;font-family:Arial,Helvetica,sans-serif;color:#083344;padding:24px 12px">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:22px;padding:24px">
    <h1 style="font-size:20px;margin:0 0 10px">Nueva cita pedida en línea</h1>
    <p style="margin:0 0 4px"><b>{{ $cita->paciente?->nombre_completo }}</b> · {{ trim($cita->paciente?->tipo_documento . ' ' . $cita->paciente?->documento) }}</p>
    <p style="margin:0 0 4px">{{ ucfirst($cita->fecha->translatedFormat('l j \d\e F')) }} a las <b>{{ $cita->hora_corta }}</b></p>
    <p style="margin:0 0 4px">{{ $cita->motivo }}{{ $cita->nota ? ' · ' . $cita->nota : '' }}</p>
    <p style="margin:0 0 16px">Celular: {{ $cita->paciente?->telefono }}</p>
    <a href="{{ route('consultorio.agenda', ['fecha' => $cita->fecha->toDateString()]) }}" style="display:inline-block;background:#0891b2;color:#fff;font-weight:bold;text-decoration:none;padding:12px 20px;border-radius:12px">Ver en la agenda</a>
</div>
</body>
</html>
