<?php

namespace AliAwwad\FineBuilder\Console\Commands;

use AliAwwad\FineBuilder\Actions\EnableFineBuilderOnCollection;
use AliAwwad\FineBuilder\Actions\GetEntriesWithOwnTemplate;
use AliAwwad\FineBuilder\Actions\InstallBuilderFiles;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Collection;

class InstallCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'fine-builder:install
        {--collection=* : Enable the builder on these collections}
        {--force : Overwrite builder files that already exist in the site}';

    protected $description = 'Install the Fine Builder files into the site and enable the builder on collections';

    public function handle(): int
    {
        $result = InstallBuilderFiles::execute((bool) $this->option('force'));

        foreach ($result['created'] as $path) {
            $this->line("  <info>created</info> $path");
        }

        if ($result['skipped']) {
            $this->line('  <comment>kept '.count($result['skipped']).' existing file(s)</comment> (use --force to overwrite)');
        }

        foreach ($this->option('collection') as $handle) {
            if (! Collection::findByHandle($handle)) {
                $this->error("Collection [$handle] not found.");

                return self::FAILURE;
            }

            EnableFineBuilderOnCollection::execute($handle);
            $this->info("Fine Builder enabled on [$handle].");

            $overriding = GetEntriesWithOwnTemplate::execute($handle);
            if ($overriding->isNotEmpty()) {
                $this->warn('  '.$overriding->count().' entries set their own template ('.$overriding->map->get('template')->unique()->implode(', ').'), so they won\'t show blocks.');
                $this->line('  Pick "'.config('fine-builder.template', 'fine_builder').'" in their Template field, or add {{ partial:sets/'.config('fine-builder.field', 'fine_builder').' }} to that template.');
            }
        }

        $this->newLine();
        $this->line('Next: run <comment>npm run build</comment> so Tailwind picks up the block templates.');

        return self::SUCCESS;
    }
}
