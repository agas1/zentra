<?php

namespace Database\Seeders;

use App\Domain\PowerUp\Models\PowerUp;
use Illuminate\Database\Seeder;

class PowerUpSeeder extends Seeder
{
    public function run(): void
    {
        PowerUp::updateOrCreate(
            ['slug' => 'slack'],
            [
                'name' => 'Slack',
                'description' => 'Receba notificacoes no Slack quando cards forem criados, movidos ou comentados.',
                'icon' => 'slack',
                'category' => 'communication',
                'is_active' => true,
            ]
        );

        PowerUp::updateOrCreate(
            ['slug' => 'google_calendar'],
            [
                'name' => 'Google Calendar',
                'description' => 'Sincronize datas de entrega dos cards com o Google Calendar automaticamente.',
                'icon' => 'calendar',
                'category' => 'productivity',
                'is_active' => true,
            ]
        );

        PowerUp::updateOrCreate(
            ['slug' => 'google_drive'],
            [
                'name' => 'Google Drive',
                'description' => 'Anexe arquivos do Google Drive diretamente nos cards.',
                'icon' => 'drive',
                'category' => 'storage',
                'is_active' => true,
            ]
        );
    }
}
