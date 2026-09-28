<?php

namespace AliAwwad\FineBuilder\Http\Controllers;

use AliAwwad\FineBuilder\Actions\AddStockBlocks;
use AliAwwad\FineBuilder\Actions\DisableFineBuilderOnCollection;
use AliAwwad\FineBuilder\Actions\EnableFineBuilderOnCollection;
use AliAwwad\FineBuilder\Actions\GetCollectionsWithFineBuilder;
use AliAwwad\FineBuilder\Actions\GetEntriesWithOwnTemplate;
use AliAwwad\FineBuilder\Actions\GetNewStockBlocks;
use AliAwwad\FineBuilder\Actions\InstallBuilderFiles;
use AliAwwad\FineBuilder\ServiceProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Facades\CP\Toast;
use Statamic\Http\Controllers\CP\CpController;

class FineBuilderController extends CpController
{
    public function index()
    {
        $this->authorize(ServiceProvider::PERMISSION);

        return Inertia::render('fine-builder::Setup', [
            'title' => __('fine-builder::messages.title'),
            'installed' => InstallBuilderFiles::isInstalled(),
            'newBlocks' => collect(GetNewStockBlocks::execute())
                ->map(fn ($block, $handle) => ['handle' => $handle] + $block)
                ->values(),
            'collections' => GetCollectionsWithFineBuilder::execute()->map(fn ($collection) => [
                'handle' => $collection->handle(),
                'title' => $collection->title(),
                'hasFineBuilder' => $collection->hasFineBuilder,
                'template' => $collection->template(),
                'entriesWithOwnTemplate' => $collection->hasFineBuilder
                    ? GetEntriesWithOwnTemplate::execute($collection->handle())->count()
                    : 0,
            ])->values(),
            'builderTemplate' => config('fine-builder.template', 'fine_builder'),
            'builderPartial' => 'sets/'.config('fine-builder.field', 'fine_builder'),
        ]);
    }

    public function install(Request $request)
    {
        $this->authorize(ServiceProvider::PERMISSION);

        $result = InstallBuilderFiles::execute($request->boolean('force'));

        Toast::success(__('fine-builder::messages.installed', [
            'created' => count($result['created']),
            'skipped' => count($result['skipped']),
        ]));

        return redirect()->cpRoute('fine-builder.index');
    }

    public function addBlocks(Request $request)
    {
        $this->authorize(ServiceProvider::PERMISSION);

        $request->validate(['blocks' => 'nullable|array']);

        $added = AddStockBlocks::execute($request->blocks);

        Toast::success(__('fine-builder::messages.blocks_added', ['count' => count($added)]));

        return redirect()->cpRoute('fine-builder.index');
    }

    public function collections(Request $request)
    {
        $this->authorize(ServiceProvider::PERMISSION);

        $request->validate(['collections' => 'nullable|array']);

        if (! InstallBuilderFiles::isInstalled()) {
            InstallBuilderFiles::execute();
        }

        $selected = $request->collections ?? [];

        foreach (GetCollectionsWithFineBuilder::execute() as $collection) {
            $wanted = in_array($collection->handle(), $selected, true);

            if ($wanted && ! $collection->hasFineBuilder) {
                EnableFineBuilderOnCollection::execute($collection->handle());
            } elseif (! $wanted && $collection->hasFineBuilder) {
                DisableFineBuilderOnCollection::execute($collection->handle());
            }
        }

        Toast::success(__('fine-builder::messages.collections_updated'));

        return redirect()->cpRoute('fine-builder.index');
    }
}
