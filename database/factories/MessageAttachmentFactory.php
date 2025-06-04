<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MessageAttachment>
 */
class MessageAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = [
            'image/jpeg'         => ['jpg', 'jpeg'],
            'image/png'          => ['png'],
            'application/pdf'    => ['pdf'],
            'application/msword' => ['doc'],
            'text/plain'         => ['txt'],
        ];

        $fileType = $this->faker->randomKey($fileTypes);
        $extension = $this->faker->randomElement($fileTypes[$fileType]);
        $fileName = $this->faker->word().'.'.$extension;
        $isImage = str_starts_with($fileType, 'image/');

        return [
            'message_id'    => Message::factory(),
            'file_name'     => $fileName,
            'file_url'      => 'attachments/'.$this->faker->uuid().'.'.$extension,
            'file_type'     => $fileType,
            'file_size'     => $this->faker->numberBetween(1024, 10485760),
            'thumbnail_url' => $isImage ? 'thumbnails/'.$this->faker->uuid().'._thumb.jpg' : null,
            'uploaded_at'   => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
