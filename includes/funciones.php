
<?php

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// --- Protección CSRF (token único por sesión) ---

// Devuelve el token de la sesión. Si todavía no existe, lo genera.
function csrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Campo oculto que hay que incluir en cada formulario con método POST.
function csrfCampo() {
    return '<input type="hidden" name="csrf_token" value="' .
           e(csrfToken()) . '">';
}

// Corta el envío si el token recibido no coincide con el de la sesión.
// Evita que otra página mande formularios en nombre del usuario logueado.
function verificarCsrf() {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        $token === '' ||
        !hash_equals(csrfToken(), $token)
    ) {
        http_response_code(403);
        exit(
            'El formulario no es válido o la sesión expiró. ' .
            'Usá el botón de atrás del navegador y volvé a intentar.'
        );
    }
}

// Formatea el peso de un producto para mostrarlo en pantalla (ej: 500 g, 1 kg).
function formatPeso($peso, $unidad) {
    if ($peso === null || $peso === '') {
        return '-';
    }

    // 1,00 -> 1 / 500,00 -> 500 / 1,50 -> 1,5
    $texto = number_format((float) $peso, 2, ',', '.');
    $texto = rtrim(rtrim($texto, '0'), ',');

    return $texto . ' ' . ($unidad === 'kg' ? 'kg' : 'g');
}
