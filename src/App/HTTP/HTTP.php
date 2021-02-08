<?php

/**
 * Простой HTTP(S) клиент с троттлингом запросов
 *
 * @author    andrey-tech
 * @copyright 2019-2021 andrey-tech
 * @see https://github.com/andrey-tech/http-client-php
 * @license   MIT
 *
 * @version 3.0.1
 *
 * v1.0.0 (21.06.2019) Начальный релиз
 * v2.0.0 (21.07.2019) Изменения для App
 * v2.1.0 (28.08.2019) Добавлен параметр $curlOptions
 * v2.2.0 (01.10.2019) Добавлен метод getHTTPCode()
 * v2.3.0 (01.10.2019) Добавлен параметр $useCookies
 * v2.4.0 (04.10.2019) Добавлено удаление BOM перед декодированием JSON
 * v2.4.1 (13.10.2019) Изменен момент сохранения lastRequestTime для троттлинга.
 *                     Замена метода: getAbsoluteFilePath() на getAbsoluteFileName()
 * v2.5.0 (16.10.2019) Добавлен метод getResponse()
 * v2.5.1 (11.11.2019) Исправлен баг в методе isSuccess()
 * v2.6.0 (17.11.2019) Добавлен параметр $addBOM
 * v2.7.0 (12.05.2020) Свойство $throttle теперь это число запросов в секунду.
                       Свойство $useCookies теперь по умолчанию false
 * v2.7.1 (22.05.2020) Исправлен метод throttleCurl(). Изменены отладочные сообщения
 * v2.7.2 (10.06.2020) Рефакторинг
 * v2.8.0 (14.06.2020) Добавлено свойство $curlConnectTimeout
 * v2.9.0 (15.06.2020) Добавлен параметр $raw в метод getResponse()
 * v2.9.1 (19.07.2020) Добавлен забытый метод PATCH
 * v3.0.0 (06.02.2021) Изменение пространства имен на \App\HTTP
 * v3.0.1 (07.02.2021) Исправлен отладочный вывод заголовка для методов запроса GET и HEAD
 *
 */

declare(strict_types=1);

namespace App\HTTP;

class HTTP
{
    /**
     * Битовые маски для указания уровня вывода отладочной информации в свойстве $debugLevel
     * @var int
     */
    const DEBUG_NONE    = 0; // 000 - не выводить
    const DEBUG_URL     = 1 << 0; // 001 - URL запросов/ответов
    const DEBUG_HEADERS = 1 << 1; // 010 - заголовки запросов/ответов
    const DEBUG_CONTENT = 1 << 2; // 100 - содержимое запросов/ответов

    /**
     * Битовая маска уровня вывода отладочной информации
     * \App\HTTP\HTTP::DEBUG_NONE,
     * \App\HTTP\HTTP::DEBUG_URL,
     * \App\HTTP\HTTP::DEBUG_HEADERS,
     * \App\HTTP\HTTP::DEBUG_CONTENT
     * @var int
     */
    public $debugLevel = self::DEBUG_NONE;

    /**
     * Максимальное число HTTP запросов в секунду (0 - троттлинг отключен)
     * @var float
     */
    public $throttle = 0;

    /**
     * Флаг добавления маркера ВОМ UTF-8 (EFBBBF) к запросам в формате JSON
     * @var boolean
     */
    public $addBOM = false;

    /**
     * Флаг использования cookie в запросах
     * @var boolean
     */
    public $useCookies = false;

    /**
     * Путь к файлу для хранения cookies
     * @var string
     */
    public $cookieFile = 'temp/cookies.txt';

    /**
     * Флаг включения проверки SSL-сертификата сервера
     * @var bool
     */
    public $verifySSLCertificate = true;

    /**
     * Файл SSL-сертификатов X.509 корневых удостоверяющих центров (относительно каталога файла данного класса)
     * (null - файл, указанный в настройках php.ini)
     * @var string | null - файл из конфигурации php.ini
     */
    public $sslCertificateFile = 'cacert.pem';

    /**
     * UserAgent в запросах
     * @var string
     */
    public $userAgent = 'HTTP-client/3.x.x';

    /**
     * Таймаут соединения для cUrl, секунд
     * @var integer
     */
    public $curlConnectTimeout = 60;

    /**
     * Таймаут обмена данными для cUrl, секунд
     * @var integer
     */
    public $curlTimeout = 60;

    /**
     * Коды статуса НТТР, соответствующие успешному выполнению запроса
     * @var array
     */
    public $successStatusCodes = [ 200 ];

    /**
     * Ресурс cURL
     * @var resource
     */
    private $curl;

    /**
     * Информация о последней операции curl
     * @var array
     */
    private $curlInfo = [];

    /**
     * Тело последнего ответа
     * @var string
     */
    private $response;

    /**
     * Заголовки последнего ответа
     * @var array
     */
    private $responseHeaders = [];

    /**
     * Время последнего запроса, микросекунды
     * @var float
     */
    private $lastRequestTime = 0;

    /**
     * Счетчик числа запросов для отладочных сообщений
     * @var integer
     */
    private $requestCounter = 0;

    /**
     * Устанавливает параметры по умолчанию для cURL
     * @return void
     */
    private function setDefaultCurlOptions()
    {
        $this->responseHeaders = [];
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($this->curl, CURLOPT_HEADER, false);

        // Использование cookies
        if ($this->useCookies) {
            $cookieFile = $this->getAbsoluteFileName($this->cookieFile);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $cookieFile);
        }

        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->curlConnectTimeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->curlTimeout);
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, [ $this, 'storeResponseHeaders' ]);

        // Включение/отключение проверки SSL-сертификата сервера
        if ($this->verifySSLCertificate) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
            if ($this->sslCertificateFile) {
                $sslCertificateFile = __DIR__ . DIRECTORY_SEPARATOR . $this->sslCertificateFile;
                curl_setopt($this->curl, CURLOPT_CAINFO, $sslCertificateFile);
            }
        } else {
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
    }

    /**
     * Отправляет HTTP запрос
     * @param string $url URL запроса
     * @param string $method Метод запроса
     * @param array $params Параметры запроса
     * @param array $requestHeaders Заголовки запроса
     * @param array $curlOptions Дополнительные опции для cURL
     * @return mixed
     */
    public function request(
        string $url,
        string $method = 'GET',
        array $params = [],
        array $requestHeaders = [],
        array $curlOptions = []
    ) {
        $this->response = null;
        $this->responseHeaders = [];

        // Увеличиваем счетчик числа отправленных запросов
        $this->requestCounter++;

        // Инициализируем cURL и устанавливаем опции по умолчанию
        $this->curl = curl_init();
        $this->setDefaultCurlOptions();

        // Устанавливаем дополнительные опции cURL
        if (count($curlOptions)) {
            curl_setopt_array($this->curl, $curlOptions);
        }

        // Формируем тело запроса
        $query = $this->buildQuery($params, $requestHeaders);

        // Устанавливаем заголовки запроса
        if (count($requestHeaders)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $requestHeaders);
        }

        switch ($method) {
            case 'GET':
            case 'HEAD':
                if ($query !== '') {
                    $url .= '?' . $query;
                }
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $query);
                break;
            default:
                throw new HTTPException("Неизвестный метод запроса {$method}");
        }

        $this->debug(PHP_EOL . "[{$this->requestCounter}] ===> {$method} {$url}", self::DEBUG_URL);

        // Устанавливаем URL запроса
        curl_setopt($this->curl, CURLOPT_URL, $url);

        // Отправляем запрос
        $this->response = $this->throttleCurl();
        $deltaTime = sprintf('%0.4f', microtime(true) - $this->lastRequestTime);

        // Сохраняем информацию cURL и завершаем сеанс cURL
        $this->curlInfo = curl_getinfo($this->curl);
        $code = (int) curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);
        curl_close($this->curl);

        // Проверяем ошибки cURL
        if ($errno) {
            throw new HTTPException("Ошибка cURL #{$errno} ({$url}): {$error}");
        }

        // Выводим заголовки и тело запроса
        $this->debug($this->curlInfo['request_header'] ?? 'REQUEST HEADERS ???', self::DEBUG_HEADERS);
        if ($method !== 'GET') {
            $this->debug($query . PHP_EOL, self::DEBUG_CONTENT);
        }

        // Выводим строку, заголовки и тело ответа
        $this->debug("[{$this->requestCounter}] <=== RESPONSE {$deltaTime}s ({$code})", self::DEBUG_URL);
        $this->debug(implode(PHP_EOL, $this->responseHeaders), self::DEBUG_HEADERS);
        $this->debug($this->response . PHP_EOL, self::DEBUG_CONTENT);
        return $this->decodeResponse($this->response, $code);
    }

    /**
     * Возвращает статус успешности выполнения запроса
     * @param array $successStatusCodes Коды статуса НТТР Статус коды успешного выполнения запроса
     * @return boolean
     */
    public function isSuccess(array $successStatusCodes = []): bool
    {
        if (! count($successStatusCodes)) {
            $successStatusCodes = $this->successStatusCodes;
        }

        $code = (int) ($this->curlInfo['http_code'] ?? 0);
        return in_array($code, $successStatusCodes);
    }

    /**
     * Возвращает HTTP Code последнего запроса
     * @return int
     */
    public function getHTTPCode()
    {
        $code = (int) $this->curlInfo['http_code'];
        return $code ?? 0;
    }

    /**
     * Возвращает тело последнего ответа
     * @param bool $raw Возвращать ответ в сыром виде
     * @return mixed
     */
    public function getResponse(bool $raw = true)
    {
        if ($raw) {
            return $this->response;
        }

        return $this->decodeResponse($this->response, $this->getHTTPCode());
    }

    /**
     * Возвращает заголовки последнего ответа
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Возвращает информацию о последней операции cURL
     * @return array
     */
    public function getCurlInfo(): array
    {
        return $this->curlInfo;
    }

    /**
     * Формирует строку запроса
     * @param  array  $params Параметры запроса
     * @param  array  $requestHeaders Заголовки запроса
     * @return string
     */
    private function buildQuery(array $params, array $requestHeaders): string
    {
        if (! count($params)) {
            return '';
        }

        $contentType = $this->getContentType($requestHeaders);
        switch ($contentType) {
            case 'json':
                $jsonParams = json_encode($params);
                if ($jsonParams === false) {
                    $errorMessage = json_last_error_msg();
                    throw new HTTPException(
                        "Не удалось закодировать в JSON ({$errorMessage}): " .
                        print_r($params, true)
                    );
                }
                // Добавляем маркер BOM
                if ($this->addBOM) {
                    $jsonParams = chr(239) . chr(187) . chr(191) . $jsonParams;
                }

                return $jsonParams;
            default:
                return http_build_query($params);
        }
    }

    /**
     * Декодирует тело ответа
     * @param  string $response Тело ответа
     * @param  int    $code Статус код ответа
     * @return mixed
     */
    private function decodeResponse(string $response, int $code)
    {
        if ($code === 204) {
            return $response;
        }

        $contentType = $this->getContentType($this->responseHeaders);
        switch ($contentType) {
            case 'json':
                // Удаляем маркер ВОМ (если он есть)
                $response = ltrim($response, chr(239) . chr(187) . chr(191));
                $decodedResponse = json_decode($response, true);
                if (is_null($decodedResponse)) {
                    $errorMessage = json_last_error_msg();
                    throw new HTTPException("Не удалось декодировать JSON ({$errorMessage}): {$response}");
                }

                break;
            default:
                $decodedResponse = $response;
        }

        return $decodedResponse;
    }

    /**
     * Возвращает тип контента из заголовков запроса/ответа
     * @param  array  $headers Заголовки запроса/ответа
     * @return string | null
     */
    private function getContentType(array $headers)
    {
        foreach ($headers as $header) {
            $header = explode(':', $header, 2);

            // Пропускаем ошибочные заголовки
            if (count($header) < 2) {
                continue;
            }

            $name = strtolower(trim($header[0]));

            // Content-Type:
            if (stripos($name, 'content-type') === 0) {
                $value = strtolower(trim($header[1]));

                // application/json, application/hal+json, ...
                if (stripos($value, 'json') !== false) {
                    return 'json';
                }

                return $value;
            }
        }

        return null;
    }

    /**
     * Обеспечивает троттлинг HTTP запросов
     * @return string|false $response
     */
    private function throttleCurl()
    {
        do {
            if (empty($this->throttle)) {
                break;
            }

            // Вычисляем необходимое время задержки перед отправкой запроса, микросекунды
            $usleep = (int)(1E6 * ($this->lastRequestTime + 1 / $this->throttle - microtime(true)));
            if ($usleep <= 0) {
                break;
            }

            $sleep = sprintf('%0.4f', $usleep / 1E6);
            $this->debug("[{$this->requestCounter}] ++++ THROTTLE ({$this->throttle}) {$sleep}s", self::DEBUG_URL);
            usleep($usleep);
        } while (false);
        $this->lastRequestTime = microtime(true);
        $response = curl_exec($this->curl);
        return $response;
    }

    /**
     * Сохраняет заголовки ответа
     * @param  resource $curl
     * @param  string $header Строка заголовка
     * @return int Длина заголовка
     * @see https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request
     */
    private function storeResponseHeaders($curl, string $header): int
    {
        $this->responseHeaders[] = trim($header);
        return strlen($header);
    }

    /**
     * Выводит в STDOUT отладочные сообщения на заданном уровне вывода отладочной информации
     * @param string $message
     * @param int Заданный уровень вывода отладочной информации
     * @return void
     */
    private function debug(string $message, int $debugLevel)
    {
        if (! ($this->debugLevel & $debugLevel)) {
            return;
        }

        echo $message . PHP_EOL;
    }

    /**
     * Возвращает абсолютное имя файла и создает каталоги при необходимости
     * @param string $relativeFileName Относительное имя файла
     * @param bool $createDir Создавать каталоги при необходимости?
     * @return string|null Абсолютное имя файла
     * @see http://php.net/manual/ru/function.stream-resolve-include-path.php#115229
     */
    private function getAbsoluteFileName(string $relativeFileName, bool $createDir = true)
    {
        $includePath = explode(PATH_SEPARATOR, get_include_path());
        foreach ($includePath as $path) {
            $absoluteFileName = $path . DIRECTORY_SEPARATOR . $relativeFileName;
            $checkDir = dirname($absoluteFileName);
            if (is_dir($checkDir)) {
                return $absoluteFileName;
            }
            if ($createDir) {
                if (!mkdir($checkDir, $mode = 0755, $recursive = true) && !is_dir($checkDir)) {
                    throw new HTTPException("Не удалось создать каталог {$checkDir}");
                }
                return $absoluteFileName;
            }
        }
        return null;
    }
}
