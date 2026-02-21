<?php

namespace App\Controller\Admin;

use App\Entity\Playlist;
use App\Repository\CategorieRepository;
use App\Repository\PlaylistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class PlaylistAdminController extends AbstractController
{
    private const PLAYLISTS_TEMPLATE = 'admin/playlist/index.html.twig';

    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
        private readonly CategorieRepository $categorieRepository
    ) {
    }

    #[Route('/playlists', name: 'admin_playlists', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $categories = $this->categorieRepository->findAll();

        $recherche = $request->query->get('recherche');
        $champ     = $request->query->get('champ', '');
        $ordre     = $request->query->get('ordre', '');
        $table     = $request->query->get('table', '');

        if ($request->query->has('recherche')) {
            $playlists = $this->playlistRepository->findByContainValue($champ, $recherche ?? '', $table, 'ASC');
            return $this->render(self::PLAYLISTS_TEMPLATE, [
                'playlists'  => $playlists,
                'categories' => $categories,
                'valeur'     => $recherche,
                'table'      => $table,
                'champ'      => $champ,
            ]);
        }

        if ($champ === 'nombreformations' && in_array($ordre, ['ASC', 'DESC'], true)) {
            $playlists = $this->playlistRepository->findAllOrderByNombreFormations($ordre);
            return $this->render(self::PLAYLISTS_TEMPLATE, [
                'playlists'  => $playlists,
                'categories' => $categories,
            ]);
        }

        if ($champ !== '' && in_array($ordre, ['ASC', 'DESC'], true)) {
            $playlists = $this->playlistRepository->findAllOrderByName($ordre);
            return $this->render(self::PLAYLISTS_TEMPLATE, [
                'playlists'  => $playlists,
                'categories' => $categories,
            ]);
        }

        $playlists = $this->playlistRepository->findAllOrderByName('ASC');
        return $this->render(self::PLAYLISTS_TEMPLATE, [
            'playlists'  => $playlists,
            'categories' => $categories,
        ]);
    }

    #[Route('/playlists/{id}/delete', name: 'admin_playlist_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $playlist = $this->playlistRepository->find($id);
        if (!$playlist instanceof Playlist) {
            throw $this->createNotFoundException('Playlist non trouvée.');
        }

        if (!$this->isCsrfTokenValid('playlist_delete_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_playlists');
        }

        if ($playlist->getFormations()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette playlist : elle contient des formations.');
            return $this->redirectToRoute('admin_playlists');
        }

        $entityManager->remove($playlist);
        $entityManager->flush();

        $this->addFlash('success', 'Playlist supprimée.');
        return $this->redirectToRoute('admin_playlists');
    }
}
