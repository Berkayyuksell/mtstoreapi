<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZplLabelTemplateResource\Pages;
use App\Models\Project;
use App\Models\ZplLabelTemplate;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ZplLabelTemplateResource extends Resource
{
    protected static ?string $model = ZplLabelTemplate::class;

    protected static ?string $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'ZPL Templateler';
    protected static ?string $modelLabel      = 'ZPL Template';
    protected static ?string $pluralModelLabel = 'ZPL Templateler';
    protected static ?int    $navigationSort  = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Temel Bilgiler')
                ->columns(2)
                ->schema([
                    Select::make('project_id')
                        ->label('Proje')
                        ->placeholder('Global (tüm projeler)')
                        ->options(Project::orderBy('project_name')->pluck('project_name', 'id'))
                        ->nullable()
                        ->searchable()
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),

                    TextInput::make('template_code')
                        ->label('Template Kodu')
                        ->placeholder('Örn: default, discount_label')
                        ->required()
                        ->maxLength(100)
                        ->alphaDash()
                        ->helperText('Flutter/web tarafından bu kod ile seçilir. Küçük harf, tire ve alt çizgi kullanın.')
                        ->columnSpan(1),

                    TextInput::make('template_name')
                        ->label('Template Adı')
                        ->placeholder('Örn: Standart Ürün Etiketi')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                ]),

            Section::make('ZPL İçeriği')
                ->description('Dinamik alanları {{VariableName}} formatında yazın. Örn: {{ItemCode}}, {{ProductPrice}}, {{Barcode}}')
                ->schema([
                    Textarea::make('zpl_template')
                        ->label('ZPL Şablonu')
                        ->required()
                        ->rows(20)
                        ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 0.8rem;'])
                        ->placeholder("^XA\n^CI28\n^PW399\n^LL239\n^A0N,18\n^FO10,15^FD{{Hierarchy}}^FS\n...\n^XZ")
                        ->helperText('Her satır ZPL komutu. Değişkenler: {{ItemCode}} {{ColorCode}} {{ProductPrice}} {{ProductDiscountPrice}} {{Barcode}} {{PriceValidDate}} {{Hierarchy}} {{Atr12}} {{StoreName}} {{StrikethroughBlock}}'),
                ]),

            Section::make('Değişkenler')
                ->description('Bu template\'de kullanılan {{...}} değişkenlerini tanımlayın. Backend bu listeyi okuyarak API verisini map eder.')
                ->schema([
                    Repeater::make('variables')
                        ->label('')
                        ->schema([
                            TextInput::make('variable_name')
                                ->label('Değişken Adı')
                                ->placeholder('Örn: ItemCode')
                                ->required()
                                ->maxLength(100),

                            TextInput::make('description')
                                ->label('Açıklama')
                                ->placeholder('Örn: Ürün kodu')
                                ->maxLength(255),

                            TextInput::make('default_value')
                                ->label('Varsayılan Değer')
                                ->placeholder('Boş bırakılabilir')
                                ->maxLength(255),
                        ])
                        ->columns(3)
                        ->addActionLabel('Değişken Ekle')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['variable_name'] ?? null),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('template_code')
                    ->label('Kod')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('template_name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.project_name')
                    ->label('Proje')
                    ->placeholder('Global')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Güncellendi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Proje')
                    ->options(Project::orderBy('project_name')->pluck('project_name', 'id'))
                    ->placeholder('Tüm projeler'),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Önizle')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn (ZplLabelTemplate $record): string => $record->template_name . ' — ZPL Önizleme')
                    ->modalContent(function (ZplLabelTemplate $record): HtmlString {
                        $imageUrl  = route('zpl.preview', $record);
                        $variables = collect($record->variables ?? []);
                        $rawZpl    = htmlspecialchars($record->zpl_template);

                        // --- Rendered image block ---
                        $errorSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:2.5rem;height:2.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>';

                        $imageBlock = '<div style="background:#111827;border-radius:0.75rem;padding:1.5rem;display:flex;justify-content:center;align-items:center;min-height:180px;margin-bottom:1.25rem;">'
                            . '<img src="' . $imageUrl . '" alt="ZPL Önizleme"'
                            . ' style="max-width:100%;max-height:380px;border-radius:0.375rem;box-shadow:0 4px 24px rgba(0,0,0,0.5);"'
                            . ' onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';" />'
                            . '<div style="display:none;flex-direction:column;align-items:center;gap:0.5rem;color:#6b7280;">'
                            . $errorSvg
                            . '<span style="font-size:0.8rem;">Önizleme yüklenemedi (Labelary API erişimi gerekli)</span>'
                            . '</div>'
                            . '</div>';

                        // --- Variable chips block (only if variables exist) ---
                        $varBlock = '';
                        if ($variables->isNotEmpty()) {
                            $chips = $variables->map(function ($v) {
                                $name = htmlspecialchars($v['variable_name'] ?? '');
                                $desc = htmlspecialchars($v['description'] ?? '');
                                $dv   = (isset($v['default_value']) && $v['default_value'] !== '')
                                    ? ' — varsayılan: ' . htmlspecialchars($v['default_value'])
                                    : '';
                                return '<span title="' . $desc . $dv . '" style="'
                                    . 'display:inline-flex;align-items:center;'
                                    . 'background:#1e293b;color:#93c5fd;'
                                    . 'border:1px solid #334155;border-radius:9999px;'
                                    . 'padding:0.15rem 0.65rem;font-size:0.7rem;'
                                    . 'font-family:monospace;cursor:default;'
                                    . '">{{' . $name . '}}</span>';
                            })->implode(' ');

                            $count    = $variables->count();
                            $varBlock = '<div style="margin-bottom:1rem;">'
                                . '<p style="font-size:0.7rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem;">'
                                . 'Değişkenler (' . $count . ')</p>'
                                . '<div style="display:flex;flex-wrap:wrap;gap:0.35rem;">' . $chips . '</div>'
                                . '</div>';
                        }

                        // --- Raw ZPL collapsible ---
                        $zplBlock = '<details style="margin-top:0.5rem;">'
                            . '<summary style="font-size:0.75rem;font-weight:600;color:#9ca3af;cursor:pointer;user-select:none;padding:0.4rem 0;letter-spacing:0.04em;text-transform:uppercase;">Ham ZPL Kodu</summary>'
                            . '<pre style="margin-top:0.6rem;font-family:monospace;font-size:0.75rem;line-height:1.6;background:#0f172a;color:#4ade80;padding:1rem 1.25rem;border-radius:0.5rem;overflow:auto;white-space:pre-wrap;word-break:break-all;max-height:55vh;">'
                            . $rawZpl
                            . '</pre>'
                            . '</details>';

                        return new HtmlString(
                            '<div style="padding:0.25rem 0 0.5rem;">'
                            . $imageBlock
                            . $varBlock
                            . $zplBlock
                            . '</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                EditAction::make()->label('Düzenle'),
                DeleteAction::make()->label('Sil'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri Sil'),
                ]),
            ])
            ->defaultSort('id', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListZplLabelTemplates::route('/'),
            'create' => Pages\CreateZplLabelTemplate::route('/create'),
            'edit'   => Pages\EditZplLabelTemplate::route('/{record}/edit'),
        ];
    }
}
