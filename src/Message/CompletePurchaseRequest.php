<?php

namespace Omnipay\CapitaPay360\Message;

use SoapClient;

/**
 * CapitaPay360 Complete Purchase Request
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    public function getData()
    {


        $timeStamp = gmdate("YmdHis");
        $uniqueReference = uniqid('PB');
        $subjectType = 'CapitaPortal';
        $algorithm = 'Original';
        $credentialsToHash = implode('!', array(
            $subjectType,
            $this->getScpId(),
            $uniqueReference,
            $timeStamp,
            $algorithm,
            $this->getHmacKeyId()
        ));
        $key = base64_decode($this->getHmacKey());
        $hash = hash_hmac('sha256', $credentialsToHash, $key, true);
        $digest = base64_encode($hash);

        $data = array(
            'credentials' => array(
                'subject' => array(
                    'subjectType' => $subjectType,
                    'identifier'  => $this->getScpId(),
                    'systemCode'  => 'SCP'
                ),
                'requestIdentification' => array(
                    'uniqueReference'   => $uniqueReference,
                    'timeStamp'         => $timeStamp
                ),
                'signature' => array(
                    'algorithm' => $algorithm,
                    'hmacKeyID' => $this->getHmacKeyId(),
                    'digest'    => $digest
                )
            ),
            'siteId' => $this->getSiteId(),
            'scpReference' => $this->getScpTransactionReference(),
        );
        return $data;
    }

    public function sendData($data)
    {
        $response_data = $this->getSoapClient()->scpSimpleQuery($data);
        return $this->response = new CompletePurchaseResponse($this, $response_data);
    }
}
