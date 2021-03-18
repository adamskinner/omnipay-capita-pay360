<?php

namespace Omnipay\CapitaPay360\Message;

use SoapClient;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\CapitaPay360\ItemBag;

/**
 * CapitaPay360 Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    /** @var string */
    protected $liveWSDL = 'https://sbs.e-paycapita.com/scp/scpws/scpSimpleClient.wsdl';
    /** @var string */
    protected $testWSDL = 'https://sbsctest.e-paycapita.com/scp/scpws/scpSimpleClient.wsdl';
    /** @var mixed Soap client, added as property for unit testing and error checking */
    public $soapClient = null;

    public function getHmacKey()
    {
        return $this->getParameter('hmacKey');
    }

    public function setHmacKey($value)
    {
        return $this->setParameter('hmacKey', $value);
    }

    public function getHmacKeyID()
    {
        return $this->getParameter('hmacKeyID');
    }

    public function setHmacKeyID($value)
    {
        return $this->setParameter('hmacKeyID', $value);
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


	public function setScpTransactionReference($value)
    {
        return $this->setParameter('scpTransactionReference', $value);        
    }

	public function getScpTransactionReference()
    {
        return $this->getParameter('scpTransactionReference');        
    }	


	

    /**
     * Override {@see AbstractRequest::setItems()} to provide additional attributes
     */
    public function setItems($items)
    {
        if ($items && !$items instanceof ItemBag) {
            $items = new ItemBag($items);
        }

        return $this->setParameter('items', $items);
    }

    public function getData()
    {
        $this->validate('scpId', 'siteId', 'hmacKeyID', 'hmacKey', 'amount', 'currency');
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
                    'identifier' => $this->getScpId(),
                    'systemCode' => 'SCP'
                ),
                'requestIdentification' => array(
                    'uniqueReference' => $uniqueReference,
                    'timeStamp' => $timeStamp
                ),
                'signature' => array(
                    'algorithm' => $algorithm,
                    'hmacKeyID' => $this->getHmacKeyId(),
                    'digest' => $digest
                )
            ),
            'requestType' => 'payOnly',
            'requestId' => $this->getTransactionId(),
            'routing' => array(
                'returnUrl' => $this->getReturnUrl(),
                'backUrl' => $this->getCancelUrl(),
                'siteId' => $this->getSiteId(),
                'scpId' => $this->getScpId()
            ),
            'panEntryMethod' => 'ECOM',
            'sale' => array(
                'saleSummary' => array(
                    'description' => substr($this->getDescription(), 0, 100),
                    'amountInMinorUnits' => $this->getAmountInteger()
                )
            )
        );

        // Create items array to return
        $items = $this->getItems();
        if ($items) {
            $saleItems = array();
            foreach ($items as $itemIndex => $item) {
                $reference = $item->getReference();
                $additionalReference = $item->getAdditionalReference();
                $fundCode = $item->getFundCode();
                $narrative = $item->getNarrative();
                $lgItemDetails = ($additionalReference ? array('additionalReference' => $additionalReference) : array())
                    + ($fundCode ? array('fundCode' => $fundCode) : array())
                    + ($narrative ? array('narrative' => $narrative) : array());
                $saleItems[] = array(
                    'itemSummary' => array(
                        'description' => substr($item->getName(), 0, 100),
                        'amountInMinorUnits' => (int) round(
                            $item->getQuantity() * $item->getPrice() * pow(10, $this->getCurrencyDecimalPlaces())
                        ),
                    ) + ($reference ? array('reference' => $reference) : array()),
                    'quantity' => $item->getQuantity(),
                    'lineId' => $itemIndex + 1
                ) + ($lgItemDetails ? array('lgItemDetails' => $lgItemDetails) : array());
            }
            // only supply item detail if there are any items
            $data['sale']['items'] = $saleItems;
        }

        // add card holder details if available
        $card = $this->getCard();
        if ($card) {
            $name = $card->getName();
            $address1 = $card->getAddress1();
            $address2 = $card->getAddress2();
            $address3 = $card->getCity();
            $country = $card->getCountry();
            $postcode = $card->getPostcode();
            $address = ($address1 ? array('address1' => $address1) : array())
                + ($address2 ? array('address2' => $address2) : array())
                + ($address3 ? array('address3' => $address3) : array())
                + ($country ? array('country' => $country) : array())
                + ($postcode ? array('postcode' => $postcode) : array());
            $cardHolderDetails = ($name ? array('cardHolderName' => $name) : array())
                + ($address ? array('address' => $address) : array());
            if ($cardHolderDetails) {
                $data['billing'] = array('cardHolderDetails' => $cardHolderDetails);
            }
        }

        return $data;
    }

    public function sendData($data)
    {
        $responseData = $this->getSoapClient()->scpSimpleInvoke($data);

        return $this->response = new PurchaseResponse($this, $responseData);
    }

    protected function getSoapClient()
    {
        return $this->soapClient === null ? new SoapClient($this->getEndpoint(), array()) : $this->soapClient;
    }

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testWSDL : $this->liveWSDL;
    }
}
