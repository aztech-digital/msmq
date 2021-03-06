<?php

namespace Aztech\Ntlm\Rpc;

use Aztech\Ntlm\Client;
use Aztech\Rpc\PduType;
use Aztech\Rpc\ProtocolDataUnit;
use Aztech\Rpc\Auth\AuthenticationContext;
use Aztech\Rpc\Auth\AuthenticationLevel;
use Aztech\Rpc\Auth\AuthenticationStrategy;
use Aztech\Rpc\Auth\NullVerifier;
use Aztech\Rpc\Pdu\ConnectionOriented\BindAckPdu;

class NtlmAuthenticationStrategy implements AuthenticationStrategy
{

    private $client = null;

    private $verifier = null;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->verifier = new NullVerifier();
    }

    public function getVerifier($type, AuthenticationContext $context, ProtocolDataUnit $lastResponse = null)
    {
        if ($context->getAuthLevel() >= AuthenticationLevel::CONNECT) {
            if ($type == PduType::BIND) {
                return new NtlmNegotiateVerifier($context, $this->client->getNegotiatePacket());
            }
            elseif ($type == PduType::BIND_RESP && $lastResponse instanceof BindAckPdu) {
                $challengeData = $lastResponse->getRawAuthData();
                $challenge = $this->client->parseChallenge($challengeData);
                $challengeResponse = $this->client->getAuthPacket($challenge);

                return new NtlmChallengeResponseVerifier($context, $challengeResponse);
            }

            if ($lastResponse && ! ($this->verifier instanceof NtlmVerifier)) {
                $this->verifier = new NtlmVerifier($context, $this->client->getSession());
            }
        }

        return $this->verifier;
    }

    public function getType()
    {
        return 0x0a;
    }
}
