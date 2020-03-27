<?php

namespace App\Controller;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Masterminds\HTML5;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * CompanyController constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

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
//        $url = 'https://angel.co/companies';
//        $url = 'https://angel.co/';

        $page = $this->disguise_curl($url);
        $html5 =new HTML5();
        $dom = $html5->loadHTML($page);

        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@class="results"]');

        $arr = [];
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $nodes = $element->childNodes;
                foreach ($nodes as $node) {
                    $arr[] = $node->nodeValue;
                }
            }
        }
        $this->save($arr);
        dump($page);
//        dump($dom);
//        dump($arr);
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
//        $proxy = '177.125.20.35:40744';
//        $proxy = '113.160.206.37:37194';
//        $proxy = '113.252.95.19:8197';
//        $proxy = '103.86.135.62:59538';
//        $proxy = '109.172.57.250:23500';
//        $proxy = '94.127.144.179:33905';
//        $proxy = '82.200.233.4:3128';
//        $proxy = '27.116.51.119:8080';
//        $proxy = '88.99.10.249';
//        $proxy = '103.79.235.160';
//        $proxy = '103.250.156.22';
//        $proxy = '195.214.222.75';
//        $proxy = '31.154.189.211';
//        $proxy = '124.41.240.126';
//        $proxy = '91.67.240.32';
//        $proxy = '185.44.229.227';
//        $proxy = '92.33.17.248';
//        $proxy = '80.106.247.145';
        $proxy = '79.135.240.254';

//        $header[] = ":authority: angel.co";
//        $header[] = ":method: GET";
//        $header[] = ":path: /companies";
//        $header[] = ":path: /";
//        $header[] = ":scheme: https";
        $header[] = "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
//        $header[] = "accept-encoding: gzip, deflate";
        $header[] = "accept-language: en-US,en;q=0.9";
        $header[] = "cache-control: max-age=0";
//        $header[] = "cookie: __cfduid=dd8e30081b217b758111fe8abbbcb33251585300634; _angellist=4e96ced5280b0392e0c5e2a90d832928; __cf_bm=ab3d5f6874f117c3a94f286841cce40456b1a2b5-1585300634-1800-ARuroRuaYZcsjFZ+z6wZwIf3OMBRQJueJZEOabfSIhO1J6sbf7FTl8+sXHmqoViYYjR6y/vL7JiVtPzjcyDzqaQ=";
        $header[] = "sec-fetch-dest: document";
        $header[] = "sec-fetch-mode: navigate";
        $header[] = "sec-fetch-site: same-origin";
        $header[] = "sec-fetch-user: ?1";
        $header[] = "upgrade-insecure-requests: 1";
//        $header[] = "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36";
        curl_setopt($curl, CURLOPT_PROXY, $proxy);
        curl_setopt($curl, CURLOPT_PROXYPORT, "47731");
//        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);

//        curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');
//        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);

        curl_setopt($curl, CURLOPT_URL, $url);

        $html = curl_exec($curl);
        if (!$html)
        {
            echo "cURL error number:" .curl_errno($curl) . "<br>";
            echo "cURL error:" . curl_error($curl) . "<br>";
            exit;
        }

        curl_close($curl);
        return $html;
    }

    public function save($array)
    {
        foreach ($array as $data) {
            $company = new Company();
            $company->setName($data['name']);
            $company->setLocation($data['location']);
            $company->setMarket($data['market']);
            $company->setWebsite($data['website']);
            $company->setEmployees($data['employees']);
            $company->setStage($data['stage']);
            $company->setRaised($data['raised']);

            $this->em->persist($company);
            $this->em->flush();
        }

    }
}
