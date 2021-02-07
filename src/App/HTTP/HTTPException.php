<?php

/**
 * Обработчик исключений к классах пространства имен \App\НTTP
 *
 * @author    andrey-tech
 * @copyright 2019-2021 andrey-tech
 * @see https://github.com/andrey-tech/http-client-php
 * @license   MIT
 *
 * @version 2.0.0
 *
 * v1.0.0 (28.05.2019) Начальный релиз
 * v1.0.1 (26.06.2019) Исправления для пространства имен \App
 * v2.0.0 (06.02.2021) Изменение пространства имен на \App\HTTP
 *
 */

declare(strict_types=1);

namespace App\HTTP;

use Exception;

class HTTPException extends Exception
{
    /**
     * Конструктор
     * @param string $message Сообщение об исключении
     * @param int $code Код исключения
     * @param Exception|null $previous Предыдущее исключение
     */
    public function __construct(string $message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct('HTTP: ' . $message, $code, $previous);
    }
}
