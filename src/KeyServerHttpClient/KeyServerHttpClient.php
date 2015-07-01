<?php
namespace KeyServerHttpClient;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Session\Container as SessionContainer;


/**
 * Class HttpClient
 * @package HttpClient
 */
class KeyServerHttpClient
{
    const STATUS_SUCCESS = 200;
    public $config;
    public $apiUrl;
    public $xHeader;
    public $apiKey;
    public $authSession;
    public $appLogger;
    public $username;

    /**
     * @param $keyserver
     * @param $appLogger
     * @param $username
     */
    public function __construct($keyserver, $appLogger, $username)
    {
        $this->appLogger   = $appLogger;
        $this->authSession = new SessionContainer('authSession'); //@todo possible refactor.  Do we always need to store in a session
        $this->xHeader     = $keyserver['xheader'];
        $this->apiKey      = $keyserver['apikey'];
        $this->sslConfig   = $keyserver['sslConfig'];
        $this->apiUrl      = $keyserver['url'] . '/' .
                             $keyserver['endpoint'] . '/' .
                             $username . '/' .
                             $keyserver['appname'];
    }

    /**
     * Get a list of roles for the user accessing a given application
     * @return mixed
     * @throws \Exception
     */
    public function getRoles()
    {
        if (empty($this->authSession->roles)) {
            try {
                $rolesData = $this->makeApiRequest();
                $this->authSession->roles = serialize($rolesData);

            } catch (\Exception $e) {
                $this->appLogger->crit($e);
                $errorMessage = $e->getMessage();
                throw new \Exception ($errorMessage);
            }

        } else {
            $rolesData = unserialize($this->authSession->roles);
        }

        if (!array_key_exists('exception', $rolesData)) {
            foreach ($rolesData as $roles) {
                if (array_key_exists('roles', $roles)) {
                    return $roles['roles'];
                }
            }
        } else {
            $errorMessage = print_r($rolesData, true);
            throw new \Exception ($errorMessage); 
        }
    }


    /**
     * Call the keyserver API using
     * @return mixed
     * @throws \Exception
     */
    public function makeApiRequest()
    {
        $request = new Request();
        $request->setUri($this->apiUrl);
        $request->setMethod('GET');
        $requestHeaders = $request->getHeaders();
        $requestHeaders->addHeaderLine($this->xHeader, $this->apiKey);

        $requestHeaders->addHeaderLine('Accept', 'Content-Type:');
        $request->setHeaders($requestHeaders);

        $client = new Client();
        $client->setOptions($this->sslConfig);

        $response = $client->dispatch($request);
        $apiData = json_decode($response->getContent(), true);

        if ($response->isOk()) {
            return $apiData;
        } else {
            if (array_key_exists('exception', $apiData)) {
                $errorMessage = $apiData['message'];
            } else {
                $errorMessage = $response->getStatusCode() .'==>' . 
                                $response->getReasonPhrase();
            }
            throw new \Exception ($errorMessage); 
        }

    }
}