<?php


namespace App\Tests\Behat;


use Behat\Behat\Context\Context;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;

final class AngelContext implements Context
{
    /**
     * @var Session
     */
    private $session;

    /**
     * AngelContext constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @When /^I am on the main page/
     */
    public function iAmOnTheMainPage()
    {
        $mink = new Mink(array(
            'browser' => new Session(new ChromeDriver('http://localhost:9222', null, 'http://www.google.com'))
        ));

        $mink->setDefaultSessionName('browser');

        $mink->getSession()->visit('https://angel.co/companies');
        $counter = 0;
        for ($counter = 0; $counter < 20; $counter++) {
            $mySearch = $mink->getSession()->getDriver()->find('//*[@class="more"]');
            if ($mySearch) {
                $mink->getSession()->getDriver()->click('//*[@class="more"]');
                $mink->getSession()->getDriver()->wait(10000, "document.getElementsByClassName('more')");
            } else {
                break;
            }
        }

        $mink->getSession()->getDriver()->click('//*[@data-value="Startup"]');

        $name = 'angellist.html';
        file_put_contents('/var/www/data/'.$name, $mink->getSession()->getPage()->getContent());
    }
}