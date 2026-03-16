<?php

namespace App\Filament\Resources\ZplLabelTemplateResource\Pages;

use App\Filament\Resources\ZplLabelTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZplLabelTemplate extends EditRecord
{
    protected static string $resource = ZplLabelTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
