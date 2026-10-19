<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('tagline', 150);
            $table->text('description');
            $table->unsignedSmallInteger('founded_year');
            $table->text('mission');
            $table->string('email', 255);
            $table->string('phone', 30);
            $table->string('address', 500);
            $table->timestamps();
        });

        DB::table('company_profiles')->insert([
            'name' => 'Au2m8',
            'tagline' => 'Building the web, streamlining the process.',
            'description' => 'We craft modern, performant web applications using the latest technologies.',
            'founded_year' => 2010,
            'mission' => 'To deliver exceptional web solutions that help businesses thrive in the digital age.',
            'email' => 'wenzel@au2m8.com',
            'phone' => '+63 (919) 095-9251',
            'address' => 'Poblacion, Urdaneta City, Pangasinan, 2428 Philippines',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
