<?php

namespace App\Filament\Resources\ZplLabelTemplateResource\Pages;

use App\Filament\Resources\ZplLabelTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZplLabelTemplates extends ListRecords
{
    protected static string $resource = ZplLabelTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Template'),
        ];
    }
}
