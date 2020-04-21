<?php


namespace App\Tests\Behat;


use Behat\Behat\Context\Context;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;
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
     * AngelContext constructor.
     * @param Session $session
     * @param KernelInterface $kernel
     */
    public function __construct(Session $session, KernelInterface $kernel)
    {
        $this->session = $session;
        $this->kernel = $kernel;
    }

    /**
     * @When /^I am on the main page/
     */
    public function iAmOnTheMainPage()
    {
        $date = new \DateTime();
        $path = $this->getContainer()->getParameter('save_path');

        $this->session->visit('https://angel.co/companies');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->setLocations();

        $this->session->getDriver()->click('//*[@data-value="Startup"]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->click('//*[@data-value="2071-New York"]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->click('//*[@data-value="Python"]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->click('//*[@data-value="E-Commerce"]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
        $this->session->getDriver()->click('//*[@data-value="Education"]');
        $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");

        $name = 'angellist.html';
        file_put_contents($path.'/'.$name, $this->session->getPage()->getContent());
    }

    protected function setLocations()
    {
        $locations = ['Moscow', 'Berlin'];

        $locations_input = $this->session->getDriver()->find('//*[@id="location"]');
        if ($locations_input) {
            foreach ($locations as $location) {
                $this->session->getDriver()->mouseOver('//*[@data-menu="locations"]');
                $this->session->getDriver()->wait(1000, "document.getElementsByClassName('more')");

                $this->session->getDriver()->setValue('//*[@id="location"]', $location);
                $this->session->getDriver()->wait(5000, "document.getElementsByClassName('more')");

                $locations = $this->session->getDriver()->find('//*[@id="ui-id-3"]/li[1]');
                if ($locations) {
                    $this->session->getDriver()->click('//*[@id="ui-id-3"]/li[1]');
                    $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
                    $this->loopPages();
                }
            }
        }
    }

    protected function loopPages()
    {
        $counter = 0;
        for ($counter = 0; $counter < 2; $counter++) {
            $mySearch = $this->session->getDriver()->find('//*[@class="more"]');
            if ($mySearch) {
                $this->session->getDriver()->click('//*[@class="more"]');
                $this->session->getDriver()->wait(10000, "document.getElementsByClassName('more')");
            } else {
                break;
            }
        }
    }

    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }
}