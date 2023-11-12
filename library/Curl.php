<?php
namespace library;

/**
 * 
 * 
 */
class Curl 
{
    private $referer = 'https://www.google.ru/';
    
    private $userAgents = [
        // Opera
        'Mozilla / 5.0 (Windows NT 10.0; Win64; x64) AppleWebKit / 537.36 (KHTML, как Gecko) Chrome / 80.0.3987.163 Safari / 537.36 OPR / 67.0.3575.137',
        'Mozilla / 5.0 (Windows NT 10.0; Win64; x64) AppleWebKit / 537.36 (KHTML, как Gecko) Chrome / 80.0.3987.149 Safari / 537.36 OPR / 67.0.3575.115',

        // Mozilla Firefox
        'Mozilla / 5.0 (Macintosh; Intel Mac OS X 10.14; rv: 75.0) Gecko / 20100101 Firefox / 75.0',
        'Mozilla / 5.0 (Windows NT 6.1; Win64; x64; rv: 74.0) Gecko / 20100101 Firefox / 74.0',
        'Mozilla / 5.0 (X11; Ubuntu; Linux x86_64; rv: 75.0) Gecko / 20100101 Firefox / 75.0',
        'Mozilla / 5.0 (Windows NT 10.0; rv: 68.0) Gecko / 20100101 Firefox / 68.0',

        // Google Chrome
        'Mozilla / 5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit / 537.36 (KHTML, как Gecko) Chrome / 80.0.3987.163 Safari / 537.36',
        'Mozilla / 5.0 (Windows NT 10.0; Win64; x64) AppleWebKit / 537.36 (KHTML, как Gecko) Chrome / 81.0.4044.92 Safari / 537.36',
        'Mozilla / 5.0 (Windows NT 6.1; Win64; x64) AppleWebKit / 537.36 (KHTML, как Gecko) Chrome / 80.0.3987.163 Safari / 537.36',

        // Safari
        'Mozilla / 5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit / 605.1.15 (KHTML, как Gecko) Версия / 13.1 Safari / 605.1.15'
    ];
    


    /**
     * Получаем данные по одному УРЛ
     * 
     * 
     */
    public function one ($url, $post = false, $followLocation = false, $maxRedirects = 10) 
    {
        // Options
        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => false,
            CURLOPT_REFERER        => $this->referer,
            CURLOPT_USERAGENT      => $this->getUserAgent(),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        if ($post) {
            $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        }

        if ($followLocation) {
            $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
            $curlOptions[CURLOPT_MAXREDIRS]      = $maxRedirects;
        }
        
        $curl = curl_init();

        curl_setopt_array($curl, $curlOptions);

        $result = curl_exec($curl);
        $info   = curl_getinfo($curl);

        curl_close($curl);

        return ['result' => $result, 'info' => $info];
    }



    /**
     * Мульти соединение
     * 
     * 
     */
    public function multi ($urls, $post = false, $followLocation = false, $maxRedirects = 10) 
    {
        // Options
        $curlOptions = [
            CURLOPT_HEADER         => false,
            CURLOPT_NOBODY         => false,
            CURLOPT_REFERER        => $this->referer,
            CURLOPT_USERAGENT      => $this->getUserAgent (),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 10
        ];

        if ($post) {
            $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        }

        if ($followLocation) {
            $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
            $curlOptions[CURLOPT_MAXREDIRS]      = $maxRedirects;
        }

        // Init multi-curl
        $mh = curl_multi_init ();
        $chArray = [];

        $urls = !is_array ($urls) ? [$urls] : $urls;
        foreach ($urls as $key => $url) {
            // Init of requests without executing
            $ch = curl_init ($url);
            curl_setopt_array ($ch, $curlOptions);

            $chArray[$key] = $ch;

            // Add the handle to multi-curl
            curl_multi_add_handle ($mh, $ch);
        }

        // Execute all requests simultaneously
        $active = null;
        do {
            $mrc = curl_multi_exec ($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            // Wait for activity on any curl-connection
            if (curl_multi_select ($mh) === -1) {
                usleep (100);
            }

            while (curl_multi_exec ($mh, $active) == CURLM_CALL_MULTI_PERFORM);
        }

        // Close the resources
        foreach ($chArray as $ch) {
            curl_multi_remove_handle ($mh, $ch);
        }
        curl_multi_close ($mh);

        // Access the results
        $result = [];
        $info   = []; 

        foreach ($chArray as $key => $ch) {
            // Get response
            $result[$key] = curl_multi_getcontent ($ch);
            $info[$key]   = curl_getinfo ($ch);
        }

        return ['result' => $result, 'info' => $info];
    }



    /**
     * Многопоточный cURL
     * 
     * 
     */
    public function multiThreads ($urls, $post = false, $threads = 10, $followLocation = false, $maxRedirects = 10)
    {
        // Options
        $curlOptions = [
            CURLOPT_HEADER         => false,
            CURLOPT_NOBODY         => false,
            CURLOPT_REFERER        => $this->referer,
            CURLOPT_USERAGENT      => $this->getUserAgent (),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 10
        ];

        if ($post) {
            $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        }

        if ($followLocation) {
            $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
            $curlOptions[CURLOPT_MAXREDIRS] = $maxRedirects;
        }

        // Init multi-curl
        $mh = curl_multi_init ();
        $chArray = [];

        $executeMethod = function ($mh, $chArray, &$result, &$running, &$currentThread, &$info) {
            usleep (100);

            while (curl_multi_exec ($mh, $running) === CURLM_CALL_MULTI_PERFORM);

            curl_multi_select ($mh);

            while ($done = curl_multi_info_read ($mh)) {

                foreach ($chArray as $key => $ch) {

                    if ($ch == $done['handle']) {

                        // Get response
                        $result[$key] = curl_multi_getcontent ($ch);
                        $info[$key]   = curl_getinfo ($ch);

                    }

                }

                curl_multi_remove_handle ($mh, $done['handle']);
                curl_close ($done['handle']);

                $currentThread--;
            }
        };

        $result        = [];
        $info          = [];
        $running       = [];
        $currentThread = 0;

        $urls = !is_array ($urls) ? [$urls] : $urls;

        foreach ($urls as $key => $url) {

            // Init of requests without executing
            $ch = curl_init ($url);
            curl_setopt_array ($ch, $curlOptions);

            $chArray[$key] = $ch;

            // Add the handle to multi-curl
            curl_multi_add_handle ($mh, $ch);

            $currentThread++;

            if ($currentThread >= $threads) {

                while ($currentThread >= $threads)
                    $executeMethod ($mh, $chArray, $result, $running, $currentThread, $info);

            }
        }

        do {

            $executeMethod($mh, $chArray, $result, $running, $currentThread, $info);

        } while($running > 0);
        
        curl_multi_close ($mh);

        return ['result' => $result, 'info' => $info];
    }



    /**
     * Получаем Юзер агент рандомно из массива
     * 
     */
    private function getUserAgent() {
        return array_rand($this->userAgents);
    }



    /**
     * Проверяем домен на существование
     * 
     */
    public function checkDomain($url) 
    {
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_HEADER        => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $curl_result      = curl_exec($curl);
        $curl_info        = curl_getinfo($curl);
        $curl_error_code  = curl_errno($curl);

        curl_close($curl);

        return $curl_error_code;
    }


} // class Curl