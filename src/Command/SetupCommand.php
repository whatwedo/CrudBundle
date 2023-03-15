<?php

declare(strict_types=1);
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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'whatwedo:crud:setup',
    description: 'Setup the CRUD bundle',
)]
class SetupCommand extends Command
{
    protected const SETUP_SKELETON = '/vendor/whatwedo/crud-bundle/src/Resources/skeleton/setup';

    protected InputInterface $input;

    protected OutputInterface $output;

    protected Filesystem $filesystem;

    protected QuestionHelper $questionHelper;

    protected string $projectRoot;

    protected string $defaultLocale;

    public function __construct(ParameterBagInterface $containerBag)
    {
        parent::__construct();
        $this->projectRoot = $containerBag->get('kernel.project_dir');
        $this->defaultLocale = $containerBag->get('kernel.default_locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;
        $this->filesystem = new Filesystem();
        $this->questionHelper = $this->getHelper('question');

        $this->setupYarnDependencies();
        $this->setupRouting();
        $this->setupTailwind();
        $this->setupTailwindFrontend();
        $this->setupPostcss();
        $this->setupFrontendPostcss();
        $this->setupBaseTemplate();
        $this->setupWebpackConfig();
        $this->setupAppJsScss();
        $this->setupFrontendJsScss();
        $this->setupWebpackEncore();
        $this->runYarnDev();
        $this->checkLanguage();

        return self::SUCCESS;
    }

    protected function setupYarnDependencies(): void
    {
        if ($this->confirm('Do you want to add @tailwindcss/forms to your yarn.lock file? [YES/no] ', true)) {
            $this->runCommand('yarn add @tailwindcss/forms');
            $this->newLine();
        }
        if ($this->confirm('Do you want to add tailwindcss, postcss-loader, sass-loader, sass and autoprefixer to your dev dependencies? [YES/no] ', true)) {
            $this->runCommand('yarn add tailwindcss postcss-loader sass-loader sass autoprefixer --dev');
            $this->newLine();
        }
    }

    protected function setupRouting(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/config/routes/whatwedo_crud.yaml')
            && ! $this->confirm('Do you want to override the existing routing? (whatwedo_crud.yaml) [NO/yes] ')) {
            return;
        }

        $prefix = $this->ask('What is the prefix for the CRUD routes? [default: ""] ');
        $content = file_get_contents($this->projectRoot . self::SETUP_SKELETON . '/whatwedo_crud.yaml');
        $content = str_replace('%%prefix%%', $prefix, $content);
        file_put_contents($this->projectRoot . '/config/routes/whatwedo_crud.yaml', $content);
        $this->output->writeln('created "config/routes/whatwedo_crud.yaml"');
        $this->newLine();
    }

    protected function setupTailwind(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/tailwind.config.js')
            && ! $this->confirm('Do you want to override the existing tailwind.config.js? [NO/yes] ')) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/tailwind.config.js',
            $this->projectRoot . '/tailwind.config.js',
            true
        );
        $this->output->writeln('created "tailwind.config.js"');
        $this->newLine();
    }

    protected function setupTailwindFrontend(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/tailwind_frontend.config.js')
            && ! $this->confirm('Do you want to override the existing tailwind_frontend.config.js? [NO/yes] ')) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/tailwind_frontend.config.js',
            $this->projectRoot . '/tailwind_frontend.config.js',
            true
        );
        $this->output->writeln('created "tailwind_frontend.config.js"');
        $this->newLine();
    }

    protected function setupPostcss(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/postcss.config.js')
            && ! $this->confirm('Do you want to override the existing postcss.config.js? [NO/yes] ')) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/postcss.config.js',
            $this->projectRoot . '/postcss.config.js',
            true
        );
        $this->output->writeln('created "postcss.config.js"');
        $this->newLine();
    }

    protected function setupFrontendPostcss(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/postcss_frontend.config.js')
            && ! $this->confirm('Do you want to override the existing postcss_frontend.config.js? [NO/yes] ')) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/postcss_frontend.config.js',
            $this->projectRoot . '/postcss_frontend.config.js',
            true
        );
        $this->output->writeln('created "postcss_frontend.config.js"');
        $this->newLine();
    }

    protected function setupBaseTemplate(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/templates/base.html.twig')
            && ! $this->confirm('Do you want to override the existing base.html.twig? [YES/no] ', true)) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/base.html.twig',
            $this->projectRoot . '/templates/base.html.twig',
            true
        );
        $this->output->writeln('created "templates/base.html.twig"');
        $this->newLine();
    }

    protected function setupWebpackConfig(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/webpack.config.js')
            && ! $this->confirm('Do you want to override the existing webpack.config.js? [YES/no] ', true)) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/webpack.config.js',
            $this->projectRoot . '/webpack.config.js',
            true
        );
        $this->output->writeln('created "webpack.config.js"');
        $this->newLine();
    }

    protected function setupAppJsScss(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/assets/admin.js')
            && ! $this->confirm('Do you want to override the existing assets/admin.js? [YES/no] ', true)) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/admin.js',
            $this->projectRoot . '/assets/admin.js',
            true
        );
        $this->output->writeln('created "assets/admin.js"');
        $this->newLine();

        if ($this->filesystem->exists($this->projectRoot . '/assets/styles/app.css')
            && $this->confirm('Do you want to delete the not used assets/styles/app.css? [YES/no] ', true)) {
            $this->filesystem->remove($this->projectRoot . '/assets/styles/app.css');
            $this->output->writeln('deleted "assets/styles/app.css"');
            $this->newLine();
        }

        if ($this->filesystem->exists($this->projectRoot . '/assets/styles/admin.scss')
            && ! $this->confirm('Do you want to override the existing assets/styles/admin.scss? [YES/no] ', true)) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/admin.scss',
            $this->projectRoot . '/assets/styles/admin.scss',
            true
        );
        $this->output->writeln('created "assets/styles/admin.scss"');
        $this->newLine();
    }

    protected function setupFrontendJsScss(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/assets/frontend.js')
            && ! $this->confirm('Do you want to override the existing assets/frontend.js? [YES/no] ', true)) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/frontend.js',
            $this->projectRoot . '/assets/frontend.js',
            true
        );
        $this->output->writeln('created "assets/frontend.js"');
        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/bootstrap_frontend.js',
            $this->projectRoot . '/assets/bootstrap_frontend.js',
            true
        );
        $this->output->writeln('created "assets/bootstrap_frontend.js"');
        if (! $this->filesystem->exists($this->projectRoot . '/assets/controllers_frontend')) {
            $this->filesystem->mkdir($this->projectRoot . '/assets/controllers_frontend');
            $this->output->writeln('created "assets/controllers_frontend"');
        }
        $this->newLine();

        if ($this->filesystem->exists($this->projectRoot . '/assets/styles/frontend.scss')
            && ! $this->confirm('Do you want to override the existing assets/styles/frontend.scss? [YES/no] ', true)) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/frontend.scss',
            $this->projectRoot . '/assets/styles/frontend.scss',
            true
        );
        $this->output->writeln('created "assets/styles/frontend.scss"');
        $this->newLine();
    }

    protected function setupWebpackEncore(): void
    {
        if ($this->filesystem->exists($this->projectRoot . '/config/packages/webpack_encore.yaml')
            && ! $this->confirm('Do you want to override the existing webpack_encore.yaml? [YES/no] ', true)) {
            return;
        }

        $this->filesystem->copy(
            $this->projectRoot . self::SETUP_SKELETON . '/webpack_encore.yaml',
            $this->projectRoot . '/config/packages/webpack_encore.yaml',
            true
        );
        $this->output->writeln('created "config/packages/webpack_encore.yaml"');
        $this->newLine();
    }

    protected function runYarnDev(): void
    {
        if ($this->confirm('Do you want to run "yarn dev" [YES/no] ', true)) {
            $this->runCommand('yarn dev');
            $this->newLine();
        }
    }

    protected function checkLanguage(): void
    {
        if ($this->defaultLocale !== 'de') {
            $this->output->writeln('<error>The default locale is not set to "de".</error>');
        }
    }

    protected function newLine(): void
    {
        $this->output->writeln('');
    }

    protected function runCommand(string $command): void
    {
        $process = new Process(explode(' ', $command));
        $process->start();
        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                $this->output->writeln($data);
            }
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
