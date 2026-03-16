<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Services\SyncService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Projeler';

    protected static ?string $modelLabel = 'Proje';

    protected static ?string $pluralModelLabel = 'Projeler';

    public static function getNavigationBadge(): ?string
    {
        return (string) Project::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Proje Bilgileri')
                    ->schema([
                        TextInput::make('project_name')
                            ->label('Proje Adı')
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Nebim Micro Service API')
                    ->description('Nebim veri senkronizasyonu için kullanılan API bağlantı bilgileri')
                    ->schema([
                        TextInput::make('project_api_address')
                            ->label('API Adresi')
                            ->placeholder('http://192.168.1.1/nebim_micro_service')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('project_api_username')
                            ->label('Kullanıcı Adı')
                            ->nullable()
                            ->maxLength(255),

                        TextInput::make('project_api_password')
                            ->label('Şifre')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Entegratör API')
                    ->description('NebimIntegrator API bağlantı bilgileri')
                    ->schema([
                        TextInput::make('project_integrator_api_address')
                            ->label('Entegratör API Adresi')
                            ->placeholder('http://192.168.1.1/NebimIntegrator/api')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('nebim_integrator_token')
                            ->label('Token')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->maxLength(500),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('project_name')
                    ->label('Proje Adı')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Kullanıcı')
                    ->counts('users')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('project_api_address')
                    ->label('API Adresi')
                    ->toggleable()
                    ->limit(40),

                TextColumn::make('project_integrator_api_address')
                    ->label('Entegratör API')
                    ->toggleable()
                    ->limit(40),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Action::make('sync')
                    ->label('Senkronize Et')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Senkronizasyonu Başlat')
                    ->modalDescription('Bu projenin şirket, ofis, mağaza ve depo verileri Nebim\'den çekilip güncellenecek. Devam etmek istiyor musunuz?')
                    ->modalSubmitActionLabel('Evet, Senkronize Et')
                    ->action(function (Project $record): void {
                        try {
                            $counts = app(SyncService::class)->sync($record);

                            Notification::make()
                                ->title('Senkronizasyon Tamamlandı')
                                ->body(
                                    "Şirket: {$counts['companies']} | " .
                                    "Ofis: {$counts['offices']} | " .
                                    "Mağaza: {$counts['stores']} | " .
                                    "Depo: {$counts['warehouses']}"
                                )
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Senkronizasyon Hatası')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make()->label('Düzenle'),
                DeleteAction::make()->label('Sil'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri Sil'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
