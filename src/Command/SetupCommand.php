<?php
/*
 * Copyright (c) 2022, whatwedo GmbH
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

namespace whatwedo\CrudBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class SetupCommand extends Command
{

    protected const SETUP_SKELETON = '/vendor/whatwedo/crud-bundle/src/Resources/skeleton/setup';

    protected static $defaultName = 'whatwedo:crud:setup';
    protected static $defaultDescription = 'Setup the CRUD bundle';

    protected InputInterface $input;
    protected OutputInterface $output;
    protected Filesystem $filesystem;
    protected QuestionHelper $questionHelper;

    public function __construct(
        protected string $projectRoot,
        protected string $defaultLocale
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->filesystem = new Filesystem();
        $this->questionHelper = $this->getHelper('question');

        $this->setupYarnDependencies();
        $this->setupRouting();
        $this->setupTailwind();
        $this->setupPostcss();
        $this->setupBaseTemplate();
        $this->checkLanguage();

        return self::SUCCESS;
    }

    protected function setupYarnDependencies(): void
    {
        if ($this->confirm('Do you want to add @tailwindcss/forms to your yarn.lock file?', true)) {
            shell_exec('yarn add @tailwindcss/forms');
        }
        if ($this->confirm('Do you want to add tailwindcss, postcss-loader, sass-loader, sass and autoprefixer to your dev dependencies?', true)) {
            shell_exec('yarn add tailwindcss postcss-loader sass-loader sass autoprefixer --dev');
        }
    }

    protected function setupRouting(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/config/routes/whatwedo_crud.yaml')
            && ! $this->confirm('Do you want to override the existing routing? (whatwedo_crud.yaml)')) {
            return;
        }

        $prefix = $this->ask('What is the prefix for the CRUD routes? (default: "")');
        $content = file_get_contents($this->projectRoot . self::SETUP_SKELETON . '/whatwedo_crud.yaml');
        $content = str_replace('%%prefix%%', $prefix, $content);
        file_put_contents($this->projectRoot . '/config/routes/whatwedo_crud.yaml', $content);
    }

    protected function setupTailwind(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/tailwind.config.js')
            && ! $this->confirm('Do you want to override the existing tailwind.config.js?')) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/tailwind.config.js',
            $this->projectRoot . '/tailwind.config.js'
        );
    }

    protected function setupPostcss(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/postcss.config.js')
            && ! $this->confirm('Do you want to override the existing postcss.config.js?')) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/postcss.config.js',
            $this->projectRoot . '/postcss.config.js'
        );
    }

    protected function setupBaseTemplate(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/templates/base.html.twig')
            && ! $this->confirm('Do you want to override the existing base.html.twig?')) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/base.html.twig',
            $this->projectRoot . '/templates/base.html.twig'
        );
    }

    protected function checkLanguage(): void
    {
        if ($this->defaultLocale !== 'de') {
            $this->output->writeln('<warning>The default locale is not set to "de".</warning>');
        }
    }

    protected function ask(string $question, string $default = ''): string
    {
        $question = new Question($question, $default);
        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    protected function confirm(string $question, bool $default = false): bool
    {
        $question = new ConfirmationQuestion($question, $default);
        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

}
