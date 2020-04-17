<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\MinkContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class CompanyContext implements Context
{
    /** @var KernelInterface */
    private $kernel;

    /** @var Response|null */

    private $response;
    /**
     * @var Session
     */
    private $session;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * DemoContext constructor.
     * @param KernelInterface $kernel
     * @param Session $session
     * @param RouterInterface $router
     */
    public function __construct(KernelInterface $kernel, Session $session, RouterInterface $router)
    {
        $this->kernel = $kernel;
        $this->session = $session;
        $this->router = $router;
    }

    /**
     * @When /^I am on the main page/
     */
    public function iAmOnTheMainPage()
    {
        $this->session->visit('http://www.angellist.loc/');
        $name = 'angellist.html';
        file_put_contents('/var/www/data'.'/'.$name, $this->session->getPage()->getContent());
    }

    /**
     * @When /^I see the word "([^"]*)" somewhere on the page$/
     */
    public function iSeeTheWord($param)
    {
        $this->assertPageContainsText($param);
    }

    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

}
