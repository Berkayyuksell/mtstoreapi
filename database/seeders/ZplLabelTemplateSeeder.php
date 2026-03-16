<?php

namespace Database\Seeders;

use App\Models\ZplLabelTemplate;
use Illuminate\Database\Seeder;

class ZplLabelTemplateSeeder extends Seeder
{
    public function run(): void
    {
        ZplLabelTemplate::updateOrCreate(
            // Benzersiz anahtar: global (proje bağımsız) varsayılan template
            ['project_id' => null, 'template_code' => 'default'],
            [
                'template_name' => 'Standart Ürün Etiketi',
                'is_active'     => true,

                // ZPL şablonu — tüm dinamik alanlar {{VariableName}} ile işaretli
                'zpl_template' => implode("\n", [
                    '^XA',
                    '^CI28',
                    '^PW399',
                    '^LL239',
                    '^A0N,18',
                    '^FO10,15^FD{{Hierarchy}}^FS',
                    '^A0N,24,24',
                    '^FB180,1,0,C,0',
                    '^FO20,45^FD{{ProductPrice}} TL^FS',
                    // İndirim bloğu: controller tarafından dolu veya boş string olarak gelir
                    '{{StrikethroughBlock}}',
                    '^A0N,28,28',
                    '^FB180,1,0,C,0',
                    '^FO20,70^FD{{ProductDiscountPrice}} TL^FS',
                    '^A0N,18',
                    '^FO240,60^FDKDV Dahildir^FS',
                    '^A0N,18',
                    '^FO240,80^FDF.D.T: {{PriceValidDate}}^FS',
                    '^A0N,24,24',
                    '^FO10,105^FD{{ItemCode}}{{ColorCode}}^FS',
                    '^A0N,22,22',
                    '^FO10,130^FB359,1,0,L^FD{{ColorDescription}}^FS',
                    '^A0N,22,22',
                    '^FO0,130^FB365,1,0,R^FD{{Atr12}}^FS',
                    '^BY2,2,60',
                    '^FO10,160',
                    '^BCN,50,Y,N,N',
                    '^FD{{Barcode}}^FS',
                    '^XZ',
                ]),

                // variables: backend bu listeyi okur, UI değişken listesi gösterir
                'variables' => [
                    ['variable_name' => 'Hierarchy',            'description' => 'Ürün hiyerarşisi / kategori',          'default_value' => ''],
                    ['variable_name' => 'ProductPrice',         'description' => 'Normal fiyat (formatlanmış)',           'default_value' => '0,00'],
                    ['variable_name' => 'StrikethroughBlock',   'description' => 'İndirim varsa üstü çizili ZPL bloğu',  'default_value' => ''],
                    ['variable_name' => 'ProductDiscountPrice', 'description' => 'İndirimli fiyat (formatlanmış)',        'default_value' => '0,00'],
                    ['variable_name' => 'PriceValidDate',       'description' => 'Fiyat geçerlilik tarihi (dd.MM.yyyy)', 'default_value' => ''],
                    ['variable_name' => 'ItemCode',             'description' => 'Ürün kodu',                            'default_value' => ''],
                    ['variable_name' => 'ColorCode',            'description' => 'Renk kodu',                            'default_value' => ''],
                    ['variable_name' => 'ColorDescription',     'description' => 'Renk açıklaması',                      'default_value' => ''],
                    ['variable_name' => 'Atr12',                'description' => 'Özel özellik 12',                      'default_value' => ''],
                    ['variable_name' => 'Barcode',              'description' => 'Barkod (max 12 karakter)',              'default_value' => ''],
                    ['variable_name' => 'StoreName',            'description' => 'Mağaza adı',                           'default_value' => ''],
                    ['variable_name' => 'Currency',             'description' => 'Para birimi',                          'default_value' => 'TRY'],
                ],
            ]
        );
    }
}
