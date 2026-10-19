<?php

namespace App\Support;

use RuntimeException;

/** Cliente IMAP mínimo (sin extensiones de PHP): login, seleccionar, buscar por UID y traer un correo. */
class ImapCliente
{
    private $s;
    private int $n = 0;

    public function __construct(string $host, int $puerto)
    {
        if ($host === '') throw new RuntimeException('Falta el servidor IMAP.');
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $destino = str_contains($host, '://') ? $host . ':' . $puerto : 'ssl://' . $host . ':' . $puerto; // tcp:// solo para pruebas
        $this->s = @stream_socket_client($destino, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->s) throw new RuntimeException('No se pudo conectar con ' . $host . ' (' . ($errstr ?: 'sin respuesta') . ').');
        stream_set_timeout($this->s, 30);
        $this->linea(); // saludo del servidor
    }

    private function linea(): string
    {
        $l = fgets($this->s);
        if ($l === false) throw new RuntimeException('El servidor de correo cerró la conexión.');
        return $l;
    }

    private static function q(string $s): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"';
    }

    /** Envía un comando y devuelve [líneas (con los literales unidos), estado OK/NO/BAD, texto]. */
    private function cmd(string $c): array
    {
        $tag = 'A' . (++$this->n);
        fwrite($this->s, $tag . ' ' . $c . "\r\n");
        $lineas = [];
        while (true) {
            $l = $this->linea();
            // Literal {n}: se leen exactamente n bytes y se pegan a la línea
            while (preg_match('/\{(\d+)\}\r\n$/', $l, $mm)) {
                $falta = (int) $mm[1];
                $dato = '';
                while ($falta > 0 && !feof($this->s)) {
                    $parte = fread($this->s, min(8192, $falta));
                    if ($parte === false || $parte === '') break;
                    $dato .= $parte;
                    $falta -= strlen($parte);
                }
                $l .= $dato . $this->linea();
            }
            if (str_starts_with($l, $tag . ' ')) {
                $resto = substr($l, strlen($tag) + 1);
                $estado = strtoupper(trim((string) strtok(trim($resto), ' ')));
                return [$lineas, $estado, trim($resto)];
            }
            $lineas[] = $l;
        }
    }

    public function login(string $usuario, string $clave): void
    {
        [, $estado, $txt] = $this->cmd('LOGIN ' . self::q($usuario) . ' ' . self::q($clave));
        if ($estado !== 'OK') throw new RuntimeException(Correo::limpiar($txt));
    }

    /** Selecciona la carpeta (solo lectura) y devuelve su UIDVALIDITY. */
    public function seleccionar(string $carpeta): string
    {
        [$lineas, $estado, $txt] = $this->cmd('EXAMINE ' . self::q($carpeta));
        if ($estado !== 'OK') throw new RuntimeException('No se pudo abrir la bandeja de entrada: ' . $txt);
        foreach ($lineas as $l) if (preg_match('/UIDVALIDITY (\d+)/i', $l, $m)) return $m[1];
        return '0';
    }

    /** UID SEARCH: devuelve la lista de UIDs. */
    public function buscar(string $criterio): array
    {
        [$lineas, $estado] = $this->cmd('UID SEARCH ' . $criterio);
        if ($estado !== 'OK') return [];
        $uids = [];
        foreach ($lineas as $l) {
            if (stripos($l, '* SEARCH') === 0) {
                foreach (preg_split('/\s+/', trim(substr($l, 8))) as $u) if (ctype_digit($u)) $uids[] = (int) $u;
            }
        }
        return $uids;
    }

    /** Trae el correo (encabezados y el comienzo del cuerpo) sin marcarlo como leído. */
    public function traer(int $uid, int $max): string
    {
        [$lineas, $estado] = $this->cmd('UID FETCH ' . $uid . ' (BODY.PEEK[]<0.' . $max . '>)');
        if ($estado !== 'OK') return '';
        $todo = implode('', $lineas);
        if (!preg_match('/\{(\d+)\}\r\n/', $todo, $m, PREG_OFFSET_CAPTURE)) return '';
        return substr($todo, $m[0][1] + strlen($m[0][0]), (int) $m[1][0]);
    }

    public function cerrar(): void
    {
        if ($this->s) {
            try { $this->cmd('LOGOUT'); } catch (\Throwable $e) {}
            @fclose($this->s);
            $this->s = null;
        }
    }
}
