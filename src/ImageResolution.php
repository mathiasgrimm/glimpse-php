<?php

namespace GlimpseImg;

final readonly class ImageResolution
{
    public function __construct(
        public float $x,
        public float $y,
    ) {}
}
