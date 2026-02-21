<?php

namespace App\Controller\Admin;

use App\Entity\Formation;
use App\Form\FormationType;
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
#[Route('/formations')]
class FormationAdminController extends AbstractController
{
    private const FORMATIONS_TEMPLATE = 'admin/formation/index.html.twig';

    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly CategorieRepository $categorieRepository
    ) {
    }

    #[Route('/admin', name: 'admin_formations', methods: ['GET'])]
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

    private const FORM_TEMPLATE = 'admin/formation/form.html.twig';

    #[Route('/admin/new', name: 'admin_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formation);
            $entityManager->flush();
            $this->addFlash('success', 'Formation créée.');
            return $this->redirectToRoute('admin_formations');
        }

        return $this->render(self::FORM_TEMPLATE, [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'admin_formation_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $formation = $this->formationRepository->find($id);
        if (!$formation instanceof Formation) {
            throw $this->createNotFoundException('Formation non trouvée.');
        }

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Formation modifiée.');
            return $this->redirectToRoute('admin_formations');
        }

        return $this->render(self::FORM_TEMPLATE, [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'admin_formation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
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
