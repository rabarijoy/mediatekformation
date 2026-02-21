<?php

namespace App\Controller\Admin;

use App\Repository\CategorieRepository;
use App\Repository\PlaylistRepository;
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

        if ($ordre === 'nombreformations') {
            $playlists = $this->playlistRepository->findAllOrderByNombreFormations('ASC');
            return $this->render(self::PLAYLISTS_TEMPLATE, [
                'playlists'  => $playlists,
                'categories' => $categories,
            ]);
        }

        if ($champ !== '' && $ordre !== '' && in_array($ordre, ['ASC', 'DESC'], true)) {
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
}
