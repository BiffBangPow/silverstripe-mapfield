<?php

namespace BiffBangPow\Helper;

use BiffBangPow\Model\Supplier;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;


class GeoLocationHelper
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @var array $latlng
     */
    private $latlng = [];

    /**
     * @var string $locationError
     */
    private $locationError;

    /**
     * @var string $locationQuery
     */
    private string $locationQuery;

    /**
     * @var string $api_key
     */
    private $api_key;

    /**
     * @config
     * @var string $geocode_url
     */
    private static $geocode_url = 'http://api.positionstack.com/v1/forward';


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->api_key = Environment::getEnv('GEOLOCATION_KEY');
        if ($this->api_key == '') {
            throw new \Exception("Geolocation API key not set.  Cannot proceed");
        }
        return $this;
    }

    /**
     * Get the latitude of the current supplier
     */
    public function getLatitude()
    {
        if (!isset($this->latlng[0])) {
            if ($this->doGeoLocation() === false) {
                return false;
            }
        }
        return (isset($this->latlng[0])) ? $this->latlng[0] : 0;
    }

    /**
     * Get the longitude of the current supplier
     */
    public function getLongitude()
    {
        if (!isset($this->latlng[1])) {
            if ($this->doGeoLocation() === false) {
                return false;
            }
        }
        return (isset($this->latlng[1])) ? $this->latlng[1] : 0;
    }


    /**
     * Return any logged errors
     * Should be empty if nothing went wrong
     * @return string
     */
    public function getErrors()
    {
        return $this->locationError;
    }


    /**
     * Set the query / search for location
     * @param $query
     * @return $this
     */
    public function setQueryData($query)
    {
        $this->locationQuery = $query;
        return $this;
    }


    /**
     * Attempt to get co-ordinates for the given search
     * @return LocationHelper|false
     */
    private function doGeoLocation()
    {

        $this->locationError = '';

        $query = trim($this->getQueryData());
        if ($query == '') {
            throw new \Exception("No query specified");
        }

        $queryData = [
            'query' => $query,
            'access_key' => $this->api_key,
            'limit' => 1
        ];

        $this->extend('updateQueryParams', $queryData);

        $res = $this->sendRequest($this->getGeoCodeURL(), $queryData);

        if ($res === false) {
            return false;
        }

        try {
            $geoData = json_decode($res, true);

            if ((isset($geoData['data'])) && (count($geoData['data']) > 0)) {
                $location = $geoData['data'][0];
                if ((isset($location['latitude'])) && (isset($location['longitude']))) {
                    $this->latlng = [
                        $location['latitude'],
                        $location['longitude']
                    ];
                }
                return $this;
            }
        } catch (\Exception $e) {
            $this->locationError = $e->getMessage();
            return false;
        }
    }

    /**
     * @return mixed
     */
    protected function getGeoCodeURL()
    {
        $url = $this->config()->get('geocode_url');
        $this->extend('updateGeoCodeURL', $url);

        return $url;
    }

    /**
     * Get the current query
     * @return string
     */
    protected function getQueryData()
    {
        return $this->locationQuery;
    }

    /**
     * @param $url
     * @param array $queryParams
     * @return false|StreamInterface
     */
    private function sendRequest($url, array $queryParams = [])
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $url, [
                'query' => $queryParams
            ]);
            if ($response->getStatusCode() !== 200) {
                $this->locationError = 'Non-200 response error during location request';
                return false;
            }

            return $response->getBody();
        } catch (ClientException|GuzzleException|RequestException $e) {
            $this->locationError = $e->getMessage();
            return false;
        }
    }


}
