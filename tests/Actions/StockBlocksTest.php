<?php

namespace AliAwwad\FineBuilder\Tests\Actions;

use AliAwwad\FineBuilder\Actions\AddStockBlocks;
use AliAwwad\FineBuilder\Actions\GetNewStockBlocks;
use AliAwwad\FineBuilder\Support\BuilderFieldset;
use AliAwwad\FineBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Fieldset;

class StockBlocksTest extends TestCase
{
    /** Simulates a site installed before the addon shipped "pricing" and "stats". */
    private function removeFromSite(string ...$handles): void
    {
        $contents = BuilderFieldset::siteContents();
        foreach ($contents['fields'][0]['field']['sets'] as $group => $config) {
            foreach ($handles as $handle) {
                unset($contents['fields'][0]['field']['sets'][$group]['sets'][$handle]);
            }
        }
        BuilderFieldset::save($contents);
    }

    #[Test]
    public function an_up_to_date_site_has_no_new_blocks(): void
    {
        $this->assertSame([], GetNewStockBlocks::execute());
    }

    #[Test]
    public function blocks_missing_from_the_site_are_offered(): void
    {
        $this->removeFromSite('pricing', 'stats');

        $new = GetNewStockBlocks::execute();

        $this->assertSame(['stats', 'pricing'], array_keys($new));
        $this->assertSame('Pricing', $new['pricing']['display']);
    }

    #[Test]
    public function adding_them_restores_sets_and_copies_missing_files_only(): void
    {
        $this->removeFromSite('pricing', 'stats');
        file_put_contents($this->site.'/fieldsets/block_stats.yaml', "title: 'Custom stats'\nfields: []\n");

        $added = AddStockBlocks::execute(['pricing', 'stats', 'hero', 'nope']);

        $this->assertSame(['pricing', 'stats'], $added);
        $this->assertSame([], GetNewStockBlocks::execute());
        $this->assertSame('blocks', BuilderFieldset::sets(BuilderFieldset::siteContents())['pricing']['group']);
        $this->assertFileExists($this->site.'/views/sets/blocks/pricing.antlers.html');
        $this->assertFileExists($this->site.'/blueprints/collections/blocks/pricing.yaml');
        $this->assertSame('Custom stats', Fieldset::find('block_stats')->title());
    }

    #[Test]
    public function added_blocks_use_the_configured_block_names(): void
    {
        config(['fine-builder.collection' => 'sections', 'fine-builder.fieldset_prefix' => 'section_', 'fine-builder.views' => 'sections']);
        $this->removeFromSite('pricing');

        AddStockBlocks::execute(['pricing']);

        $this->assertSame([['import' => 'section_pricing']], BuilderFieldset::sets(BuilderFieldset::siteContents())['pricing']['set']['fields']);
        $this->assertNotNull(Fieldset::find('section_pricing'));
        $this->assertFileExists($this->site.'/views/sections/pricing.antlers.html');
        $this->assertStringContainsString('import: section_pricing', file_get_contents($this->site.'/blueprints/collections/sections/pricing.yaml'));
    }

    #[Test]
    public function nothing_is_offered_before_install(): void
    {
        unlink($this->site.'/fieldsets/fine_builder.yaml');

        $this->assertSame([], GetNewStockBlocks::execute());
        $this->assertSame([], AddStockBlocks::execute());
    }
}
