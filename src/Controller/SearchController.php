<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use whatwedo\SearchBundle\Manager\SearchManager;
use whatwedo\SearchBundle\Traits\SearchTrait;

class SearchController extends AbstractController
{
    use SearchTrait;

    public function searchAction(Request $request, SearchManager $searchManager): Response
    {
        $templateParams = $this->getGlobalResults($request, $searchManager);

        return $this->render($this->getSearchTemplate(), $templateParams);
    }
}
