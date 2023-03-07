<?php

namespace DummyApp;

use DummyApp\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;

    /**
     * @return MorphTo
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    protected static function newFactory()
    {
        return ImageFactory::new();
    }
}
