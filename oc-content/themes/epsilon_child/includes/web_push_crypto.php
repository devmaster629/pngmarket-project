<?php
/**
 * PNG Market — OpenSSL-only Web Push crypto (VAPID + aes128gcm).
 *
 * No libsodium / Composer. Requires OpenSSL with prime256v1 + aes-128-gcm.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'web_push_crypto.php'
) {
    exit;
}

/**
 * @param string $bin
 * @return string
 */
function pngm_b64url_encode($bin)
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

/**
 * @param string $b64
 * @return string|false
 */
function pngm_b64url_decode($b64)
{
    $b64 = strtr((string) $b64, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad > 0) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    return base64_decode($b64, true);
}

/**
 * @param string $data
 * @param int    $len
 * @return string
 */
function pngm_push_pad32($data, $len = 32)
{
    if (strlen($data) > $len) {
        return substr($data, -$len);
    }
    return str_pad($data, $len, "\0", STR_PAD_LEFT);
}

/**
 * HKDF-Extract+Expand with N=1 (RFC 5869), matching web-push-php.
 *
 * @param string $salt
 * @param string $ikm
 * @param string $info
 * @param int    $length
 * @return string
 */
function pngm_push_hkdf($salt, $ikm, $info, $length)
{
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    return substr(hash_hmac('sha256', $info . chr(1), $prk, true), 0, $length);
}

/**
 * Uncompressed P-256 public key (65 bytes: 0x04 || x || y) → PEM.
 *
 * @param string $uncompressed
 * @return string|false
 */
function pngm_push_public_pem($uncompressed)
{
    if (strlen($uncompressed) !== 65 || $uncompressed[0] !== "\x04") {
        return false;
    }

    // SubjectPublicKeyInfo for id-ecPublicKey + prime256v1 + uncompressed point
    $der = hex2bin(
        '3059' // SEQUENCE
        . '3013' // AlgorithmIdentifier
        . '06072a8648ce3d0201' // ecPublicKey
        . '06082a8648ce3d030107' // prime256v1
        . '034200' // BIT STRING, 0 unused bits + 65 bytes
    );
    if ($der === false) {
        return false;
    }
    $der .= $uncompressed;

    return "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode($der), 64, "\n")
        . "-----END PUBLIC KEY-----\n";
}

/**
 * Build EC private key PEM (SEC1) from d + optional public point.
 *
 * @param string      $d32
 * @param string|null $uncompressed65
 * @return string|false
 */
function pngm_push_private_pem($d32, $uncompressed65 = null)
{
    $d32 = pngm_push_pad32($d32, 32);
    if (strlen($d32) !== 32) {
        return false;
    }

    $body = "\x02\x01\x01" // version
        . "\x04\x20" . $d32
        . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"; // curve OID

    if (is_string($uncompressed65) && strlen($uncompressed65) === 65) {
        $body .= "\xa1\x44\x03\x42\x00" . $uncompressed65;
    }

    $der = "\x30" . chr(strlen($body)) . $body;
    return "-----BEGIN EC PRIVATE KEY-----\n"
        . chunk_split(base64_encode($der), 64, "\n")
        . "-----END EC PRIVATE KEY-----\n";
}

/**
 * Convert OpenSSL ECDSA DER signature to JOSE r||s (64 bytes).
 *
 * @param string $der
 * @return string|false
 */
function pngm_push_der_sig_to_jose($der)
{
    $offset = 0;
    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x30) {
        return false;
    }
    $offset++;
    $seqLen = ord($der[$offset]);
    $offset++;
    if ($seqLen & 0x80) {
        $n = $seqLen & 0x7f;
        $seqLen = 0;
        for ($i = 0; $i < $n; $i++) {
            $seqLen = ($seqLen << 8) | ord($der[$offset++]);
        }
    }

    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
        return false;
    }
    $offset++;
    $rLen = ord($der[$offset++]);
    $r = substr($der, $offset, $rLen);
    $offset += $rLen;

    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
        return false;
    }
    $offset++;
    $sLen = ord($der[$offset++]);
    $s = substr($der, $offset, $sLen);

    // Strip leading zero padding from ASN.1 integers
    $r = ltrim($r, "\0");
    $s = ltrim($s, "\0");
    if ($r === '') {
        $r = "\0";
    }
    if ($s === '') {
        $s = "\0";
    }

    return pngm_push_pad32($r, 32) . pngm_push_pad32($s, 32);
}

/**
 * Generate a new VAPID key pair.
 *
 * @return array{publicKey:string,privateKey:string,privatePem:string}|false
 */
function pngm_push_generate_vapid_keys()
{
    $res = openssl_pkey_new(array(
        'ec' => array(
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ),
    ));
    if ($res === false) {
        return false;
    }

    $details = openssl_pkey_get_details($res);
    if ($details === false || empty($details['ec']['x']) || empty($details['ec']['y']) || empty($details['ec']['d'])) {
        return false;
    }

    $x = pngm_push_pad32($details['ec']['x'], 32);
    $y = pngm_push_pad32($details['ec']['y'], 32);
    $d = pngm_push_pad32($details['ec']['d'], 32);
    $pub = "\x04" . $x . $y;

    $pem = '';
    if (!openssl_pkey_export($res, $pem) || $pem === '') {
        $pem = pngm_push_private_pem($d, $pub);
        if ($pem === false) {
            return false;
        }
    }

    return array(
        'publicKey' => pngm_b64url_encode($pub),
        'privateKey' => pngm_b64url_encode($d),
        'privatePem' => $pem,
    );
}

/**
 * Create VAPID Authorization header value for aes128gcm.
 *
 * @param string $audience  Origin of push service, e.g. https://fcm.googleapis.com
 * @param string $subject   mailto: or https URL
 * @param string $publicKeyB64url
 * @param string $privatePem
 * @return string|false
 */
function pngm_push_vapid_authorization($audience, $subject, $publicKeyB64url, $privatePem)
{
    $header = pngm_b64url_encode(json_encode(array('typ' => 'JWT', 'alg' => 'ES256')));
    $payload = pngm_b64url_encode(json_encode(array(
        'aud' => $audience,
        'exp' => time() + 12 * 3600,
        'sub' => $subject,
    ), JSON_UNESCAPED_SLASHES));

    $signingInput = $header . '.' . $payload;
    $key = openssl_pkey_get_private($privatePem);
    if ($key === false) {
        return false;
    }

    $derSig = '';
    if (!openssl_sign($signingInput, $derSig, $key, OPENSSL_ALGO_SHA256)) {
        return false;
    }

    $jose = pngm_push_der_sig_to_jose($derSig);
    if ($jose === false) {
        return false;
    }

    $jwt = $signingInput . '.' . pngm_b64url_encode($jose);
    return 'vapid t=' . $jwt . ', k=' . $publicKeyB64url;
}

/**
 * Encrypt a UTF-8 payload for a push subscription (RFC 8291 aes128gcm).
 *
 * @param string $payload
 * @param string $userPublicB64  p256dh
 * @param string $userAuthB64    auth
 * @return array{body:string,localPublicKey:string}|false
 */
function pngm_push_encrypt_aes128gcm($payload, $userPublicB64, $userAuthB64)
{
    $userPublic = pngm_b64url_decode($userPublicB64);
    $userAuth = pngm_b64url_decode($userAuthB64);
    if ($userPublic === false || $userAuth === false || strlen($userPublic) !== 65) {
        return false;
    }

    $local = openssl_pkey_new(array(
        'ec' => array(
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ),
    ));
    if ($local === false) {
        return false;
    }

    $details = openssl_pkey_get_details($local);
    if ($details === false || empty($details['ec'])) {
        return false;
    }
    $localPublic = "\x04"
        . pngm_push_pad32($details['ec']['x'], 32)
        . pngm_push_pad32($details['ec']['y'], 32);

    $userPem = pngm_push_public_pem($userPublic);
    if ($userPem === false) {
        return false;
    }

    $shared = openssl_pkey_derive($userPem, $local, 32);
    if ($shared === false) {
        return false;
    }
    $shared = pngm_push_pad32($shared, 32);

    $ikmInfo = 'WebPush: info' . chr(0) . $userPublic . $localPublic;
    $ikm = pngm_push_hkdf($userAuth, $shared, $ikmInfo, 32);

    $salt = random_bytes(16);
    $cek = pngm_push_hkdf($salt, $ikm, 'Content-Encoding: aes128gcm' . chr(0), 16);
    $nonce = pngm_push_hkdf($salt, $ikm, 'Content-Encoding: nonce' . chr(0), 12);

    // Minimal padding: payload || 0x02 (last-record delimiter)
    $plaintext = $payload . chr(2);

    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($cipher === false) {
        return false;
    }

    $header = $salt . pack('N', 4096) . chr(strlen($localPublic)) . $localPublic;
    return array(
        'body' => $header . $cipher . $tag,
        'localPublicKey' => $localPublic,
    );
}

/**
 * HTTP POST a Web Push message.
 *
 * @param string $endpoint
 * @param string $body
 * @param array  $headers  name => value
 * @return array{ok:bool,status:int,body:string}
 */
function pngm_push_http_post($endpoint, $body, $headers)
{
    $headerLines = array();
    foreach ($headers as $name => $value) {
        $headerLines[] = $name . ': ' . $value;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        ));
        $respBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($respBody === false) {
            return array('ok' => false, 'status' => 0, 'body' => $err);
        }
        return array(
            'ok' => ($status >= 200 && $status < 300),
            'status' => $status,
            'body' => (string) $respBody,
        );
    }

    $ctx = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => implode("\r\n", $headerLines),
            'content' => $body,
            'timeout' => 20,
            'ignore_errors' => true,
        ),
        'ssl' => array(
            'verify_peer' => true,
            'verify_peer_name' => true,
        ),
    ));
    $respBody = @file_get_contents($endpoint, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    return array(
        'ok' => ($status >= 200 && $status < 300),
        'status' => $status,
        'body' => is_string($respBody) ? $respBody : '',
    );
}
