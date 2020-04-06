<?php

namespace App\Command;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Nullable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ScrapeCommand extends Command
{
    protected static $defaultName = 'scrape';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ObjectNormalizer
     */
    private $normalizer;

    /**
     * @var JsonEncoder
     */
    private $encoder;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $header;

    /**
     * ScrapeCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);

        $this->em = $entityManager;
        $this->normalizer = new ObjectNormalizer();
        $this->encoder    = new JsonEncoder();
        $this->serializer = new Serializer([$this->normalizer], [$this->encoder]);

        $this->header[] = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $this->header[] = 'accept-encoding: gzip, deflate';
        $this->header[] = 'accept-language: en-US,en;q=0.9';
        $this->header[] = 'cache-control: max-age=0';
        $this->header[] = 'connection: keep-alive';
        $this->header[] = 'upgrade-insecure-requests: 1';
        $this->header[] = 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';
    }

    /**
     * @param null
     */
    protected function configure()
    {
        $this
            ->setDescription('Scrape angel.co. First argument - proxy address, second argument - proxy port number')
            ->addArgument('proxy', InputArgument::OPTIONAL, 'Proxy address')
            ->addArgument('port', InputArgument::OPTIONAL, 'Proxy port')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $url_keys   = 'http://www.angellist.loc/api/keys';
        $url_values = 'http://www.angellist.loc/api/values';
        $parameters_for_keys = 'sort=signal&page=2';
        $response_counter = 0;

        do {
            $values = $this->getPageValues($io, $url_keys, $url_values, $parameters_for_keys, $input->getArgument('proxy'), $input->getArgument('port'));

            $parameters_for_keys = $values[0];

            $page = $this->serializer->decode($values[1], 'json');
            isset($page['html']) ? $html = $page['html'] : $io->error('[Line'.__LINE__.'] Command failed while getting values: page not found or wrong parameters sent');

            try {
                $arr = $this->parse($html);
            } catch (\Throwable $exception) {
                $io->error('[Line'.__LINE__.'] Command failed while parsing with exception: ' .$exception->getMessage() . '. (Parameter keys: '.$parameters_for_keys .')');
                exit();
            }
            foreach ($arr as $ar) {
                try {
                    $this->save($ar);
                } catch (\Throwable $exception) {
                    $io->error('[Line'.__LINE__.'] Command failed while saving with exception: ' . $exception->getMessage(). '. (Parameter keys: '.$parameters_for_keys .')');
                    exit();
                }
            }

            $response_counter++;

        } while ($response_counter<3);

        $io->success('Command Scrape successfully finished');

        return 0;
    }

    /**
     * @param object $io
     * @param string $url_keys
     * @param string $url_values
     * @param string $parameters_for_keys
     * @param null|string $proxy
     * @param null|string $port
     * @return array
     */
    private function getPageValues($io, $url_keys, $url_values, $parameters_for_keys, $proxy=null, $port=null)
    {
        try {
            $query_type = 'getKey';
            $keys = $this->curlInit($url_keys, $parameters_for_keys, $query_type, $proxy, $port);
        } catch (\Throwable $exception) {
            $io->error('[Line'.__LINE__.']Command failed while getting keys with exception: ' .$exception->getMessage() . '. (Parameter keys: '.$parameters_for_keys .')');
            exit();
        }

        $decoded = json_decode($keys);
        if (isset($decoded->hexdigest)) {
            $parameters_for_keys = 'sort='.$decoded->sort.'&page='.$decoded->page;
        } else {
            $io->error('[Line'.__LINE__.'] Command failed while getting key: wrong parameters sent or hexdigest key was not found in the response' . '. (Parameter keys: '.$parameters_for_keys .')');
            exit();
        }

        $parameters_for_values = $keys;
        try {
            $query_type = 'getVal';
            $page = $this->curlInit($url_values, $parameters_for_values, $query_type, $proxy, $port);
        } catch (\Throwable $exception) {
            $io->error('[Line'.__LINE__.'] Command failed while getting values with exception: ' .$exception->getMessage() . '. (Parameter keys: '.$parameters_for_keys .')');
            exit();
        }

        $arr_values_and_parameters = [];
        $arr_values_and_parameters[] = $parameters_for_keys;
        $arr_values_and_parameters[] = $page;

        return $arr_values_and_parameters;
    }

    /**
     * @param string       $url
     * @param null|string  $type
     * @param array|string $parameters
     * @param null|string  $proxy
     * @param null|string  $port
     * @return bool|string
     * @throws \ErrorException
     */
    private function curlInit($url, $parameters=null, $type=null, $proxy=null, $port=null)
    {
        $curl = curl_init();

        if (isset($proxy) && isset($port)) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            curl_setopt($curl, CURLOPT_PROXYPORT, $port);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        curl_setopt($curl, CURLOPT_ENCODING, "UTF-8" );
        curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        if ($type=='getVal') {
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($curl, CURLOPT_URL, $url);

        $html = curl_exec($curl);
        if (!$html)
        {
            throw new \ErrorException('Error number: ' .curl_errno($curl). ' Error message: ' .curl_error($curl));
        }
        curl_close($curl);

        return $html;
    }

    /**
     * @param string $html
     * @return array
     */
    private function parse($html)
    {
        $dom   = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@class="base startup"]');

        $arr = [];
        $counter = 0;
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $nodes = $element->childNodes;
                $arr_base_startup = [];
                foreach ($nodes as $node) {
                    $node_val = $node->nodeValue;
                    $node_val = preg_replace("/[^A-Za-z0-9@.\- ]/", '', $node_val);
                    $arr_base_startup[] = $node_val;
                }
                $node_imploded = implode('', $arr_base_startup);
                $delimiters = ['Signal', 'Joined', 'Location', 'Market', 'Website', 'Stage', 'Employees', 'Total Raised'];
                $node = $this->multiexplode($delimiters, $node_imploded);

                $keys = ['Name', 'Signal', 'Joined', 'Location', 'Market', 'Website', 'Employees', 'Stage', 'Total Raised'];

                if (count($node)==9) {
                    $node = array_combine($keys, $node);
                    $name        = $xpath->query('//*[@class="name"]');
                    $description = $xpath->query('//*[@class="pitch"]');
                    $node['Name']        = $this->getNodeValue($name, $counter);
                    $node['Description'] = $this->getNodeValue($description, $counter);
                    if ($node) {
                        $arr[] = $node;
                    }

                } elseif (count($node)!==9) {
                    $arr = $this->parseWithOnlyXpath($html, $arr, $counter);
                }
                $counter++;
            }
        }

        if (count($arr)>20) {
            array_shift($arr);
            array_pop($arr);
        }

        return $arr;
    }

    /**
     * @param string $html
     * @param array  $arr
     * @param int    $counter
     * @return array
     */
    private function parseWithOnlyXpath($html, $arr, $counter)
    {
        $dom   = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $name        = $xpath->query('//*[@class="name"]');
        $description = $xpath->query('//*[@class="pitch"]');
        $joined      = $xpath->query('//*[@data-column="joined"]');
        $location    = $xpath->query('//*[@data-column="location"]');
        $market      = $xpath->query('//*[@data-column="market"]');
        $website     = $xpath->query('//*[@data-column="website"]');
        $employees   = $xpath->query('//*[@data-column="company_size"]');
        $stage       = $xpath->query('//*[@data-column="stage"]');
        $raised      = $xpath->query('//*[@data-column="raised"]');

        $nodeXpath['Name']         = $this->getNodeValue($name, $counter);
        $nodeXpath['Description']  = $this->getNodeValue($description, $counter);
        $nodeXpath['Joined']       = $this->getNodeValue($joined, $counter);
        $nodeXpath['Location']     = $this->getNodeValue($location, $counter);
        $nodeXpath['Market']       = $this->getNodeValue($market, $counter);
        $nodeXpath['Website']      = $this->getNodeValue($website, $counter);
        $nodeXpath['Employees']    = $this->getNodeValue($employees, $counter);
        $nodeXpath['Stage']        = $this->getNodeValue($stage, $counter);
        $nodeXpath['Total Raised'] = $this->getNodeValue($raised, $counter);

        if ($nodeXpath) {
            $arr[] = $nodeXpath;
        }

        return $arr;
    }

    /**
     * @param array $data
     * @return null |null
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

    /**
     * @param object $node
     * @param int    $counter
     * @return mixed
     */
    private function getNodeValue($node, $counter)
    {
        $value = [];
        foreach ($node[$counter]->childNodes as $child) {
            $val = str_replace(array("\n","\r"), '', $child->nodeValue);
            if ($val) {
                $value[] = $val;
            }
        }
        if (count($value)>1) {
            return $value[1];
        }
        if ($value) {
            return $value[0];
        }
    }

    /**
     * @param array $delimiters
     * @param string $string
     * @return array
     */
    private final function multiexplode($delimiters,$string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);

        return  $launch;
    }

}
