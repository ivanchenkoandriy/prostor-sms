<?php

namespace IA\ProstorSMS;

class Client
{

    const ERROR_EMPTY_API_LOGIN = 'Empty api login not allowed';
    const ERROR_EMPTY_API_PASSWORD = 'Empty api password not allowed';
    const ERROR_EMPTY_RESPONSE = 'errorEmptyResponse';

    protected $apiLogin = null;
    protected $apiPassword = null;
    protected $host = 'json.gate.prostor-sms.ru';
    protected $packetSize = 200;
    protected $results = array();

    public function __construct($apiLogin, $apiPassword)
    {
        $this->setApiLogin($apiLogin);
        $this->setApiPassword($apiPassword);
    }

    private function setApiLogin($apiLogin)
    {
        if (empty($apiLogin)) {
            throw new Exception(self::ERROR_EMPTY_API_LOGIN);
        }
        $this->apiLogin = $apiLogin;
    }

    private function setApiPassword($apiPassword)
    {
        if (empty($apiPassword)) {
            throw new Exception(self::ERROR_EMPTY_API_PASSWORD);
        }
        $this->apiPassword = $apiPassword;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function getHost()
    {
        return $this->host;
    }

    private function sendRequest($uri, $params = null)
    {
        $url = $this->getUrl($uri);
        $data = $this->formPacket($params);
        $client = curl_init($url);
        curl_setopt_array($client, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array('Host: ' . $this->getHost()),
            CURLOPT_POSTFIELDS => $data,
        ));
        $body = curl_exec($client);
        curl_close($client);
        if (empty($body)) {
            throw new Exception(self::ERROR_EMPTY_RESPONSE);
        }
        $decodedBody = json_decode($body, true);
        if (is_null($decodedBody)) {
            throw new Exception($body);
        }
        return $decodedBody;
    }

    private function getUrl($uri)
    {
        return 'http://' . $this->getHost() . '/' . $uri . '/';
    }

    private function formPacket($params = null)
    {
        $params['login'] = $this->apiLogin;
        $params['password'] = $this->apiPassword;
        foreach ($params as $key => $value) {
            if (empty($value)) {
                unset($params[$key]);
            }
        }
        $packet = json_encode($params);
        return $packet;
    }

    public function getPacketSize()
    {
        return $this->packetSize;
    }

    public function send($messages, $statusQueueName = null,
            $scheduleTime = null)
    {
        $params = array(
            'messages' => $messages,
            'statusQueueName' => $statusQueueName,
            'scheduleTime' => $scheduleTime,
        );
        return $this->sendRequest('send', $params);
    }

    public function status($messages)
    {
        return $this->sendRequest('status', array('messages' => $messages));
    }

    public function statusQueue($name, $limit)
    {
        return $this->sendRequest('statusQueue', array(
                    'statusQueueName' => $name,
                    'statusQueueLimit' => $limit,
        ));
    }

    public function credits()
    {
        return $this->sendRequest('credits');
    }

    public function senders()
    {
        return $this->sendRequest('senders');
    }

}
