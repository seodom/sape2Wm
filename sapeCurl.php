<?php
/**
 * Sape.ru cUrl interaction class
 *
 * @author Odarchenko N.D. <odarchenko.n.d@gmail.com>
 * @created 07.01.13 11:53
 */
class sapeCurl
{
    protected $cookieFile = '';
    protected $lastError = '';

    /**
     * @param $cookieFile
     */
    public function __construct($cookieFile)
    {
        $this->cookieFile = $cookieFile;
    }

    /**
     * Get cookie and save into cookieFile
     *
     * @param string $username
     * @param string $pass
     *
     * @return string
     */
    public function login($username, $pass)
    {
        return $this->myCurl(
            'https://auth.sape.ru/login/',
            array(
                 'act'      => 'login',
                 'r'        => 'https://www.sape.ru',
                 'username' => $username,
                 'password' => $pass
            )
        );

    }

    /**
     * Get last script error
     *
     * @return string
     */
    public function getLastError()
    {
        if (!$this->lastError)
        {
            return '';
        }

        return 'Error: ' . htmlspecialchars($this->lastError);
    }

    /**
     * make a request for payment
     *
     * @param float $amount
     *
     * @return bool
     */
    public function makeRequest4Payment($amount)
    {
        $cookie = file_get_contents($this->cookieFile);
        if (!$cookie)
        {
            $this->lastError = 'You need login before "makeRequest4Payment"';
            return FALSE; //you need login
        }

        //$regexp = '|JSESSIONID\s+([0-9a-z]+)|i';

        $page = $this->myCurl('http://passport.sape.ru/withdraw/webmoney/'); //getting JSESSIONID
       /* $cookie = file_get_contents($this->cookieFile);
        if (!preg_match($regexp, $cookie, $sid))
        {
            $this->lastError = 'regexp in "makeRequest4Payment" does not work!';
            return FALSE;
        }
        */
        if(!preg_match('|method="post" action="\.\./\.\.(/withdraw/webmoney/[^"]+)|', $page, $pUrl))
        {
            $this->lastError = 'regexp2 in "makeRequest4Payment" does not work!';
            return FALSE;
        }

        echo 'http://passport.sape.ru' .  $pUrl[1], '<br>';

        var_dump($amount);

        $answer = $this->myCurl(
        //http://passport.sape.ru/withdraw/webmoney/?wicket:interface=:1:withdrawFormContainer:form::IFormSubmitListener::
            'http://passport.sape.ru' .  $pUrl[1],
            array(
                 'amount'       => $amount,
                 'submitButton' => 'Создать заявку'
            ),
            'http://passport.sape.ru/withdraw/webmoney/'

        );

        if(preg_match('|(wicket[^\s]+)|', $answer, $p ))
        {
            echo 'http://passport.sape.ru/?' . $p[1], '<br>';
            $answer = $this->myCurl(
                'http://passport.sape.ru/?' . $p[1],
                array(
                     'idd_hf_0'     => '',
                     'amount'       => $amount,
                     'submitButton' => 'Создать заявку'
                ));
        }
        print_r($answer);
        return TRUE;
    }

    /**
     * Get available money
     *
     * @return bool|float
     */
    public function getBalance()
    {
        $js = $this->myCurl(
            'http://widget.sape.ru/balance/?alt=html&tpl=balance_main&container_id=balance_widget_src&charset=utf-8'
        );

        if (preg_match('|<th>Доступно</th>\s+<td colspan="2">\s*<b>([^<]+)</b>|', $js, $match))
        {
            //remove extra characters (ex. &nbsp;)
            $amount = preg_replace("|[^0-9,]|", '', $match[1]);
            return (float)str_replace(',', '.', $amount);
        }

        return FALSE;
    }

    /**
     * Curl Request
     *
     * @param string $url
     * @param string $post
     * @param string $referer
     *
     * @return string
     */
    protected function myCurl($url, $post = '', $referer = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if (!$referer)
        {
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        }
        else
        {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:17.0) Gecko/20.0 Firefox/20.0');
        curl_setopt($ch, CURLOPT_ENCODING, 'utf-8');

        curl_setopt($ch, CURLOPT_TIMEOUT, 200);

        if ($post)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        if ($this->cookieFile)
        {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
