<?php
namespace KeyserverHttpClient;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Session\Container as SessionContainer;


/**
 * Class HttpClient
 * @package HttpClient
 */
class KeyserverHttpClient
{
    const STATUS_SUCCESS = 200;
    public $apiUrl;
    public $xHeader;
    public $apiKey;
    public $appLogger;
    public $username;

    protected $roles;
    protected $locations;

    /**
     * @param $keyserver
     * @param $appLogger
     * @param $username
     */
    public function __construct($keyserver, $appLogger, $username)
    {
        $this->appLogger   = $appLogger;
        $this->xHeader     = $keyserver['xheader'];
        $this->apiKey      = $keyserver['apikey'];
        $this->sslConfig   = $keyserver['sslConfig'];
        $this->apiUrl      = $keyserver['url'] . '/';
        $this->appName     = $keyserver['appname'];
        $this->username    = $username;
    }

    /**
     * Call the keyserver api to get a list of roles a user is authorized to
     * @return $this
     * @throws \Exception
     */
    public function sendRolesAndLocationsRequest()
    {
        $endPoint = 'roles/' . $this->username . '/' . $this->appName;

        try {
            $data = $this->makeApiRequest($endPoint);

            if (!array_key_exists('exception', $data)) {
                $this->setRolesAndLocations($data);
            }
        } catch (\Exception $e) {
            $this->appLogger->crit($e);
            $errorMessage = $e->getMessage();
            throw new \Exception ($errorMessage);
        }

        return $this;
    }

    public function setRolesAndLocations($data)
    {
        foreach ($data as $item) {
            if (array_key_exists('roles', $item)) {
                $this->roles = $item['roles'];
            }
            if (array_key_exists('locations', $item)) {
                $this->locations = $item['locations'];
            }
        }
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @return mixed
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Call the keyserver API using
     * @return mixed
     * @throws \Exception
     */
    public function makeApiRequest($endPoint)
    {
        $request = new Request();
        $request->setUri($this->apiUrl . $endPoint);

        $request->setMethod('GET');
        $requestHeaders = $request->getHeaders();
        $requestHeaders->addHeaderLine($this->xHeader, $this->apiKey);

        $requestHeaders->addHeaderLine('Accept', 'Content-Type:');
        $request->setHeaders($requestHeaders);

        $client = new Client();
        $client->setOptions($this->sslConfig);

        $response = $client->dispatch($request);
        /**
         * Weird bug in dev that outputs trash in content. only happens when running OdinCA locally
         */
        /**
        $tmp = substr($response->getContent(), 3);
        $tmp2 = substr($tmp, 0, -7);
        $apiData = json_decode($tmp2, true);
        //print_r($response->getContent()); exit;
        /*** end weird bug ***/
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
