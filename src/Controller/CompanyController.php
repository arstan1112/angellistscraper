<?php

namespace App\Controller;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class CompanyController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * CompanyController constructor.
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     */
    public function __construct(EntityManagerInterface $em, PaginatorInterface $paginator)
    {
        $this->em = $em;
        $this->paginator = $paginator;
    }

    /**
     * @Route("/", name="company.main")
     * @return Response
     */
    public function index()
    {
        return $this->redirectToRoute('company.list');
    }

    /**
     * @Route("/list/{category}", name="company.list", defaults={"category":""})
     * @param Request $request
     * @param string $category
     * @return Response
     */
    public function list(Request $request, string $category)
    {
        if (!(empty($category))) {
            $companiesQuery = $this->em->getRepository(Company::class)->findByCategory($category);
        } else {
            $companiesQuery = $this->em->getRepository(Company::class)->findAll();
        }
        $totalCompanies = count($companiesQuery);
        $companies = $this->paginator->paginate(
            $companiesQuery,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('company/list.html.twig', [
            'companies' => $companies,
            'totalCompanies' => $totalCompanies,
            'name' => 'name',
            'location' => 'location',
            'market' => 'market',
            'joined' => 'joined'
        ]);
    }

    /**
     * @Route("/show/{id}", name="company.show", requirements={"id"="\d+"})
     * @param $id
     * @return Response
     */
    public function show($id)
    {
        $company = $this->em->getRepository(Company::class)->find($id);
        return $this->render('company/show.html.twig', [
            'company' => $company,
        ]);
    }

    /**
     * @Route("/search", name="company.search", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function search(Request $request)
    {
        $field = $request->get('field');
        $value = $request->get('value');
        $companiesQuery = $this->em->getRepository(Company::class)->findByParameter($field, $value);
        $totalCompanies = count($companiesQuery);
        $companies = $this->paginator->paginate(
            $companiesQuery,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('company/list.html.twig', [
            'companies' => $companies,
            'totalCompanies' => $totalCompanies,
            'name' => 'name',
            'location' => 'location',
            'market' => 'market',
            'joined' => 'joined'
        ]);
    }

    /**
     * @Route("/sort/date", name="company.sort.date", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function sortByDate(Request $request)
    {
        $from = strtotime($request->get('from'));
        $from = date('M y', $from);
        $from = \DateTime::createFromFormat('M y', $from);

        $to = strtotime($request->get('to'));
        $to = date('M y', $to);
        $to = \DateTime::createFromFormat('M y', $to);

        $companiesQuery = $this->em->getRepository(Company::class)->findByDate($from, $to);
        $totalCompanies = count($companiesQuery);
        $companies = $this->paginator->paginate(
            $companiesQuery,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('company/list.html.twig', [
            'companies' => $companies,
            'totalCompanies' => $totalCompanies,
            'name' => 'name',
            'location' => 'location',
            'market' => 'market',
            'joined' => 'joined'
        ]);
    }
}
