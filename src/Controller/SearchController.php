<?php

namespace whatwedo\CrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use whatwedo\SearchBundle\Manager\SearchManager;
use whatwedo\SearchBundle\Trait\SearchTrait;

class SearchController extends AbstractController
{
    use SearchTrait;
    
    public function search(Request $request, SearchManager $searchManager): Response
    {
        $templateParams = $this->getGlobalResults($request, $searchManager);

        return $this->render($this->getSearchTemplate(), $templateParams);
    }
}
