<?php


namespace App\Context;


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
     * AngelContext constructor.
     * @param Session $session
     * @param KernelInterface $kernel
     * @param EntityManagerInterface $em
     * @param LoggerInterface $angelLogger
     */
    public function __construct(
        Session $session,
        KernelInterface $kernel,
        EntityManagerInterface $em,
        LoggerInterface $angelLogger)
    {
        $this->session = $session;
        $this->kernel = $kernel;
        $this->em = $em;
        $this->angelLogger = $angelLogger;
    }

    /**
     * @When /^I am on the main page/
     */
    public function iAmOnTheMainPage()
    {
        $this->session->visit('https://angel.co/companies');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->angelLogger->info('Start the process');
        $this->startExecution();
    }

    private function startExecution()
    {
        $this->loopLocations();
    }

    private function loopLocations()
    {
        $locations = ['Palo Alto', 'Moscow'];
        $locationsInput = $this->session->getDriver()->find('//*[@id="location"]');
        if ($locationsInput) {
            foreach ($locations as $location) {
                $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");
                $this->session->getDriver()->mouseOver('//*[@data-menu="locations"]');
                $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");

                $this->session->getDriver()->setValue('//*[@id="location"]', $location);
                $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");

                $dropDownMenu = $this->session->getDriver()->find('//*[@id="ui-id-3"]/li[1]');
                if ($dropDownMenu) {
                    $this->scrapeWithLocations($location);
                }
            }
        }
    }

    private function scrapeWithLocations($location)
    {
        $this->angelLogger->info('Drop down found');
        $this->session->getDriver()->click('//*[@id="ui-id-3"]/li[1]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->loopPages();
        $this->getContent($location);
        $this->angelLogger->info('File has been put to the directory');
        $this->parseAndSave($location);

        $totalCompanies = $this->session->getDriver()->find('//*[@class="count"]');
        $totalCompaniesForThisLocation = preg_replace("/[^0-9]/", '', $totalCompanies[1]->getText());
        $totalCompaniesForThisLocation = (int)$totalCompaniesForThisLocation;

        if ($totalCompaniesForThisLocation > 5) {
            $this->loopMarkets();
        } else {
            $this->session->getDriver()->wait(6000, "document.getElementsByClassName('more')");
            $this->session->getDriver()->click('//*[@class="clear_all_link hidden"]');
            $this->session->getDriver()->wait(4000, "document.getElementsByClassName('more')");
        }
    }

    private function loopMarkets()
    {
        $markets = ['Food and Beverages', 'Independent Pharmacies'];
        $marketsInput = $this->session->getDriver()->find('//*[@id="market"]');
        if ($marketsInput) {
            foreach ($markets as $market) {
                $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");
                $this->session->getDriver()->mouseOver('//*[@data-menu="markets"]');
                $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");

                $this->session->getDriver()->setValue('//*[@id="market"]', $market);
                $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");

                $dropDownMenu = $this->session->getDriver()->find('//*[@id="ui-id-4"]/li[1]');
                if ($dropDownMenu) {
                    $this->scrapeWithMarkets($market);
                }
            }
        }
    }

    private function scrapeWithMarkets($market)
    {
        $this->angelLogger->info('Drop down found');
        $this->session->getDriver()->click('//*[@id="ui-id-4"]/li[1]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->loopPages();
        $this->getContent($market);
        $this->angelLogger->info('File has been put to the directory');
        $this->parseAndSave($market);

        $this->session->getDriver()->wait(6000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->click('//*[@id="root"]/div[4]/div[2]/div/div[2]/div[1]/div[1]/div/div[2]/div[2]/img');

    }

    protected function loopPages()
    {
        for ($page = 1; $page < 3; $page++) {
            $mySearch = $this->session->getDriver()->find('//*[@class="more"]');
            if ($mySearch) {
                $this->session->getDriver()->click('//*[@class="more"]');
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
            } else {
                break;
            }
        }
    }

    protected function getContent($nameSuffix)
    {
        $date = new \DateTime();
        $nameSuffix = strtolower($nameSuffix);
        $nameSuffix = preg_replace("/[^a-z\-]/", '', $nameSuffix);

        $path = $this->getContainer()->getParameter('save_path');
        $name = 'angellist_' . $nameSuffix . '.html';

        file_put_contents($path . '/' . $name, '<html>' . $this->session->getPage()->getContent() . '</html>');
    }

    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

    protected function parseAndSave($location)
    {
        $this->angelLogger->info('Parsing and saving has been initiated');
        $path = $this->getContainer()->getParameter('save_path');
        $location = strtolower($location);
        $location = preg_replace("/[^a-z\-]/", '', $location);
        $name = 'angellist_' . $location . '.html';

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
                $this->save($ar);
            } catch (\Throwable $exception) {
                $this->angelLogger->error('Error occurred while saving with exception: ' . $exception->getMessage() . ' on ');
                exit();
            }
        }
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