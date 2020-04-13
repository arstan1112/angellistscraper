<?php

namespace App\Controller;

use App\Entity\Company;
use CloudflareBypass\CFCurlImpl;
use CloudflareBypass\Model\UAMOptions;
use Doctrine\ORM\EntityManagerInterface;
use Masterminds\HTML5;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
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
     * @Route("/", name="company.emulate")
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
     * @Route("/list", name="company.list")
     * @return Response
     */
    public function list()
    {
        $companies = $this->em->getRepository(Company::class)->findAll();
        return $this->render('company/list.html.twig', [
            'companies' => $companies,
        ]);
    }

    /**
     * @Route("/api/values", name="company.api.values")
     * @param Request $request
     * @return Response
     */
    public function getValues(Request $request)
    {
        $uri = $request->getRequestUri();
        $parts = parse_url($uri);
        parse_str($parts['query'], $query);

        if ($query['hexdigest']=='9b92fafa1c1fa463c7d48c6ac41cb2896e3fbffb') {
            return $this->render('company/values.html.twig', [
                'data' => 'values',
            ]);
        } else {
            return $this->json([
                'status' => 'error',
            ], 404);
        }
    }

    /**
     * @Route("/api/keys", name="company.api.keys")
     * @param Request $request
     * @return Response
     */
    public function getKeys(Request $request)
    {
        if ($request->get('sort')) {
            return $this->json([
//                'status' => 'error',
                'request' => $request->request,
            ], 404);
//            return $this->render('company/keys.html.twig', [
//                'data' => 'keys',
//            ]);
        } else {
            return $this->json([
                'status' => 'error',
            ], 404);
        }
    }

}
