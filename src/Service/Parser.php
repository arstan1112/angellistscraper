<?php


namespace App\Service;


use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Masterminds\HTML5;
use Symfony\Component\HttpKernel\KernelInterface;

class Parser
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Parser constructor.
     * @param KernelInterface $kernel
     * @param EntityManagerInterface $em
     */
//    public function __construct(KernelInterface $kernel, EntityManagerInterface $em, LoggerInterface $angelLogger)
    public function __construct(KernelInterface $kernel, EntityManagerInterface $em)
    {
        $this->kernel = $kernel;
        $this->em = $em;
//        $this->angelLogger = $angelLogger;
    }

    /**
     * @param string $location
     * @return null
     */
    public function execute(string $location)
//    public function execute()
    {
//        $this->angelLogger->info('Parser Service has been initiated');

        $path = $this->kernel->getContainer()->getParameter('save_path');
        $name = 'angellist_' . strtolower($location) . '.html';
//        $name = 'angellist.html';

        $htmlFile = $path . '/' . $name;
        $html = file_get_contents($htmlFile);

        $html5 = new HTML5();
        $dom = $html5->loadHTML($html);

        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@class="base startup"]');

        $arr = [];
        $counter = 0;
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $arr = $this->parse($xpath, $arr, $counter);
                $counter++;
            }
        }

        foreach ($arr as $ar) {
            try {
                $this->save($ar);
            } catch (\Throwable $exception) {
//                $io->error('[Line ' . __LINE__ . '] Command failed while saving with exception: ' . $exception->getMessage());
                exit();
            }
        }

//        $this->angelLogger->info('Parser Service executed successfully');

        return null;
    }

    /**
     * @param object $xpath
     * @param array $arr
     * @param int $counter
     * @return array
     */
    protected function parse(object $xpath, array $arr, int $counter)
    {
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
        $counter = $counter + 1;
        $nodeXpath['Joined'] = $this->getNodeValue($joined, $counter);
        $nodeXpath['Location'] = $this->getNodeValue($location, $counter);
        $nodeXpath['Market'] = $this->getNodeValue($market, $counter);
        $nodeXpath['Website'] = $this->getNodeValue($website, $counter);
        $nodeXpath['Employees'] = $this->getNodeValue($employees, $counter);
        $nodeXpath['Stage'] = $this->getNodeValue($stage, $counter);
        $nodeXpath['Total Raised'] = $this->getNodeValue($raised, $counter);
        $nodeXpath['Total Raised'] = preg_replace("/[^0-9]/", '', $nodeXpath['Total Raised']);;

        if ($nodeXpath) {
            $arr[] = $nodeXpath;
        }

        return $arr;
    }

    /**
     * @param object $node
     * @param int $counter
     * @return mixed|string|string[]
     */
    protected function getNodeValue(object $node, int $counter)
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
     * @param array $data
     * @return null
     */
    protected function save(array $data)
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