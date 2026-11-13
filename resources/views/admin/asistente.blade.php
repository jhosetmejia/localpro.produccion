{{-- Asistente IA (kit DiCreWeb): lo que pregunta la gente, lo que no supo responder y lo que el equipo le enseña --}}
@extends('layouts.app')

@section('title', 'Asistente IA')

@section('content')
    <div style="padding:20px 16px;">
        @include('partials.asistente-admin', ['rutas' => [
            'base' => route('admin.asistente'),
            'ensenar' => route('admin.asistente.ensenar'),
            'calificar' => route('admin.asistente.calificar'),
            'conocimiento' => route('admin.asistente.conocimiento'),
        ]])
    </div>
@endsection
