<?php
namespace App\Controller\Admin;
use App\Entity\User; // Ajoute cette ligne en haut du fichier

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('ARTOUNSI');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
    }
    #[Route('/admin/test', name: 'admin_test')]

    public function testAdmin(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
    
        if (!$user) {
            return new Response("❌ Aucun utilisateur connecté !");
        }
    
        return new Response("✅ Connecté en tant que : " . $user->getEmail() . "<br>Rôles : " . implode(", ", $user->getRoles()));
    }
    

}
