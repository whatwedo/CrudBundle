<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;
use whatwedo\CrudBundle\Content\RelationContent;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageMode;
use whatwedo\CrudBundle\Event\CrudEvent;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\Manager\ExportManager;
use whatwedo\CrudBundle\View\DefinitionView;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\DataLoader\DoctrineTreeDataLoader;
use whatwedo\TableBundle\Entity\TreeInterface;
use whatwedo\TableBundle\Factory\TableFactory;

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

        $dataLoader = DoctrineDataLoader::class;
        if (is_subclass_of($this->getDefinition()::getEntity(), TreeInterface::class)) {
            $dataLoader = DoctrineTreeDataLoader::class;
        }

        $table = $tableFactory->create('index', $dataLoader, [
            'dataloader_options' => [
                DoctrineDataLoader::OPTION_QUERY_BUILDER => $this->getDefinition()->getQueryBuilder(),
            ],
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

        $block = $request->attributes->get('block');
        $field = $request->attributes->get('field');

        $this->dispatchEvent(CrudEvent::PRE_SHOW_PREFIX, $entity);

        return $this->render(
            $this->getTemplate('reload.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::RELOAD,
                [
                    'view' => $this->getDefinition()->createView(Page::RELOAD, $entity),
                    'blockAcronym' => $block,
                    'fieldAcronym' => $field,
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

        $mode = PageMode::NORMAL;
        if ($request->query->has('mode')) {
            $mode = PageMode::from($request->query->get('mode'));
        }

        $this->dispatchEvent(CrudEvent::PRE_EDIT_FORM_CREATION_PREFIX, $entity);

        $view = $this->getDefinition()->createView(Page::EDIT, $entity);

        $form = $view->getEditForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->dispatchEvent(CrudEvent::PRE_VALIDATE_PREFIX, $entity);
            if ($form->isValid()) {
                $this->dispatchEvent(CrudEvent::POST_VALIDATE_PREFIX, $entity);
                $this->dispatchEvent(CrudEvent::PRE_EDIT_PREFIX, $entity);
                $this->entityManager->flush();
                $this->dispatchEvent(CrudEvent::POST_EDIT_PREFIX, $entity);

                $this->addFlash('success', sprintf('Erfolgreich gespeichert.'));

                if ($mode === PageMode::MODAL) {
                    return new Response('', 200);
                }

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
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    public function create(Request $request): Response
    {
        $mode = PageMode::NORMAL;
        if ($request->query->has('mode')) {
            $mode = PageMode::from($request->query->get('mode'));
        }

        $this->denyAccessUnlessGrantedCrud(Page::CREATE, $this->getDefinition());

        $entity = $this->getDefinition()->createEntity($request);

        $this->dispatchEvent(CrudEvent::NEW_PREFIX, $entity);

        $view = $this->getDefinition()->createView(Page::CREATE, $entity);

        $this->preselectEntities($request, $view, $entity);

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

        match ($mode) {
            PageMode::NORMAL => $template = $this->getTemplate('create.html.twig'),
            PageMode::MODAL => $template = $this->getTemplate('create_modal.html.twig'),
        };

        return $this->render(
            $template,
            $this->getDefinition()->getTemplateParameters(Page::CREATE, [
                'view' => $view,
                'title' => $this->getDefinition()->getTitle(null, Page::CREATE),
                'form' => $form->createView(),
                '_route' => Page::CREATE,
            ], $entity),
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
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
            $this->addFlash('error', sprintf('Eintrag konnte nicht gelöscht werden: ' . $e->getMessage()));
            $this->container->get(LoggerInterface::class)->warning('Error while deleting: ' . $e->getMessage(), [
                'entity' => get_class($entity),
                'id' => $entity->getId(),
            ]);
        }

        return $this->getDefinition()->getRedirect(Page::DELETE, $entity);
    }

    public function export(Request $request, ExportManager $exportManager, TableFactory $tableFactory): Response
    {
        $this->denyAccessUnlessGrantedCrud(Page::EXPORT, $this->getDefinition());

        $table = $tableFactory
            ->create('index', DoctrineDataLoader::class, [
                'dataloader_options' => [
                    DoctrineDataLoader::OPTION_QUERY_BUILDER => $this->getDefinition()->getQueryBuilder(),
                ],
            ]);

        // to respect column sort order
        $this->getDefinition()->configureTable($table);
//        $this->getDefinition()->overrideTableConfiguration($table);

        $data = $table->getRows();

        $exportArray = $exportManager->prepareData($table);

        $spreadsheet = $exportManager->createSpreadsheet($table, $exportArray);

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.xls"');

        return $response;
    }

    public function jsonsearch(Request $request): Response
    {
        $array = $this->definition->jsonSearch($request->query->get('q', ''));
        $items = [];
        foreach ($array as $value) {
            $items[] = (object) [
                'id' => $value->getId(),
                'label' => (string) $value,
            ];
        }

        return new JsonResponse((object) [
            'items' => $items,
        ]);
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

    public function ajaxForm(Request $request): Response
    {
        $this->denyAccessUnlessGrantedCrud(Page::AJAXFORM, $this->getDefinition());
        $case = $request->query->get('case', 'create');
        $entity = $this->getDefinition()->createEntity($request);
        if (str_starts_with($case, 'create')) {
            $this->dispatchEvent(CrudEvent::NEW_PREFIX, $entity);
            $view = $this->getDefinition()->createView(Page::CREATE, $entity);
            $this->preselectEntities($request, $view, $entity);
            $this->dispatchEvent(CrudEvent::CREATE_SHOW_PREFIX, $entity);
            $form = $view->getCreateForm();
            $toRenderPage = Page::CREATE;
            $uneditedEntity = null;
        } else {
            $view = $this->getDefinition()->createView(Page::EDIT, $entity);
            $form = $view->getEditForm();
            $toRenderPage = Page::EDIT;
            $uneditedEntity = $this->getEntityOr404($request);
        }

        $form->handleRequest($request);
        $data = $form->getData();
        $this->definition->ajaxForm($data, $toRenderPage);
        $view = $this->getDefinition()->createView($toRenderPage, $data);
        $form = $toRenderPage === Page::CREATE ? $view->getCreateForm() : $view->getEditForm();
        $context = [
            'view' => $view,
            'title' => $this->getDefinition()->getTitle(null, $toRenderPage),
            'form' => $form->createView(),
            '_route' => $toRenderPage,
        ];
        if ($case === 'createmodal') {
            $template = $this->twig->load($this->getTemplate('create_modal.html.twig'));
            $html = $template->render($this->twig->mergeGlobals($context));
        } else {
            $templatePath = $this->getTemplate($toRenderPage === Page::CREATE ? 'create.html.twig' : 'edit.html.twig');
            $template = $this->twig->load($templatePath);
            $html = $template->renderBlock('main', $this->twig->mergeGlobals($context));
        }

        return new Response($html);
    }

    /**
     * @param $event
     * @param $entity
     */
    public function dispatchEvent($event, $entity)
    {
        $this->eventDispatcher->dispatch(new CrudEvent($entity), $event);
        $this->eventDispatcher->dispatch(new CrudEvent($entity), $event . '.' . $this->getDefinition()::getAlias());
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

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EventDispatcherInterface::class,
            LoggerInterface::class,
        ]);
    }

    protected function preselectEntities(Request $request, DefinitionView $view, object $entity)
    {
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

                            if (! $propertyAccessor->getValue($entity, $content->getOption('accessor_path'))
                                && $request->isMethod('get')) {
                                $propertyAccessor->setValue($entity, $content->getOption('accessor_path'), $value);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * get specific view for a definition.
     */
    protected function getTemplate(string $filename): string
    {
        if ($this->twig->getLoader()->exists($this->getDefinition()->getTemplateDirectory() . '/' . $filename)) {
            return $this->getDefinition()->getTemplateDirectory() . '/' . $filename;
        }

        return '@whatwedoCrud/Crud/' . $filename;
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
                ->andWhere($this->getIdentifierColumn() . ' = :id')
                ->setParameter('id', $request->attributes->getInt('id'))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            throw new NotFoundHttpException(sprintf('Der gewünschte Datensatz existiert in %s nicht.', $this->getDefinition()->getTitle()));
        }
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

    /**
     * @override
     */
    protected function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (! $this->isGranted($attribute, $subject)) {
            $exception = $this->createAccessDeniedException($message);
            if (is_object($attribute) && enum_exists(get_class($attribute))) {
                $attribute = $attribute->value;
            }
            $exception->setAttributes((string) $attribute);
            $exception->setSubject($subject);

            throw $exception;
        }
    }

    private function redirectToDefinitionObject(DefinitionInterface $definition, string $capability, array $parameters = [], int $status = 302): RedirectResponse
    {
        $route = $definition::getRoutePrefix() . '_' . $capability;

        return $this->redirectToRoute($route, $parameters, $status);
    }
}
