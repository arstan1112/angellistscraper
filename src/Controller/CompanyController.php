<?php

namespace App\Controller;

use App\Entity\Company;
use CloudflareBypass\CFCurlImpl;
use CloudflareBypass\Model\UAMOptions;
use Doctrine\ORM\EntityManagerInterface;
use Masterminds\HTML5;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
//        dump($request);
//        return new Response(var_dump($request->getSession()));
//        dump($request->getContent());
//        die();
        return $this->render('company/index.html.twig', [
//            'controller_name' => 'CompanyController',
//            'request' => dump($request->request),
//            'request' => dump($request),
            'request' => $request,
//            'request' => var_dump($request->request),
//            'request' => print_r($request->request),
        ]);
    }

    /**
     * @Route("scrape")
     */
    public function scrape()
    {
        //        $myip = '185.66.252.188';

//        $url = 'https://angel.co/companies';
//        $url = 'https://angel.co/';
        $url = 'http://www.angellist.loc/';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        //        $url = 'http://www.angellist.loc/';
//        $url = 'https://whatismyipaddress.com/';

//        $url = 'https://104.18.24.39:443/';
//        $url = 'https://104.18.24.39/index.php';
//        $url = 'https://104.18.24.39/'; //angel.co
//        $url = 'https://104.16.155.36/'; //whatismyipaddress.com


//        $client = \Symfony\Component\Panther\Client::createChromeClient();
//        $client = \Symfony\Component\Panther\Client::createChromeClient(null, [
//            '--window-size=1200,1100',
//            '--proxy-server=http://134.209.101.192:8080',
////            "--proxy-server=socks://103.141.108.161:3127",
//            '--headless',
//            '--disable-gpu',
//        ], [
//            'connection_timeout_in_ms'=>null,
//            'request_timeout_in_ms'=>60000,
//        ]);

//        $crawler = $client->request('GET', $url);
//        $page = $crawler->html();
//        $client->close();

        $page = $this->disguise_curl($url);
//
        $html5 =new HTML5();
        $dom = $html5->loadHTML($page);

//        $dom = new \DOMDocument();
//        $dom->loadHTML($page);

////
        $xpath = new \DOMXPath($dom);
//        $xpath = new \DOMXPath($page);
//        $elements = $xpath->query('//*[@id="ipv4"]');
//        $elements = $xpath->query('/html/body/div[1]/div[4]/div[2]/div/div[2]/div[2]/div[2]');
//        $elements = $xpath->query('//*[@id="root"]/div[4]/div[2]/div/div[2]/div[2]/div[2]');
//        $elements = $xpath->query('//*[@class="results"]');
        $elements = $xpath->query('//*[@class="base startup"]');
//        $elements = $xpath->query('//*[@class="content"]');
//        $elements = $xpath->query('//*[@class="main_container"]');
//        $elements = $xpath->query('//*[@class="results"]/div[@class=" dc59 frw44 _a _jm"]');
//        $elements = $xpath->query('//*[@class="results"]/div[0]');
//        $elements = $xpath->query('//*[@id="root"]/div[4]/div[2]/div/div[2]/div[2]/div[2]/div[2]/div');

        $array_company_names = [];
        $array_company_locations = [];
        foreach ($elements as $element) {
            $nodes = $element->getElementsByTagName('div');

            //get company name
            $div_company_column = $nodes[0]->getElementsByTagName('div');
            $div_g_lockup       = $div_company_column[0]->getElementsByTagName('div');
            $div_text           = $div_g_lockup[1]->getElementsByTagName('div');
            $array_company_names[] = preg_replace("/[^A-Za-z0-9]/", '', $div_text[0]->nodeValue);

            //get company location
            $div_column_location = $nodes[14]->getElementsByTagName('div');
            $location_name = $div_column_location[0]->nodeValue;
            $array_company_locations[] = $location_name;
//            $div_column_location = $nodes[14]->getElementsByTagName('div');
//            $location_name = $div_column_location[0]->nodeValue;
//            $array_company_locations[] = $location_name;
//            $array_company_locations[] = preg_replace("/[^A-Za-z0-9]/", '', $div_column_location[0]->nodeValue);

        }

//        $nodes = $elements[0]->getElementsByTagName('div');
//        $div_column_location = $nodes[14]->getElementsByTagName('div');
//        $location_name = $div_column_location[0]->nodeValue;
//        $array_company_locations[] = $location_name;

//        dump($location_name);
//        die();
//        $div_value = $div_column_location[0]->getElementsByTagName('div');
//        dump($div_value);
//        die();
//        $array_company_locations[] = preg_replace("/[^A-Za-z0-9]/", '', $div_value->nodeValue);


//        $nodes = $elements[0]->getElementsByTagName('div');
//        $childnode1 = $nodes[0]->getElementsByTagName('div');
//        $childnode2 = $childnode1[0]->getElementsByTagName('div');
//        $childnode3 = $childnode2[1]->getElementsByTagName('div');
//        $string = $childnode3[0]->nodeValue;
//        $string = preg_replace("/[^A-Za-z0-9]/", '', $string);
//        dump($string);
//        dump($array_company_names);
        dump($array_company_locations);
        die();

//        foreach ($nodes as $node) {
//            $arr[] = $node->nodeValue;
//        }
//
////        foreach ($nodes as $node) {
//            $count = 1;
//            foreach ($xpath->query('//*[@class="base startup"]', $nodes[0]) as $child) {
////            foreach ($xpath->query('//*[@class="company column"]', $nodes[0]) as $child) {
////            foreach ($xpath->query('//*[@class="name"]', $nodes[0]) as $child) {
//                echo $count. " " .$child->nodeValue . "<br>";
////                echo $child->nodeValue, PHP_EOL;
////                dump($child->nodeValue, PHP_EOL);
////                $array[] = $child->nodeValue;
//                $count++;
//            }
////        }
//
////        dump($arr);
//        die();
//
////        dump($elements);
////        die();
//
//        $arr = [];
//        if (!is_null($elements)) {
//            foreach ($elements as $element) {
//                $nodes = $element->childNodes;
////                $arr[] = $nodes->nodeValue;
////                dump($nodes);
//                foreach ($nodes as $node) {
//                    $arr[] = $node->nodeValue;
////                    $child = $node->childNodes;
////                    foreach ($child as $ch) {
////                        $arr[] = $ch->nodeValue;
////                    }
//                }
//            }
//        }

//        $this->save($arr);
//        echo $page;
//        foreach ($arr as $ar) {
//            echo $ar .'<br>';
//            print_r($ar);
//            echo '<br>';
//        }
//        print_r($arr[1]);
//        dump($page);
//        dump($dom);
//        dump($arr);
//        die();
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

    protected function disguise_curl($url)
    {
//        $curl = curl_init($url);
        $curl = curl_init();

//        $proxy = '103.217.90.129';
//        $proxy = '5.172.2.240';
//        $proxy = '103.43.7.93';
//        $proxy = '123.231.226.114';
        $proxy = '217.146.219.118';
//        $port = '44005';
//        $port = '42088';
//        $port = '30004';
//        $port = '8080';
        $port = '39331';
        $user_agent = 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';

//        $header[] = ':authority: angel.co';
//        $header[] = ':method: GET';
//        $header[] = ':path: /companies';
//        $header[] = ':path: /';
//        $header[] = ':scheme: https';
        $header[] = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $header[] = 'accept-encoding: gzip, deflate';
//        $header[] = 'accept-language: en-US,en;q=0.9,ky;q=0.8';
        $header[] = 'accept-language: en-US,en;q=0.9';
        $header[] = 'cache-control: max-age=0';
        $header[] = 'connection: keep-alive';
//        $header[] = 'referer: https://www.google.com/';
//        $header[] = 'sec-fetch-dest: document';
//        $header[] = 'sec-fetch-mode: navigate';
//        $header[] = 'sec-fetch-site: cross-site';
//        $header[] = 'sec-fetch-user: ?1';
        $header[] = 'upgrade-insecure-requests: 1';
        $header[] = 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';

//        curl_setopt($curl, CURLOPT_PROXY, $proxy);
//        curl_setopt($curl, CURLOPT_PROXYPORT, $port);
//        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

//        curl_setopt($curl, CURLOPT_SSLVERSION, 3);
        curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');

        curl_setopt($curl, CURLOPT_ENCODING, "UTF-8" );
        curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);

//        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        curl_setopt($curl, CURLOPT_URL, $url);


        $html = curl_exec($curl);
        if (!$html)
        {
            echo "cURL error number:" .curl_errno($curl) . "<br>";
            echo "cURL error:" . curl_error($curl) . "<br>";
            exit;
        }
        curl_close($curl);


//        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
////        curl_setopt($curl, CURLOPT_HTTPHEADER,
////            array(
////                "upgrade-Insecure-Requests: 1",
////                "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36",
////                "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",
////                "accept-Language: en-US,en;q=0.9"
////            ));
//        $cfCurl = new CFCurlImpl();
//        $cfOptions = new UAMOptions();
//        $cfOptions->setVerbose(true);
//
//        try {
//            $html = $cfCurl->exec($curl, $cfOptions);
//        } catch (\ErrorException $ex) {
//            return "Unknown error ->" .$ex->getMessage();
//        }

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
