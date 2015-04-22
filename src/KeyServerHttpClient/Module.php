<?php
/**
 * Date: 4/22/15
 * Time: 9:17 AM
 */
namespace HttpClient;

use HttpClient;

class Module
{

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return array (
            'HttpClient\KeyserverHttpClient' => function($sm) {
                $config = $sm->get('config');
                $logger = $sm->get('Zend\Log');

                $httpClient =  new KeyServerHttpClient($config['keyserver_client'],
                    $config['role_resources'],
                    $logger
                );
                return $httpClient;
            }
        );
    }
}
