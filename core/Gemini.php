<?php
/**
 * Cliente mínimo de la API de Gemini (Google AI Studio), con cURL.
 * La clave se lee de config/gemini.local.php (que NO se sube a git) o de la variable GEMINI_API_KEY.
 */
class Gemini
{
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public static function config(): array
    {
        $c = [];
        $f = ROOT . '/config/gemini.local.php';
        if (is_file($f)) {
            $c = (array)require $f;
        }
        $key = trim((string)(($c['key'] ?? '') ?: getenv('GEMINI_API_KEY')));
        // Si un modelo no está disponible (404) o se agotó su cuota (429), prueba con el siguiente.
        $modelos = $c['modelos'] ?? ['gemini-3.5-flash', 'gemini-3.1-flash-lite', 'gemini-2.5-flash'];
        return [
            'key'     => $key,
            'modelos' => array_values((array)$modelos),
            // Usuario MySQL de solo lectura para el asistente (se crea con database/migracion_ia_v5.sql)
            'db_user' => (string)($c['db_user'] ?? 'ia_lector'),
            'db_pass' => (string)($c['db_pass'] ?? 'ia_lector_2026'),
        ];
    }

    public static function disponible(): bool
    {
        return self::config()['key'] !== '';
    }

    /**
     * $historial: [['rol' => 'user'|'model', 'texto' => '...'], ...]  (el último es la pregunta nueva)
     * Devuelve el texto de la respuesta o lanza RuntimeException con un mensaje legible.
     */
    public static function responder(string $sistema, array $historial): string
    {
        $cfg = self::config();
        if ($cfg['key'] === '') {
            throw new RuntimeException('Falta la clave de Gemini. Crea config/gemini.local.php (mira las instrucciones arriba).');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP no tiene la extensión cURL activada (actívala en Laragon > PHP > Extensiones > curl).');
        }

        $contenido = [];
        foreach ($historial as $h) {
            $contenido[] = [
                'role'  => $h['rol'] === 'model' ? 'model' : 'user',
                'parts' => [['text' => (string)$h['texto']]],
            ];
        }
        $cuerpo = json_encode([
            'systemInstruction' => ['parts' => [['text' => $sistema]]],
            'contents'          => $contenido,
            'generationConfig'  => ['temperature' => 0.3, 'maxOutputTokens' => 4096],
        ], JSON_UNESCAPED_UNICODE);

        $ultimo = 'No se pudo obtener respuesta.';
        foreach ($cfg['modelos'] as $modelo) {
            [$codigo, $resp] = self::llamar(sprintf(self::URL, rawurlencode((string)$modelo)), $cfg['key'], $cuerpo);
            $detalle = (string)($resp['error']['message'] ?? '');

            if ($codigo === 200) {
                $texto = '';
                foreach ($resp['candidates'][0]['content']['parts'] ?? [] as $p) {
                    if (!empty($p['text']) && empty($p['thought'])) {
                        $texto .= $p['text'];
                    }
                }
                if (trim($texto) !== '') {
                    return trim($texto);
                }
                $motivo = $resp['candidates'][0]['finishReason'] ?? ($resp['promptFeedback']['blockReason'] ?? 'sin texto');
                throw new RuntimeException('Gemini no devolvió texto (' . $motivo . '). Prueba con otra pregunta.');
            }
            if ($codigo === 429) {
                $ultimo = 'Se alcanzó el límite gratuito de Gemini. Espera un minuto e inténtalo otra vez.';
                continue;
            }
            if ($codigo === 404 || $codigo >= 500) {
                $ultimo = "El modelo $modelo no está disponible ahora (HTTP $codigo). $detalle";
                continue;
            }
            if (($codigo === 400 && stripos($detalle, "API key") !== false) || $codigo === 401 || $codigo === 403) {
                throw new RuntimeException('Gemini rechazó la clave (HTTP ' . $codigo . '). Revisa config/gemini.local.php. ' . $detalle);
            }
            throw new RuntimeException("Gemini respondió HTTP $codigo. $detalle");
        }
        throw new RuntimeException($ultimo);
    }

    /**
     * Igual que responder(), pero Gemini puede pedir consultas a la base de datos (function calling).
     * $ejecutor(string $sql): array  -> ejecuta la consulta y devuelve el resultado, o lanza RuntimeException.
     * $consultas se llena con lo que se consultó (para mostrarlo al administrador): [['sql'=>..., 'filas'=>n, 'error'=>?], ...]
     */
    public static function responderConBd(string $sistema, array $historial, callable $ejecutor, array &$consultas): string
    {
        $cfg = self::config();
        if ($cfg['key'] === '') {
            throw new RuntimeException('Falta la clave de Gemini. Crea config/gemini.local.php (mira las instrucciones arriba).');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP no tiene la extensión cURL activada (actívala en Laragon > PHP > Extensiones > curl).');
        }
        $base = [];
        foreach ($historial as $h) {
            $base[] = ['role' => $h['rol'] === 'model' ? 'model' : 'user', 'parts' => [['text' => (string)$h['texto']]]];
        }
        $herramientas = [[ 'functionDeclarations' => [[
            'name'        => 'consultar_bd',
            'description' => 'Ejecuta UNA consulta SQL SELECT (MySQL 8) de solo lectura sobre las vistas ia_* del negocio y devuelve las filas (máx. ' . IaBd::MAX_FILAS . '). Úsala siempre que necesites cifras o datos; agrupa y suma en SQL en vez de pedir filas sueltas.',
            'parameters'  => [
                'type'       => 'OBJECT',
                'properties' => ['sql' => ['type' => 'STRING', 'description' => 'Consulta SELECT completa, sin punto y coma final.']],
                'required'   => ['sql'],
            ],
        ]]]];

        $ultimo = 'No se pudo obtener respuesta.';
        foreach ($cfg['modelos'] as $modelo) {
            $contenido = $base;
            $consultas = [];
            $reintentarConOtroModelo = false;

            for ($vuelta = 0; $vuelta < 8; $vuelta++) {
                $cuerpo = json_encode([
                    'systemInstruction' => ['parts' => [['text' => $sistema]]],
                    'contents'          => $contenido,
                    'tools'             => $herramientas,
                    'generationConfig'  => ['temperature' => 0.2, 'maxOutputTokens' => 8192],
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

                [$codigo, $resp] = self::llamar(sprintf(self::URL, rawurlencode((string)$modelo)), $cfg['key'], $cuerpo);
                $detalle = (string)($resp['error']['message'] ?? '');

                if ($codigo !== 200) {
                    if ($codigo === 429) {
                        $ultimo = 'Se alcanzó el límite gratuito de Gemini. Espera un minuto e inténtalo otra vez.';
                    } elseif ($codigo === 404 || $codigo >= 500 || ($codigo === 400 && $vuelta === 0 && stripos($detalle, 'tool') !== false)) {
                        $ultimo = "El modelo $modelo no está disponible ahora (HTTP $codigo). $detalle";
                    } elseif (($codigo === 400 && stripos($detalle, 'API key') !== false) || $codigo === 401 || $codigo === 403) {
                        throw new RuntimeException('Gemini rechazó la clave (HTTP ' . $codigo . '). Revisa config/gemini.local.php. ' . $detalle);
                    } else {
                        throw new RuntimeException("Gemini respondió HTTP $codigo. $detalle");
                    }
                    $reintentarConOtroModelo = true;
                    break;
                }

                $cand  = $resp['candidates'][0] ?? [];
                $partes = $cand['content']['parts'] ?? [];
                $llamadas = [];
                $texto = '';
                foreach ($partes as $p) {
                    if (isset($p['functionCall'])) {
                        $llamadas[] = $p['functionCall'];
                    } elseif (!empty($p['text']) && empty($p['thought'])) {
                        $texto .= $p['text'];
                    }
                }

                if (!$llamadas) {
                    if (trim($texto) !== '') {
                        return trim($texto);
                    }
                    $motivo = $cand['finishReason'] ?? ($resp['promptFeedback']['blockReason'] ?? 'sin texto');
                    throw new RuntimeException('Gemini no devolvió texto (' . $motivo . '). Prueba con otra pregunta.');
                }

                // Se devuelve el turno del modelo tal cual llegó (incluye thoughtSignature, que Gemini 3 exige).
                $contenido[] = ['role' => 'model', 'parts' => $partes];
                $respuestas = [];
                foreach ($llamadas as $ll) {
                    $sql = (string)($ll['args']['sql'] ?? '');
                    try {
                        $r = $ejecutor($sql);
                        $consultas[] = ['sql' => $sql, 'filas' => count($r['filas']), 'error' => null];
                        $salida = ['columnas' => $r['columnas'], 'filas' => $r['filas'], 'truncado' => $r['truncado']];
                    } catch (Throwable $e) {
                        $consultas[] = ['sql' => $sql, 'filas' => 0, 'error' => $e->getMessage()];
                        $salida = ['error' => $e->getMessage() . ' Corrige la consulta e inténtalo de nuevo.'];
                    }
                    $respuestas[] = ['functionResponse' => ['name' => (string)($ll['name'] ?? 'consultar_bd'), 'response' => $salida]];
                }
                $contenido[] = ['role' => 'user', 'parts' => $respuestas];
            }
            if (!$reintentarConOtroModelo) {
                $ultimo = 'La consulta fue demasiado compleja. Intenta una pregunta más concreta.';
            }
        }
        throw new RuntimeException($ultimo);
    }

    /** @return array{0:int,1:?array} [código HTTP, JSON decodificado] */
    private static function llamar(string $url, string $key, string $cuerpo): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $key],
        ]);
        $salida = curl_exec($ch);
        if ($salida === false) {
            $m = curl_error($ch);
            curl_close($ch);
            if (stripos($m, 'SSL certificate') !== false) {
                $m .= ' (Falta el certificado raíz: en php.ini define curl.cainfo con la ruta de cacert.pem.)';
            }
            throw new RuntimeException('No se pudo conectar con Gemini: ' . $m);
        }
        $codigo = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $json = json_decode($salida, true);
        return [$codigo, is_array($json) ? $json : null];
    }
}
