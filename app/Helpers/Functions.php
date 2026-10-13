<?php

namespace App\Helpers;

class Functions
{
    /**
     * Sanitizar input de usuario
     */
    public static function sanitize($input)
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generar slug para URLs
     */
    public static function slugify($text)
    {
        // Reemplazar caracteres no alfabéticos por guiones
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Formatear fecha
     */
    public static function formatDate($date, $format = 'd/m/Y H:i:s')
    {
        if ($date instanceof \DateTime) {
            return $date->format($format);
        }
        
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }

    /**
     * Redirigir a URL
     */
    public static function redirect($url, $permanent = false)
    {
        if ($permanent) {
            header('HTTP/1.1 301 Moved Permanently');
        }
        header('Location: ' . $url);
        exit();
    }

    /**
     * Obtener URL base del proyecto
     */
    public static function baseUrl($path = '')
    {
        $base = sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            dirname($_SERVER['SCRIPT_NAME'])
        );
        
        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Generar contraseña aleatoria
     */
    public static function generatePassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }

    /**
     * Validar email
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Obtener IP del cliente
     */
    public static function getClientIP()
    {
        $ipaddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
            
        return $ipaddress;
    }

    /**
     * Formatear número a moneda
     */
    public static function formatMoney($amount, $symbol = 'S/', $decimals = 2)
    {
        return $symbol . ' ' . number_format($amount, $decimals, '.', ',');
    }

    /**
     * Truncar texto
     */
    public static function truncate($text, $length = 100, $append = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        
        return $text . $append;
    }

    /**
     * Verificar si es AJAX request
     */
    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Enviar respuesta JSON
     */
    public static function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Obtener edad desde fecha de nacimiento
     */
    public static function calculateAge($birthDate)
    {
        $birth = new \DateTime($birthDate);
        $today = new \DateTime('today');
        $age = $birth->diff($today)->y;
        
        return $age;
    }

    /**
     * Debug seguro (solo muestra en desarrollo)
     */
    public static function debug($data, $die = false)
    {
        // Activar solo en entorno de desarrollo
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
            echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin: 10px;">';
            print_r($data);
            echo '</pre>';
            
            if ($die) {
                die('DEBUG END');
            }
        }
    }
}