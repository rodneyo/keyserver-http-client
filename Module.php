<?php
/**
 * Date: 4/22/15
 * Time: 9:17 AM
 */
namespace KeyserverHttpClient;

class Module
{
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
            'factories' => array(
                'KeyserverHttpClient' => function($sm) {
                    $config = $sm->get('config');
                    $logger = $sm->get('KeyserverHttpClient\Log');
                    $user   = $sm->get('User');

                    $httpClient =  new KeyserverHttpClient($config['keyserver_client'], $logger, $user->getIdentity());
                    return $httpClient;
                }
            )
        );
    }
}
