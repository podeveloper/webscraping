<?php

namespace App\Filament\Resources\ScrapResource\Pages;

use App\Filament\Resources\ScrapResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageScraps extends ManageRecords
{
    protected static string $resource = ScrapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
