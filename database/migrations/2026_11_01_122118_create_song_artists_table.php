
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('song_artists', function (Blueprint $table) {
            $table->foreignId('song_id')
                ->constrained('songs')->cascadeOnDelete();
            $table->foreignId('artist_id')
                ->constrained('artists')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);

            $table->primary(['song_id', 'artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_artists');
    }
};
