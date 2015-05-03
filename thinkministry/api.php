<?php

/**
 * Lightweight SOAP API Wrapper for ThinkMinistry (Ministry Platform)
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @copyright Daniel Boorn
 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 */


namespace ThinkMinistry;

/**
 * Class Exception
 * @package ThinkMinistry
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @copyright Daniel Boorn
 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 */
class Exception extends \Exception
{

    /**
     * @var null
     */
    public $response;
    /**
     * @var null
     */
    public $request;

    /**
     * @param string $message
     * @param int $code
     * @param null $response
     * @param null $request
     * @param \Exception $previous
     */
    public function __construct($message, $code = 0, $response = null, $request = null, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->request = $request;
    }

    /**
     * @return null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return null
     */
    public function getRequest()
    {
        return $this->request;
    }

}

/**
 * Class API
 * @package ThinkMinistry
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @copyright Daniel Boorn
 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 */
class API
{
    /**
     *
     */
    const API_PATH = '/ministryplatformapi/Api.svc?wsdl';

    /**
     * @var
     */
    protected $domain;

    /**
     * @var
     */
    protected $guId;
    /**
     * @var
     */
    protected $password;
    /**
     * @var
     */
    protected $soap;
    /**
     * @var
     */
    protected $userId;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @param $domain
     * @param $guId
     * @param $password
     */
    public function __construct($domain, $guId, $password)
    {
        $this->domain = $domain;
        $this->guId = $guId;
        $this->password = $password;
        $this->setSoap($this->domain);
    }

    /**
     * @param $domain
     */
    protected function setSoap($domain)
    {
        $this->soap = new \SoapClient($domain . static::API_PATH, array('trace' => 1));
    }

    /**
     * @param $domain
     * @param $guId
     * @param $password
     * @return API
     */
    public static function forge($domain, $guId, $password)
    {
        return new self($domain, $guId, $password);
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($name, array $arguments)
    {
        return $this->fetch(count($arguments) ? current($arguments) : array(), $name);
    }

    /**
     * @param $userId
     * @return $this
     */
    public function setUser($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEndpoints()
    {
        return $this->soap->__getFunctions();
    }

    /**
     * @return mixed
     */
    public function getPaymentTypes()
    {
        $r = $this->ExecuteStoredProcedure(array(
            'StoredProcedureName' => 'api_OGCC_GetProgramsAndPaymentTypes',
            'RequestString'       => http_build_query(array()),
        ));
        return $r->NewDataSet->Table1;
    }

    /**
     * @return mixed
     */
    public function getCreditCardPaymentTypeId()
    {
        foreach ($this->getPaymentTypes() as $row) {
            if ($row->Payment_Type == "Credit Card") return $row->Payment_Type_ID;
        }
    }

    /**
     * @return mixed
     */
    public function getAchPaymentTypeId()
    {
        foreach ($this->getPaymentTypes() as $row) {
            if ($row->Payment_Type == "ACH/EFT") return $row->Payment_Type_ID;
        }
    }

    /**
     * @param array $params
     * @param $endpoint
     * @return mixed
     * @throws Exception
     */
    public function fetch(array $params, $endpoint)
    {

        if ($endpoint !== 'AuthenticateUser') {
            $params = array_merge($params, array(
                'GUID'     => $this->guId,
                'Password' => $this->password,
            ));
        }

        $response = $this->soap->{$endpoint}($params);

        if ($this->debug) {
            var_dump( //example of how to debug soap
                $params,
                $endpoint,
                $this->soap->__getLastRequestHeaders(),
                $this->soap->__getLastRequest(),
                $this->soap->__getLastResponse(),
                $this->soap->__getLastResponseHeaders()
            );
        }

        if (isset($response->{"{$endpoint}Result"})) {
            // return xml result response
            if (isset($response->{"{$endpoint}Result"}->any)) {
                $result = new \SimpleXMLElement($response->{"{$endpoint}Result"}->any);
                return json_decode(json_encode($result));
            }

            // return parse pipe-delimited response
            $responseStr = $response->{"{$endpoint}Result"};
            $result = explode("|", $responseStr);
            if (count($result) !== 3) {
                throw new Exception("Unexpected response handled. {$responseStr}");
            }
            $result = (object)array(
                'id'         => $result[0],
                'error_code' => $result[1],
                'message'    => $result[2],
            );
            if (empty($result->error_code)) {
                throw new Exception("API Error Response: {$result->message} ({$result->code}", $result->code);
            }
        }
        // return xml response
        return $response;
    }

}
