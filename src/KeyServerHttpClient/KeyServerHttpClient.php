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
    protected $locationNames;

    /**
     * @param $keyserver
     * @param $appLogger
     * @param $username
     */
    public function __construct($keyserver, $appLogger, $username)
    {
        $this->appLogger        = $appLogger;
        $this->xHeader          = $keyserver['xheader'];
        $this->apiKey           = $keyserver['apikey'];
        $this->sslConfig        = $keyserver['sslConfig'];
        $this->apiUrl           = $keyserver['url'] . '/';
        $this->appName          = $keyserver['appname'];
        $this->endPoint         = $keyserver['endpoint'];
        $this->optionalParams   = $keyserver['optional_params'];
        $this->username         = $username;
    }

    /**
     * Call the keyserver api to get a list of roles a user is authorized to
     * @return $this
     * @throws \Exception
     */
    public function sendRolesAndLocationsRequest()
    {
        $endPoint = $this->endPoint . '/'.  $this->username . '/' . $this->appName .
            $this->optionalParams;

        try {
            $data = $this->makeApiRequest($endPoint);

            if (!array_key_exists('exception', $data)) {
                $this->setRolesAndLocations($data);
            }
        } catch (\Exception $e) {
            $this->appLogger->crit($e->getMessage());
            throw new \Exception ($e->getMessage());
        }

        return $this;
    }

    /**
     * Set the roles and locations into class properties to
     * make the accessible to other classes
     *
     * @param $data
     *
     * @throws \Exception
     */
    public function setRolesAndLocations($data)
    {
        foreach ($data as $item) {
            if (array_key_exists('roles', $item)) {
                if (count($item['roles']) <= 0) {
                   throw new \Exception('no_roles');
                }
                $this->roles = $item['roles'];
            }


            if (array_key_exists('locations', $item)) {
                if (count($item['locations']) <= 0) {
                    throw new \Exception('no_locations');
                }

                //$this->locations = $item['locations'];
                foreach ($item['locations'] as $location) {
                    $this->locations[$location] = $location;
                }
            }

            if (array_key_exists('names', $item)) {
                if (count($item['names']) <= 0) {
                    throw new \Exception('no_location_names');
                }

                $this->locationNames = $item['names'];
            }

            /**
            foreach ($locations as $key => $location) {
                $this->locations[$location] = $locationNames[$key];
            }
            **/

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

    public function getLocationNumbers()
    {
        return array_keys($this->locations);
    }

    /**
     * @return mixed
    **/
    public function getLocationNames()
    {
        return $this->locationNames;
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
        $apiData = json_decode($response->getBody(), true);

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
