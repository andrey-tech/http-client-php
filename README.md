# HTTP(S) Client PHP

Простой НТТР(S) клиент на PHP7+ с троттлингом запросов, поддержкой маркера BOM в теле сообщения формата JSON и выводом отладочной информации о запросах и ответах в STDOUT.  

[![Latest Stable Version](https://poser.pugx.org/andrey-tech/http-client-php/v)](https://packagist.org/packages/andrey-tech/http-client-php)
[![Total Downloads](https://poser.pugx.org/andrey-tech/http-client-php/downloads)](https://packagist.org/packages/andrey-tech/http-client-php)
[![License](https://poser.pugx.org/andrey-tech/http-client-php/license)](https://packagist.org/packages/andrey-tech/http-client-php)

# Содержание

<!-- MarkdownTOC levels="1,2,3,4,5,6" autoanchor="true" autolink="true" -->

- [Требования](#%D0%A2%D1%80%D0%B5%D0%B1%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F)
- [Установка](#%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)
- [Класс `HTTP`](#%D0%9A%D0%BB%D0%B0%D1%81%D1%81-http)
    - [Методы класса](#%D0%9C%D0%B5%D1%82%D0%BE%D0%B4%D1%8B-%D0%BA%D0%BB%D0%B0%D1%81%D1%81%D0%B0)
    - [Параметры](#%D0%9F%D0%B0%D1%80%D0%B0%D0%BC%D0%B5%D1%82%D1%80%D1%8B)
    - [Пример](#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80)
- [Автор](#%D0%90%D0%B2%D1%82%D0%BE%D1%80)
- [Лицензия](#%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F)

<!-- /MarkdownTOC -->

<a id="%D0%A2%D1%80%D0%B5%D0%B1%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F"></a>
## Требования

- PHP >= 7.0;
- Произвольный автозагрузчик классов, реализующий стандарт [PSR-4](https://www.php-fig.org/psr/psr-4/).

<a id="%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0"></a>
## Установка

Установка через composer:
```
$ composer require andrey-tech/http-client-php:"^3.0"
```

или добавить

```
"andrey-tech/http-client-php": "^3.0"
```

в секцию require файла composer.json.

<a id="%D0%9A%D0%BB%D0%B0%D1%81%D1%81-http"></a>
## Класс `HTTP`

Класс `\App\HTTP\НТТР` обеспечивает:

- выполнение запросов по протоколу НТТР(S);
- настраиваемый троттлинг запросов;
- проверку SSL/TLS-сертификата сервера c возможностью ее отключения;
- удаление или добавление [маркера ВОМ](https://ru.wikipedia.org/wiki/%D0%9C%D0%B0%D1%80%D0%BA%D0%B5%D1%80_%D0%BF%D0%BE%D1%81%D0%BB%D0%B5%D0%B4%D0%BE%D0%B2%D0%B0%D1%82%D0%B5%D0%BB%D1%8C%D0%BD%D0%BE%D1%81%D1%82%D0%B8_%D0%B1%D0%B0%D0%B9%D1%82%D0%BE%D0%B2) в тело сообщений формата JSON;
- вывод отладочной информации о запросах и ответах в STDOUT.

При возникновении ошибок выбрасывается исключение класса `\App\HTTP\HTTPException`.

<a id="%D0%9C%D0%B5%D1%82%D0%BE%D0%B4%D1%8B-%D0%BA%D0%BB%D0%B0%D1%81%D1%81%D0%B0"></a>
### Методы класса

- `__construct()` Конструктор класса.
- `request(string $url, string $method = 'GET', array $params = [], array $requestHeaders = [], array $curlOptions = []) :?array`  
    Отправляет запрос по протоколу HTTP(S). Возвращает декодированный ответ сервера или `null` при возникновении ошибки cURL.
    + `$url` - URL запроса;
    + `$method` - метод запроса;
    + `$params` - параметры запроса;
    + `$curlOptions` - дополнительные параметры для cURL.
- `isSuccess(array $successStatusCodes = []) :bool` Возвращает статус успешности выполнения запроса.
    + `$successStatisCodes` Коды статуса НТТР, соответствующие успешному выполнению запроса. Если не передан, то используется значение по умолчанию, установленное в свойстве `$successStatusCodes`.
- `getHTTPCode() :int` Возвращает код статуса HTTP для последнего запроса.
- `getResponse() :?string` Возвращает тело последнего ответа в сыром виде.
- `getResponseHeaders() :array` Возвращает заголовки последнего ответа.
- `getCurlInfo() :array` Возвращает информацию о последней операции cURL.

<a id="%D0%9F%D0%B0%D1%80%D0%B0%D0%BC%D0%B5%D1%82%D1%80%D1%8B"></a>
### Параметры

Дополнительные параметры устанавливаются через публичные свойства объекта класса `\App\HTTP\HTTP`:

Свойство                | По умолчанию            | Описание
----------------------- | ----------------------- | --------
`$debugLevel`           | `\App\HTTP\HTTP::DEBUG_NONE` | Устанавливает уровень вывода отладочной информации о запросах в STDOUT (битовая маска, составляемая из значений DEBUG_NONE, DEBUG_URL, DEBUG_HEADERS, DEBUG_CONTENT)
`$throttle`             | 0                       | Максимальное число HTTP запросов в секунду (0 - троттлинг отключен)
`$addBOM`               | false                   | Добавлять [маркер ВОМ](https://ru.wikipedia.org/wiki/%D0%9C%D0%B0%D1%80%D0%BA%D0%B5%D1%80_%D0%BF%D0%BE%D1%81%D0%BB%D0%B5%D0%B4%D0%BE%D0%B2%D0%B0%D1%82%D0%B5%D0%BB%D1%8C%D0%BD%D0%BE%D1%81%D1%82%D0%B8_%D0%B1%D0%B0%D0%B9%D1%82%D0%BE%D0%B2) UTF-8 (EFBBBF) к запросам в формате JSON
`$useCookies`           | false                   | Использовать cookies в запросах
`$cookieFile`           | 'temp/cookies.txt'      | Путь к файлу для хранения cookies
`$verifySSLCertificate` | true                    | Включить проверку SSL/TLS-сертификата сервера
`$SSLCertificateFile`   | 'cacert.pem'            | Устанавливает файл SSL/TLS-сертификатов X.509 корневых удостоверяющих центров (CA) в формате РЕМ (установка в null означает использовать файл, указанный в параметре [curl.cainfo](https://www.php.net/manual/ru/curl.configuration.php) файла php.ini)
`$userAgent`            | 'HTTP-client/3.x.x'     | Устанавливает НТТР заголовок UserAgent в запросах
`$curlConnectTimeout`   | 60                      | Устанавливает таймаут соединения, секунды
`$curlTimeout`          | 60                      | Устанавливает таймаут обмена данными, секунды
`$successStatusCodes`   | [ 200 ]                 | Коды статуса НТТР, соответствующие успешному выполнению запроса

<a id="%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80"></a>
### Пример

```php
use App\HTTP\HTTP;
use App\HTTP\HTTPException;

try {
    // Создаем клиента
    $http = new HTTP();

    // Устанавливаем максимальный уровень вывода отладочных сообщений в STDOUT
    $http->debugLevel = HTTP::DEBUG_URL |  HTTP::DEBUG_HEADERS | HTTP::DEBUG_CONTENT;

    // Устанавливаем троттлинг запросов на уровне не более 1 запроса в 2 секунды
    $http->throttle = 0.5;

    // Устанавливаем таймаут соединения в 30 секунд
    $http->curlConnectTimeout = 30;

    // Устанавливаем таймаут обмена данными в 30 секунд
    $http->curlTimeout = 30;

    // Отправляем POST запрос
    $response = $http->request(
        $url            = 'https://www.example.com',
        $method         = 'POST',
        $params         = [ 'username' => 'ivan@example.com', 'password' => '1234567890' ],
        $requestHeaders = [ 'Content-Type: application/json' ]
    );

    // Проверяем НТТР статус ответа
    if (! $http->isSuccess()) {
        $httpCode = $http->getHTTPCode();
        $response = $http->getResponse();
        throw new HTTPException("HTTP {$httpCode}: {$response}");
    }

    print_r($response);

} catch (HTTPException $e) {
    printf('Ошибка (%d): %s' . PHP_EOL, $e->getCode(), $e->getMessage());
}
```

Пример отладочных сообщений:

```
[1] ===> POST https://www.example.com
POST / HTTP/1.1
Host: www.example.com
User-Agent: HTTP-client/3.x.x
Accept: */*
Content-Type: application/json
Content-Length: 55

{"username":"ivan@example.com","password":"1234567890"}

[1] <=== RESPONSE 0.9269s (200)
HTTP/1.1 200 OK
Accept-Ranges: bytes
Cache-Control: max-age=604800
Content-Type: text/html; charset=UTF-8
Date: Sun, 14 Jun 2020 13:09:33 GMT
Etag: "3147526947"
Expires: Sun, 21 Jun 2020 13:09:33 GMT
Last-Modified: Thu, 17 Oct 2019 07:18:26 GMT
Server: EOS (vny/0453)
Content-Length: 1256

<!doctype html>
<html>
<head>
    <title>Example Domain</title>
</head>
<body>
<div>
    <h1>Example Domain</h1>
    <p>This domain is for use in illustrative examples in documents. You may use this
    domain in literature without prior coordination or asking for permission.</p>
    <p><a href="https://www.iana.org/domains/example">More information...</a></p>
</div>
</body>
</html>
```

<a id="%D0%90%D0%B2%D1%82%D0%BE%D1%80"></a>
## Автор

© 2019-2022 andrey-tech

<a id="%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F"></a>
## Лицензия

Данная библиотека распространяется на условиях лицензии [MIT](./LICENSE).
