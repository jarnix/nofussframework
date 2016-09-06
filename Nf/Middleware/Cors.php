<?php

namespace Nf\Middleware;

use \Nf\Registry;
use \Nf\Settings;
use \Nf\Middleware\MiddlewareInterface;
use \Nf\Middleware\Pre;
use \Nf\Front;
use \Nf\Front\Request\Http;

class Cors implements \Nf\Middleware\MiddlewareInterface {
    
    use Pre;

    public function execute() {
        
        $settings = Settings::getInstance();

        // reference: https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS

        // if CORS is enabled in the config+env settings
        if(isset($settings->security->cors->enable)) {
            if($settings->security->cors->enable) {
                $front = Front::getInstance();
                if($front->getRequest() instanceof Http) {
                    // is it a CORS preflight request ?
                    if (isset($_SERVER['HTTP_ORIGIN']) && isset($_SERVER['HTTP_HOST'])) {
                        $parsedOrigin = parse_url($_SERVER['HTTP_ORIGIN']);
                        $parsedCurrent = [];
                        $parsedCurrent['host'] = $_SERVER['HTTP_HOST'];
                        $parsedCurrent['scheme'] = $_SERVER['REQUEST_SCHEME'];
                        $parsedCurrent['port'] = $_SERVER['SERVER_PORT'];
                        if(!($parsedCurrent['host'] === $parsedOrigin['host'])
                            || ! ($parsedCurrent['port'] === $parsedOrigin['port'])
                            || ! ($parsedCurrent['scheme'] === $parsedOrigin['scheme'])
                        ) {
                            if ($front->getRequest()->isOptions()) {
                                if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                                    $corsAllowed = false;
                                    // it's a CORS preflight request
                                    // origins
                                    $allowedOriginsFromSettings = $settings->security->cors->allowed_origins;
                                    if($allowedOriginsFromSettings!='*') {
                                        $allowedOrigins = array_map('trim', explode(',', $settings->security->cors->allowed_origins));
                                        if(in_array($parsedCurrent['host'], $allowedOrigins)) {
                                            $corsAllowed = true;
                                        }
                                    }
                                    else {
                                        $corsAllowed = true;
                                    }
                                    // methods
                                    $allowedMethodsFromSettings = $settings->security->cors->allowed_methods;
                                    $allowedMethods = array_map('strtoupper', array_map('trim', explode(',', $settings->security->cors->allowed_methods)));
                                    if(!in_array(strtoupper($front->getRequest()->getMethod()), $allowedMethods)) {
                                        $corsAllowed = false;
                                    }
                                    // headers
                                    $allowedHeaders = array_map('trim', explode(',', $settings->security->cors->allowed_headers));
                                    // sending the (empty) response
                                    if($corsAllowed) {
                                        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                                        if($allowedOriginsFromSettings=='*') {
                                            if(isset($_SERVER['HTTP_VARY'])) {
                                                $varyHeaders = array_map('trim', explode(',', $_SERVER['HTTP_VARY'])) ;
                                            }
                                            // adding the Vary: Origin for proxied requests
                                            $varyHeaders[] = 'Origin';
                                        }
                                        header('Vary: ' . implode(', ', $varyHeaders));
                                        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
                                        if ($settings->security->cors->allowed_credentials) {
                                            header('Access-Control-Allow-Credentials: true');
                                        }
                                        header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
                                        header('Access-Control-Max-Age: ' . $settings->security->cors->max_age);
                                    }
                                    else {
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }


}