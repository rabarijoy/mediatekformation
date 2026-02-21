<?php
namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Description of PlaylistsController
 *
 * @author emds
 */
class PlaylistsController extends AbstractController
{
    private const PLAYLISTS_TEMPLATE = 'pages/playlists.html.twig';

    /**
     *
     * @var PlaylistRepository
     */
    private $playlistRepository;

    /**
     *
     * @var FormationRepository
     */
    private $formationRepository;

    /**
     *
     * @var CategorieRepository
     */
    private $categorieRepository;

    /**
     * @param PlaylistRepository $playlistRepository
     * @param CategorieRepository $categorieRepository
     * @param FormationRepository $formationRespository
     */
    public function __construct(
        PlaylistRepository $playlistRepository,
        CategorieRepository $categorieRepository,
        FormationRepository $formationRespository
    ) {
        $this->playlistRepository = $playlistRepository;
        $this->categorieRepository = $categorieRepository;
        $this->formationRepository = $formationRespository;
    }

    /**
     * @Route("/playlists", name="playlists")
     * @return Response
     */
    #[Route('/playlists', name: 'playlists')]
    public function index(): Response
    {
        $playlists = $this->playlistRepository->findAllOrderByName('ASC');
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PLAYLISTS_TEMPLATE, [
            'playlists' => $playlists,
            'categories' => $categories
        ]);
    }

    /**
     * Affiche les playlists triées par nombre de formations.
     * @param string $ordre
     * @param Request $request
     * @return Response
     */
    #[Route('/playlists/tri/nombreformations/{ordre}', name: 'playlists.sort.nombreformations', requirements: ['ordre' => 'ASC|DESC'])]
    public function sortByNombreFormations(string $ordre, Request $request): Response
    {
        $valeur = $request->query->get('recherche', '');
        $champ = $request->query->get('champ', 'name');
        $table = $request->query->get('table', '');
        if ($valeur !== '' || $table !== '') {
            $playlists = $this->playlistRepository->findByContainValueOrderByNombreFormations($champ, $valeur, $table, $ordre);
        } else {
            $playlists = $this->playlistRepository->findAllOrderByNombreFormations($ordre);
        }
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PLAYLISTS_TEMPLATE, [
            'playlists' => $playlists,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table,
            'champ' => $champ,
        ]);
    }

    /**
     * Affiche les playlists triées sur un champ.
     * @param string $champ
     * @param string $ordre
     * @param Request $request
     * @return Response
     */
    #[Route('/playlists/tri/{champ}/{ordre}', name: 'playlists.sort')]
    public function sort(string $champ, string $ordre, Request $request): Response
    {
        $valeur = $request->query->get('recherche', '');
        $table = $request->query->get('table', '');
        if ($valeur !== '' || $table !== '') {
            $playlists = $this->playlistRepository->findByContainValue($champ, $valeur, $table, $ordre);
        } else {
            $playlists = $this->playlistRepository->findAllOrderByName($ordre);
        }
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PLAYLISTS_TEMPLATE, [
            'playlists' => $playlists,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table,
            'champ' => $champ,
        ]);
    }

    /**
     * Affiche les playlists dont un champ contient la valeur recherchée.
     * @param string $champ
     * @param Request $request
     * @param string $table
     * @return Response
     */
    #[Route('/playlists/recherche/{champ}/{table}', name: 'playlists.findallcontain')]
    public function findAllContain($champ, Request $request, $table = ""): Response
    {
        $valeur = $request->get("recherche") ?? '';
        $playlists = $this->playlistRepository->findByContainValue($champ, $valeur, $table, 'ASC');
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PLAYLISTS_TEMPLATE, [
            'playlists' => $playlists,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table,
            'champ' => $champ,
        ]);
    }

    /**
     * Affiche le détail d'une playlist avec ses formations et catégories.
     * @param int $id
     * @return Response
     */
    #[Route('/playlists/playlist/{id}', name: 'playlists.showone')]
    public function showOne($id): Response
    {
        $playlist = $this->playlistRepository->find($id);
        $playlistCategories = $this->categorieRepository->findAllForOnePlaylist($id);
        $playlistFormations = $this->formationRepository->findAllForOnePlaylist($id);
        return $this->render("pages/playlist.html.twig", [
            'playlist' => $playlist,
            'playlistcategories' => $playlistCategories,
            'playlistformations' => $playlistFormations
        ]);
    }

}
