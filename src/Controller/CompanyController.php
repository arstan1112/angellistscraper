<?php

namespace App\Controller;

use Masterminds\HTML5;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    /**
     * @Route("/", name="company.list")
     */
    public function index()
    {
        return $this->render('company/index.html.twig', [
            'controller_name' => 'CompanyController',
        ]);
    }

    /**
     * @Route("scrape")
     */
    public function scrape()
    {
        $url = 'https://angel.co/companies';
        $page = $this->disguise_curl($url);
        dump($page);
        die();
//        $user_agent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';
//        $options = array(
//            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
//            CURLOPT_POST           =>false,        //set to GET
//            CURLOPT_USERAGENT      => $user_agent, //set user agent
//            CURLOPT_RETURNTRANSFER => true,     // return web page
//            CURLOPT_HEADER         => false,    // don't return headers
//            CURLOPT_FOLLOWLOCATION => false,     // follow redirects
//            CURLOPT_ENCODING       => "",       // handle all encodings
//            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
//            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
//            CURLOPT_TIMEOUT        => 120,      // timeout on response
//            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
//        );
//
//        $ch      = curl_init( $url );
//        curl_setopt_array( $ch, $options );
//        $content = curl_exec( $ch );
//        $err     = curl_errno( $ch );
//        $errmsg  = curl_error( $ch );
//        $header  = curl_getinfo( $ch );
//        curl_close( $ch );
//
//        $header['errno']   = $err;
//        $header['errmsg']  = $errmsg;
//        $header['content'] = $content;
//        dump($header);
//        die();
//
//        $curl = curl_init('https://angel.co/companies');
////        $curl = curl_init('https://www.lipsum.com/');
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
//        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36');
//
//        $page = curl_exec($curl);
//
//        dump($page);
//        die();
//
//        $html5 =new HTML5();
//        $dom = $html5->loadHTML($page);
//
////        $doc = new \DOMDocument();
////        $doc->loadHTML($page);
//
//        $xpath = new \DOMXPath($dom);
//
////        $ids = $xpath->query("//*[@id]");
////        $elements = $xpath->query('//*[@id="Packages"]');
//        $elements = $xpath->query('//*[@id="root"]');
//
////        $elements = $xpath->query('*/div[@id="Packages"]');
////        $elements = $xpath->query('//div[@class="results"]');
////        $elements = $xpath->query('//div[contains(@class, "results")]');
////        $elements = $xpath->query('//div[contains(@class, "banner")]');
////        $elements = $xpath->query('/html/body/div[contains(@class, "banner")]');
////        $elements = $xpath->query('//div[@class="banner"]');
////        $elements = $xpath->query('//*[@class="banner"]');
////        $elements = $xpath->query('//*[@class="results"]');
////        dump($elements);
////        die();
////        $arr = [];
//        if (!is_null($elements)) {
//            foreach ($elements as $element) {
////                echo "<br/>[". $element->nodeName. "]";
//
//                $nodes = $element->childNodes;
//                foreach ($nodes as $node) {
////                    echo $node->nodeValue. "\n";
//                    $arr[] = $node->nodeValue;
//                }
//            }
//        }
////        dump($arr);
////        die();
//
//
//        if (!empty($curl)){
//
//            $html5 =new HTML5();
//            $dom = $html5->loadHTML($page);
//
//            $crawler = new Crawler($dom);
//            $crawler = $crawler->filter('div');
////            $subsetCrawler = $crawler->filterXPath('descendant-or-self::div');
//
////            $firstDiv = $crawler->filter('body > div')->last();
//            dump($crawler);
////            $outer = $crawler->filter('#Packages a');
////            var_dump(count($crawler));
////            var_dump($subsetCrawler);
////            var_dump($crawler);
////            dump($outer);
////            dump($firstDiv);
////            dump($firstDiv->text());
//
////        dump($firstDiv);
////        dump($crawler);
//
////        dump($page);
////        curl_close($curl);
//        }


    }

    function disguise_curl($url)
    {
        $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';
        $curl = curl_init();
        $header[] = ":authority: angel.co";
        $header[] = ":method: GET";
        $header[] = ":path: /companies";
        $header[] = ":scheme: https";
        $header[] = "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
        $header[] = "accept-encoding: gzip, deflate";
        $header[] = "accept-language: en-US,en;q=0.9,ky;q=0.8";
        $header[] = "cache-control: max-age=0";
        $header[] = "cookie: _angellist=e1ce62dd3d7b6dc5b3fc70edfe0e314c; _ga=GA1.2.1687189962.1585124251; _gid=GA1.2.141327994.1585124251; _fbp=fb.1.1585124251060.333499133; ajs_user_id=null; ajs_group_id=null; ajs_anonymous_id=%22e1ce62dd3d7b6dc5b3fc70edfe0e314c%22; _hjid=7d511e76-67ea-4cf3-833d-729e17833c8f; _hjIncludedInSample=1; amplitude_idundefinedangel.co=eyJvcHRPdXQiOmZhbHNlLCJzZXNzaW9uSWQiOm51bGwsImxhc3RFdmVudFRpbWUiOm51bGwsImV2ZW50SWQiOjAsImlkZW50aWZ5SWQiOjAsInNlcXVlbmNlTnVtYmVyIjowfQ==; amplitude_id_add5896bb4e577b77205df2195a968f6angel.co=eyJkZXZpY2VJZCI6IjEzNzY3NmEwLWE4MjAtNDFkNy05ZWY5LThlODlhMjJlZTVmMlIiLCJ1c2VySWQiOm51bGwsIm9wdE91dCI6ZmFsc2UsInNlc3Npb25JZCI6MTU4NTIzMTk4MjI5NiwibGFzdEV2ZW50VGltZSI6MTU4NTIzMjE3MjE3MiwiZXZlbnRJZCI6MSwiaWRlbnRpZnlJZCI6MCwic2VxdWVuY2VOdW1iZXIiOjF9; __cfduid=da941b8df211c823313bf33a6164509281585232182; __cf_bm=6c31d13e1729840514dcfe2e2237581f9c065673-1585238040-1800-AWfYy+OgDnLHzIWuOMCQ9BlvZOKBJ4DXW5JxXqAHcYKZC05ubPRZqAtdnbhvV7cttpVGUhg+dZ14wF/sOzXd+U4=";
        $header[] = "sec-fetch-dest: document";
        $header[] = "sec-fetch-mode: navigate";
        $header[] = "sec-fetch-site: same-origin";
        $header[] = "sec-fetch-user: ?1";
        $header[] = "upgrade-insecure-requests: 1";
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($curl);
        if (!$html)
        {
            echo "cURL error number:" .curl_errno($curl);
            echo "cURL error:" . curl_error($curl);
            exit;
        }

        curl_close($curl);
        return $html;
    }
}
