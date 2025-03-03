<?php

namespace App\Service;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    private PaginatorInterface $paginator;

    public function __construct(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
    }

    public function paginate(Request $request, $query, int $limit = 10)
    {
        $page = max(1, $request->query->getInt('page', 1));

        return $this->paginator->paginate(
            $query,  // La requête à paginer
            $page,   // Numéro de la page actuelle
            $limit   // Nombre d'éléments par page
        );
    }
}
