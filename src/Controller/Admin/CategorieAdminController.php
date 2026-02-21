<?php

namespace App\Controller\Admin;

use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class CategorieAdminController extends AbstractController
{
    private const CATEGORIES_TEMPLATE = 'admin/categorie/index.html.twig';

    public function __construct(
        private readonly CategorieRepository $categorieRepository
    ) {
    }

    #[Route('/categories', name: 'admin_categories', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $categories = $this->categorieRepository->findBy([], ['name' => 'ASC']);

        return $this->render(self::CATEGORIES_TEMPLATE, [
            'categories' => $categories,
        ]);
    }
}
