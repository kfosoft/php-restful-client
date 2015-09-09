# RESTful PHP Client
## Installation

Installation with Composer

Either run
~~~
    php composer.phar require --prefer-dist kfosoft/rest-client:"*"
~~~
or add in composer.json
~~~
    "require": {
            ...
            "kfosoft/php-restful-client":"*"
    }
~~~

Well done!

#### Example call GET
~~~
    $result = (new RestClient())->requestParams(RestClient::GET, $url, $data)->call();
~~~

#### Example call CUSTOM with GET data
~~~
    $result = (new RestClient())->requestParams(RestClient::CUSTOM, $url, $data)->call();
~~~

#### Example call CUSTOM with POST data
~~~
    $result = (new RestClient())->requestParams(RestClient::CUSTOM, $url, $data)->usePost()->call();
~~~

#### Example call to url with http auth
~~~
    $result = (new RestClient())->requestParams(RestClient::POST, $url, $data)->useHttpAuth($username, $password)->call();
~~~

#### Use response content type
~~~
    $result = (new RestClient())->requestParams(RestClient::PATCH, $url, $data)->useResponseContentType()->call();
~~~

#### Get result in stdClass
~~~
    $result = (new RestClient())->requestParams(RestClient::PUT, $url, $data)->usePost()->call(true);
~~~


Enjoy, guys!
