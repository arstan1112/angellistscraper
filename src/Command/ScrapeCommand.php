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
    const MAX_PAGE_NUMBER = 3;

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
     * @var string
     */
    private $urlKeys;

    /**
     * @var string
     */
    private $urlValues;

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
        $this->encoder = new JsonEncoder();
        $this->serializer = new Serializer([$this->normalizer], [$this->encoder]);

        $this->header[] = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $this->header[] = 'accept-encoding: gzip, deflate';
        $this->header[] = 'accept-language: en-US,en;q=0.9';
        $this->header[] = 'cache-control: max-age=0';
        $this->header[] = 'connection: keep-alive';
        $this->header[] = 'upgrade-insecure-requests: 1';
        $this->header[] = 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';

        $this->urlKeys = 'http://www.angellist.loc/api/keys';
        $this->urlValues = 'http://www.angellist.loc/api/values';
    }

    /**
     * @param null
     */
    protected function configure()
    {
        $this
            ->setDescription('Scrape angel.co. First argument - proxy address, second argument - proxy port number')
            ->addArgument('proxy', InputArgument::OPTIONAL, 'Proxy address')
            ->addArgument('port', InputArgument::OPTIONAL, 'Proxy port');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $proxy = $input->getArgument('proxy');
        $port = $input->getArgument('port');
        $parametersForKeys = [
            'sort' => 'signal',
        ];
        $filterData = [
            'filter_data[markets]' => ['E-Commerce', 'Enterprise Software', 'Education', 'Games', 'Healthcare', 'Mobile'],
            'filter_data[company_types]' => ['Startup', 'Private Company', 'New Company', 'Same new Company'],
            'filter_data[locations]' => ['1688-United States', '1681-Silicon Valley', '2071-New York'],
            'filter_data[in_done_deals]' => 'Done deals',
        ];

        $counterForCommandProgress = 0;

        foreach ($filterData as $key => $filters) {
            if (is_array($filters)) {
                foreach ($filters as $index => $filter) {
                    $counterForCommandProgress++;

                    $parametersForKeys[$key][0] = $filter;
                    unset($filters[$index]);
                    $filters = array_values($filters);
                    $filterData[$key] = $filters;
                    $this->loopFilters($io, $filterData, $parametersForKeys, $proxy, $port);
                    unset($parametersForKeys[$key]);
                    $filters[$index] = $filter;
                    $filterData[$key] = $filters;

                    $io->note('Command completed for ' . $counterForCommandProgress . ' set of filters');
                }
            } else {
                $counterForCommandProgress++;

                $parametersForKeys[$key] = $filters;
                unset($filterData[$key]);
                $this->loopFilters($io, $filterData, $parametersForKeys, $proxy, $port);
                $filterData[$key] = $filters;

                $io->note('Command completed for ' . $counterForCommandProgress . ' set of filters');
            }
        }

        $io->success('Command Scrape successfully finished');

        return 0;
    }

    /**
     * @param object       $io
     * @param array|string $filterData
     * @param array|string $parametersForKeys
     * @param null|string  $proxy
     * @param null|string  $port
     */
    private function loopFilters($io, $filterData, $parametersForKeys, $proxy = null, $port = null)
    {
        foreach ($filterData as $key => $filters) {
            if (is_array($filters)) {
                foreach ($filters as $index => $filter) {
                    $parametersForKeys[$key][] = $filter;
                    $this->getExecution($io, $parametersForKeys, $proxy, $port);
                    array_pop($parametersForKeys[$key]);
                }
            } else {
                $parametersForKeys[$key] = $filters;
                $this->getExecution($io, $parametersForKeys, $proxy, $port);
            }
        }
    }

    /**
     * @param object $io
     * @param array|string $parametersForKeys
     * @param null|string $proxy
     * @param null|string $port
     */
    private function getExecution($io, $parametersForKeys, $proxy = null, $port = null)
    {
        $pageNumber = 1;
        do {
            $io->progressStart(20);

            $values = $this->getPageValues($io, $parametersForKeys, $proxy, $port);

            if ($values == null) {
                break;
            }

            $parametersForKeys = $values[0];
            $page = $this->serializer->decode($values[1], 'json');
            isset($page['html']) ? $html = $page['html'] : $io->error('[Line ' . __LINE__ . '] Command failed while getting values: page not found or wrong parameters sent');

            try {
                $arr = $this->parse($html);
            } catch (\Throwable $exception) {
                $io->error('[Line ' . __LINE__ . '] Command failed while parsing with exception: ' . $exception->getMessage() . '. (Parameter keys: page-' . $parametersForKeys['page'] . ', sort-' . $parametersForKeys['sort'] . ')');
                exit();
            }
            foreach ($arr as $ar) {
                try {
                    $this->save($ar);
                } catch (\Throwable $exception) {
                    $io->error('[Line ' . __LINE__ . '] Command failed while saving with exception: ' . $exception->getMessage() . '. (Parameter keys: page-' . $parametersForKeys['page'] . ', sort-' . $parametersForKeys['sort'] . ')');
                    exit();
                }
            }
            $pageNumber++;
            $io->progressFinish();

        } while ($pageNumber < self::MAX_PAGE_NUMBER);
    }

    /**
     * @param object       $io
     * @param array|string $parametersForKeys
     * @param null|string  $proxy
     * @param null|string  $port
     * @return array
     */
    private function getPageValues($io, $parametersForKeys, $proxy = null, $port = null)
    {
        try {
            $queryType = 'getKey';
            $keys = $this->curlInit($this->urlKeys, $this->fixQueryString(http_build_query($parametersForKeys)),
                $queryType, $proxy, $port);
        } catch (\Throwable $exception) {
            $io->error('[Line ' . __LINE__ . ']Command failed while getting keys with exception: ' . $exception->getMessage() . '. (Parameter keys: page-' . $parametersForKeys['page'] . ', sort-' . $parametersForKeys['sort'] . ')');
            exit();
        }
        $decoded = json_decode($keys);
        if (($decoded->total) == 0) {
            return null;
        } elseif (isset($decoded->hexdigest) && isset($decoded->page)) {
            $parametersForKeys['page'] = ($decoded->page + 1);
        } else {
            $io->error('[Line ' . __LINE__ . '] Command failed while getting keys: wrong parameters sent or hexdigest key was not found in the response' . '. (Parameter keys: page-' . $parametersForKeys['page'] . ', sort-' . $parametersForKeys['sort'] . ')');
            exit();
        }

        $queryParametersForValues = http_build_query($decoded);
        $fullUrlValues = $this->urlValues . '?' . $queryParametersForValues;
        try {
            $queryType = 'getVal';
            $values = $this->curlInit($fullUrlValues, null, $queryType, $proxy, $port);
        } catch (\Throwable $exception) {
            $io->error('[Line ' . __LINE__ . '] Command failed while getting values with exception: ' . $exception->getMessage() . '. (Parameter keys: page-' . $parametersForKeys['page'] . ', sort-' . $parametersForKeys['sort'] . ')');
            exit();
        }

        $arrValuesAndParameters = [];
        $arrValuesAndParameters[] = $parametersForKeys;
        $arrValuesAndParameters[] = $values;

        return $arrValuesAndParameters;
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
    private function curlInit($url, $parameters = null, $type = null, $proxy = null, $port = null)
    {
        $curl = curl_init();

        if (isset($proxy) && isset($port)) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            curl_setopt($curl, CURLOPT_PROXYPORT, $port);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        curl_setopt($curl, CURLOPT_ENCODING, "UTF-8");
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        if ($type == 'getKey') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        $html = curl_exec($curl);
        if (!$html) {
            throw new \ErrorException('Error number: ' . curl_errno($curl) . ' Error message: ' . curl_error($curl));
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
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@class="base startup"]');

        $arr = [];
        $counter = 0;
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $nodes = $element->childNodes;
                $arrBaseStartup = [];
                foreach ($nodes as $node) {
                    $nodeVal = $node->nodeValue;
                    $nodeVal = preg_replace("/[^A-Za-z0-9@.\- ]/", '', $nodeVal);
                    $arrBaseStartup[] = $nodeVal;
                }
                $nodeImploded = implode('', $arrBaseStartup);
                $delimiters = [
                    'Signal',
                    'Joined',
                    'Location',
                    'Market',
                    'Website',
                    'Stage',
                    'Employees',
                    'Total Raised'
                ];
                $node = $this->multiexplode($delimiters, $nodeImploded);

                $keys = [
                    'Name',
                    'Signal',
                    'Joined',
                    'Location',
                    'Market',
                    'Website',
                    'Employees',
                    'Stage',
                    'Total Raised'
                ];

                if (count($node) == 9) {
                    $node = array_combine($keys, $node);
                    $name = $xpath->query('//*[@class="name"]');
                    $description = $xpath->query('//*[@class="pitch"]');
                    $node['Name'] = $this->getNodeValue($name, $counter);
                    $node['Description'] = $this->getNodeValue($description, $counter);
                    if ($node) {
                        $arr[] = $node;
                    }

                } elseif (count($node) !== 9) {
                    $arr = $this->parseWithOnlyXpath($html, $arr, $counter);
                }
                $counter++;
            }
        }

        if (count($arr) > 20) {
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
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $name = $xpath->query('//*[@class="name"]');
        $description = $xpath->query('//*[@class="pitch"]');
        $joined = $xpath->query('//*[@data-column="joined"]');
        $location = $xpath->query('//*[@data-column="location"]');
        $market = $xpath->query('//*[@data-column="market"]');
        $website = $xpath->query('//*[@data-column="website"]');
        $employees = $xpath->query('//*[@data-column="company_size"]');
        $stage = $xpath->query('//*[@data-column="stage"]');
        $raised = $xpath->query('//*[@data-column="raised"]');

        $nodeXpath['Name'] = $this->getNodeValue($name, $counter);
        $nodeXpath['Description'] = $this->getNodeValue($description, $counter);
        $nodeXpath['Joined'] = $this->getNodeValue($joined, $counter);
        $nodeXpath['Location'] = $this->getNodeValue($location, $counter);
        $nodeXpath['Market'] = $this->getNodeValue($market, $counter);
        $nodeXpath['Website'] = $this->getNodeValue($website, $counter);
        $nodeXpath['Employees'] = $this->getNodeValue($employees, $counter);
        $nodeXpath['Stage'] = $this->getNodeValue($stage, $counter);
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
     * @param int $counter
     * @return mixed
     */
    private function getNodeValue($node, $counter)
    {
        $value = [];
        foreach ($node[$counter]->childNodes as $child) {
            $val = str_replace(array("\n", "\r"), '', $child->nodeValue);
            if ($val) {
                $value[] = $val;
            }
        }
        if (count($value) > 1) {
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
    private final function multiexplode($delimiters, $string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);

        return $launch;
    }

    /**
     * @param string $query
     * @return string|string[]|null
     */
    private function fixQueryString(string $query)
    {
        return preg_replace('/%5B([^[a-zA-Z]|%5D]*)%5D/', '%5B%5D', $query);
    }

}
