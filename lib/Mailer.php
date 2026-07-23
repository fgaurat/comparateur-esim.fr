<?php

/**
 * Mailer — envoi de mail multi-driver (brevo | smtp)
 *
 * Usage :
 *   $ok = Mailer::send($config, [
 *       'to'      => ['email' => 'dest@example.com', 'name' => 'Destinataire'],
 *       'from'    => ['email' => 'no-reply@example.com', 'name' => 'Mon Site'],
 *       'replyTo' => ['email' => 'user@example.com', 'name' => 'User'],
 *       'subject' => 'Sujet du mail',
 *       'html'    => '<p>Corps HTML</p>',
 *       'text'    => 'Corps texte',
 *   ]);
 *
 * Config :
 *   'mailer'        => 'brevo' | 'smtp'   (défaut : 'smtp')
 *   'brevo_api_key' => '...'              (requis si mailer = brevo)
 */
class Mailer
{
    /**
     * Envoie un mail. Retourne true en cas de succès, false sinon.
     *
     * @param array $config  Configuration globale du site
     * @param array $message Clés : to, from, replyTo, subject, html, text
     */
    public static function send(array $config, array $message): bool
    {
        $driver = $config['mailer'] ?? 'smtp';

        return match ($driver) {
            'brevo' => self::sendBrevo($config, $message),
            default => self::sendSmtp($message),
        };
    }

    // -------------------------------------------------------------------------

    private static function sendBrevo(array $config, array $message): bool
    {
        $apiKey = $config['brevo_api_key'] ?? '';
        if (empty($apiKey)) {
            error_log('[Mailer] brevo_api_key manquante dans la configuration');
            return false;
        }

        $payload = [
            'sender'      => $message['from'],
            'to'          => [$message['to']],
            'subject'     => $message['subject'],
            'htmlContent' => $message['html'] ?? '',
            'textContent' => $message['text'] ?? '',
        ];

        if (!empty($message['replyTo'])) {
            $payload['replyTo'] = $message['replyTo'];
        }

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'api-key: ' . $apiKey,
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log('[Mailer] Brevo error ' . $httpCode . ' : ' . ($curlErr ?: $response));
        return false;
    }

    private static function sendSmtp(array $message): bool
    {
        $to      = $message['to']['email'];
        $subject = '=?UTF-8?B?' . base64_encode($message['subject']) . '?=';
        $body    = $message['text'] ?? strip_tags($message['html'] ?? '');

        $headers   = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        if (!empty($message['from'])) {
            $from      = $message['from'];
            $headers[] = 'From: ' . $from['name'] . ' <' . $from['email'] . '>';
        }

        if (!empty($message['replyTo'])) {
            $r         = $message['replyTo'];
            $headers[] = 'Reply-To: ' . $r['name'] . ' <' . $r['email'] . '>';
        }

        $result = mail($to, $subject, base64_encode($body), implode("\r\n", $headers));

        if (!$result) {
            error_log('[Mailer] mail() a échoué pour : ' . $to);
        }

        return $result;
    }
}
