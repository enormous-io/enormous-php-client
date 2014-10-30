<?php

class  enormous
{
    public $appKey,
        $appSecret,
        $enormousURL = 'https://enormous.io',
        $enormousRealtimeMessageURL;

    function __construct($appKey, $appSecret)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;

        $this->enormousRealtimeMessageURL = $this->enormousURL . '/apps/emit/';

        return $this;
    }

    //Publics
    public function generatePrivateKeyParams($params = array())
    {
        $secureParams = array(
            'auth'   => array(
                'timestamp'     => null,
                'timestampHash' => null
            ),
            'params' => $params
        );

        $timestamp = time();

        $secureParams['auth']['timestamp'] = $timestamp;
        $secureParams['auth']['timestampHash'] = hash_hmac('sha256', $timestamp, $this->appSecret);

        if (is_array($params) && count($params))
            $secureParams['auth']['paramsHash'] = hash_hmac('sha256', $this->_stringifyParams($params), $this->appSecret);

        return $secureParams;
    }

    public function getEmitRequestConfig($options = array())
    {

        $emitData = array(
            'channel'    => $options['channel'],
            'actionName' => $options['actionName'],
            'data'       => $options['data']
        );

        if ($options['privateKeyAuth']) {
            $secureParams = $this->generatePrivateKeyParams($emitData['data']);

            $emitData['auth'] = $secureParams['auth'];
        } elseif (!$options['basicUsername']) {
            $emitData['appSecret'] = $this->appSecret;
        }

        return $emitData;
    }

    public function emitToChannel($options)
    {
        $requestURL = $this->enormousRealtimeMessageURL . $this->appKey;
        $emitData = $this->getEmitRequestConfig($options);

        $request = curl_init($requestURL);
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($emitData));

        if (isset($options['basicUsername'])) {
            curl_setopt($request, CURLOPT_HEADER, 1);
            curl_setopt($request, CURLOPT_USERPWD, $options['basicUsername'] . ":" . $options['basicPassword']);
        }

        curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/javascript'));
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
        $return = curl_exec($request);
        curl_close($request);

        return json_decode($return, true);
    }

    //Privates
    private function _stringifyParams($params)
    {
        if (!is_array($params))
            return $params;

        $paramArr = array();

        foreach ($params as $var => $val) {
            $paramArr[] = $var;

            if (is_array($val)) {
                $paramArr[] = $this->_stringifyParams($val);

                continue;
            }

            $paramArr[] = $val;
        }

        $returnStr = implode(' ', $paramArr);

        return $returnStr;
    }
}