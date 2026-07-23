<?php

namespace QnbSolutions\QnbEsolutions\service;

class user_service
{
    public function __construct(
        private readonly \SoapClient $soap,
    ) {
    }

    public function ws_login(string $username, string $password, string $lang = 'TR'): void
    {
        $this->soap->wsLogin([
            'userId' => $username,
            'password' => $password,
            'lang' => $lang,
        ]);
    }

    public function logout(): void
    {
        $this->soap->logout();
    }

    public function __soap_call(string $method, array $params): mixed
    {
        return $this->soap->__soapCall($method, [$params]);
    }
}
