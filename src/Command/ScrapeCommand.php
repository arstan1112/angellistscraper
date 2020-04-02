<?php

namespace App\Command;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
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
     * ScrapeCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    /**
     * @param null
     */
    protected function configure()
    {
        $this
            ->setDescription('Scrape angel.co')
            ->addArgument('proxy', InputArgument::OPTIONAL, 'Proxy address')
            ->addArgument('port', InputArgument::OPTIONAL, 'Proxy port')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $url_keys = 'http://www.angellist.loc/api/keys';
        $url_values = 'http://www.angellist.loc/api/values';

        try {
            $page = $this->disguise_curl($url_values, $input->getArgument('proxy'), $input->getArgument('port'));
        } catch (\Throwable $exception) {
            $io->error('Command while connection failed with exception: ' .$exception->getMessage());
        }

        $normalizer = new ObjectNormalizer();
        $encoder = new JsonEncoder();
        $serializer = new Serializer([$normalizer], [$encoder]);
        $page = $serializer->decode($page, 'json');
        $html = $page['html'];

        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@class="base startup"]');

        $arr = [];
        $normal = [];
        $anomaly = [];
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
                    $normal[] = $node;
                    $node = array_combine($keys, $node);
                    $name = $xpath->query('//*[@class="name"]');
                    $description = $xpath->query('//*[@class="pitch"]');
                    $node['Name'] = $this->getNodeValue($name, $counter);
                    $node['Description'] = $this->getNodeValue($description, $counter);
                    if ($node) {
                        $arr[] = $node;
                    }

                } elseif (count($node)!==9) {
                    $anomaly[] = $node;
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
                }
                $counter++;
            }
        }

        if (count($arr)>20) {
            array_shift($arr);
            array_pop($arr);
        }

        foreach ($arr as $ar) {
            try {
                $this->save($ar);
            } catch (\Throwable $exception) {
                $io->error('Command while saving failed with exception: ' . $exception->getMessage());
            }
        }
        dump($input->getArgument('proxy'));
        dump($input->getArgument('port'));


        $io->success('Command Scrape successfully finished');

        return 0;
    }

    /**
     * @param string $url
     * @param null|string $proxy
     * @param null|string $port
     * @return bool|string
     */
    private function disguise_curl($url, $proxy=null, $port=null)
    {
        $curl = curl_init();

        $header[] = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $header[] = 'accept-encoding: gzip, deflate';
        $header[] = 'accept-language: en-US,en;q=0.9';
        $header[] = 'cache-control: max-age=0';
        $header[] = 'connection: keep-alive';
        $header[] = 'upgrade-insecure-requests: 1';
        $header[] = 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';

        if (isset($proxy)) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            curl_setopt($curl, CURLOPT_PROXYPORT, $port);
        }
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

    /**
     * @param object $node
     * @param int $counter
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
}
