<?php

namespace App\Controller;

use App\Entity\Company;
use CloudflareBypass\CFCurlImpl;
use CloudflareBypass\Model\UAMOptions;
use Doctrine\ORM\EntityManagerInterface;
use Masterminds\HTML5;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CompanyController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * CompanyController constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/", name="company.list")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->render('company/index.html.twig', [
            'request' => $request,
        ]);
    }

    /**
     * @Route("/api/values", name="company.api.values")
     * @return Response
     */
    public function getValues()
    {
        return $this->render('company/values.html.twig', [
            'data' => 'values',
        ]);
    }

    /**
     * @Route("/api/keys", name="company.api.keys")
     * @return Response
     */
    public function getKeys()
    {
        return $this->render('company/keys.html.twig', [
            'data' => 'keys',
        ]);
    }

}
