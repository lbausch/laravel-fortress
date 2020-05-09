<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use Tests\Models\Post;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'text' => $faker->paragraph(),
    ];
});
