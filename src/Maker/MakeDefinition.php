<?php

declare(strict_types=1);
/*
 * Copyright (c) 2017, whatwedo GmbH
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

namespace  whatwedo\CrudBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;
use whatwedo\CoreBundle\Manager\FormatterManager;

final class MakeDefinition extends AbstractMaker
{
    private $doctrineHelper;

    private $formTypeRenderer;

    private $inflector;

    private string $rootPath;

    private FormatterManager $formatterManager;

    private TranslatorInterface $translator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FormTypeRenderer $formTypeRenderer,
        string $rootPath,
        FormatterManager $formatterManager,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->formTypeRenderer = $formTypeRenderer;
        $this->inflector = InflectorFactory::create()->build();
        $this->rootPath = $rootPath;
        $this->formatterManager = $formatterManager;
        $this->translator = $translator;
    }

    public static function getCommandDescription(): string
    {
        return 'Creates Defintion for the wwd CrudBundle';
    }

    public static function getCommandName(): string
    {
        return 'make:definition';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption(
                'no-translations',
                't',
                InputOption::VALUE_NONE,
                sprintf(
                    'do not add translations in your messages.%s.yaml',
                    $this->translator->getLocale()
                )
            )
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if ($input->getArgument('entity-class') === null) {
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
            $entityClassDetails->getRelativeNameWithoutSuffix() . 'Definition',
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
            $fieldFormatters
        );

        $generator->writeChanges();

        if (! $input->getOption('no-translations')) {
            $this->createTranslations(
                $entityClassDetails,
                $entityDoctrineMetaData
            );
        }

        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            TranslatorInterface::class,
            'translation'
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
    }

    /**
     * @param ConsoleStyle $io
     */
    public function askFormatters(ClassMetadata $entityDoctrineMetaData, $io): array
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
                'Formatter for Field "' . $fieldName . '" type:' . $mapping['type'] . ' (default none)?',
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

    private function singularize(string $word): string
    {
        return $this->inflector->singularize($word);
    }

    private function generateDefition(
        Generator $generator,
        ClassNameDetails $definitionClassDetails,
        ClassNameDetails $entityClassDetails,
        ?ClassMetadata $entityDetails,
        string $entityVarSingular,
        array $fieldFormatters
    ) {
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

    private function createTranslations($entityClassDetails, $entityDetails)
    {
        $fieldNames = $entityDetails->fieldNames;
        foreach ($entityDetails->getIdentifierFieldNames() as $idField) {
            unset($fieldNames[$idField]);
        }

        $data = '';
        $data .= sprintf('wwd.app_entity_%s.title: %s', StringUtil::fqcnToBlockPrefix($entityClassDetails->getFullName()), $entityClassDetails->getShortName()) . PHP_EOL;
        $data .= sprintf('wwd.app_entity_%s.title_plural: %s', StringUtil::fqcnToBlockPrefix($entityClassDetails->getFullName()), $entityClassDetails->getShortName()) . PHP_EOL;
        $data .= sprintf('wwd.app_entity_%s.block.base: %s', StringUtil::fqcnToBlockPrefix($entityClassDetails->getFullName()), 'Base' . PHP_EOL);
        foreach ($fieldNames as $fieldName) {
            $data .= sprintf('wwd.app_entity_%s.property.%s: %s', StringUtil::fqcnToBlockPrefix($entityClassDetails->getFullName()), $fieldName, $fieldName) . PHP_EOL;
        }

        file_put_contents(
            sprintf('%s/translations/messages.%s.yaml', $this->rootPath, $this->translator->getLocale()),
            $data,
            FILE_APPEND
        );
    }
}
