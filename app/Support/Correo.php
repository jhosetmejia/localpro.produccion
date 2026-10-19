<?php

namespace App\Support;

use App\Models\RedConversacion;
use App\Models\RedCuenta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Correo en "Mis redes": el negocio conecta su Gmail, Outlook o el correo de su dominio con IMAP (leer)
 * y SMTP (responder) usando una contraseña de aplicación. Los correos que llegan a la bandeja de entrada
 * aparecen como chats y se responden desde LocalPro en el mismo hilo.
 *
 * Liviano a propósito: se revisa cada 2 minutos junto con Facebook/Instagram, solo se bajan los correos
 * NUEVOS (por UID), máximo 25 por vuelta y sin adjuntos (solo el texto).
 */
class Correo
{
    // Servidores de los proveedores más usados: [imap host, imap puerto, smtp host, smtp puerto, cifrado smtp]
    public const PROVEEDORES = [
        'gmail' => ['Gmail', 'imap.gmail.com', 993, 'smtp.gmail.com', 465, 'ssl', 'https://myaccount.google.com/apppasswords'],
        'outlook' => ['Outlook / Hotmail', 'outlook.office365.com', 993, 'smtp-mail.outlook.com', 587, 'tls', 'https://account.live.com/proofs/AppPassword'],
        'yahoo' => ['Yahoo', 'imap.mail.yahoo.com', 993, 'smtp.mail.yahoo.com', 465, 'ssl', 'https://login.yahoo.com/account/security'],
        'otro' => ['Otro (correo de mi dominio)', '', 993, '', 465, 'ssl', null],
    ];

    public const MAX_POR_VUELTA = 25;
    private const LEER_BYTES = 300000; // de cada correo se lee hasta 300 KB (texto; los adjuntos grandes se ignoran)

    // ---------- Conexión ----------

    /** Prueba IMAP y SMTP con la configuración. Lanza RuntimeException con un mensaje claro si falla. */
    public static function probar(array $cfg): void
    {
        $imap = self::abrir($cfg);
        $imap->cerrar();
        try {
            $t = self::transporte($cfg);
            $t->start();
            $t->stop();
        } catch (\Throwable $e) {
            throw new RuntimeException('Se pudo leer el correo, pero no enviar (SMTP): ' . self::limpiar($e->getMessage()));
        }
    }

    private static function abrir(array $cfg): ImapCliente
    {
        $imap = new ImapCliente($cfg['imap_host'], (int) $cfg['imap_port']);
        $imap->login($cfg['usuario'], $cfg['clave']);
        return $imap;
    }

    private static function transporte(array $cfg): EsmtpTransport
    {
        $ssl = ($cfg['smtp_cifrado'] ?? 'ssl') === 'ssl' || (int) $cfg['smtp_port'] === 465;
        $t = new EsmtpTransport($cfg['smtp_host'], (int) $cfg['smtp_port'], $ssl);
        $t->setUsername($cfg['usuario']);
        $t->setPassword($cfg['clave']);
        return $t;
    }

    public static function limpiar(string $m): string
    {
        $m = preg_replace('/\s+/', ' ', $m);
        if (preg_match('/AUTHENTICATIONFAILED|Invalid credentials|authentication failed|535|Username and Password not accepted|LOGIN failed/i', $m)) {
            return 'El correo o la contraseña de aplicación no son correctos (use una contraseña de aplicación, no su clave normal).';
        }
        return mb_substr($m, 0, 220);
    }

    // ---------- Leer correos nuevos ----------

    public static function sincronizar(RedCuenta $c): int
    {
        TiendaActual::set($c->tienda_id);
        $cfg = $c->correo_config;
        if (!$cfg) return 0;
        $imap = self::abrir($cfg);
        $n = 0;
        try {
            $validez = $imap->seleccionar('INBOX');
            // Si cambió la bandeja (UIDVALIDITY), se empieza de nuevo desde hoy
            if (($cfg['uidvalidity'] ?? null) !== $validez) {
                $cfg['uidvalidity'] = $validez;
                $cfg['uid'] = null;
            }
            if (empty($cfg['uid'])) {
                // Primera vez: solo los correos de los últimos 3 días (los más recientes)
                $uids = $imap->buscar('SINCE ' . now()->subDays(3)->format('d-M-Y'));
                $uids = array_slice($uids, -10);
                if (!$uids) {
                    $todos = $imap->buscar('ALL');
                    $cfg['uid'] = $todos ? max($todos) : 0;
                }
            } else {
                $uids = array_values(array_filter($imap->buscar('UID ' . ((int) $cfg['uid'] + 1) . ':*'), fn ($u) => $u > (int) $cfg['uid']));
            }
            sort($uids);
            foreach (array_slice($uids, 0, self::MAX_POR_VUELTA) as $uid) {
                try {
                    $crudo = $imap->traer($uid, self::LEER_BYTES);
                    if ($crudo !== '' && self::guardar($c, $cfg, $crudo)) $n++;
                } catch (\Throwable $e) {
                    Log::warning('Correo ' . $c->page_nombre . ' UID ' . $uid . ': ' . $e->getMessage());
                }
                $cfg['uid'] = max((int) ($cfg['uid'] ?? 0), $uid);
            }
        } finally {
            $imap->cerrar();
        }
        $c->guardarCorreoConfig($cfg);
        $c->forceFill(['sincronizado_en' => now(), 'estado' => 'activa', 'error' => null])->save();
        \Illuminate\Support\Facades\Cache::forget('correo_fallos:' . $c->id);
        return $n;
    }

    private static function guardar(RedCuenta $c, array $cfg, string $crudo): bool
    {
        $m = Mime::parsear($crudo);
        [$email, $nombre] = Mime::direccion($m['headers']['reply-to'] ?? $m['headers']['from'] ?? '');
        if (!$email || strcasecmp($email, $cfg['email']) === 0) return false;
        // Avisos automáticos que no se responden
        if (preg_match('/^(mailer-daemon|postmaster)@/i', $email)) return false;

        $asunto = Mime::encabezado($m['headers']['subject'] ?? '') ?: '(sin asunto)';
        $mid = trim($m['headers']['message-id'] ?? '') ?: ('uid-' . md5($crudo));
        $fecha = null;
        try {
            $fecha = !empty($m['headers']['date']) ? Carbon::parse(preg_replace('/\s*\(.*\)$/', '', $m['headers']['date']))->setTimezone(config('app.timezone')) : null;
        } catch (\Throwable $e) {
        }
        $texto = Mime::sinCitas($m['texto']);
        $texto = mb_substr(trim("✉️ " . $asunto . "\n\n" . $texto), 0, 4000);

        $cv = RedConversacion::firstOrCreate(
            ['cuenta_id' => $c->id, 'plataforma' => 'correo', 'contacto_id' => mb_strtolower($email)],
            ['tienda_id' => $c->tienda_id, 'contacto_nombre' => $nombre ?: null, 'etiqueta' => 'nuevo']
        );
        if ($nombre && !$cv->contacto_nombre) $cv->contacto_nombre = $nombre;
        $cv->asunto = mb_substr($asunto, 0, 250);
        $cv->ultimo_mid = mb_substr($mid, 0, 250);
        $cv->save();
        return \App\Support\Meta::guardarMensaje($cv, mb_substr($mid, 0, 191), true, $texto, null, null, $fecha ?: now());
    }

    // ---------- Responder ----------

    /** Envía la respuesta en el mismo hilo. Devuelve el Message-ID del correo enviado. */
    public static function enviar(RedConversacion $cv, string $texto): string
    {
        $c = $cv->cuenta;
        $cfg = $c->correo_config;
        if (!$cfg) throw new RuntimeException('El correo no está conectado.');
        $asunto = $cv->asunto ?: 'Respuesta de ' . (TiendaActual::tienda()->nombre ?? 'nuestro negocio');
        if (!preg_match('/^re:/i', $asunto)) $asunto = 'Re: ' . $asunto;

        $correo = (new Email())
            ->from(new Address($cfg['email'], $cfg['nombre'] ?? (TiendaActual::tienda()->nombre ?? '')))
            ->to(new Address($cv->contacto_id, (string) $cv->contacto_nombre))
            ->subject($asunto)
            ->text($texto);
        $mid = sprintf('<%s.%s@localproo.com>', now()->format('YmdHis'), bin2hex(random_bytes(6)));
        $correo->getHeaders()->addIdHeader('Message-ID', trim($mid, '<>'));
        if ($cv->ultimo_mid && !str_starts_with($cv->ultimo_mid, 'uid-')) {
            $correo->getHeaders()->addTextHeader('In-Reply-To', $cv->ultimo_mid);
            $correo->getHeaders()->addTextHeader('References', $cv->ultimo_mid);
        }
        try {
            (new Mailer(self::transporte($cfg)))->send($correo);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo enviar el correo: ' . self::limpiar($e->getMessage()));
        }
        return $mid;
    }
}
