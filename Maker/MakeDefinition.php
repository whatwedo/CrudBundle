<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace  whatwedo\CrudBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Common\Inflector\Inflector as LegacyInflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityDetails;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\CrudBundle\Enum\VisibilityEnum;
use whatwedo\TableBundle\Table\FormattableColumnInterface;

final class MakeDefinition extends AbstractMaker
{
    private $doctrineHelper;

    private $formTypeRenderer;

    private $inflector;
    private string $rootPath;
    private FormatterManager $formatterManager;

    public function __construct(DoctrineHelper $doctrineHelper, FormTypeRenderer $formTypeRenderer, string $rootPath, FormatterManager $formatterManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->formTypeRenderer = $formTypeRenderer;

        if (class_exists(InflectorFactory::class)) {
            $this->inflector = InflectorFactory::create()->build();
        }
        $this->rootPath = $rootPath;
        $this->formatterManager = $formatterManager;
    }

    public static function getCommandName(): string
    {
        return 'make:definition';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates Defintion for the wwd CrudBundle')
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
            'Entity\\'
        );

        $entityDoctrineMetaData = $this->doctrineHelper->getMetadata($entityClassDetails->getFullName());
        $definitionClassDetails = $generator->createClassNameDetails(
            $entityClassDetails->getRelativeNameWithoutSuffix().'Definition',
            'Definition\\',
            'Definition'
        );
        $entityVarSingular = lcfirst($this->singularize($entityClassDetails->getShortName()));

        $fieldFormatters = $this->askFormatters($entityDoctrineMetaData, $io);

        $this->generateDefition(
            $generator,
            $definitionClassDetails,
            $entityClassDetails,
            $entityDoctrineMetaData,
            $entityVarSingular,
            $fieldFormatters);

        $this->genereteRoute($generator, $entityVarSingular, $definitionClassDetails->getFullName());

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(sprintf('Next: Check your new Definition by going to <fg=yellow>%s/</>', Str::asRoutePath($definitionClassDetails->getRelativeNameWithoutSuffix())));
    }



    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
        );

        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm-pack'
        );

        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );

        $dependencies->addClassDependency(
            ParamConverter::class,
            'annotations'
        );
    }



    private function singularize(string $word): string
    {
        if (null !== $this->inflector) {
            return $this->inflector->singularize($word);
        }

        return LegacyInflector::singularize($word);
    }



    private function generateDefition(
        Generator $generator,
        ClassNameDetails $definitionClassDetails,
        ClassNameDetails $entityClassDetails,
        ?ClassMetadata $entityDetails,
        string $entityVarSingular,
        array $fieldFormatters)
    {
        $templatePath = __DIR__ . '/../Resources/skeleton/definition/Definition.tpl.php';
        $fieldNames = $entityDetails->fieldNames;
        foreach ($entityDetails->getIdentifierFieldNames() as $idField) {
            unset($fieldNames[$idField]);
        }

        $generator->generateClass(
            $definitionClassDetails->getFullName(),
            $templatePath,
            [
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'entity_twig_var_singular' => $entityVarSingular,
                'entity_class_name' => $entityClassDetails->getShortName(),
                'fields' => $fieldNames,
                'fieldFormatters' => $fieldFormatters,
            ],
        );

    }


    /**
     * @param Generator $generator
     * @param string $prefix
     */
    private function genereteRoute(Generator $generator, string $prefix, string $defintion): void
    {
        $templatePath = __DIR__ . '/../Resources/skeleton/routes/crud.tpl.php';
        $generator->generateFile(
            sprintf('%s/config/routes/%s_crud.yaml', $this->rootPath, $prefix),
            $templatePath,
            [
                'prefix' => $prefix,
                'defitionClass' => $defintion,

            ],
        );
    }

    /**
     * @param $entities
     * @param ConsoleStyle $io
     */
    public function askFormatters(ClassMetadata $entityDoctrineMetaData,  $io): array
    {
        $r = new \ReflectionObject($this->formatterManager);
        $p = $r->getProperty('formatters');
        $p->setAccessible(true); // <--- you set the property to public before you read the value
        $formattersInstances = $p->getValue($this->formatterManager);

        $formatters = ['none'];

        foreach ($formattersInstances as $formattersInstance) {
            if ($formattersInstance instanceof DefaultFormatter) {
                continue;
            }
            $formatters[] = get_class($formattersInstance);
        }

        $responses = [];
        foreach ($entityDoctrineMetaData->getFieldNames() as $fieldName) {
            $mapping = $entityDoctrineMetaData->getFieldMapping($fieldName);

            if (isset($mapping['id']) && $mapping['id']) {
                continue;
            }
            $question = new ChoiceQuestion(
                'Formatter for Field "' . $fieldName . '" type:'. $mapping['type'] . ' (default none)?',
                $formatters,
                0
            );
            $response = $io->askQuestion($question);
            if ($response === 'none') {
                continue;
            }
            $responses[$fieldName] = $response;
        }

        return $responses;
    }

}
