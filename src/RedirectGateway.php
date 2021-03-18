<?php

namespace Omnipay\CapitaPay360;

use Omnipay\Common\AbstractGateway;
use Omnipay\CapitaPay360\Message\CompletePurchaseRequest;
use Omnipay\CapitaPay360\Message\PurchaseRequest;

/**
 * CapitaPay360 Redirect Gateway
 */
class RedirectGateway extends AbstractGateway
{
    public function getName()
    {
        return 'Capita Pay360 Redirect';
    }

    public function getDefaultParameters()
    {
        return array(
            'scpId' => '',
            'siteId' => '',
            'hmacKeyId' => '',
            'hmacKey' => '',
            'testMode' => false,
			'scpReference' => '',
        );
    }

    public function getScpId()
    {
        return $this->getParameter('scpId');
    }

    public function setScpId($value)
    {
        return $this->setParameter('scpId', $value);
    }

    public function getSiteId()
    {
        return $this->getParameter('siteId');
    }

    public function setSiteId($value)
    {
        return $this->setParameter('siteId', $value);
    }

    public function getHmacKeyId()
    {
        return $this->getParameter('hmacKeyId');
    }

    public function setHmacKeyId($value)
    {
        return $this->setParameter('hmacKeyId', $value);
    }

    public function getHmacKey()
    {
        return $this->getParameter('hmacKey');
    }

    public function setHmacKey($value)
    {
        return $this->setParameter('hmacKey', $value);
    }

	public function setScpTransactionReference($value)
    {
        return $this->setParameter('scpTransactionReference', $value);        
    }

	public function getScpTransactionReference()
    {
        return $this->getParameter('scpTransactionReference');        
    }	


    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\CapitaPay360\Message\PurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\CapitaPay360\Message\CompletePurchaseRequest', $parameters);
    }
}
