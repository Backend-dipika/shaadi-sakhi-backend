<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $categories = [
            'Bridal Ensemble',
            'Sarees',
            'Ethnic wear',
            'Indo Western outfits',
            'Real Jewellery',
            'Semi precious & Fine Jewellery',
            'Bags & Clutches',
            'Hair Accessories',
            'Kidswear',
            'Footwear',
            'Skin care',
            'Festive Décor',
            'Handmade Gifts',
            'Food & Gourmet',
            'Casual wear',
            'Home Linen',
            'Others'
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category], // condition
                ['name' => $category]  // values to update
            );
        }
    }
}
