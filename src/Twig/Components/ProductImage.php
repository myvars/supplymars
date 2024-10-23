<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ProductImage
{
    public ?string $alt = null;

    public ?string $imageName = null;

    public string $filter = 'small_thumbnail';

    public string $style = 'rounded-s-lg';

    public ?string $showLink = null;

    public function getImageClasses(): string
    {
        return match ($this->filter) {
            'small_thumbnail' => 'w-[90px] h-[90px] min-w-[90px] min-h-[90px]',
            'medium_thumbnail' => 'w-[130px] h-[130px] min-w-[130px] min-h-[130px]',
            'large_thumbnail' => 'w-[230px] h-[230px] min-w-[230px] min-h-[230px]',
            'medium_image' => 'w-[342px] h-[342px] min-w-[342px] min-h-[342px]',
            default => throw new \LogicException(sprintf('Unknown filter "%s"', $this->filter)),
        };
    }
}
