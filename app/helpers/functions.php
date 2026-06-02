<?php

if (!function_exists('formatCpf')) {
    function formatCpf($cpf)
    {
        return FormatHelper::cpf($cpf);
    }
}

if (!function_exists('formatTelefone')) {
    function formatTelefone($telefone)
    {
        return FormatHelper::phone($telefone);
    }
}

if (!function_exists('formatCnpj')) {
    function formatCnpj($cnpj)
    {
        return FormatHelper::cnpj($cnpj);
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($value, $currency = 'R$ ')
    {
        return FormatHelper::currency($value, $currency);
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd/m/Y H:i')
    {
        return FormatHelper::date($date, $format);
    }
}

if (!function_exists('formatStock')) {
    function formatStock($quantity)
    {
        return FormatHelper::stock($quantity);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateCpf')) {
    function validateCpf($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('validateCnpj')) {
    function validateCnpj($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        $soma = 0;
        $multiplicador = 5;

        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }

        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;

        if ($cnpj[12] != $digito1) {
            return false;
        }

        $soma = 0;
        $multiplicador = 6;

        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }

        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;

        return $cnpj[13] == $digito2;
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validatePhone')) {
    function validatePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
}

if (!function_exists('generateSlug')) {
    function generateSlug($string)
    {
        $string = strtolower($string);
        $string = preg_replace('/[áàâãäå]/', 'a', $string);
        $string = preg_replace('/[éèêë]/', 'e', $string);
        $string = preg_replace('/[íìîï]/', 'i', $string);
        $string = preg_replace('/[óòôõö]/', 'o', $string);
        $string = preg_replace('/[úùûü]/', 'u', $string);
        $string = preg_replace('/[ç]/', 'c', $string);
        $string = preg_replace('/[^a-z0-9\s]/', '', $string);
        $string = preg_replace('/\s+/', '-', $string);
        return trim($string, '-');
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 100, $suffix = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);

        if ($time < 60) return 'agora mesmo';
        if ($time < 3600) return floor($time / 60) . ' min atrás';
        if ($time < 86400) return floor($time / 3600) . ' h atrás';
        if ($time < 2592000) return floor($time / 86400) . ' dias atrás';
        if ($time < 31536000) return floor($time / 2592000) . ' meses atrás';

        return floor($time / 31536000) . ' anos atrás';
    }
}

if (!function_exists('isActiveRoute')) {
    function isActiveRoute($route)
    {
        $currentRoute = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($currentRoute, $route) !== false;
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        $baseUrl = rtrim($_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/');
        return 'http://' . $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        $baseUrl = rtrim($_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/');
        return 'http://' . $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('old')) {
    function old($key, $default = '')
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}
