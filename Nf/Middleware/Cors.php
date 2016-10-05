<?php

namespace Nf\Middleware;

use \Nf\Registry;
use \Nf\Settings;
use \Nf\Middleware\MiddlewareInterface;
use \Nf\Middleware\Pre;
use \Nf\Front;
use \Nf\Front\Request\Http;

class Cors implements \Nf\Middleware\MiddlewareInterface
{
    
    use Pre;

    /*
    Overwrite these values in your config.ini:
    security.cors.allowed_origins = "*"
    security.cors.allowed_methods = GET, POST, DELETE, PUT, PATCH, OPTIONS
    security.cors.allowed_credentials = true
    security.cors.allowed_headers = authorization
    security.cors.max_age = 86400
    */
    const DEFAULT_ALLOWED_ORIGINS = '*';
    const DEFAULT_ALLOWED_METHODS = 'GET, POST, DELETE, PUT, PATCH, OPTIONS';
    const DEFAULT_ALLOWED_CREDENTIALS = true;
    const DEFAULT_ALLOWED_HEADERS = 'authorization';
    const DEFAULT_MAX_AGE = 86400;

    public function execute()
    {
        
        $settings = Settings::getInstance();

        // reference: https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS

        // if CORS is enabled in the config+env settings
        if (isset($settings->security->cors->enable)) {
            if ($settings->security->cors->enable) {
                $front = Front::getInstance();
                if ($front->getRequest() instanceof Http) {
                    // is it a CORS preflight request ?
                    if (isset($_SERVER['HTTP_ORIGIN']) && isset($_SERVER['HTTP_HOST'])) {
                        $parsedOrigin = parse_url($_SERVER['HTTP_ORIGIN']);
                        $parsedCurrent = [];
                        $parsedCurrent['host'] = $_SERVER['HTTP_HOST'];
                        $parsedCurrent['scheme'] = $_SERVER['REQUEST_SCHEME'];
                        $parsedCurrent['port'] = $_SERVER['SERVER_PORT'];
                        if (!($parsedCurrent['host'] === $parsedOrigin['host'])
                            || ! ($parsedCurrent['port'] === $parsedOrigin['port'])
                            || ! ($parsedCurrent['scheme'] === $parsedOrigin['scheme'])
                        ) {
                            $corsAllowed = false;
                            // it's a CORS request
                            // origins
                            if (isset($settings->security->cors->allowed_origins)) {
                                $allowedOriginsFromSettings = $settings->security->cors->allowed_origins;
                            } else {
                                $allowedOriginsFromSettings = self::DEFAULT_ALLOWED_ORIGINS;
                            }
                            if ($allowedOriginsFromSettings!='*') {
                                $allowedOrigins = array_map('trim', explode(',', $settings->security->cors->allowed_origins));
                                if (in_array($parsedCurrent['host'], $allowedOrigins)) {
                                    $corsAllowed = true;
                                }
                            } else {
                                $corsAllowed = true;
                            }
                            // methods
                            if (isset($settings->security->cors->allowed_methods)) {
                                $allowedMethodsFromSettings = $settings->security->cors->allowed_methods;
                            } else {
                                $allowedMethodsFromSettings = self::DEFAULT_ALLOWED_METHODS;
                            }
                            $allowedMethods = array_map('strtoupper', array_map('trim', explode(',', $allowedMethodsFromSettings)));
                            if (!in_array(strtoupper($front->getRequest()->getMethod()), $allowedMethods)) {
                                $corsAllowed = false;
                            }
                            // headers
                            if (isset($settings->security->cors->allowed_headers)) {
                                $allowedHeadersFromSettings = $settings->security->cors->allowed_headers;
                            } else {
                                $allowedHeadersFromSettings = self::DEFAULT_ALLOWED_HEADERS;
                            }
                            $allowedHeaders = array_map('trim', explode(',', $allowedHeadersFromSettings));
                            // sending the response
                            if ($corsAllowed) {
                                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                                if ($allowedOriginsFromSettings=='*') {
                                    if (isset($_SERVER['HTTP_VARY'])) {
                                        $varyHeaders = array_map('trim', explode(',', $_SERVER['HTTP_VARY'])) ;
                                    }
                                    // adding the Vary: Origin for proxied requests
                                    $varyHeaders[] = 'Origin';
                                }
                                header('Vary: ' . implode(', ', $varyHeaders));
                                if ($front->getRequest()->isOptions()) {
                                    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
                                }
                                if (isset($settings->security->cors->allowed_credentials)) {
                                    $allowedCredentialsFromSettings = $settings->security->cors->allowed_credentials;
                                } else {
                                    $allowedCredentialsFromSettings = self::DEFAULT_ALLOWED_CREDENTIALS;
                                }
                                if ($allowedCredentialsFromSettings) {
                                    header('Access-Control-Allow-Credentials: true');
                                }
                                if ($front->getRequest()->isOptions()) {
                                    header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
                                }
                                // max-age
                                if (isset($settings->security->cors->max_age)) {
                                    $allowedMaxAgeFromSettings = $settings->security->cors->max_age;
                                } else {
                                    $allowedMaxAgeFromSettings = self::DEFAULT_MAX_AGE;
                                }
                                header('Access-Control-Max-Age: ' . $allowedMaxAgeFromSettings);
                                // every OPTIONS request should return a 200 ok and bypass every other middleware
                                if ($front->getRequest()->isOptions()) {
                                    return false;
                                }
                                return true;
                            } else {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }
}
