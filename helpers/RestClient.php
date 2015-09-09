<?php
namespace kfosoft\helpers;

use Exception;

/**
 * RESTful client.
 * Example:
 * (new RestClient('path_to_cookie_file'))->setContentType(RestClient::JSON)->setUserAgent('Yah')->setHttpAuth('aloha', '123123123')->call();
 * Generate link and create request to http://myapi.com/api.php?param1=1&param2=2
 * Supported methods: POST, PUT, PATCH, GET, DELETE, HEAD, OPTIONS & CUSTOM
 * @package kfosoft\helpers
 * @version 1.0
 * @copyright (c) 2014-2015 KFOSoftware Team <kfosoftware@gmail.com>
 */
class RestClient
{
    const GET = 'get';
    const POST = 'post';
    const PUT = 'put';
    const PATCH = 'patch';
    const DELETE = 'delete';
    const OPTIONS = 'options';
    const HEAD = 'head';
    const CUSTOM = 'custom';

    const JSON = 'application/json';
    const XML = 'application/xml';

    /** @var string $_contentType content type for this request. */
    protected $_contentType = self::JSON;

    /** @var resource $_request curl request resource. */
    protected $_request = null;

    /** @var string $_userAgent user agent. */
    protected $_userAgent = 'PHP RESTful Client/1.0';

    /** @var bool $_httpAuth if your request to api with http auth, also you must set username & password. */
    protected $_httpAuth = false;

    /** @var string $_url url to api service. */
    protected $_url = '';

    /** @var string $_username http auth username. */
    protected $_username = '';

    /** @var string $_password http auth password. */
    protected $_password = '';

    /** @var string $_method REST method use class const POST, PUT, GET, DELETE */
    protected $_method;

    /** @var array|null $data array("param" => "value") ==> index.php?param=value */
    protected $_data;

    /** @var array|null $_requestContentType curl request ContentType. */
    protected $_requestContentType;

    /** @var bool $_useResponseContentType use curl response ContentType. */
    protected $_useResponseContentType = false;

    /** @var null|string $_cookieFilePath cookie file path. */
    protected $_cookieFilePath = null;

    /** @var bool $_paramsIsSet request params is set? */
    protected $_paramsIsSet = false;

    /** @var null|string $_customType custom request method. */
    protected $_customType = null;

    /** @var bool $_usePost use post params for custom request. */
    protected $_usePost = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Set cookie file */
        $this->_cookieFilePath = tempnam('/tmp','cookie');
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        /* Unlink cookie file */
        unlink($this->_cookieFilePath);
    }

    /**
     * @param int $method use class const POST, PUT, GET, DELETE
     * @param string $url url to api
     * @param array|null $data array("param" => "value") ==> index.php?param=value
     * @return $this
     * @throws Exception
     */
    public function requestParams($method, $url, array $data = null)
    {
        if (!method_exists($this, $method)) {
            throw new Exception('Undefined RESTful method!');
        }

        $this->_method = $method;
        $this->_url = $url;
        $this->_data = $data;

        $this->_paramsIsSet = true;

        return $this;
    }

    /**
     * Apply http authentication.
     */
    protected function httpAuth()
    {
        curl_setopt($this->_request, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->_request, CURLOPT_USERPWD, "{$this->_username}:{$this->_password}");
    }

    /**
     * Apply REST GET method.
     */
    protected function get()
    {
        $this->setGetData();
    }

    /**
     * Apply REST POST method.
     */
    protected function post()
    {
        curl_setopt($this->_request, CURLOPT_POST, 1);
        $this->setPostData();
    }

    /**
     * Apply REST PUT method.
     */
    protected function put()
    {
        curl_setopt($this->_request, CURLOPT_PUT, 1);
        $this->setPostData();
    }

    /**
     * Apply REST PATCH method.
     */
    protected function patch()
    {
        curl_setopt($this->_request, CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->setPostData();
    }

    /**
     * Apply REST DELETE method.
     */
    protected function delete()
    {
        curl_setopt($this->_request, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->setPostData();
    }

    /**
     * Apply REST HEAD method.
     */
    protected function head()
    {
        curl_setopt($this->_request, CURLOPT_CUSTOMREQUEST, 'HEAD');
        $this->setGetData();
    }

    /**
     * Apply REST OPTIONS method.
     */
    protected function options()
    {
        curl_setopt($this->_request, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        $this->setGetData();
    }

    /**
     * Apply REST CUSTOM method.
     */
    protected function custom()
    {
        curl_setopt($this->_request, CURLOPT_CUSTOMREQUEST, $this->_customType);
        $this->_usePost ? $this->setPostData() : $this->setGetData();
    }

    /**
     * Use cookie.
     */
    protected function useCookie()
    {
        curl_setopt($this->_request, CURLOPT_COOKIEJAR, $this->_cookieFilePath);
        curl_setopt($this->_request, CURLOPT_COOKIEFILE, $this->_cookieFilePath);
    }

    /**
     * Use content type from response.
     * @param bool $value
     * @return $this
     */
    public function useResponseContentType($value = true)
    {
        $this->_useResponseContentType = $value;

        return $this;
    }

    /**
     * Use post data with custom method.
     * @param bool $value
     * @return $this
     */
    public function usePost($value = true)
    {
        $this->_usePost = $value;

        return $this;
    }

    /**
     * @param string $username username for http auth.
     * @param string $password password for http auth.
     * @return $this
     */
    public function useHttpAuth($username, $password)
    {
        $this->_httpAuth = true;
        $this->_username = $username;
        $this->_password = $password;

        return $this;
    }

    /**
     * @param string $string string for format.
     * @param bool $asObject if you need get result as object.
     * @return array|\stdClass
     * @throws Exception
     */
    protected function formatResult($string, $asObject)
    {
        $contentType = !empty($this->_requestContentType) ? $this->_requestContentType : $this->_contentType;
        switch ($contentType) {
            case self::JSON :
                $result = json_decode($string, true);
                break;
            case self::XML :
                $result = (new XML())->decode($string);
                break;
            default :
                throw new Exception('Undefined RESTful response type!');
        }

        return $asObject ? (object)$result : $result;
    }

    /**
     * @return \SimpleXMLElement|string
     * @throws Exception
     */
    protected function getFormattedData()
    {
        switch ($this->_contentType) {
            case self::JSON :
                $result = json_encode($this->_data);
                break;
            case self::XML :
                $rootNode = key($this->_data);
                $result = (new XML())->encode(reset($this->_data), $rootNode);
                break;
            default :
                throw new Exception('Undefined RESTful response type!');
        }

        return $result;
    }

    /**
     * Set GET data.
     */
    protected function setGetData()
    {
        if (!empty($this->_data)) {
            $this->_url = sprintf("%s?%s", $this->_url, http_build_query($this->_data));
        }
    }

    /**
     * Set POST data.
     * @throws Exception
     */
    protected function setPostData()
    {
        if (!empty($this->_data)) {
            curl_setopt($this->_request, CURLOPT_POST, count($this->_data));
            curl_setopt($this->_request, CURLOPT_POSTFIELDS, $this->getFormattedData());
        }
    }

    /**
     * @param string $value content type for this request.
     * @return $this
     */
    public function setContentType($value)
    {
        $this->_contentType = $value;

        return $this;
    }

    /**
     * @param string $value user agent.
     * @return $this
     */
    public function setUserAgent($value)
    {
        $this->_userAgent = $value;

        return $this;
    }

    /**
     * @param bool $asObject if you need get result as object.
     * @return array|\stdClass result.
     * @throws Exception if curl request have errors.
     */
    public function call($asObject = false)
    {
        if (!$this->_paramsIsSet) {
            throw new Exception('Required params isn`t set. Please use requestParams()');
        }

        $this->_request = curl_init();

        /* Set user agent & content type. */
        curl_setopt($this->_request, CURLOPT_USERAGENT, $this->_userAgent);
        curl_setopt($this->_request, CURLOPT_HTTPHEADER, ['Accept:' . $this->_contentType]);
        curl_setopt($this->_request, CURLOPT_HTTPHEADER, ['Content-Type:' . $this->_contentType]);

        /* Call REST method. */
        $this->{$this->_method}();

        /* Optional Authentication: */
        !empty($this->_httpAuth) && $this->httpAuth();

        /* Set url for api request */
        curl_setopt($this->_request, CURLOPT_URL, $this->_url);
        curl_setopt($this->_request, CURLOPT_RETURNTRANSFER, true);

        /* Set cookie */
        !empty($this->_cookieFilePath) && $this->useCookie();

        /* Call api */
        $result = curl_exec($this->_request);

        /* Get curl errors */
        if (curl_errno($this->_request)) {
            throw new Exception(curl_error($this->_request), curl_errno($this->_request));
        }

        if ($this->_useResponseContentType) {
            /* Set request ContentType */
            $this->_requestContentType = curl_getinfo($this->_request, CURLINFO_CONTENT_TYPE);

            if ($this->_requestContentType) {
                $this->_requestContentType = explode(';', $this->_requestContentType);
                $this->_requestContentType = $this->_requestContentType[0];
            }
        }

        /* End request */
        curl_close($this->_request);

        return $this->formatResult($result, $asObject);
    }
}
