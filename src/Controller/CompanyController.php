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
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->render('company/index.html.twig', [
            'request' => $request,
        ]);
    }

    /**
     * @Route("/api/values", name="company.api.values")
     * @return Response
     */
    public function getValues()
    {
        return $this->render('company/values.html.twig', [
            'data' => 'values',
        ]);
    }

    /**
     * @Route("/api/keys", name="company.api.keys")
     * @return Response
     */
    public function getKeys()
    {
        return $this->render('company/keys.html.twig', [
            'data' => 'keys',
        ]);
    }

    /**
     * @Route("scrape")
     * @param null
     * @return null
     */
    public function scrape()
    {
        $url_keys = 'http://www.angellist.loc/api/keys';
        $url_values = 'http://www.angellist.loc/api/values';

        $page = $this->disguise_curl($url_values);

        $normalizer = new ObjectNormalizer();
        $encoder = new JsonEncoder();
        $serializer = new Serializer([$normalizer], [$encoder]);
        $page = $serializer->decode($page, 'json');
        $html = $page['html'];

        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@class="results"]');

        $arr = [];
        $empty = [];
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $nodes = $element->childNodes;
                foreach ($nodes as $node) {
                    $node = $node->nodeValue;
                    $node = preg_replace("/[^A-Za-z0-9@.\- ]/", '', $node);
                    $delimiters = ['Signal', 'Joined', 'Location', 'Market', 'Website', 'Stage', 'Employees', 'Total Raised'];
                    $node = $this->multiexplode($delimiters, $node);
                    $keys = ['Name', 'Signal', 'Joined', 'Location', 'Market', 'Website', 'Employees', 'Stage', 'Total Raised'];
                    if (count($node)==9) {
                        $node = array_combine($keys, $node);
                        foreach ($node as $key=>$val) {
                            if (ctype_space($val)) {
                                $node[$key] = '-';
                            }
                        }
                    } else {
                        $empty[] = $node;
                    }

                    $node = array_filter(array_map('trim', $node));

                    if (isset($node['Name'])) {
                        $node['Name'] = explode('  ', $node['Name']);
                        $shift = array_shift($node['Name']);
                        if (is_array($node['Name'])) {
                            $node['Name'] = implode('', $node['Name']);
                        }
                        $node['Description'] = $node['Name'];
                        $node['Name'] = $shift;
                    }

                    $node = preg_replace('/\s+/', ' ', $node);
                    if ($node) {
                        $arr[] = $node;
                    }
                }
            }
        }

        array_shift($arr);
        array_pop($arr);

        foreach ($arr as $ar) {
            dump($ar);
        }

        die();

    }

    /**
     * @param array $delimiters
     * @param string $string
     * @return array
     */
    protected function multiexplode($delimiters,$string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }

    /**
     * @param string $url
     * @return bool|string
     */
    private function disguise_curl($url)
    {
        $curl = curl_init();

        $proxy = '217.146.219.118';
        $port = '39331';
        $user_agent = 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';

        $header[] = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $header[] = 'accept-encoding: gzip, deflate';
        $header[] = 'accept-language: en-US,en;q=0.9';
        $header[] = 'cache-control: max-age=0';
        $header[] = 'connection: keep-alive';
        $header[] = 'upgrade-insecure-requests: 1';
        $header[] = 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        curl_setopt($curl, CURLOPT_ENCODING, "UTF-8" );
        curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
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

        return $html;
    }

    /**
     * @param array $data
     * @return null
     */
    private function save($data)
    {
            $company = new Company();
            $company->setName($data['Name']);
            $company->setLocation($data['Location']);
            $company->setMarket($data['Market']);
            $company->setWebsite($data['Website']);
            $company->setEmployees($data['Employees']);
            $company->setStage($data['Stage']);
            $company->setRaised((int)$data['Total Raised']);

            $this->em->persist($company);
            $this->em->flush();

            return null;
    }
}
