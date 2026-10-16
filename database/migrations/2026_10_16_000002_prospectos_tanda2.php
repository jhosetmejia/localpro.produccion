<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Segunda tanda de prospectos: firmas contables de 20 ciudades con página web (el sistema busca el correo publicado)
return new class extends Migration
{
    public function up(): void
    {
        $lista = [
            ['F&S Contadores', 'Bucaramanga', 'http://fysco.co/', '+57 310 8169808'],
            ['Contadores Públicos Neiva', 'Neiva', 'https://sterlingyco.com/contacto-local/', '+57 318 7784135'],
            ['Abacus Gestión Contable S.A.S.', 'Pereira', 'http://abacusgestioncontable.com/', '+57 313 5293152'],
            ['AIL S.A.S contadores públicos', 'Ibagué', 'https://contadorespublicosailsas.lovable.app/', '+57 311 5464880'],
            ['ALIANZA CONTABLE', 'Bogotá', 'https://alianzacontable.com/', '+57 318 7573009'],
            ['Outsourcing Contable', 'Bogotá', 'http://outcontable.com/', '+57 315 5519164'],
            ['OPYT Consulting', 'Cali', 'https://opytconsulting.com.co/', '+57 321 2477559'],
            ['Millán & Asociados Auditores y Consultores de Negocios S.A.', 'Cali', 'http://millanyasociados.com/', '+57 602 8895274'],
            ['Alfredo López y Cía S.A.S.', 'Cali', 'https://alfredolopez.com.co/', '+57 316 4466649'],
            ['Peña Molina & Asociados', 'Barranquilla', 'http://www.pma.com.co/', '+57 301 2008819'],
            ['Confia Services Office', 'Bucaramanga', 'https://confiaservices.co/', '+57 301 5971567'],
            ['ACIERTO CONTABLE', 'Medellín', 'https://www.aciertocontable.com/', '+57 310 3854063'],
            ['A&C Alvarez & Cadavid', 'Medellín', 'https://www.alvarezycadavid.com/', '+57 300 1316119'],
            ['Conta2 S.A.S.', 'Medellín', 'http://www.conta2.co/', '+57 310 5610515'],
            ['ContableMente Asesores', 'Medellín', 'https://contablementeasesores.com/', '+57 321 7523626'],
            ['M G CONTADORES', 'Pereira', 'https://www.mg-contadores.com/', '+57 310 3752078'],
            ['Jurifinanzas SAS', 'Neiva', 'http://www.jurifinanzas.co/', '+57 302 8396992'],
            ['AIPSN', 'Medellín', 'https://www.aipsn.com/', '+57 310 5075121'],
            ['GEA Auditores y Consultores', 'Bucaramanga', 'https://geauditores.com/', '+57 312 5584508'],
            ['Contador Villavicencio', 'Villavicencio', 'https://www.contador-villavicencio.online/', '+57 314 3487159'],
            ['TRIVUTA', 'Villavicencio', 'http://www.trivuta.com/', '+57 314 4668956'],
            ['BESITZ SAS- Contadores y Auditores', 'Barranquilla', 'http://www.besitz.co/', '+57 301 6581069'],
            ['DLT Contadores Especialistas', 'Barranquilla', 'http://www.dltcontadores.com/', '+57 605 3044552'],
            ['AC Asesores', 'Barranquilla', 'http://www.acasesores.co/', '+57 605 3328226'],
            ['ASECONDL SAS Outsourcing Contable y Asesorias Empresariales', 'Cali', 'https://www.asecondl.com/', '+57 602 3742044'],
            ['Sterling y Asociados', 'Villavicencio', 'http://www.sterlingasociados.com/', '+57 314 2739112'],
            ['NAG Servicios empresariales', 'Cali', 'http://www.nag.com.co/', '+57 316 0256295'],
            ['outsourcing contable', 'Bogotá', 'http://afospina.com/', '+57 316 5760613'],
            ['Arango & Asociados Grupo Empresarial S.A.S.', 'Pereira', 'http://arangoyasociados.com/', '+57 314 8204687'],
            ['G & D CONSULTING GROUP S.A.S.', 'Bogotá', 'https://www.gydconsulting.com/', ''],
            ['Inconfin Asociados SAS', 'Palmira', 'https://inconfin.com/', '+57 318 2619708'],
            ['The Colombian Accountant', 'Cartagena', 'https://www.colombianaccountant.com/', ''],
            ['Cassandra Tuñon', 'Cartagena', 'https://cassandratunonagencia.myshopify.com/', '+57 300 5365307'],
            ['Asercont S.A.S', 'Cartagena', 'https://asercontsas.com.co/', '+57 301 7216681'],
            ['Martínez & Martínez Contadores', 'Armenia', 'http://www.martinezcontadores.com/', '+57 301 6350849'],
            ['Asesoría Contable', 'Cartagena', 'https://atce.com.co/', '+57 320 5751543'],
            ['DCM Asesorías y consultorías contables y tributarias', 'Manizales', 'https://www.dcmasesorias.com/', '+57 317 3740703'],
            ['Arcila Asociados S.A.S.', 'Manizales', 'https://www.arcilaasociados.com/', '+57 606 8815720'],
            ['Rentabilidad Total S.A.S. BIC', 'Santa Marta', 'http://www.rentabilidadtotal.com/', '+57 316 0181485'],
            ['P.R.O ASESORES Contables, financieros y Tributarios', 'Tuluá', 'https://proasesorescontables.my.canva.site/', '+57 315 3557404'],
            ['Marggie Núñez Contadores', 'Cúcuta', 'https://mncontador.com/', '+57 317 4627454'],
            ['Contador Público Jesús Galán', 'Santa Marta', 'https://jesusgalancontador.netlify.app/', '+57 310 3566929'],
            ['contamas', 'Cúcuta', 'https://contamas.com/', '+57 322 6543526'],
            ['Asesorías Contables de Córdoba- ASECORD', 'Montería', 'http://asecord.co/', '+57 7835114'],
        ];
        foreach ($lista as [$empresa, $ciudad, $web, $tel]) {
            DB::table('prospectos')->insertOrIgnore([
                'empresa' => $empresa, 'ciudad' => $ciudad ?: null, 'web' => $web, 'telefono' => $tel ?: null,
                'estado' => 'sin_correo', 'token' => Str::random(32), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
