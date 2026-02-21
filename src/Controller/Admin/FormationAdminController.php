<?php

namespace App\Controller\Admin;

use App\Entity\Formation;
use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur admin des formations.
 * Même logique de tri et filtrage que le front office, avec les mêmes paramètres GET.
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
    public function index(Request $request): Response
    {
        $categories = $this->categorieRepository->findAll();

        // Mêmes paramètres GET que le front : recherche, champ, ordre, table
        $recherche = $request->query->get('recherche');
        $champ = $request->query->get('champ', '');
        $ordre = $request->query->get('ordre', '');
        $table = $request->query->get('table', '');

        // Filtrage (comme formations.findallcontain) : priorité si "recherche" est présent en GET
        if ($request->query->has('recherche')) {
            $formations = $this->formationRepository->findByContainValue($champ, $recherche ?? '', $table);
            return $this->render(self::FORMATIONS_TEMPLATE, [
                'formations' => $formations,
                'categories' => $categories,
                'valeur' => $recherche,
                'table' => $table,
            ]);
        }

        // Tri (comme formations.sort) : si champ et ordre sont présents
        if ($champ !== '' && $ordre !== '' && in_array($ordre, ['ASC', 'DESC'], true)) {
            $formations = $this->formationRepository->findAllOrderBy($champ, $ordre, $table);
            return $this->render(self::FORMATIONS_TEMPLATE, [
                'formations' => $formations,
                'categories' => $categories,
            ]);
        }

        // Liste par défaut (comme formations index)
        $formations = $this->formationRepository->findAll();
        return $this->render(self::FORMATIONS_TEMPLATE, [
            'formations' => $formations,
            'categories' => $categories,
        ]);
    }

    #[Route('/formations/{id}/edit', name: 'admin_formation_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function edit(int $id): Response
    {
        return $this->redirectToRoute('admin_formations');
    }

    #[Route('/formations/{id}/delete', name: 'admin_formation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = $this->formationRepository->find($id);
        if (!$formation instanceof Formation) {
            throw $this->createNotFoundException('Formation non trouvée.');
        }
        if (!$this->isCsrfTokenValid('formation_delete_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_formations');
        }

        $playlist = $formation->getPlaylist();
        if ($playlist !== null) {
            $playlist->removeFormation($formation);
        }

        $entityManager->remove($formation);
        $entityManager->flush();

        $this->addFlash('success', 'Formation supprimée.');
        return $this->redirectToRoute('admin_formations');
    }
}
