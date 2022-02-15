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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;
use whatwedo\CrudBundle\Content\EditableContentInterface;
use whatwedo\CrudBundle\Content\RelationContent;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Encoder\CsvEncoder;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Event\CrudEvent;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\Normalizer\ObjectNormalizer;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\ActionColumn;
use whatwedo\TableBundle\Table\DoctrineTable;

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
     * @var Environment
     */
    protected $templating;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RouterInterface
     */
    protected $router;

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
     */
    public function __construct(Environment $templating, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher, RouterInterface $router, DefinitionManager $definitionManager, TableFactory $tableFactory)
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
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGrantedCrud(RouteEnum::INDEX, $this->getDefinition());

        $table = $this->tableFactory
            ->createDoctrineTable('index', [
                'query_builder' => $this->getDefinition()->getQueryBuilder(),
            ]);

        $this->configureTable($table);

        $this->definition->buildBreadcrumbs(null, RouteEnum::INDEX);

        return $this->render(
            $this->getView('index.html.twig'),
            $this->getIndexParameters(
                [
                    'view' => $this->getDefinition()->createView(),
                    'table' => $table,
                    'title' => $this->getDefinition()->getTitle(null, RouteEnum::INDEX),
                    'voter_entity' => $this->getDefinition(),
                ]
            )
        );
    }

    /**
     * @return Response
     */
    public function showAction(Request $request)
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(RouteEnum::SHOW, $entity);

        $this->dispatchEvent(CrudEvent::PRE_SHOW_PREFIX, $entity);

        $this->definition->buildBreadcrumbs($entity, RouteEnum::SHOW);

        return $this->render(
            $this->getView('show.html.twig'),
            $this->getShowParameters(
                $entity,
                [
                    'view' => $this->getDefinition()->createView($entity),
                    'title' => $this->getDefinition()->getTitle($entity, RouteEnum::SHOW),
                ]
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request)
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(RouteEnum::EDIT, $entity);

        $view = $this->getDefinition()->createView($entity);

        $form = $view->getEditForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->dispatchEvent(CrudEvent::PRE_EDIT_PREFIX, $entity);
                $this->getDoctrine()->getManager()->flush();
                $this->dispatchEvent(CrudEvent::POST_EDIT_PREFIX, $entity);

                $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));

                return $this->getDefinition()->getEditRedirect($this->router, $entity);
            }
            $this->addFlash('danger', sprintf('Beim Speichern ist ein Fehler aufgetreten. Bitte überprüfe deine Eingaben.'));
        }

        $this->definition->buildBreadcrumbs($entity, RouteEnum::EDIT);

        return $this->render(
            $this->getView('edit.html.twig'),
            $this->getEditParameters(
                $entity,
                [
                    'view' => $view,
                    'title' => $this->getDefinition()->getTitle($entity, RouteEnum::EDIT),
                ]
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGrantedCrud(RouteEnum::CREATE, $this->getDefinition());

        $className = $this->getDefinition()->getEntity();
        $entity = new $className();

        $this->dispatchEvent(CrudEvent::NEW_PREFIX, $entity);

        $view = $this->getDefinition()->createView($entity);

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
                                $this->router->generate(
                                    call_user_func([$content->getPreselectDefinition(), 'getRouteName'], RouteEnum::SHOW),
                                    ['id' => $value->getId()]
                                );
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

                return $this->getDefinition()->getCreateRedirect($this->router, $entity);
            }
            $this->addFlash('danger', 'Beim Speichern ist ein Fehler aufgetreten. Bitte überprüfe deine Eingaben.');
        }

        $this->definition->buildBreadcrumbs(null, RouteEnum::CREATE);

        return $this->render($this->getView('create.html.twig'), $this->getCreateParameters([
            'view' => $view,
            'title' => $this->getDefinition()->getTitle(null, RouteEnum::CREATE),
        ]));
    }

    /**
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(RouteEnum::DELETE, $entity);

        try {
            $this->getDoctrine()->getManager()->remove($entity);
            $this->dispatchEvent(CrudEvent::PRE_DELETE_PREFIX, $entity);
            $this->getDoctrine()->getManager()->flush($entity);
            $this->dispatchEvent(CrudEvent::POST_DELETE_PREFIX, $entity);
            $this->addFlash('success', 'Eintrag erfolgreich gelöscht.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Eintrag konnte nicht gelöscht werden: ' . $e->getMessage());
            $this->logger->warning('Error while deleting: ' . $e->getMessage(), [
                'entity' => get_class($entity),
                'id' => $entity->getId(),
            ]);
        }

        return $this->getDefinition()->getDeleteRedirect($this->router, $entity);
    }

    /**
     * @return Response
     */
    public function exportAction(Request $request)
    {
        $this->denyAccessUnlessGrantedCrud(RouteEnum::EXPORT, $this->getDefinition());

        $entities = $this->getExportEntities($request);
        if (!$entities) {
            $this->addFlash('warning', 'Nichts zu exportieren');
            return $this->redirectToRoute($this->getDefinition()::getRouteName(RouteEnum::INDEX));
        }

        $objectNormalizer = new ObjectNormalizer($this->definition);
        $objectNormalizer->setCustomCallbacks($this->definition->getExportCallbacks());
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

    public static function convertToWindowsCharset($string)
    {
        $charset =  mb_detect_encoding(
            $string,
            'UTF-8, ISO-8859-1, ISO-8859-15',
            true
        );

        $string =  mb_convert_encoding($string, 'Windows-1252', $charset);
        return $string;
    }

    /**
     * @return Response
     */
    public function ajaxAction(Request $request)
    {
        $this->denyAccessUnlessGrantedCrud(RouteEnum::AJAX, $this->getDefinition());

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
        if ($this->templating->getLoader()->exists($this->getDefinition()->getTemplateDirectory() . '/' . $file)) {
            return $this->getDefinition()->getTemplateDirectory() . '/' . $file;
        }

        return '@whatwedoCrud/Crud/' . $file;
    }

    public function getActionColumnItems($row)
    {
        $targetDefinition = $this->definitionManager->getDefinitionFor($row);

        $actionColumnItems = [];

        if ($targetDefinition->hasCapability(RouteEnum::SHOW)) {
            $actionColumnItems[RouteEnum::SHOW] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => $targetDefinition::getRouteName(RouteEnum::SHOW),
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::SHOW,
            ];
        }

        if ($targetDefinition->hasCapability(RouteEnum::EDIT)) {
            $actionColumnItems[RouteEnum::EDIT] = [
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

    public function getShowRoute($row)
    {
        $targetDefinition = $this->definitionManager->getDefinitionFor($row);

        return $targetDefinition::getRouteName(RouteEnum::SHOW);
    }

    /**
     * @param $event
     * @param $entity
     */
    public function dispatchEvent($event, $entity)
    {
        $this->eventDispatcher->dispatch(
            new CrudEvent($entity),
            $event
        );

        $this->eventDispatcher->dispatch(
            new CrudEvent($entity),
            $event . '.' . $this->getDefinition()::getAlias()
        );
    }

    /**
     * @return DefinitionInterface
     */
    protected function getDefinition()
    {
        return $this->definition;
    }

    protected function getIndexParameters($parameters = [])
    {
        return $parameters;
    }

    protected function getShowParameters($entity, $parameters = [])
    {
        return $parameters;
    }

    protected function getEditParameters($entity, $parameters = [])
    {
        return $parameters;
    }

    protected function getCreateParameters($parameters = [])
    {
        return $parameters;
    }

    /**
     * configures list table
     *
     * @param DoctrineTable $table
     */
    protected function configureTable($table)
    {
        $this->getDefinition()->configureTable($table);

        if ($this->getDefinition()->hasCapability(RouteEnum::SHOW)) {
            $table->setShowRoute([$this, 'getShowRoute']);
        }

        if ($this->getDefinition()->hasCapability(RouteEnum::EXPORT)) {
            $table->setExportRoute($this->getDefinition()::getRouteName(RouteEnum::EXPORT));
        }

        // this is normally the main table of the page, so we're fixing the header
        $table->setOption('table_attr', array_merge($table->getOption('table_attr'), [
            'data-fixed-header' => true,
        ]));

        $table->addColumn('actions', ActionColumn::class, [
            'items' => [$this, 'getActionColumnItems'],
        ]);

        $this->getDefinition()->overrideTableConfiguration($table);
    }

    /**
     * returns the required entity
     *
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

    /**
     * @return array
     * @throws \whatwedo\TableBundle\Exception\DataLoaderNotAvailableException
     */
    protected function getExportEntities(Request $request)
    {
        $export = $request->query->get('export') ?: [];
        if (isset($export['definition']) && isset($export['acronym']) && isset($export['class']) && isset($export['id'])
            && ($definition = $this->definitionManager->getDefinitionFromClass($export['definition']))
            && ($content = $definition->getContent($export['acronym']))
            && $content instanceof RelationContent
            && ($repository = $this->getDoctrine()->getRepository($export['class']))
            && ($row = $repository->find($export['id']))
        ) {
            $table = $content->getTable($export['acronym'], $row);
        } else {
            $table = $this->tableFactory
                ->createDoctrineTable('index', [
                    'query_builder' => $this->getDefinition()->getQueryBuilder(),
                ]);

            // to respect column sort order
            $this->getDefinition()->configureTable($table);
            $this->getDefinition()->overrideTableConfiguration($table);
        }

        $table->loadData();

        return $table->getResults();
    }

    protected function getIdentifierColumn()
    {
        return sprintf(
            '%s.%s',
            $this->getDefinition()::getQueryAlias(),
            $this->getDefinition()->getQueryBuilder()->getEntityManager()->getClassMetadata($this->getDefinition()::getEntity())->identifier[0]
        );
    }

    protected function redirectToCapability(string $capability, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirectToDefinitionObject($this->definition, $capability, $parameters, $status);
    }

    protected function redirectToDefinition(string $definitionClass, string $capability, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirectToDefinitionObject($this->definitionManager->getDefinitionFromClass($definitionClass), $capability, $parameters, $status);
    }

    /**
     * @param $attributes
     * @param null $subject
     */
    protected function denyAccessUnlessGrantedCrud($attributes, $subject = null, string $message = 'Access Denied.')
    {
        if (!$this->getUser()) {
            return;
        }
        $this->denyAccessUnlessGranted($attributes, $subject, $message);
    }

    private function redirectToDefinitionObject(DefinitionInterface $definition, string $capability, array $parameters = [], int $status = 302): RedirectResponse
    {
        $route = $definition::getRouteName($capability);
        return $this->redirectToRoute($route, $parameters, $status);
    }
}
