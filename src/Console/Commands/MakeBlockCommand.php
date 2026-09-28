<?php

namespace AliAwwad\FineBuilder\Console\Commands;

use AliAwwad\FineBuilder\Actions\MakeBlock;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Statamic\Console\RunsInPlease;

class MakeBlockCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'fine-builder:make-block
        {handle : snake_case block handle, e.g. testimonials}
        {--display= : Name shown to editors (default: from the handle)}
        {--group=blocks : Set group in the builder\'s block picker}
        {--no-reusable : Don\'t create a reusable blocks collection blueprint}';

    protected $description = 'Create a new Fine Builder block: fieldset, builder set, template and reusable blocks blueprint';

    public function handle(): int
    {
        try {
            $created = MakeBlock::execute(
                $this->argument('handle'),
                $this->option('display'),
                $this->option('group'),
                ! $this->option('no-reusable'),
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($created as $path) {
            $this->line("  <info>created</info> $path");
        }
        $this->line('  <info>updated</info> resources/fieldsets/'.config('fine-builder.field', 'fine_builder').'.yaml');

        $this->newLine();
        $this->line('Next: add your fields to the fieldset, render them in the template, then run <comment>npm run build</comment>.');

        return self::SUCCESS;
    }
}
