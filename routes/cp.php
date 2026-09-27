<?php

use AliAwwad\FineBuilder\Http\Controllers\FineBuilderController;
use Illuminate\Support\Facades\Route;

Route::get('fine-builder', [FineBuilderController::class, 'index'])->name('fine-builder.index');
Route::post('fine-builder/install', [FineBuilderController::class, 'install'])->name('fine-builder.install');
Route::post('fine-builder/collections', [FineBuilderController::class, 'collections'])->name('fine-builder.collections');
