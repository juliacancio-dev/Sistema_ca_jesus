<?php

class FormatHelper
{
    public static function currency($value, $currency = 'R$ ')
    {
        if (!is_numeric($value)) return $currency . '0,00';
        return $currency . number_format($value, 2, ',', '.');
    }
    public static function date($date, $format = 'd/m/Y H:i')
    {
        if (empty($date)) return '';

        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : 'Data inválida';
    }
    public static function phone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        $length = strlen($phone);

        if ($length === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        } elseif ($length === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        }

        return $phone;
    }
    public static function cpf($cpf)
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        return strlen($cpf) === 11 ?
            substr($cpf, 0, 3) . '.' .
            substr($cpf, 3, 3) . '.' .
            substr($cpf, 6, 3) . '-' .
            substr($cpf, 9, 2) : $cpf;
    }
    public static function cnpj($cnpj)
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        return strlen($cnpj) === 14 ?
            substr($cnpj, 0, 2) . '.' .
            substr($cnpj, 2, 3) . '.' .
            substr($cnpj, 5, 3) . '/' .
            substr($cnpj, 8, 4) . '-' .
            substr($cnpj, 12, 2) : $cnpj;
    }
    public static function stock($quantity)
    {
        return number_format($quantity, 0, ',', '.');
    }
}
