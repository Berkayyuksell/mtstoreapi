<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Company;
use App\Models\Module;
use App\Models\Office;
use App\Models\Project;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ZplLabelTemplate;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kullanıcılar';

    protected static ?string $modelLabel = 'Kullanıcı';

    protected static ?string $pluralModelLabel = 'Kullanıcılar';

    public static function getNavigationBadge(): ?string
    {
        return (string) User::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Temel Bilgiler')
                    ->schema([
                        Select::make('project_id')
                            ->label('Proje')
                            ->options(Project::pluck('project_name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($set) {
                                $set('company_id', null);
                                $set('office_id', null);
                                $set('store_id', null);
                                $set('warehouse_id', null);
                            }),

                        TextInput::make('name')
                            ->label('Ad Soyad')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('E-posta')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Şifre')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Toggle::make('is_admin')
                            ->label('Admin mi?')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Atamalar (İsteğe Bağlı)')
                    ->description('Bu alanlar zorunlu değildir.')
                    ->schema([
                        Select::make('company_id')
                            ->label('Şirket')
                            ->options(function (Get $get) {
                                $projectId = $get('project_id');
                                if (!$projectId) {
                                    return [];
                                }
                                return Company::where('project_id', $projectId)
                                    ->pluck('CompanyName', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($set) {
                                $set('office_id', null);
                                $set('store_id', null);
                                $set('warehouse_id', null);
                            })
                            ->placeholder('Şirket seçin (opsiyonel)'),

                        Select::make('office_id')
                            ->label('Ofis')
                            ->options(function (Get $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return [];
                                }
                                return Office::where('company_id', $companyId)
                                    ->pluck('OfficeName', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($set) {
                                $set('store_id', null);
                                $set('warehouse_id', null);
                            })
                            ->placeholder('Ofis seçin (opsiyonel)'),
                    ])
                    ->columns(2),

                Section::make('Modüller')
                    ->description('Kullanıcının erişebileceği modülleri seçin.')
                    ->schema([
                        Select::make('modules')
                            ->label('Modüller')
                            ->multiple()
                            ->relationship('modules', 'name')
                            ->options(Module::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Modül seçin (opsiyonel)'),
                    ]),

                Section::make('Lokasyon (İsteğe Bağlı)')
                    ->description('Ofise bağlı mağaza veya depo seçin. Her ikisi de seçilebilir.')
                    ->schema([
                        Select::make('store_id')
                            ->label('Mağaza')
                            ->options(function (Get $get) {
                                $officeId = $get('office_id');
                                if (!$officeId) {
                                    return [];
                                }
                                return Store::where('office_id', $officeId)
                                    ->pluck('StoreName', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->placeholder('Mağaza seçin (opsiyonel)')
                            ->helperText(function (Get $get) {
                                $officeId = $get('office_id');
                                if (!$officeId) {
                                    return 'Önce ofis seçmelisiniz.';
                                }
                                $count = Store::where('office_id', $officeId)->count();
                                return $count === 0 ? 'Bu ofise ait mağaza bulunmuyor.' : null;
                            }),

                        Select::make('warehouse_id')
                            ->label('Depo')
                            ->options(function (Get $get) {
                                $officeId = $get('office_id');
                                if (!$officeId) {
                                    return [];
                                }
                                return Warehouse::where('office_id', $officeId)
                                    ->pluck('WareHouseName', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->placeholder('Depo seçin (opsiyonel)')
                            ->helperText(function (Get $get) {
                                $officeId = $get('office_id');
                                if (!$officeId) {
                                    return 'Önce ofis seçmelisiniz.';
                                }
                                $count = Warehouse::where('office_id', $officeId)->count();
                                return $count === 0 ? 'Bu ofise ait depo bulunmuyor.' : null;
                            }),
                    ])
                    ->columns(2),

                Section::make('ZPL Etiket Ayarları')
                    ->description('Kullanıcıya özgü fiyat grupları ve ZPL template. Boş bırakılırsa mağaza → global varsayılanlar kullanılır.')
                    ->schema([
                        TextInput::make('price_group_code')
                            ->label('Fiyat Grup Kodu')
                            ->placeholder('Örn: PSF (boş = mağaza/global)')
                            ->maxLength(50)
                            ->nullable(),

                        TextInput::make('disc_price_group_code')
                            ->label('İndirim Fiyat Grup Kodu')
                            ->placeholder('Örn: PSF_IND (boş = mağaza/global)')
                            ->maxLength(50)
                            ->nullable(),

                        Select::make('zpl_label_template_id')
                            ->label('ZPL Template')
                            ->placeholder('Varsayılan template (boş bırakılabilir)')
                            ->options(function (Get $get) {
                                $projectId = $get('project_id');
                                return ZplLabelTemplate::where('is_active', true)
                                    ->where(function ($q) use ($projectId) {
                                        $q->whereNull('project_id');
                                        if ($projectId) {
                                            $q->orWhere('project_id', $projectId);
                                        }
                                    })
                                    ->get()
                                    ->mapWithKeys(fn ($t) => [
                                        $t->id => $t->template_name . ' [' . $t->template_code . ']' . ($t->project_id ? '' : ' — Global'),
                                    ])
                                    ->toArray();
                            })
                            ->nullable()
                            ->searchable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.project_name')
                    ->label('Proje')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('company.CompanyName')
                    ->label('Şirket')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('office.OfficeName')
                    ->label('Ofis')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('store.StoreName')
                    ->label('Mağaza')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('warehouse.WareHouseName')
                    ->label('Depo')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('zplLabelTemplate.template_name')
                    ->label('ZPL Template')
                    ->placeholder('Varsayılan')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Proje')
                    ->relationship('project', 'project_name'),
            ])
            ->actions([
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
