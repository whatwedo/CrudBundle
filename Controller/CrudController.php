<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;
use whatwedo\CrudBundle\Content\RelationContent;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Encoder\CsvEncoder;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageMode;
use whatwedo\CrudBundle\Event\CrudEvent;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\Normalizer\ObjectNormalizer;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\DoctrineTable;

#[AsController]
class CrudController extends AbstractController implements CrudDefinitionController
{
    protected ?DefinitionInterface $definition = null;
    protected DefinitionManager $definitionManager;
    protected EventDispatcherInterface $eventDispatcher;
    protected EntityManagerInterface $entityManager;
    protected Environment $twig;

    public function index(TableFactory $tableFactory): Response
    {
        $this->denyAccessUnlessGrantedCrud(Page::INDEX, $this->getDefinition());

        $table = $tableFactory->createDoctrineTable('index', [
            'query_builder' => $this->getDefinition()->getQueryBuilder(),
        ]);

        $this->getDefinition()->configureTable($table);
        $this->getDefinition()->buildBreadcrumbs(null, Page::INDEX);

        return $this->render(
            $this->getTemplate('index.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::INDEX,
                [
                    'view' => $this->getDefinition()->createView(Page::INDEX),
                    'table' => $table,
                ]
            )
        );
    }

    public function show(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::SHOW, $entity);

        $this->dispatchEvent(CrudEvent::PRE_SHOW_PREFIX, $entity);

        $this->definition->buildBreadcrumbs($entity, Page::SHOW);

        return $this->render(
            $this->getTemplate('show.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::SHOW,
                [
                    'view' => $this->getDefinition()->createView(Page::SHOW, $entity),
                    'title' => $this->getDefinition()->getTitle($entity, Page::SHOW),
                    '_route' => Page::SHOW,
                ],
                $entity
            )
        );
    }

    public function reload(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::SHOW, $entity);

        $field = $request->attributes->get('field');

        $this->dispatchEvent(CrudEvent::PRE_SHOW_PREFIX, $entity);

        return $this->render(
            $this->getTemplate('reload.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::RELOAD,
                [
                    'view' => $this->getDefinition()->createView(Page::RELOAD, $entity),
                    'field' => $field,
                    '_route' => Page::RELOAD,
                ],
                $entity
            )
        );
    }

    public function edit(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::EDIT, $entity);

        $view = $this->getDefinition()->createView(Page::EDIT, $entity);

        $form = $view->getEditForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->dispatchEvent(CrudEvent::PRE_EDIT_PREFIX, $entity);
                $this->entityManager->flush();
                $this->dispatchEvent(CrudEvent::POST_EDIT_PREFIX, $entity);

                $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));

                return $this->getDefinition()->getRedirect(Page::EDIT, $entity);
            }
            $this->addFlash('error', sprintf('Beim Speichern ist ein Fehler aufgetreten. Bitte überprüfe deine Eingaben.'));
        }

        $this->definition->buildBreadcrumbs($entity, Page::EDIT);

        return $this->render(
            $this->getTemplate('edit.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::EDIT,
                [
                    'view' => $view,
                    'title' => $this->getDefinition()->getTitle($entity, Page::EDIT),
                    'form' => $form->createView(),
                    '_route' => Page::EDIT,
                ],
                $entity
            ),
            new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200)
        );
    }

    public function createmodal(Request $request): Response
    {
        return $this->create($request, PageMode::MODAL);
    }

    public function create(Request $request, PageMode $mode = PageMode::NORMAL ): Response
    {
        $this->denyAccessUnlessGrantedCrud(Page::CREATE, $this->getDefinition());

        $entity = $this->getDefinition()->createEntity($request);

        $this->dispatchEvent(CrudEvent::NEW_PREFIX, $entity);

        match($mode) {
            PageMode::NORMAL => $template = $this->getTemplate('create.html.twig'),
            PageMode::MODAL => $template = $this->getTemplate('create_modal.html.twig'),
        };


        $view = $this->getDefinition()->createView(Page::CREATE, $entity);

        if ($request->isMethod('get') || $request->isMethod('post')) {
            // set preselected entities
            $propertyAccessor = PropertyAccess::createPropertyAccessor();

            foreach ($view->getBlocks() as $block) {
                foreach ($block->getContents() as $content) {
                    if ($content->hasOption('preselect_definition')
                        && $content->getOption('preselect_definition')) {
                        $queryParameter = call_user_func([$content->getOption('preselect_definition'), 'getAlias']);

                        if ($queryParameter
                            && $request->query->has($queryParameter)) {
                            $value = $this->entityManager
                                ->getRepository(call_user_func([$content->getOption('preselect_definition'), 'getEntity']))
                                ->find($request->query->getInt($queryParameter));

                            if (null !== $value) {
                                $this->get(RouterInterface::class)->generate(
                                    call_user_func([$content->getOption('preselect_definition'), 'getRouteName'], Page::SHOW),
                                    [
                                        'id' => $value->getId(),
                                    ]
                                );
                            }
                            if (! $propertyAccessor->getValue($entity, $content->getOption('accessor_path'))
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

                $objectManager = $this->entityManager;
                $objectManager->persist($entity);
                $objectManager->flush();

                $this->dispatchEvent(CrudEvent::POST_CREATE_PREFIX, $entity);

                $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));

                if ($mode === PageMode::MODAL) {
                    return new Response('', 200);
                }
                return $this->getDefinition()->getRedirect(Page::CREATE, $entity);
            }
            $this->addFlash('error', 'Beim Speichern ist ein Fehler aufgetreten. Bitte überprüfe deine Eingaben.');
        }

        $this->definition->buildBreadcrumbs(null, Page::CREATE);

        return $this->render(
            $template,
            $this->getDefinition()->getTemplateParameters(Page::CREATE, [
                'view' => $view,
                'title' => $this->getDefinition()->getTitle(null, Page::CREATE),
                'form' => $form->createView(),
                '_route' => Page::CREATE,
            ], $entity),
            new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200)
        );
    }

    public function delete(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::DELETE, $entity);

        try {
            $this->entityManager->remove($entity);
            $this->dispatchEvent(CrudEvent::PRE_DELETE_PREFIX, $entity);
            $this->entityManager->flush($entity);
            $this->dispatchEvent(CrudEvent::POST_DELETE_PREFIX, $entity);
            $this->addFlash('success', 'Eintrag erfolgreich gelöscht.');
        } catch (\Exception $e) {
            $this->addFlash('error', sprintf('Eintrag konnte nicht gelöscht werden: '.$e->getMessage()));
            $this->logger->warning('Error while deleting: '.$e->getMessage(), [
                'entity' => get_class($entity),
                'id' => $entity->getId(),
            ]);
        }

        return $this->getDefinition()->getRedirect(Page::DELETE, $entity);
    }

    /**
     * TODO: migrate to excel export.
     */
    public function export(Request $request): Request
    {
        $this->denyAccessUnlessGrantedCrud(Page::EXPORT, $this->getDefinition());

        $entities = $this->getExportEntities($request);
        if (! $entities) {
            $this->addFlash('warning', 'Nichts zu exportieren');

            return $this->redirectToRoute($this->getDefinition()::getRoutePrefix().'_'.Page::INDEX);
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
        $charset = mb_detect_encoding(
            $string,
            'UTF-8, ISO-8859-1, ISO-8859-15',
            true
        );

        $string = mb_convert_encoding($string, 'Windows-1252', $charset);

        return $string;
    }

    /**
     * @return Response
     */
    public function ajax(Request $request): Request
    {
        $this->denyAccessUnlessGrantedCrud(Page::AJAX, $this->getDefinition());

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
     * get specific view for a definition.
     */
    protected function getTemplate(string $filename): string
    {
        if ($this->twig->getLoader()->exists($this->getDefinition()->getTemplateDirectory().'/'.$filename)) {
            return $this->getDefinition()->getTemplateDirectory().'/'.$filename;
        }

        return '@whatwedoCrud/Crud/'.$filename;
    }

    /**
     * @param $event
     * @param $entity
     */
    public function dispatchEvent($event, $entity)
    {
        $this->eventDispatcher->dispatch(new CrudEvent($entity), $event);
        $this->eventDispatcher->dispatch(new CrudEvent($entity), $event.'.'.$this->getDefinition()::getAlias());
    }

    public function setDefinition(?DefinitionInterface $definition): void
    {
        $this->definition = $definition;
    }

    /**
     * @required
     */
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    /**
     * @required
     */
    public function setDefinitionManager(DefinitionManager $definitionManager): void
    {
        $this->definitionManager = $definitionManager;
    }

    /**
     * @required
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @required
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    protected function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }

    /**
     * returns the required entity.
     *
     * @throws NotFoundHttpException
     */
    protected function getEntityOr404(Request $request)
    {
        try {
            return $this->getDefinition()->getQueryBuilder()
                ->andWhere($this->getIdentifierColumn().' = :id')
                ->setParameter('id', $request->attributes->getInt('id'))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            throw new NotFoundHttpException(sprintf('Der gewünschte Datensatz existiert in %s nicht.', $this->getDefinition()->getTitle()));
        }
    }

    /**
     * @return array
     *
     * @throws \whatwedo\TableBundle\Exception\DataLoaderNotAvailableException
     */
    protected function getExportEntities(Request $request)
    {
        $export = $request->query->get('export', []);
        if (isset($export['definition']) && isset($export['acronym']) && isset($export['class']) && isset($export['id'])
            && ($definition = $this->definitionManager->getDefinitionByClassName($export['definition']))
            && ($content = $definition->getContent($export['acronym']))
            && $content instanceof RelationContent
            && ($repository = $this->entityManager->getRepository($export['class']))
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
        return $this->redirectToDefinitionObject($this->definitionManager->getDefinitionByClassName($definitionClass), $capability, $parameters, $status);
    }

    /**
     * @param $attributes
     * @param null $subject
     */
    protected function denyAccessUnlessGrantedCrud($attributes, $subject = null, string $message = 'Access Denied.')
    {
        if (! $this->getUser()) {
            return;
        }
        $this->denyAccessUnlessGranted($attributes, $subject, $message);
    }

    private function redirectToDefinitionObject(DefinitionInterface $definition, string $capability, array $parameters = [], int $status = 302): RedirectResponse
    {
        $route = $definition::getRoutePrefix().'_'.$capability;

        return $this->redirectToRoute($route, $parameters, $status);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EventDispatcherInterface::class,
        ]);
    }
}
