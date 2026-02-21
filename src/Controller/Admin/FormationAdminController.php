<?php

namespace App\Controller\Admin;

use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur admin des formations
 */
#[Route('/admin')]
class FormationAdminController extends AbstractController
{
    private const FORMATIONS_TEMPLATE = 'admin/formation/index.html.twig';

    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly CategorieRepository $categorieRepository
    ) {
    }

    #[Route('/formations', name: 'admin_formations', methods: ['GET'])]
    public function index(): Response
    {
        $formations = $this->formationRepository->findAll();
        $categories = $this->categorieRepository->findAll();

        return $this->render(self::FORMATIONS_TEMPLATE, [
            'formations' => $formations,
            'categories' => $categories,
        ]);
    }

    #[Route('/formations/tri/{champ}/{ordre}/{table?}', name: 'admin_formations_sort', requirements: ['ordre' => 'ASC|DESC'], methods: ['GET'], defaults: ['table' => ''])]
    public function sort(string $champ, string $ordre, string $table = ''): Response
    {
        $formations = $this->formationRepository->findAllOrderBy($champ, $ordre, $table);
        $categories = $this->categorieRepository->findAll();

        return $this->render(self::FORMATIONS_TEMPLATE, [
            'formations' => $formations,
            'categories' => $categories,
        ]);
    }

    #[Route('/formations/recherche/{champ}/{table?}', name: 'admin_formations_findallcontain', methods: ['GET', 'POST'], defaults: ['table' => ''])]
    public function findAllContain(string $champ, Request $request, string $table = ''): Response
    {
        $valeur = $request->get('recherche');
        $formations = $this->formationRepository->findByContainValue($champ, $valeur ?? '', $table);
        $categories = $this->categorieRepository->findAll();

        return $this->render(self::FORMATIONS_TEMPLATE, [
            'formations' => $formations,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table,
        ]);
    }
}
