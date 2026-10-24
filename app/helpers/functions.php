<?php

if (!function_exists('formatCpf')) {
    function formatCpf($cpf)
    {
        return FormatHelper::cpf($cpf);
    }
}
