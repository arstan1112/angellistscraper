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

        $statusCode = $this->session->getStatusCode();
        $this->angelLogger->info('Status code: ' .$statusCode);

        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->angelLogger->info('Start the process');
        $this->startExecution();
    }

    private function startExecution()
    {
        $this->loopPagesParseSave();
        $this->loopLocations();
        $this->restartExecution();
    }

    protected function restartExecution()
    {
        $totalCompanies = $this->countTotalCompanies();
        $this->angelLogger->info('Restart is called');

        do {
            $this->loopLocations();
            $totalCompaniesInDb = $this->companyRepository->findAll();
            $this->angelLogger->info('Total companies checked in DB,   total: ' . count($totalCompaniesInDb));
            $this->angelLogger->info('Total companies checked in site, total: ' . $totalCompanies);
        } while (10 > count($totalCompaniesInDb));
    }

    protected function loopLocations()
    {
        $locations = $this->locationRepository->findAllUnchecked();
        $locationsInputField = $this->session->getDriver()->find('//*[@id="location"]');
        if ($locationsInputField) {
            $counter = 1;
            foreach ($locations as $location) {
                $dropDownMenu = $this->setFilterName('locations', 'location', '3', $location->getName());
                if ($dropDownMenu) {
                    if (!$this->scrapeWithLocations()) {
                        break;
                    } else {
                        $location->setStatus('checked');
                        $this->em->flush();
                    }
                } else {
                    $this->session->restart();
                    $this->session->visit('http://www.angellist.loc/list');
                    $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
                    $this->restartExecution();
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
        $marketsInputField = $this->session->getDriver()->find('//*[@id="market"]');
        if ($marketsInputField) {
            $counter = 1;
            foreach ($markets as $market) {
                $dropDownMenu = $this->setFilterName('markets', 'market', '4', $market->getName());
                if ($dropDownMenu) {
                    $this->scrapeWithMarkets();
                    $market->setStatus('checked');
                    $this->em->flush();
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
            $this->selectFilter('company_types', $type);
            $totalCompanies = $this->countTotalCompanies();
            if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
                $this->angelLogger->info('Type selected successfully');
                $this->loopPagesParseSave();
                $this->loopTechs();
            } elseif ($totalCompanies !== 0) {
                $this->loopPagesParseSave();
            }
            $this->removeFilter('3');
        }
    }

    protected function loopTechs()
    {
        $techs = ['Javascript', 'Python', 'HTML5', 'CSS', 'Java'];
        foreach ($techs as $tech) {
            $this->selectFilter('teches', $tech);
            $totalCompanies = $this->countTotalCompanies();
            if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
                $this->angelLogger->info('Tech selected successfully');
                $this->loopPagesParseSave();
                $this->loopStages();
            } elseif ($totalCompanies !== 0) {
                $this->loopPagesParseSave();
            }
            $this->removeFilter('4');
        }
    }

    protected function loopStages()
    {
        $stages = ['Seed', 'Series A', 'Series B', 'Series C', 'Acquired'];
        foreach ($stages as $stage) {
            $this->selectFilter('stages', $stage);
            $this->loopPagesParseSave();
            $this->removeFilter('5');
        }
    }

    protected function scrapeWithLocations()
    {
        try {
            $this->session->getDriver()->click('//*[@id="ui-id-3"]/li[1]');
        } catch (\Exception $exception) {
            $this->angelLogger->error('Click drop down did not work with exception: ' .$exception->getMessage());
            return false;
        }
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");

        $totalCompanies = $this->countTotalCompanies();

        if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
            $this->loopPagesParseSave();
            $this->loopMarkets();
        } elseif ($totalCompanies !== 0) {
            $this->loopPagesParseSave();
        }
        $this->removeAllFilters();
    }

    protected function scrapeWithMarkets()
    {
        $this->session->getDriver()->click('//*[@id="ui-id-4"]/li[1]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");

        $totalCompanies = $this->countTotalCompanies();
        if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
            $this->loopPagesParseSave();
            $this->loopTypes();
        } elseif ($totalCompanies !== 0) {
            $this->loopPagesParseSave();
        }
        $this->removeFilter('2');
    }

    protected function selectFilter(string $dataAttrMenu, string $dataAttrValue)
    {
        $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->mouseOver('//*[@data-menu="' . $dataAttrMenu . '"]');
        $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");

        $this->session->getDriver()->click('//*[@data-value="' . $dataAttrValue . '"]');
        $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");
    }

    protected function removeAllFilters()
    {
        $this->session->getDriver()->wait(6000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->click('//*[@class="clear_all_link hidden"]');
        $this->session->getDriver()->wait(4000, "document.getElementsByClassName('more')");
    }

    protected function removeFilter(string $nodeNumber)
    {
        $this->session->getDriver()->wait(6000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->click('//*[@id="root"]/div[4]/div[2]/div/div[2]/div[1]/div[1]/div/div[2]/div[' . $nodeNumber . ']/img');
        $this->session->getDriver()->wait(4000, "document.getElementsByClassName('more')");
    }

    protected function setFilterName(string $dataAttr, string $id, string $nodeNumber, string $filterName)
    {
        $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->mouseOver('//*[@data-menu="' . $dataAttr . '"]');
        $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");

        $this->session->getDriver()->setValue('//*[@id="' . $id . '"]', $filterName);
        $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");

        try {
            $this->session->getDriver()->find('//*[@id="ui-id-' . $nodeNumber . '"]/li[1]');
            return true;
        } catch (\Exception $exception) {
            $this->angelLogger->error('Drop down was not found with exception: ' .$exception->getMessage());
        }
        return false;
    }

    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

    protected function countTotalCompanies()
    {
        $total = $this->session->getDriver()->find('//*[@class="count"]');
        $totalCompanies = preg_replace("/[^0-9]/", '', $total[1]->getText());
        $totalCompanies = (int)$totalCompanies;

        return $totalCompanies;
    }

    protected function loopPagesParseSave()
    {
        $this->loopPages();
        $this->putContentsToFile();
        $this->angelLogger->info('Content has been put to the file');
        $arrParsedElements = $this->parseAllElements($this->getXpath());

        foreach ($arrParsedElements as $ar) {
            try {
                $this->saveLocation($ar['Location']);
                $this->saveMarket($ar['Market']);
                $this->checkAndSaveCompany($ar);
            } catch (\Throwable $exception) {
                $this->angelLogger->error('[Line '.__LINE__.'] Error occurred while saving with exception: ' . $exception->getMessage() . ' on ');
                exit();
            }
        }
        $this->angelLogger->info('Saving has been finished');
    }

    protected function loopPages()
    {
        for ($page = 1; $page < 2; $page++) {
            $moreButton = $this->session->getDriver()->find('//*[@class="more"]');
            if ($moreButton) {
                $this->session->getDriver()->click('//*[@class="more"]');
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
            } else {
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
        $this->angelLogger->info('Parsing and saving have been initiated');

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
        $this->angelLogger->info('Parsing has been finished');

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

                    $this->em->persist($location);
                    $this->em->flush();
                    $this->angelLogger->info('Location saved');
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

                    $this->em->persist($market);
                    $this->em->flush();
                    $this->angelLogger->info('Market saved');
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

        $this->em->persist($company);
        $this->em->flush();
    }
}