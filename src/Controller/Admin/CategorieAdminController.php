<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(): Response
    {
        $categories = $this->categorieRepository->findBy([], ['name' => 'ASC']);

        return $this->render(self::CATEGORIES_TEMPLATE, [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/add', name: 'admin_categorie_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $name = trim($request->request->get('name', ''));

        if ($name === '') {
            $this->addFlash('error', 'Le nom de la catégorie est obligatoire.');
            return $this->redirectToRoute('admin_categories');
        }

        if ($this->categorieRepository->findOneBy(['name' => $name]) !== null) {
            $this->addFlash('error', 'Cette catégorie existe déjà.');
            return $this->redirectToRoute('admin_categories');
        }

        $categorie = new Categorie();
        $categorie->setName($name);
        $entityManager->persist($categorie);
        $entityManager->flush();

        $this->addFlash('success', 'Catégorie ajoutée.');
        return $this->redirectToRoute('admin_categories');
    }
}
