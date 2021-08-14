<?php

class RetailcrmService
{
    /**
     * @var string
     */
    private $apiBaseUrl;

    /**
     * @var string[]
     */
    private $headers;

    /**
     * @var string
     */
    private $keyParam;


    const ARTICLE = 'AZ105R';

    /**
     * RetailcrmService constructor.
     */
    public function __construct()
    {
        //Can be stored in env file
        $this->apiBaseUrl = 'https://test.retailcrm.ru/api/v5/';
        $this->headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];
        $this->keyParam = '&apiKey=QlnRWTTWw9lv3kjxy1A8byjUmBQedYqb';
    }

    /**
     * @param $url
     * @return mixed|null
     */
    public function sendGet($url)
    {
        $curlGet = curl_init();
        curl_setopt($curlGet, CURLOPT_URL, $url);
        curl_setopt($curlGet, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curlGet, CURLOPT_RETURNTRANSFER, true);
        $responseRow = curl_exec($curlGet);
        curl_close($curlGet);

        $response = json_decode($responseRow);
        if (isset($response->success) && false == $response->success) {
            // record log
            return null;
        }
        return $response;
    }

    /**
     * @return mixed|null
     */
    public function getPagination()
    {
        $url = $this->apiBaseUrl . 'store/products?filter[manufacturer]=Azalita' . $this->keyParam;
        $pagination = $this->sendGet($url);
        return $pagination;

    }

    /**
     * @return null
     */
    public function getProductId()
    {
        $productId = null;
        $paginationObject = $this->getPagination();
        $pageTotal = $paginationObject->pagination->totalPageCount;

        if (is_null($pageTotal)) {
            return null;
        }

        for ($pageCurr = 1; $pageCurr <= $pageTotal; $pageCurr++) {
            $apiPageUrl = '&page=' . $pageCurr;
            $url = $this->apiBaseUrl . 'store/products?filter[manufacturer]=Azalita' . $apiPageUrl . $this->keyParam;

            $products = $this->sendGet($url);
            if (!isset($products->products)) {
                break;
            }

            //check productArticle is 'TRA7909'
            foreach ($products->products as $productArticle) {
                if (self::ARTICLE == $productArticle->article) {
                    $productId = $productArticle->id;
                    break;
                }
            }
        }
        return $productId;
    }

    /**
     * @return mixed|null
     */
    public function saveOrder()
    {
        $productId = $this->getProductId();
        if (is_null($productId)) {
            return null;
        }

        $orderParams = $this->getOrderParams($productId);
        $result = $this->sendPost($orderParams);

        return $result;
    }

    /**
     * @param $productId
     * @return false|string
     */
    public function getOrderParams($productId)
    {
        $orderRow = [
            'orderType' => 'fizik',
            'orderMethod' => 'test',
            'number' => '11101995',
            'lastName' => 'Которобай',
            'firstName' => 'Кристиан',
            'patronymic' => 'Виорелович',
            'prim' => 'тестовое задание',
            'customerComment' => 'https://github.com/Fetell/TZ-SuperPosuda/blob/main/index.php',
            'items' => [
                [
                    'offer' => [
                        'id' => $productId,
                    ],
                ],
            ],
        ];

        $order = json_encode($orderRow);

        return $order;
    }

    /**
     * @param $urlParams
     * @return mixed|null
     */
    public function sendPost($urlParams)
    {
        $url = $this->apiBaseUrl . 'orders/create?order=' . $urlParams . $this->keyParam;

        $curlPost = curl_init();

        curl_setopt($curlPost, CURLOPT_URL, $url);
        curl_setopt($curlPost, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curlPost, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curlPost, CURLOPT_RETURNTRANSFER, true);

        $responseRow = curl_exec($curlPost);
        $response = json_decode($responseRow);
        curl_close($curlPost);

        if (!isset($response->success)) {
            return null;
        }
        if (isset($response->success) && false == $response->success) {
            return null;
        }

        return $response;
    }
}

$foo = new RetailcrmService();
$foo->saveOrder();