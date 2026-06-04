<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds image paths for specialties and doctor profiles.
 * Images are stored in storage/app/public/ and served via storage link.
 */
class ImagePathSeeder extends Seeder
{
    public function run(): void
    {
        // Specialty images
        $specialtyImages = [
            'Cardiology'              => 'specialties/1861510649115236.webp',
            'Dermatology'             => 'specialties/1861511170098071.webp',
            'Orthopedics'             => 'specialties/1861511223131855.jpg',
            'Pediatrics'              => 'specialties/1861511273602480.jpg',
            'Neurology'               => 'specialties/1861511309794122.jpg',
            'Otolaryngology (ENT)'    => 'specialties/1861511524244317.jpg',
            'Endocrinology'           => 'specialties/1861511561093747.jpeg',
            'General Internal Medicine' => 'specialties/1861511594045550.jpg',
            'Rheumatology'            => 'specialties/1861511639824742.jpg',
            'Psychiatry'              => 'specialties/1861511668439185.jpg',
        ];

        foreach ($specialtyImages as $name => $imagePath) {
            DB::table('specialties')
                ->where('name', $name)
                ->update(['image_path' => $imagePath]);
        }

        $this->command->info('Specialty image paths seeded.');

        // Doctor profile images (mapped by user_id)
        $doctorImages = [
            1 => 'doctor-profiles/aKvFJHB8KjRBC4MemMk5WvOyrQJMQzQXiO54WN3Z.jpg',
            2 => 'doctor-profiles/345q8Wm5Pxra0gPmgw1a1ESRTwd2uY03XKfC6fPX.jpg',
            3 => 'doctor-profiles/dhLWlsDbTI3BmwO1qOUb37uBc3yJGBOJEzLNa9Rl.jpg',
        ];

        foreach ($doctorImages as $id => $imagePath) {
            DB::table('doctor_profiles')
                ->where('id', $id)
                ->update(['profile_image' => $imagePath]);
        }

        $this->command->info('Doctor profile image paths seeded.');
    }
}
