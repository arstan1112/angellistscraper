<?php


namespace App\Context;


use App\Repository\CompanyRepository;
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
    const MAX_NUMBER_COMPANIES_PER_REQUEST = 20;
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
     * AngelContext constructor.
     * @param Session $session
     * @param KernelInterface $kernel
     * @param EntityManagerInterface $em
     * @param LoggerInterface $angelLogger
     * @param CompanyRepository $companyRepository
     */
    public function __construct(
        Session $session,
        KernelInterface $kernel,
        EntityManagerInterface $em,
        LoggerInterface $angelLogger,
        CompanyRepository $companyRepository)
    {
        $this->session = $session;
        $this->kernel = $kernel;
        $this->em = $em;
        $this->angelLogger = $angelLogger;
        $this->companyRepository = $companyRepository;
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
        $this->loopLocations();
    }

    protected function loopLocations()
    {
        $locations = ['Minsk', 'Istanbul'];
        $locationsInputField = $this->session->getDriver()->find('//*[@id="location"]');
        if ($locationsInputField) {
            foreach ($locations as $locationName) {
                $dropDownMenu = $this->setFilterName('locations', 'location', '3', $locationName);
                if ($dropDownMenu) {
                    $this->scrapeWithLocations($locationName);
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
                $this->loopStages();
            } elseif ($totalCompanies !== 0) {
                $this->loopParseSave($type);
            }
            $this->removeFilter('2');
        }
    }

    protected function loopStages()
    {
        $stages = ['Seed', 'Series A', 'Series B', 'Series C', 'Acquired'];
        foreach ($stages as $stage) {
            $this->selectFilter('stages', $stage);
            $totalCompanies = $this->countTotalCompanies();
            if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
                $this->angelLogger->info('Stage selected successfully');
//                $this->loopMarkets();
            } elseif ($totalCompanies !== 0) {
                $this->loopParseSave($stage);
            }
        }
    }

    protected function loopMarkets()
    {
        $markets = ['Food and Beverages', 'Independent Pharmacies', 'Waste Management', 'Energy Efficiency', 'Small and Medium Businesses', 'Oil and Gas', 'Advertising Platforms', 'Virtualization', 'Restaurants', 'Hospitality', 'Heavy Industry', 'Families', 'Journalism', 'Chemicals', 'E-Commerce', 'Commodities', 'Material Science', 'Credit'];
        $marketsInputField = $this->session->getDriver()->find('//*[@id="market"]');
        if ($marketsInputField) {
            foreach ($markets as $marketName) {
                $dropDownMenu = $this->setFilterName('markets', 'market', '4', $marketName);
                if ($dropDownMenu) {
                    $this->scrapeWithMarkets($marketName);
                }
            }
        }
    }

    protected function loopTechs()
    {
        $techs = ['Security', 'Cloud'];
        $techsInputField = $this->session->getDriver()->find('//*[@id="tech"]');
        if ($techsInputField) {
            foreach ($techs as $techName) {
                $dropDownMenu = $this->setFilterName('teches', 'tech', '5', $techName);
                if ($dropDownMenu) {
                    $this->scrapeWithTechs($techName);
                }
            }
        }
    }

    protected function scrapeWithLocations($locationName)
    {
        $this->angelLogger->info('Drop down for locations found');
        $this->session->getDriver()->click('//*[@id="ui-id-3"]/li[1]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");

        $totalCompanies = $this->countTotalCompanies();

        if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
            $this->loopTypes();
        } elseif ($totalCompanies !== 0) {
            $this->loopParseSave($locationName);
        }
        $this->removeAllFilters();
    }

    protected function scrapeWithMarkets($market)
    {
        $this->angelLogger->info('Drop down for markets found');
        $this->session->getDriver()->click('//*[@id="ui-id-4"]/li[1]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");

        $totalCompanies = $this->countTotalCompanies();

        if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
            $this->loopTechs();
        } elseif ($totalCompanies !== 0) {
            $this->loopParseSave($market);
        }
        $this->removeFilter('3');
    }

    protected function scrapeWithTechs($tech)
    {
        $this->angelLogger->info('Drop down for techs found');
        $this->session->getDriver()->click('//*[@id="ui-id-5"]/li[1]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");

        $totalCompanies = $this->countTotalCompanies();

        if ($totalCompanies > self::MAX_NUMBER_COMPANIES_PER_REQUEST) {
            $this->angelLogger->info('Tech filter has more than 400 companies');
        } elseif ($totalCompanies !== 0) {
            $this->loopParseSave($tech);
        }
        $this->removeFilter('4');
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

        $dropDownMenu = $this->session->getDriver()->find('//*[@id="ui-id-' . $nodeNumber . '"]/li[1]');

        if ($dropDownMenu) {
            return true;
        } else {
            return false;
        }
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

    protected function loopParseSave($nameSuffix)
    {
        $this->loopPages();
        $this->putContentToFile($nameSuffix);
        $this->angelLogger->info('Content has been put to the file');
        $this->parseAndSave($nameSuffix);
    }

    protected function loopPages()
    {
        for ($page = 1; $page < 3; $page++) {
            $moreButton = $this->session->getDriver()->find('//*[@class="more"]');
            if ($moreButton) {
                $this->session->getDriver()->click('//*[@class="more"]');
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
            } else {
                break;
            }
        }
    }

    protected function putContentToFile($nameSuffix)
    {
        $date = new \DateTime();
        $nameSuffix = strtolower($nameSuffix);
        $nameSuffix = preg_replace("/[^a-z\-]/", '', $nameSuffix);

        $path = $this->getContainer()->getParameter('save_path');
        $name = 'angellist_' . $nameSuffix . '.html';

        file_put_contents($path . '/' . $name, '<html>' . $this->session->getPage()->getContent() . '</html>');
    }

    protected function parseAndSave($nameSuffix)
    {
        $this->angelLogger->info('Parsing and saving have been initiated');

        $path = $this->getContainer()->getParameter('save_path');
        $nameSuffix = strtolower($nameSuffix);
        $nameSuffix = preg_replace("/[^a-z\-]/", '', $nameSuffix);
        $name = 'angellist_' . $nameSuffix . '.html';

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
        $this->angelLogger->info('Parsing has been finished');

        foreach ($arr as $ar) {
            try {
                $this->checkAndSave($ar);
            } catch (\Throwable $exception) {
                $this->angelLogger->error('Error occurred while saving with exception: ' . $exception->getMessage() . ' on ');
                exit();
            }
        }
        $this->angelLogger->info('Saving has been finished');
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

    protected function checkAndSave($data)
    {
        $website = preg_replace("/[^A-Za-z0-9\-.]/", '', $data['Website']);
        if ($website == '' || $website == '-' || $website == 'Website' || $website == 'website') {
            $companyWithGivenName = $this->companyRepository->findByName($data['Name']);
            if (!$companyWithGivenName) {
                $this->save($data);
            } elseif ($companyWithGivenName) {
                $companyLocation = $companyWithGivenName[0]->getLocation();
                if ($companyLocation !== $data['Location']) {
                    $this->save($data);
                }
            }
        } else {
            $companyWithGivenWebsite = $this->companyRepository->findByWebsite($website);
            if (!$companyWithGivenWebsite) {
                $this->save($data);
            }
        }
    }

    protected function save($data)
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