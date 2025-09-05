<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Listing extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title', 'price', 'location', 'type', 'status',
        'year', 'condition', 'size', 'capacity', 'features', 'description'
    ];

    protected $casts = [
        'features' => 'array',
    ];
    //  public function registerMediaCollections(): void
    // {
    //     $this->addMediaCollection('images')->useDisk('public');

    // $this->addMediaCollection('primary')->singleFile();
    // }
    public function getPrimaryImageUrl(): ?string
{
    $media = $this->getMedia('images')->firstWhere('custom_properties.primary', true);
    return $media?->getUrl();
}

public function registerMediaCollections(): void
{
    $this->addMediaCollection('primary')->singleFile();
    $this->addMediaCollection('images'); // gallery
}
}
