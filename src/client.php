<?php

namespace QnbSolutions\QnbEsolutions;

use QnbSolutions\QnbEsolutions\exception\auth_exception;
use QnbSolutions\QnbEsolutions\service\archive_service;
use QnbSolutions\QnbEsolutions\service\despatch_service;
use QnbSolutions\QnbEsolutions\service\invoice_service;
use QnbSolutions\QnbEsolutions\service\ledger_service;
use QnbSolutions\QnbEsolutions\service\user_service;

class client
{
    public const ENV_TEST = 'test';
    public const ENV_TEST2 = 'test2';
    public const ENV_PROD = 'prod';

    public const AUTH_WSSE = 'wsse';
    public const AUTH_COOKIE = 'cookie';

    private const URLS = [
        self::ENV_TEST => [
            'user' => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl',
            'connector' => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl',
            'archive_user' => 'https://connectortest.qnbesolutions.com.tr/connector/ws/userService?wsdl',
            'archive' => 'https://earsivtest.qnbesolutions.com.tr/earsiv/ws/EarsivWebService?wsdl',
        ],
        self::ENV_TEST2 => [
            'user' => 'https://erpefaturatest2.qnbesolutions.com.tr/efatura/ws/userService?wsdl',
            'connector' => 'https://erpefaturatest2.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl',
            'archive_user' => 'https://connectortest.qnbesolutions.com.tr/connector/ws/userService?wsdl',
            'archive' => 'https://earsivtest.qnbesolutions.com.tr/earsiv/ws/EarsivWebService?wsdl',
        ],
        self::ENV_PROD => [
            'user' => 'https://connector.qnbesolutions.com.tr/connector/ws/connectorService?wsdl',
            'connector' => 'https://connector.qnbesolutions.com.tr/connector/ws/connectorService?wsdl',
            'archive_user' => 'https://connector.qnbesolutions.com.tr/connector/ws/connectorService?wsdl',
            'archive' => 'https://earsiv.qnbesolutions.com.tr/earsiv/ws/EarsivWebService?wsdl',
        ],
    ];

    private ?user_service $user_service = null;
    private ?invoice_service $invoice_service = null;
    private ?despatch_service $despatch_service = null;
    private ?archive_service $archive_service = null;
    private ?ledger_service $ledger_service = null;

    private bool $authenticated = false;

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $environment = self::ENV_TEST,
        private readonly string $auth_method = self::AUTH_WSSE,
    ) {
        if (!extension_loaded('soap')) {
            throw new \RuntimeException('ext-soap gerekli. PHP SoapClient kullanılabilir olmalıdır.');
        }
    }

    public function user(): user_service
    {
        if ($this->user_service === null) {
            $soap = $this->create_soap(self::URLS[$this->environment]['user']);
            $this->user_service = new user_service($soap);
        }
        return $this->user_service;
    }

    public function invoice(): invoice_service
    {
        if ($this->invoice_service === null) {
            $soap = $this->create_soap(self::URLS[$this->environment]['connector']);
            $this->invoice_service = new invoice_service($soap);
        }
        return $this->invoice_service;
    }

    public function despatch(): despatch_service
    {
        if ($this->despatch_service === null) {
            $soap = $this->create_soap(self::URLS[$this->environment]['connector']);
            $this->despatch_service = new despatch_service($soap);
        }
        return $this->despatch_service;
    }

    public function archive(): archive_service
    {
        if ($this->archive_service === null) {
            $soap = $this->create_soap(self::URLS[$this->environment]['archive']);
            $this->archive_service = new archive_service($soap);
        }
        return $this->archive_service;
    }

    public function ledger(): ledger_service
    {
        if ($this->ledger_service === null) {
            $soap = $this->create_soap(self::URLS[$this->environment]['connector']);
            $this->ledger_service = new ledger_service($soap);
        }
        return $this->ledger_service;
    }

    /**
     * Kimlik doğrulama yapar. Cookie yönteminde oturum açar.
     */
    public function authenticate(): void
    {
        if ($this->authenticated) {
            return;
        }

        if ($this->auth_method === self::AUTH_COOKIE) {
            $this->user()->ws_login($this->username, $this->password);
        }

        $this->authenticated = true;
    }

    private function create_soap(string $wsdl): \SoapClient
    {
        $options = [
            'trace' => true,
            'exceptions' => true,
            'encoding' => 'UTF-8',
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];

        $soap = new \SoapClient($wsdl, $options);

        if ($this->auth_method === self::AUTH_WSSE) {
            $this->attach_wsse_header($soap);
        }

        return $soap;
    }

    private function attach_wsse_header(\SoapClient $soap): void
    {
        $xml = <<<XML
<wsse:Security
    xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
    <wsse:UsernameToken>
        <wsse:Username>{$this->username}</wsse:Username>
        <wsse:Password>{$this->password}</wsse:Password>
    </wsse:UsernameToken>
</wsse:Security>
XML;

        $header = new \SoapHeader(
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
            'Security',
            new \SoapVar($xml, XSD_ANYXML),
        );

        $soap->__setSoapHeaders($header);
    }
}
