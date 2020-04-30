<?php


namespace App\Context;


use App\Entity\Location;
use App\Entity\Market;
use App\Repository\CompanyRepository;
use App\Repository\LocationRepository;
use App\Repository\MarketRepository;
use Behat\Behat\Context\Context;
use App\Entity\Company;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;
use Doctrine\ORM\EntityManagerInterface;
use Masterminds\HTML5;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AngelContext implements Context
{
    const MAX_NUMBER_COMPANIES_PER_REQUEST = 2000;

    const MAX_PAGES = 2;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var LoggerInterface
     */
    private $angelLogger;
    /**
     * @var CompanyRepository
     */
    private $companyRepository;
    /**
     * @var LocationRepository
     */
    private $locationRepository;
    /**
     * @var MarketRepository
     */
    private $marketRepository;

    /**
     * AngelContext constructor.
     * @param Session $session
     * @param KernelInterface $kernel
     * @param EntityManagerInterface $em
     * @param LoggerInterface $angelLogger
     * @param CompanyRepository $companyRepository
     * @param LocationRepository $locationRepository
     * @param MarketRepository $marketRepository
     */
    public function __construct(
        Session $session,
        KernelInterface $kernel,
        EntityManagerInterface $em,
        LoggerInterface $angelLogger,
        CompanyRepository $companyRepository,
        LocationRepository $locationRepository,
        MarketRepository $marketRepository)
    {
        $this->session = $session;
        $this->kernel = $kernel;
        $this->em = $em;
        $this->angelLogger = $angelLogger;
        $this->companyRepository = $companyRepository;
        $this->locationRepository = $locationRepository;
        $this->marketRepository = $marketRepository;
    }

    /**
     * @When /^I am on the main page/
     */
    public function iAmOnTheMainPage()
    {
        $this->session->visit('https://angel.co/companies');
        $this->angelLogger->info('Start the process');

        $statusCode = $this->session->getStatusCode();
        $this->angelLogger->info('Status code: ' .$statusCode);

        $this->startExecution();
    }

    protected function restartSession()
    {
        $this->session->restart();
        $this->session->visit('https://angel.co/companies');
        $this->restartExecution();
    }

    private function startExecution()
    {
        $this->loopPagesParseSave();
        $this->loopLocations();
        $this->restartExecution();
    }

    protected function restartExecution()
    {
        do {
            $totalCompanies = $this->countTotalCompanies();
            if (!$totalCompanies) {
                break;
            }
            $totalCompaniesInDb = $this->companyRepository->findAll();
            $this->loopLocations();
            $this->angelLogger->info('Total companies checked in DB,   total: ' . count($totalCompaniesInDb));
            $this->angelLogger->info('Total companies checked in site, total: ' . $totalCompanies);
        } while (100 > count($totalCompaniesInDb));
    }

    protected function loopLocations()
    {
        $locations = $this->locationRepository->findAllUnchecked();
        shuffle($locations);
        $counter = 1;
        if (!empty($locations)) {
            foreach ($locations as $location) {
                if ($this->setFilterName('locations', 'location', $location->getName())) {
                    if ($this->scrapeWithLocations()) {
                        try {
                            $location->setStatus('checked');
                            $this->em->flush();
                        } catch (\Exception $exception) {
                            $this->angelLogger->error('[Line '.__LINE__.'] Error occurred while setting status to location: ' .$location->getName());
                        }

                    } else {
                        if ($this->removeAllFilters()) {
                            break;
                        } else {
                            $this->restartSession();
                        }
                    }
                }
                $counter++;
                if ($counter==3) {
                    break;
                }
            }
        }
    }

    protected function loopMarkets()
    {
        $markets = $this->marketRepository->findAllUnchecked();
        shuffle($markets);
        $counter = 1;
        if (!empty($markets)) {
            foreach ($markets as $market) {
                if ($this->setFilterName('markets', 'market', $market->getName())) {
                    if ($this->scrapeWithMarkets()) {
                        $market->setStatus('checked');
                        $this->em->flush();
                    } else {
                        if ($this->removeFilter('markets')) {
                            break;
                        } else {
                            $this->restartSession();
                        }
                    }
                }
                $counter++;
                if ($counter==3) {
                    break;
                }
            }
        }
    }

    protected function loopTypes()
    {
        $types = ['Startup', 'VC Firm', 'Private Company', 'SaaS', 'Incubator', 'Mobile App'];
        foreach ($types as $type) {
            if ($this->selectFilter('company_types', $type)) {
                $totalCompanies = $this->countTotalCompanies();
                if (!$totalCompanies) {
                    break;
                }
                if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
                    $this->loopPagesParseSave();
                    $this->loopTechs();
                } elseif ($totalCompanies !== 0) {
                    $this->loopPagesParseSave();
                }
                $this->removeFilter('company_types');
            }
        }
    }

    protected function loopTechs()
    {
        $techs = ['Javascript', 'Python', 'HTML5', 'CSS', 'Java'];
        foreach ($techs as $tech) {
            if ($this->selectFilter('teches', $tech)) {
                $totalCompanies = $this->countTotalCompanies();
                if (!$totalCompanies) {
                    break;
                }
                if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
                    $this->loopPagesParseSave();
                    $this->loopStages();
                } elseif ($totalCompanies !== 0) {
                    $this->loopPagesParseSave();
                }
                $this->removeFilter('teches');
            }
        }
    }

    protected function loopStages()
    {
        $stages = ['Seed', 'Series A', 'Series B', 'Series C', 'Acquired'];
        foreach ($stages as $stage) {
            if ($this->selectFilter('stages', $stage)) {
                $this->loopPagesParseSave();
                $this->removeFilter('stage');
            }
        }
    }

    protected function scrapeWithLocations()
    {
        if ($this->clickDropDown('1')) {
            $totalCompanies = $this->countTotalCompanies();
            if (!$totalCompanies) {
                return false;
            }
            if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
                if ($this->loopPagesParseSave()) {
                    $this->loopMarkets();
                }
            } elseif ($totalCompanies !== 0) {
                $this->loopPagesParseSave();
            }

            if ($this->removeAllFilters()) {
                return true;
            }

            return false;
        }

        return false;
    }

    protected function clickDropDown(string $nodeId)
    {
        try {
            $this->session->getDriver()->wait(4000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->click('//*[@id="ui-id-'.$nodeId.'"]/li[1]');
            $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
            return true;
        } catch (\Exception $exception) {
            try {
                $nodeId = ((int)$nodeId+2);
                $nodeId = strval($nodeId);
                $this->session->getDriver()->wait(4000, "document.getElementsByClassName('more')");
                $this->session->getDriver()->click('//*[@id="ui-id-'.$nodeId.'"]/li[1]');
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
                return true;
            } catch (\Exception $exception) {
                $this->angelLogger->error('[Line '.__LINE__.'] Could not click drop down for locations with exception: ' .$exception->getMessage());
                return false;
            }
        }
    }

    protected function scrapeWithMarkets()
    {
        if ($this->clickDropDown('2')) {
            $totalCompanies = $this->countTotalCompanies();
            if (!$totalCompanies) {
                return false;
            }
            if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
                if ($this->loopPagesParseSave()) {
                    $this->loopTypes();
                }
            } elseif ($totalCompanies !== 0) {
                $this->loopPagesParseSave();
            }
            if ($this->removeFilter('markets')) {
                return true;
            }
            return false;
        }

        return false;
    }

    protected function selectFilter(string $dataAttrMenu, string $dataAttrValue)
    {
        try {
            $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->mouseOver('//*[@data-menu="' . $dataAttrMenu . '"]');
            $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->click('//*[@data-value="' . $dataAttrValue . '"]');
            $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");
            return true;
        } catch (\Exception $exception) {
            $this->angelLogger->error('[Line '.__LINE__.'] Could not select filter with exception ' . $exception->getMessage());
            return false;
        }
    }

    protected function removeAllFilters()
    {
        try {
            $this->session->getDriver()->wait(6000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->click('//*[@class="clear_all_link hidden"]');
            $this->session->getDriver()->wait(4000, "document.getElementsByClassName('more')");
            return true;
        } catch (\Exception $exception) {
            $this->angelLogger->error('[Line '.__LINE__.'] Could not remove all filters with exception: ' .$exception->getMessage());
        }
        return false;
    }

    protected function removeFilter(string $dataKeyName)
    {
        try {
            $this->session->getDriver()->wait(6000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->click('//*[@data-key="' . $dataKeyName . '"]');
            $this->session->getDriver()->wait(4000, "document.getElementsByClassName('more')");
            return true;
        } catch (\Exception $exception) {
            $this->angelLogger->error('[Line ' . __LINE__ . '] Could not remove filter with exception: ' . $exception->getMessage());
        }

        return false;
    }

    protected function setFilterName(string $dataAttr, string $idName, string $filterName)
    {
        try {
            $this->session->getDriver()->wait(3000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->mouseOver('//*[@data-menu="' . $dataAttr . '"]');
            $this->session->getDriver()->wait(3000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->setValue('//*[@id="' . $idName . '"]', $filterName);
            $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");
            return true;
        } catch (\Exception $exception) {
            $this->angelLogger->error('[Line '.__LINE__.'] Mouseover or set value in input did not work with exception: ' . $exception->getMessage());
        }

        return false;
    }

    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

    protected function countTotalCompanies()
    {
        try {
            $total = $this->session->getDriver()->find('//*[@class="count"]');
            $totalCompanies = preg_replace("/[^0-9]/", '', $total[1]->getText());
            $totalCompanies = (int)$totalCompanies;
            return $totalCompanies;
        } catch (\Exception $exception) {
            $this->angelLogger->error('[Line '.__LINE__.'] Could not find total company with exception: ' . $exception->getMessage());
            return false;
        }
    }

    protected function loopPagesParseSave()
    {
        $this->loopPages();
        $this->putContentsToFile();
        $arrParsedElements = $this->parseAllElements($this->getXpath());

        if (!empty($arrParsedElements)) {
            foreach ($arrParsedElements as $ar) {

                try {
                    $this->saveLocation($ar['Location']);
                    $this->saveMarket($ar['Market']);
                    $this->checkAndSaveCompany($ar);
                } catch (\Throwable $exception) {
                    $this->angelLogger->error('[Line '.__LINE__.'] Error occurred while saving with exception: ' . $exception->getMessage() . ' on ');
                    break;
                }
            }
            return true;
        }

        return false;
    }

    protected function loopPages()
    {
        for ($page = 1; $page < self::MAX_PAGES; $page++) {
            try {
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
                $this->session->getDriver()->click('//*[@class="more"]');
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
            } catch (\Exception $exception) {
                break;
            }
        }
    }

    protected function putContentsToFile()
    {
        $path = $this->getContainer()->getParameter('save_path');
        $name = 'angellist.html';

        file_put_contents($path . '/' . $name, '<html>' . $this->session->getPage()->getContent() . '</html>');
    }

    protected function getXpath()
    {
        $path = $this->getContainer()->getParameter('save_path');
        $name = 'angellist.html';

        $htmlFile = $path . '/' . $name;
        $html = file_get_contents($htmlFile);

        $html5 = new HTML5();
        $dom = $html5->loadHTML($html);

        return new \DOMXPath($dom);
    }

    protected function parseAllElements(object $xpath)
    {
        $elements = $xpath->query('//*[@class="base startup"]');
        $arr = [];
        $counter = 0;
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $arr = $this->parse($xpath, $arr, $counter);
                $counter++;
            }
        }

        return $arr;
    }

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
        return 'nodeEmpty';
    }

    protected function saveLocation(string $locationName)
    {
        if ($locationName) {
            $locationName = trim($locationName);
            if ($locationName !== '-' && $locationName !== '') {
                $locationInDb = $this->locationRepository->findByName($locationName);
                if (!$locationInDb) {
                    $location = new Location();
                    $location->setName($locationName);
                    $location->setStatus('unchecked');

                    if (!$this->em->isOpen()) {
                        $this->em->getConnection();
                        $this->em->getConfiguration();
                    }
                    $this->em->persist($location);
                    $this->em->flush();
                }
            }
        }
    }

    protected function saveMarket(string $marketName)
    {
        if ($marketName) {
            $marketName = trim($marketName);
            if ($marketName !== '-' && $marketName !== '') {
                $marketInDb = $this->marketRepository->findByName($marketName);
                if (!$marketInDb) {
                    $market = new Market();
                    $market->setName($marketName);
                    $market->setStatus('unchecked');

                    if (!$this->em->isOpen()) {
                        $this->em->getConnection();
                        $this->em->getConfiguration();
                    }
                    $this->em->persist($market);
                    $this->em->flush();
                }
            }
        }
    }

    protected function checkAndSaveCompany(array $data)
    {
        if ($data['Name']) {
            if ($data['Website']) {
                $website = preg_replace("/[^A-Za-z0-9\-.]/", '', $data['Website']);
                if ($website == '' || $website == '-' || $website == 'Website' || $website == 'website') {
                    $this->checkForCompanyNameAndSave($data);
                } else {
                    $companyWebsiteInDb = $this->companyRepository->findByWebsite($website);
                    if (!$companyWebsiteInDb) {
                        $this->saveCompany($data);
                    }
                }
            } else {
                $this->checkForCompanyNameAndSave($data);
            }
        }
    }

    protected function checkForCompanyNameAndSave(array $data)
    {
        $companyNameInDb = $this->companyRepository->findByName($data['Name']);
        if (!$companyNameInDb) {
            $this->saveCompany($data);
        } elseif ($companyNameInDb) {
            $companyLocation = $companyNameInDb[0]->getLocation();
            if ($companyLocation !== $data['Location']) {
                $this->saveCompany($data);
            }
        }
    }

    protected function saveCompany(array $data)
    {
        $company = new Company();
        $company->setName($data['Name']);
        $company->setLocation($data['Location']);
        $company->setMarket($data['Market']);
        $company->setWebsite($data['Website']);
        $company->setEmployees($data['Employees']);
        $company->setStage($data['Stage']);
        $company->setRaised((int)$data['Total Raised']);

        if (!$this->em->isOpen()) {
            $this->em->getConnection();
            $this->em->getConfiguration();
        }
        $this->em->persist($company);
        $this->em->flush();
    }
}