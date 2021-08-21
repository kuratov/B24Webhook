<?php
namespace kuratovru\B24Webhook;

class B24Webhook
{

    /**
     * @var string
     */
    protected $domain;

    /**
     * Url segments for the API
     */
    const CRM_LEAD_ADD = 'crm.lead.add';
    const CRM_DUPLICATE_FINDBYCOMM = 'crm.duplicate.findbycomm';
    const CRM_LEAD_PRODUCTROWS_SET = 'crm.lead.productrows.set';
    const CRM_PRODUCT_LIST = 'crm.product.list';

    /**
     * B24Webhook constructor.
     * @param string $domain
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param string $secret
     * @param array $data
     * @param bool $register_event
     * @return int
     */
    public function addLead($secret, $data, $register_event = true)
    {
        $url = $this->prepareUrl($secret, self::CRM_LEAD_ADD);

        if (!isset($data['TITLE']))
            $data['TITLE'] = "Лид {$data['NAME']} с сайта {$_SERVER['HTTP_HOST']}";

        $data = [
            'fields' => $data,
            'params' => $register_event ? ["REGISTER_SONET_EVENT" => "Y"] : []
        ];

        $result = $this->sendRequest($url, $data);

        return $result['result'];
    }

    /**
     * @param string $secret
     * @param array $values
     * @param string $entity
     * @return array
     */
    public function checkDuplicateByPhone($secret, $values, $entity = '')
    {
        return $this->checkDuplicate($secret, 'PHONE', $values, $entity);
    }

    /**
     * @param string $secret
     * @param array $values
     * @param string $entity
     * @return array
     */
    public function checkDuplicateByEmail($secret, $values, $entity = '')
    {
        return $this->checkDuplicate($secret, 'EMAIL', $values, $entity);
    }

    /**
     * @param string $secret
     * @param int $leadId
     * @param array $rows
     * @return bool
     */
    public function addProductsToLead($secret, $leadId, $rows)
    {
        $url = $this->prepareUrl($secret, self::CRM_LEAD_PRODUCTROWS_SET);

        $data = [
            'id' => $leadId,
            'rows' => $rows
        ];

        $result = $this->sendRequest($url, $data);

        return $result['result'];
    }

    /**
     * @param string $secret
     * @param array $order
     * @param array $filter
     * @param array $select
     * @return array
     */
    public function getProducts($secret, $order = [], $filter = [], $select = [])
    {
        $url = $this->prepareUrl($secret, self::CRM_PRODUCT_LIST);

        $data = [
            'order' => $order,
            'filter' => $filter,
            'select' => $select
        ];

        $result = $this->sendRequest($url, $data);

        return $result['result'];
    }

    /**
     * @param string $secret
     * @param string $type
     * @param array $values
     * @param string $entity
     * @return array
     */
    protected function checkDuplicate($secret, $type, $values, $entity = '')
    {
        $url = $this->prepareUrl($secret, self::CRM_DUPLICATE_FINDBYCOMM);

        $data = [
            'type' => $type,
            'values' => $values
        ];

        if (in_array($entity, ['LEAD', 'CONTACT', 'COMPANY']))
            $data['entity_type'] = $entity;

        $result = $this->sendRequest($url, $data);

        $result = $result['result'];

        return $result;
    }

    /**
     * @param string $url
     * @param array $data
     * @return array
     * @throws B24WebhookException
     */
    protected function sendRequest($url, $data)
    {
        $data = $this->prepareData($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $data,
        ));

        $result = curl_exec($curl);

        $error = '';
        if (curl_errno($curl))
            $error = 'Error curl: ' . curl_error($curl);

        curl_close($curl);

        if (!empty($error))
            throw new B24WebhookException($error);

        $result = json_decode($result, true);

        if (isset($result['error']))
            throw new B24WebhookException($result['error_description']);

        return $result;
    }

    /**
     * @param array $data
     * @return string
     */
    protected function prepareData($data)
    {
        return http_build_query($data);
    }

    /**
     * @param string $secret
     * @param string $method
     * @return string
     */
    protected function prepareUrl($secret, $method)
    {
        return "https://{$this->domain}/rest/1/{$secret}/{$method}.json";
    }
}

class B24WebhookException extends \Exception
{
}