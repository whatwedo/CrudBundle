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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use whatwedo\CoreBundle\Controller\BaseController;
use whatwedo\CrudBundle\Content\EditableContentInterface;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Event\CrudEvent;
use whatwedo\CrudBundle\Normalizer\WhatwedoObjectNormalizer;
use whatwedo\TableBundle\Table\ActionColumn;
use whatwedo\TableBundle\Table\Table;
use Symfony\Component\Serializer\Serializer;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;


/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class CrudController extends BaseController implements CrudDefinitionController
{
    protected $twigParametersIndex = [];
    protected $twigParametersShow = [];
    protected $twigParametersEdit = [];

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var RequestStack|null
     */
    protected $requestStack;

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
    public function indexAction()
    {
        $table = $this->get('whatwedo_table.table')
            ->setQueryBuilder($this->getDefinition()->getQueryBuilder());

        $this->configureTable($table);
        $this->definition->overrideTableConfiguration($table);
        $this->checkQueryBuilder($table);

        $this->definition->buildBreadcrumbs(null, RouteEnum::INDEX);

        return $this->render($this->getView('index.html.twig'), $this->getIndexParameters([
            'view' => $this->getDefinition()->createView(),
            'table' => $table,
            'title' => $this->getDefinition()->getTitle(null, RouteEnum::INDEX),
        ]));
    }

    /**
     * @param Table $table
     * @throws \Exception
     * checks if OrderEventListener can order all columns
     */
    protected function checkQueryBuilder(Table $table)
    {
        $alias = count($table->getQueryBuilder()->getRootAliases()) > 0 ? $table->getQueryBuilder()->getRootAliases()[0] : '';
        foreach ($table->getColumns()->toArray() as $column)
        {
            if ($column->isSortableColumn() && $column->isSortable()) {
                $sortExp = strpos($column->getSortExpression(), '.') !== false
                    ? $column->getSortExpression()
                    : sprintf('%s.%s', $alias, $column->getSortExpression());
                if (!in_array(explode('.', $sortExp)[0], $table->getQueryBuilder()->getAllAliases())) {
                    $notFound = explode('.', $sortExp)[0];
                    throw new \Exception(sprintf('"%s" is not defined in querybuilder. Please override getQueryBuilder in your definition and add a join. For example: %s%s ->leftJoin(sprintf(\'%%s.%s\', self::getQueryAlias()), \'%s\')', $notFound, PHP_EOL, PHP_EOL, $notFound, $notFound));
                }
            }
        }
    }

    protected function getIndexParameters($parameters = [])
    {
        return $parameters;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function showAction(Request $request)
    {
        $entity = $this->getEntityOr404($request);

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
        if (!$this->definition->allowEdit($entity)) {
            $this->addFlash('danger', 'Aktion nicht erlaubt!');
            return $this->redirectToRoute(get_class($this->definition)::getRoutePrefix() . '_' . RouteEnum::INDEX);
        }
        $view = $this->getDefinition()->createView($entity);

        $form = $view->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->dispatchEvent(CrudEvent::PRE_EDIT_PREFIX, $entity);
                $this->getDoctrine()->getManager()->flush();
                $this->dispatchEvent(CrudEvent::POST_EDIT_PREFIX, $entity);

                $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));

                return $this->redirectToRoute($this->getDefinition()->getRoutePrefix() . '_show', [
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
        if (!$this->getDefinition()->allowCreate()) {
            $this->addFlash('danger', 'Aktion ist nicht erlaubt!');
            return $this->redirectToRoute(get_class($this->definition)::getRoutePrefix() . '_' . RouteEnum::INDEX);
        }
        $entityName = $this->getDefinition()->getEntity();
        $entity = new $entityName;

        $view = $this->getDefinition()->createView($entity);
        $uri = null;

        if ($request->isMethod('get') || $request->isMethod('post')) {

            // set preselected entities
            $propertyAccessor = PropertyAccess::createPropertyAccessor();

            foreach ($view->getBlocks() as $block) {
                foreach ($block->getContents() as $content) {
                    if ($content instanceof EditableContentInterface
                        && $content->getPreselectDefinition()) {
                        $queryParameter = call_user_func([$content->getPreselectDefinition(), 'getChildRouteAddition']);

                        if ($queryParameter
                            && $request->query->has($queryParameter)) {
                            $value = $this->getDoctrine()
                                ->getRepository(call_user_func([$content->getPreselectDefinition(), 'getEntity']))
                                ->find($request->query->getInt($queryParameter));

                            if (!is_null($value)) {
                                $uri = $this->get('router')->generate(
                                    sprintf(
                                        '%s_%s',
                                        call_user_func([$content->getPreselectDefinition(), 'getRoutePrefix']),
                                        RouteEnum::SHOW),
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

        $form = $view->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->dispatchEvent(CrudEvent::PRE_VALIDATE_PREFIX, $entity);
            if ($form->isValid()) {
                $this->dispatchEvent(CrudEvent::POST_VALIDATE_PREFIX, $entity);
                $this->dispatchEvent(CrudEvent::PRE_CREATE_PREFIX, $entity);
                $this->getDoctrine()->getManager()->persist($entity);
                $this->getDoctrine()->getManager()->flush();
                $this->dispatchEvent(CrudEvent::POST_CREATE_PREFIX, $entity);


                if (is_null($uri)) {
                    $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));
                    return $this->redirectToRoute($this->getDefinition()->getRoutePrefix() . '_show', [
                        'id' => $entity->getId(),
                    ]);
                } else {
                    $this->addFlash('success', sprintf('Erfolgreich hinzugefügt.'));
                    return $this->redirect($uri);
                }
            } else {
                $this->addFlash('danger', sprintf('Beim Speichern ist ein Fehler aufgetreten. Bitte überprüfe deine Eingaben.'));
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
        if (!$this->definition->allowDelete($entity)) {
            $this->addFlash('danger', 'Aktion nicht erlaubt!');
            return $this->redirectToRoute(get_class($this->definition)::getRoutePrefix() . '_' . RouteEnum::INDEX);
        }
        $redirect = $this->getDefinition()->getDeleteRedirect($this->get('router'), $entity);

        if (!$this->definition->allowDelete($entity)) {
            $this->addFlash('error', sprintf('Eintrag darf nicht gelöscht werden.'));
        } else {
            try {
                $this->getDoctrine()->getManager()->remove($entity);
                $this->getDoctrine()->getManager()->flush($entity);
                $this->addFlash('success', sprintf('Eintrag erfolgreich gelöscht.'));
            } catch (\Exception $e) {
                $this->addFlash('error', sprintf('Eintrag konnte nicht gelöscht werden: ' . $e->getMessage()));
                $this->getLogger()->warning('Error while deleting: ' . $e->getMessage(), [
                    'entity' => get_class($entity),
                    'id' => $entity->getId(),
                ]);
            }
        }

        return $redirect;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function exportAction(Request $request)
    {
        $entities = $this->getEntities($request);
        if (!isset($entities[0])) {
            $this->addFlash('warning', 'Nichts zu exportieren');
            return $this->redirectToRoute($this->getDefinition()->getRoutePrefix() . '_' . RouteEnum::INDEX);
        }

        $objNormalizer = new WhatwedoObjectNormalizer($this->definition);
        $objNormalizer->setCallbacks($this->definition->getExportCallbacks());
        $objNormalizer->setCircularReferenceHandler(function ($obj) {
            return $obj->__toString();
        });
        /** @var Serializer $serializer */
        $serializer = new Serializer([$objNormalizer], [new CsvEncoder(';')]);
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
        $data = [];
        foreach ($request->request->get('data') as $pair) {
            $data[$pair['key']] = $pair['value'];
        }
        $obj = $this->definition->ajaxOnDataChanged($data);
        if (is_null($obj)) {
            // try deprecated
            $obj = $this->definition->ajaxOnChange($request);
        }
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
        if ($this->getTemplating()->exists($this->getDefinition()->getTemplateDirectory() . ':' . $file)) {
            return $this->getDefinition()->getTemplateDirectory() . ':' . $file;
        }

        // fallback if it does not exist
        return 'whatwedoCrudBundle:Crud:' . $file;
    }

    /**
     * configures list table
     *
     * @param Table $table
     */
    protected function configureTable(Table $table)
    {
        $this->getDefinition()->configureTable($table);

        $actionColumnItems = [];

        if ($this->getDefinition()->hasCapability(RouteEnum::SHOW)) {
            $table->setRowRoute(sprintf('%s_%s', $this->getDefinition()->getRoutePrefix(), RouteEnum::SHOW));

            $actionColumnItems[] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => sprintf('%s_%s', $this->getDefinition()->getRoutePrefix(), RouteEnum::SHOW),
            ];
        }

        if ($this->getDefinition()->hasCapability(RouteEnum::EXPORT)) {
            $table->setExportRoute(sprintf('%s_%s', $this->getDefinition()->getRoutePrefix(), RouteEnum::EXPORT));
        }

        if ($this->getDefinition()->hasCapability(RouteEnum::EDIT)) {
            $actionColumnItems[] = [
                'label' => 'Bearbeiten',
                'icon' => 'pencil',
                'button' => 'warning',
                'route' => sprintf('%s_%s', $this->getDefinition()->getRoutePrefix(), RouteEnum::EDIT),
            ];
        }

        $reflection = new \ReflectionClass(get_class($this->getDefinition()));
        $allowEdit = $reflection->getMethod('allowEdit')->getClosure($this->getDefinition());
        $table->addColumn('actions', ActionColumn::class, [
            'items' => $actionColumnItems,
            'showActionColumn' => [
                sprintf('%s_%s', $this->getDefinition()->getRoutePrefix(), RouteEnum::EDIT) => $allowEdit
            ]
        ]);
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
        $entity = $this->getDefinition()->getRepository()->find($request->attributes->getInt('id'));

        if (!$entity) {
            throw new NotFoundHttpException(sprintf(
                'Der gewünschte Datensatz existiert in %s nicht.',
                $this->getDefinition()->getEntity()
            ));
        }

        return $entity;
    }

    protected function getEntities(Request $request)
    {
        $ids = $request->query->get('ids');
        if (count($ids) > 0) {
            if ($ids[0] == -1) {
                return $this->getDefinition()->getRepository()->findAll();
            } else {
                return $this->getDefinition()->getRepository()->createQueryBuilder('xx')
                    ->where('xx.id in (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()->getResult();
            }
        } else {
            return [];
        }
    }

    public function dispatchEvent($event, $entity)
    {
        $this->getEventDispatcher()->dispatch(
            $event,
            new CrudEvent($entity)
        );

        $this->getEventDispatcher()->dispatch(
            $event . '.' . $this->getDefinition()->getAlias(),
            new CrudEvent($entity)
        );
    }

    /**
     * @return \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher|EventDispatcherInterface|\Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher
     */
    public function getEventDispatcher()
    {
        if (!$this->eventDispatcher instanceof EventDispatcherInterface) {
            $this->eventDispatcher = $this->get('event_dispatcher');
        }

        return $this->eventDispatcher;
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack()
    {
        if (!$this->requestStack instanceof RequestStack) {
            $this->requestStack = $this->get('request_stack');
        }

        return $this->requestStack;
    }
}
