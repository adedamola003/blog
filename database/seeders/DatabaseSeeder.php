<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Article::factory(20)->create();
        \App\Models\Tag::factory(5)->create();
         \App\Models\User::factory(1)->create();

        foreach (\App\Models\Article::all() as $article) {
            \App\Models\PostTag::create([
                'article_id' => $article->id,
                'tag_id' => \App\Models\Tag::inRandomOrder()->first()->id,
            ]);
        }

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
