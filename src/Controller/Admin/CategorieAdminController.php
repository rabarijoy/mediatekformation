<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categories')]
class CategorieAdminController extends AbstractController
{
    private const CATEGORIES_TEMPLATE = 'admin/categorie/index.html.twig';

    public function __construct(
        private readonly CategorieRepository $categorieRepository
    ) {
    }

    #[Route('/admin', name: 'admin_categories', methods: ['GET'])]
    public function index(): Response
    {
        $categories = $this->categorieRepository->findBy([], ['name' => 'ASC']);

        return $this->render(self::CATEGORIES_TEMPLATE, [
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/add', name: 'admin_categorie_add', methods: ['POST'])]
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

    #[Route('/admin/{id}/delete', name: 'admin_categorie_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorie = $this->categorieRepository->find($id);
        if ($categorie === null) {
            throw $this->createNotFoundException('Catégorie non trouvée.');
        }

        if (!$this->isCsrfTokenValid('categorie_delete_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_categories');
        }

        if ($categorie->getFormations()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette catégorie : elle est rattachée à des formations.');
            return $this->redirectToRoute('admin_categories');
        }

        $entityManager->remove($categorie);
        $entityManager->flush();

        $this->addFlash('success', 'Catégorie supprimée.');
        return $this->redirectToRoute('admin_categories');
    }
}
