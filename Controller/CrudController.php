<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Serializer;
use whatwedo\CrudBundle\Content\EditableContentInterface;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Encoder\CsvEncoder;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Event\CrudEvent;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\Normalizer\ObjectNormalizer;
use whatwedo\CrudBundle\View\DefinitionViewInterface;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\ActionColumn;
use whatwedo\TableBundle\Table\DoctrineTable;


/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class CrudController extends AbstractController implements CrudDefinitionController
{
    protected $twigParametersIndex = [];
    protected $twigParametersShow = [];
    protected $twigParametersEdit = [];

    /**
     * @var DefinitionInterface|AbstractDefinition
     */
    protected $definition;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RouterInterface
     */
    protected  $router;

    /**
     * @var DefinitionManager
     */
    protected $definitionManager;
    
    /**
     * @var TableFactory
     */
    protected $tableFactory;

    /**
     * CrudController constructor.
     * @param EngineInterface $templating
     * @param LoggerInterface $logger
     */
    public function __construct(EngineInterface $templating, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher, RouterInterface $router, DefinitionManager $definitionManager, TableFactory $tableFactory)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
        $this->templating = $templating;
        $this->logger = $logger;
        $this->definitionManager = $definitionManager;
        $this->tableFactory = $tableFactory;
    }

    public function configureDefinition(DefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return DefinitionInterface
     */
    protected function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(RouteEnum::INDEX, $this->getDefinition());

        $table = $this->tableFactory
            ->createDoctrineTable('index', [
                'query_builder' => $this->getDefinition()->getQueryBuilder()
            ]);

        $this->configureTable($table);

        $this->definition->buildBreadcrumbs(null, RouteEnum::INDEX);

        return $this->render($this->getView('index.html.twig'), [
            'view' => $this->getDefinition()->createView(),
            'table' => $table,
            'title' => $this->getDefinition()->getTitle(null, RouteEnum::INDEX),
            'voter_entity' => $this->getDefinition(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function showAction(Request $request)
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGranted(RouteEnum::SHOW, $entity);

        $this->dispatchEvent(CrudEvent::PRE_SHOW_PREFIX, $entity);

        $this->definition->buildBreadcrumbs($entity, RouteEnum::SHOW);

        return $this->render($this->getView('show.html.twig'), $this->getShowParameters($entity, [
            'view' => $this->getDefinition()->createView($entity),
            'title' => $this->getDefinition()->getTitle($entity, RouteEnum::SHOW),
        ]));
    }

    protected function getShowParameters($entity, $parameters = [])
    {
        return $parameters;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request)
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGranted(RouteEnum::EDIT, $entity);

        $view = $this->getDefinition()->createView($entity);

        $form = $view->getEditForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->dispatchEvent(CrudEvent::PRE_EDIT_PREFIX, $entity);
                $this->getDoctrine()->getManager()->flush();
                $this->dispatchEvent(CrudEvent::POST_EDIT_PREFIX, $entity);

                $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));

                return $this->redirectToRoute($this->getDefinition()::getRouteName(RouteEnum::SHOW), [
                    'id' => $entity->getId(),
                ]);
            } else {
                $this->addFlash('danger', sprintf('Beim Speichern ist ein Fehler aufgetreten. Bitte überprüfe deine Eingaben.'));
            }
        }

        $this->definition->buildBreadcrumbs($entity, RouteEnum::EDIT);

        return $this->render($this->getView('edit.html.twig'), $this->getEditParameters($entity, [
            'view' => $view,
            'title' => $this->getDefinition()->getTitle($entity, RouteEnum::EDIT),
        ]));
    }

    protected function getEditParameters($entity, $parameters = [])
    {
        return $parameters;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(RouteEnum::CREATE, $this->getDefinition());

        $className = $this->getDefinition()->getEntity();
        $entity = new $className;

        $view = $this->getDefinition()->createView($entity);
        $uri = null;

        if ($request->isMethod('get') || $request->isMethod('post')) {

            // set preselected entities
            $propertyAccessor = PropertyAccess::createPropertyAccessor();

            foreach ($view->getBlocks() as $block) {
                foreach ($block->getContents() as $content) {
                    if ($content instanceof EditableContentInterface
                        && $content->getPreselectDefinition()) {
                        $queryParameter = call_user_func([$content->getPreselectDefinition(), 'getAlias']);

                        if ($queryParameter
                            && $request->query->has($queryParameter)) {
                            $value = $this->getDoctrine()
                                ->getRepository(call_user_func([$content->getPreselectDefinition(), 'getEntity']))
                                ->find($request->query->getInt($queryParameter));

                            if (!is_null($value)) {
                                $uri = $this->router->generate(
                                        call_user_func([$content->getPreselectDefinition(), 'getRouteName'], RouteEnum::SHOW),
                                    ['id' => $value->getId()]);
                            }
                            if (!$propertyAccessor->getValue($entity, $content->getOption('accessor_path'))
                                && $request->isMethod('get')) {
                                $propertyAccessor->setValue($entity, $content->getOption('accessor_path'), $value);
                            }
                        }
                    }
                }
            }
        }

        $this->dispatchEvent(CrudEvent::CREATE_SHOW_PREFIX, $entity);

        $form = $view->getCreateForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->dispatchEvent(CrudEvent::PRE_VALIDATE_PREFIX, $entity);
            if ($form->isValid()) {
                $this->dispatchEvent(CrudEvent::POST_VALIDATE_PREFIX, $entity);
                $this->dispatchEvent(CrudEvent::PRE_CREATE_PREFIX, $entity);

                $objectManager = $this->getDoctrine()->getManager();
                $objectManager->persist($entity);
                $objectManager->flush();

                $this->dispatchEvent(CrudEvent::POST_CREATE_PREFIX, $entity);

                $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));

                if(isset($redirectPath) && $redirectPath) {
                    return $this->redirect($redirectPath);
                }
                else {
                    return $this->redirectToRoute($this->getDefinition()::getRouteName(RouteEnum::SHOW), [
                        'id' => $entity->getId(),
                    ]);
                }
            } else {
                $this->addFlash('danger', 'Beim Speichern ist ein Fehler aufgetreten. Bitte überprüfe deine Eingaben.');
            }
        }

        $this->definition->buildBreadcrumbs(null, RouteEnum::CREATE);

        return $this->render($this->getView('create.html.twig'), $this->getCreateParameters([
            'view' => $view,
            'title' => $this->getDefinition()->getTitle(null, RouteEnum::CREATE),
        ]));
    }

    protected function getCreateParameters($parameters = [])
    {
        return $parameters;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGranted(RouteEnum::DELETE, $entity);

        try {
            $this->getDoctrine()->getManager()->remove($entity);
            $this->dispatchEvent(CrudEvent::PRE_DELETE_PREFIX, $entity);
            $this->getDoctrine()->getManager()->flush($entity);
            $this->dispatchEvent(CrudEvent::POST_DELETE_PREFIX, $entity);
            $this->addFlash('success', 'Eintrag erfolgreich gelöscht.');
        } catch (\Exception $e) {
            $this->addFlash('error', sprintf('Eintrag konnte nicht gelöscht werden: ' . $e->getMessage()));
            $this->logger->warning('Error while deleting: ' . $e->getMessage(), [
                'entity' => get_class($entity),
                'id' => $entity->getId(),
            ]);
        }

        return $this->getDefinition()->getDeleteRedirect($this->router, $entity);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function exportAction(Request $request)
    {
        $this->denyAccessUnlessGranted(RouteEnum::EXPORT, $this->getDefinition());

        $entities = $this->getExportEntities();
        if (!$entities) {
            $this->addFlash('warning', 'Nichts zu exportieren');
            return $this->redirectToRoute($this->getDefinition()::getRouteName(RouteEnum::INDEX));
        }

        $objectNormalizer = new ObjectNormalizer($this->definition);
        $objectNormalizer->setCustomCallbacks($this->definition->getExportCallbacks());
        $objectNormalizer->setCircularReferenceHandler(function ($obj) {
            return $obj->__toString();
        });
        $exportOptions = $this->definition->getExportOptions()['csv'];
        $csvEncoder = new CsvEncoder($exportOptions['delimiter'], $exportOptions['enclosure'], $exportOptions['escapeChar'], $exportOptions['keySeparator']);
        /** @var Serializer $serializer */
        $serializer = new Serializer([$objectNormalizer], [$csvEncoder->setHeaderTransformation($this->definition->getExportHeaders())]);
        $normalized = $serializer->normalize($entities);
        $csv = $serializer->encode($normalized, 'csv');
        $csv = static::convertToWindowsCharset($csv);
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');
        return $response;
    }

    public static function convertToWindowsCharset($string) {
        $charset =  mb_detect_encoding(
            $string,
            "UTF-8, ISO-8859-1, ISO-8859-15",
            true
        );

        $string =  mb_convert_encoding($string, "Windows-1252", $charset);
        return $string;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function ajaxAction(Request $request)
    {
        $this->denyAccessUnlessGranted(RouteEnum::AJAX, $this->getDefinition());

        $data = [];
        foreach ($request->request->get('data') as $pair) {
            $data[$pair['key']] = $pair['value'];
        }
        $obj = $this->definition->ajaxOnDataChanged($data);
        $response = new Response(json_encode($obj));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    /**
     * get specific view for a definition
     *
     * @param string $file file name
     * @return string
     */
    public function getView($file)
    {
        if ($this->templating->exists($this->getDefinition()->getTemplateDirectory() . '/' . $file)) {
            return $this->getDefinition()->getTemplateDirectory() . '/' . $file;
        }

        return '@whatwedoCrud/Crud/' . $file;
    }

    public function getActionColumnItems($row) {
        $targetDefinition = $this->definitionManager->getDefinitionFor($row);

        $actionColumnItems = [];

        if ($targetDefinition->hasCapability(RouteEnum::SHOW)) {
            $actionColumnItems[] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => $targetDefinition::getRouteName(RouteEnum::SHOW),
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::SHOW,
            ];
        }

        if ($targetDefinition->hasCapability(RouteEnum::EDIT)) {
            $actionColumnItems[] = [
                'label' => 'Bearbeiten',
                'icon' => 'pencil',
                'button' => 'warning',
                'route' => $targetDefinition::getRouteName(RouteEnum::EDIT),
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::EDIT,
            ];
        }

        return $actionColumnItems;
    }

    public function getShowRoute($row) {
        $targetDefinition = $this->definitionManager->getDefinitionFor($row);

        return $targetDefinition::getRouteName(RouteEnum::SHOW);
    }

    /**
     * configures list table
     *
     * @param DoctrineTable $table
     */
    protected function configureTable(DoctrineTable $table)
    {
        $this->getDefinition()->configureTable($table);

        if ($this->getDefinition()->hasCapability(RouteEnum::SHOW)) {
            $table->setShowRoute([$this, 'getShowRoute']);
        }

        if ($this->getDefinition()->hasCapability(RouteEnum::EXPORT)) {
            $table->setExportRoute($this->getDefinition()::getRouteName(RouteEnum::EXPORT));
        }

        // this is normally the main table of the page, so we're fixing the header
        $table->setOption('table_attr', [
            'data-fixed-header' => true
        ]);

        $table->addColumn('actions', ActionColumn::class, [
            'items' => [$this, 'getActionColumnItems'],
        ]);

        $this->getDefinition()->overrideTableConfiguration($table);
    }

    /**
     * returns the required entity
     *
     * @param Request $request
     * @return object
     * @throws NotFoundHttpException
     */
    protected function getEntityOr404(Request $request)
    {
        try {
            return $this->getDefinition()->getQueryBuilder()
                ->andWhere($this->getIdentifierColumn() . ' = :id')
                ->setParameter('id', $request->attributes->getInt('id'))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException|NonUniqueResultException $e) {
            throw new NotFoundHttpException(sprintf(
                'Der gewünschte Datensatz existiert in %s nicht.',
                $this->getDefinition()->getEntity()
            ));
        }
    }

    protected function getExportEntities()
    {
        $table = $this->tableFactory
            ->createDoctrineTable('index', [
                'query_builder' => $this->getDefinition()->getQueryBuilder()
            ]);

        // to respect column sort order
        $this->getDefinition()->configureTable($table);
        $this->getDefinition()->overrideTableConfiguration($table);

        $table->loadData();

        return $table->getResults();
    }

    public function dispatchEvent($event, $entity)
    {
        $this->eventDispatcher->dispatch(
            $event,
            new CrudEvent($entity)
        );

        $this->eventDispatcher->dispatch(
            $event . '.' . $this->getDefinition()::getAlias(),
            new CrudEvent($entity)
        );
    }

    protected function getIdentifierColumn()
    {
        return sprintf('%s.%s',
            $this->getDefinition()::getQueryAlias(),
            $this->getDefinition()->getQueryBuilder()->getEntityManager()->getClassMetadata($this->getDefinition()::getEntity())->identifier[0]
        );
    }

    protected function redirectToCapability(string $capability, array $parameters = array(), int $status = 302): RedirectResponse {
        return $this->redirectToDefinitionObject($this->definition, $capability, $parameters, $status);
    }

    protected function redirectToDefinition(string $definitionClass, string $capability, array $parameters = array(), int $status = 302): RedirectResponse {
        return $this->redirectToDefinitionObject($this->definitionManager->getDefinitionFromClass($definitionClass), $capability, $parameters, $status);
    }

    private function redirectToDefinitionObject(DefinitionInterface $definition, string $capability, array $parameters = array(), int $status = 302): RedirectResponse {
        $route = $definition::getRouteName($capability);
        return $this->redirectToRoute($route, $parameters, $status);
    }
}
