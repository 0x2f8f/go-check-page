<?php
namespace CheckPageBundle\Service;

use \GuzzleHttp\Client as Client;
use Symfony\Component\DomCrawler\Crawler;

class CheckPageService
{
    public function __construct()
    {
    }

    /**
     * Кусок html по указанному селектору
     *
     * @param $url
     * @param $selector
     * @param bool $throughPrerender
     * @return null|string
     */
    public function getHtmlPart($url, $selector, $throughPrerender = false)
    {
        $html = $this->getHtmlPage($url, $throughPrerender);
        $crawler = new Crawler($html);
        $filter = $crawler->filter($selector);
        if (iterator_count($filter)) {
            $descr = $filter->html();
            return $descr;
        }
        return null;
    }

    /**
     * html страницы
     *
     * @param $url
     * @param bool $throughPrerender
     * @return null|string
     */
    public function getHtmlPage($url, $throughPrerender = false)
    {
        $clientParams = [
            'base_uri' => $url,
            'timeout'         => 30,
            'verify' => false
        ];
        if ($throughPrerender) {
            $url = 'http://37.200.70.51:3002/'.$url;
            $clientParams['headers'] = [
                'check-page'
            ];
        } else {
            $clientParams['headers'] = [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3'
            ];
        }
        $clientParams['base_uri'] = $url;

        $client = new Client($clientParams);
        try {
            $response = $client->request('GET', '');
            $html = $response->getBody()->getContents();
            return $html;
        } catch (\Exception $e) {
            return null;
        }
    }
}