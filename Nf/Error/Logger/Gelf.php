<?php
namespace Nf\Error\Logger;

use \Nf\Registry;
use \Nf\Error\Handler;

class Gelf
{

    public function log($err)
    {
        $config = Registry::get('config');

        // We need a transport - UDP via port 12201 is standard.
        $transport = new \Gelf\Transport\UdpTransport($config->error->logger->gelf->ip, $config->error->logger->gelf->port, \Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN);

        // While the UDP transport is itself a publisher, we wrap it in a real Publisher for convenience
        // A publisher allows for message validation before transmission, and it calso supports to send messages
        // to multiple backends at once
        $publisher = new \Gelf\Publisher();
        $publisher->addTransport($transport);

        $fullMessage = \Nf\Front\Response\Cli::displayErrorHelper($err);

        // Now we can create custom messages and publish them
        $message = new \Gelf\Message();
        $message->setShortMessage(Handler::recursiveArrayToString($err['message']))
            ->setLevel(\Psr\Log\LogLevel::ERROR)
            ->setFile($err['file'])
            ->setLine($err['line'])
            ->setFullMessage($fullMessage);

        if (php_sapi_name() == 'cli') {
            global $argv;
            $argv2 = $argv;
            unset($argv2[0]);
            $message->setAdditional('url', 'su ' . $_SERVER['LOGNAME'] . ' -c "php ' . Registry::get('applicationPath') . '/html/index.php ' . implode(' ', $argv2) . '"');
        } else {
            $message->setAdditional('url', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        }

        if (isset($config->error->logger->additionals)) {
            foreach ($config->error->logger->additionals as $additionalName => $additionalValue) {
                $message->setAdditional($additionalName, $additionalValue);
            }
        }

        if ($publisher->publish($message)) {
            return true;
        } else {
            return false;
        }
    }
}
