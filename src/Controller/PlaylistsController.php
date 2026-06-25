<?php
namespace App\Controller;

use App\Entity\Inscription;
use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use App\Repository\PlaylistRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    private $playlistRepository;
    private $formationRepository;
    private $categorieRepository;
    private $inscriptionRepository;
    private $entityManager;

    public function __construct(
        PlaylistRepository $playlistRepository,
        CategorieRepository $categorieRepository,
        FormationRepository $formationRespository,
        InscriptionRepository $inscriptionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->playlistRepository = $playlistRepository;
        $this->categorieRepository = $categorieRepository;
        $this->formationRepository = $formationRespository;
        $this->inscriptionRepository = $inscriptionRepository;
        $this->entityManager = $entityManager;
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

    #[Route('/playlists/playlist/{id}', name: 'playlists.showone')]
    public function showOne($id): Response
    {
        $playlist = $this->playlistRepository->find($id);
        $playlistCategories = $this->categorieRepository->findAllForOnePlaylist($id);
        $playlistFormations = $this->formationRepository->findAllForOnePlaylist($id);

        $inscription = null;
        if ($this->getUser()) {
            $inscription = $this->inscriptionRepository->findOneBy([
                'user' => $this->getUser(),
                'playlist' => $playlist,
            ]);
        }

        return $this->render("pages/playlist.html.twig", [
            'playlist' => $playlist,
            'playlistcategories' => $playlistCategories,
            'playlistformations' => $playlistFormations,
            'inscription' => $inscription,
        ]);
    }

    #[Route('/playlists/playlist/{id}/desinscription', name: 'playlists.desinscription', methods: ['POST'])]
    public function desinscription(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $playlist = $this->playlistRepository->find($id);
        if (!$playlist) {
            throw $this->createNotFoundException('Playlist introuvable.');
        }

        $inscription = $this->inscriptionRepository->findOneBy([
            'user' => $this->getUser(),
            'playlist' => $playlist,
        ]);

        if ($inscription) {
            $this->entityManager->remove($inscription);
            $this->entityManager->flush();
            $this->addFlash('success', 'Désinscription effectuée.');
        }

        return $this->redirectToRoute('playlists.showone', ['id' => $id]);
    }

    #[Route('/playlists/playlist/{id}/inscrire', name: 'playlists.inscrire', methods: ['POST'])]
    public function inscrire(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $playlist = $this->playlistRepository->find($id);
        if (!$playlist) {
            throw $this->createNotFoundException('Playlist introuvable.');
        }

        // Vérifie l'unicité avant persist (double protection avec la contrainte UNIQUE BDD)
        $existante = $this->inscriptionRepository->findOneBy([
            'user' => $this->getUser(),
            'playlist' => $playlist,
        ]);

        if (!$existante) {
            $inscription = new Inscription();
            $inscription->setUser($this->getUser());
            $inscription->setPlaylist($playlist);
            $this->entityManager->persist($inscription);
            $this->entityManager->flush();
            $this->addFlash('success', 'Inscription enregistrée.');
        }

        return $this->redirectToRoute('playlists.showone', ['id' => $id]);
    }

}
