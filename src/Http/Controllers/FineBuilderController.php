<?php

namespace AliAwwad\FineBuilder\Http\Controllers;

use AliAwwad\FineBuilder\Actions\DisableFineBuilderOnCollection;
use AliAwwad\FineBuilder\Actions\EnableFineBuilderOnCollection;
use AliAwwad\FineBuilder\Actions\GetCollectionsWithFineBuilder;
use AliAwwad\FineBuilder\Actions\InstallBuilderFiles;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Facades\CP\Toast;

class FineBuilderController
{
    public function index()
    {
        return Inertia::render('fine-builder::Setup', [
            'title' => __('fine-builder::messages.title'),
            'installed' => InstallBuilderFiles::isInstalled(),
            'collections' => GetCollectionsWithFineBuilder::execute()->map(fn ($collection) => [
                'handle' => $collection->handle(),
                'title' => $collection->title(),
                'hasFineBuilder' => $collection->hasFineBuilder,
            ])->values(),
        ]);
    }

    public function install(Request $request)
    {
        $result = InstallBuilderFiles::execute($request->boolean('force'));

        Toast::success(__('fine-builder::messages.installed', [
            'created' => count($result['created']),
            'skipped' => count($result['skipped']),
        ]));

        return redirect()->cpRoute('fine-builder.index');
    }

    public function collections(Request $request)
    {
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
