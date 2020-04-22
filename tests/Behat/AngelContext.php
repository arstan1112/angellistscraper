<?php


namespace App\Tests\Behat;


use App\Service\Parser;
use Behat\Behat\Context\Context;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;
use phpDocumentor\Reflection\Location;
use Symfony\Component\HttpKernel\KernelInterface;

final class AngelContext implements Context
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
     * @var Parser
     */
    private $parser;

    /**
     * AngelContext constructor.
     * @param Session $session
     * @param KernelInterface $kernel
     * @param Parser $parser
     */
    public function __construct(Session $session, KernelInterface $kernel, Parser $parser)
    {
        $this->session = $session;
        $this->kernel = $kernel;
        $this->parser = $parser;
    }

    /**
     * @When /^I am on the main page/
     */
    public function iAmOnTheMainPage()
    {
        $this->session->visit('https://angel.co/companies');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->setLocations();
    }

    private function setLocations()
    {
        $locations = ['Moscow', 'Rome'];

        $locations_input = $this->session->getDriver()->find('//*[@id="location"]');
        if ($locations_input) {
            foreach ($locations as $location) {
                $this->session->getDriver()->wait(1000, "document.getElementsByClassName('more')");
                $this->session->getDriver()->mouseOver('//*[@data-menu="locations"]');
                $this->session->getDriver()->wait(2000, "document.getElementsByClassName('more')");

                $this->session->getDriver()->setValue('//*[@id="location"]', $location);
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");

                $locations = $this->session->getDriver()->find('//*[@id="ui-id-3"]/li[1]');
                if ($locations) {
                    $this->session->getDriver()->click('//*[@id="ui-id-3"]/li[1]');
                    $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
                    $this->loopPages();
                    $this->getContent($location);
                    $this->parser->execute($location);
                    $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");
                    $this->session->getDriver()->click('//*[@class="clear_all_link hidden"]');
                    $this->session->getDriver()->wait(3000, "document.getElementsByClassName('more')");
                }
            }
        }
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

    protected function getContent($location)
    {
        $date = new \DateTime();
        $path = $this->getContainer()->getParameter('save_path');
        $name = 'angellist_' . strtolower($location) . '.html';
        file_put_contents($path . '/' . $name, '<html>' . $this->session->getPage()->getContent() . '</html>');
    }

    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }
}