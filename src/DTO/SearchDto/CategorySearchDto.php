<?php

namespace App\DTO\SearchDto;

use Symfony\Component\Validator\Constraints as Assert;

final class CategorySearchDto extends SearchDto implements SearchFilterInterface
{
    public const SORT_DEFAULT = 'id';

    public const SORT_OPTIONS = ['id', 'name', 'defaultMarkup', 'isActive'];

    public const SORT_DIRECTION_DEFAULT = 'ASC';

    public const LIMIT_DEFAULT = 5;

    private ?string $priceModel = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Manager Id', min: 1, max: 1000000)]
    private ?int $managerId = null;

    public function getSearchParams(): array
    {
        $searchFilterParams = [
            'priceModel' => $this->priceModel,
            'managerId' => $this->managerId,
        ];

        if (array_filter($searchFilterParams)) {
            $searchFilterParams['filter'] = 'on';
        }

        return array_merge($searchFilterParams, parent::getSearchParams());
    }

    public function getPriceModel(): ?string
    {
        return $this->priceModel;
    }

    public function setPriceModel(?string $priceModel): CategorySearchDto
    {
        $this->priceModel = $priceModel;
        return $this;
    }

    public function getManagerId(): ?int
    {
        return $this->managerId;
    }

    public function setManagerId(?int $managerId): CategorySearchDto
    {
        $this->managerId = $managerId;

        return $this;
    }
}